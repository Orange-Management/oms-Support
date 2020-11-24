<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package   Modules\Support
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Support\Controller;

use Modules\Admin\Models\NullAccount;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskElement;
use Modules\Tasks\Models\TaskElementMapper;
use Modules\Tasks\Models\TaskMapper;
use Modules\Tasks\Models\TaskStatus;
use Modules\Tasks\Models\TaskType;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\Message\NotificationLevel;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Message\FormValidation;
use phpOMS\Utils\Parser\Markdown\Markdown;
use Modules\Tag\Models\NullTag;
use phpOMS\Message\Http\HttpResponse;

/**
 * Api controller for the tasks module.
 *
 * @package Modules\Support
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 *
 * @todo Orange-Management/oms-Tasks#9
 *  Create task/calendar reference
 *  Show tasks in calendars not just in user calendars but also in event calendars and project calendars?!
 *
 * @todo Orange-Management/Modules#33
 *  Repeating tasks should be implemented.
 *  At the same time this means a fix to the due date needs to be implemented.
 *  Maybe simple calculate the time difference between first start and first due?
 *
 * @todo Orange-Management/oms-Tasks#6
 *  Add tags
 *  The user should be able to add a tag to a task and also decide on the color of the tag.
 *  User means the creator of the task.
 *  If task elements should have their own tags of if other users should be able to modify tags should be ignored for now.
 *  Maybe instead of creating a tasks specific tag system a global tag system could be used? Maybe this was already created as an issue.
 *  Tags should be globally and users should be able to define tags separately for their own organization purposes.
 */
final class ApiController extends Controller
{
    /**
     * Validate task create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool> Returns the validation array of the request
     *
     * @since 1.0.0
     */
    private function validateTicketCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['title'] = empty($request->getData('title')))
            || ($val['plain'] = empty($request->getData('plain')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create a task
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateTicketCreate($request))) {
            $response->set($request->uri->__toString(), new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $task = $this->createTicketFromRequest($request);
        $this->createModel($request->header->account, $task, TaskMapper::class, 'task', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task', 'Task successfully created.', $task);
    }

    /**
     * Method to create task from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return Task Returns the created task from the request
     *
     * @since 1.0.0
     */
    private function createTicketFromRequest(RequestAbstract $request) : Task
    {
        $task = new Task();
        $task->title = (string) ($request->getData('title') ?? '');
        $task->description = Markdown::parse((string) ($request->getData('plain') ?? ''));
        $task->descriptionRaw = (string) ($request->getData('plain') ?? '');
        $task->setCreatedBy(new NullAccount($request->header->account));
        $task->setStatus(TaskStatus::OPEN);
        $task->setType(TaskType::SINGLE);

        if (empty($request->getData('priority'))) {
            $task->due = empty($request->getData('due')) ? null : new \DateTime($request->getData('due'));
        } else {
            $task->setPriority((int) $request->getData('priority'));
        }

        if (!empty($tags = $request->getDataJson('tags'))) {
            foreach ($tags as $tag) {
                if (!isset($tag['id'])) {
                    $request->setData('title', $tag['title'], true);
                    $request->setData('color', $tag['color'], true);
                    $request->setData('language', $tag['language'], true);

                    $internalResponse = new HttpResponse();
                    $this->app->moduleManager->get('Tag')->apiTagCreate($request, $internalResponse, null);
                    $task->addTag($internalResponse->get($request->uri->__toString())['response']);
                } else {
                    $task->addTag(new NullTag((int) $tag['id']));
                }
            }
        }

        $element = new TaskElement();
        $element->addTo(new NullAccount((int) ($request->getData('forward') ?? $request->header->account)));
        $element->createdBy = $task->createdBy;
        $element->due = $task->due;
        $element->setPriority($task->getPriority());
        $element->setStatus(TaskStatus::OPEN);

        $task->addElement($element);

        return $task;
    }

    /**
     * Api method to get a task
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketGet(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $task = TaskMapper::get((int) $request->getData('id'));
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task', 'Task successfully returned.', $task);
    }

    /**
     * Api method to update a task
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketSet(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $old = clone TaskMapper::get((int) $request->getData('id'));
        $new = $this->updateTicketFromRequest($request);
        $this->updateModel($request->header->account, $old, $new, TaskMapper::class, 'task', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task', 'Task successfully updated.', $new);
    }

    /**
     * Method to update an task from a request
     *
     * @param RequestAbstract $request Request
     *
     * @return Task Returns the updated task from the request
     *
     * @since 1.0.0
     */
    private function updateTicketFromRequest(RequestAbstract $request) : Task
    {
        $task = TaskMapper::get((int) ($request->getData('id')));
        $task->title = (string) ($request->getData('title') ?? $task->getTitle());
        $task->description = Markdown::parse((string) ($request->getData('plain') ?? $task->descriptionRaw));
        $task->descriptionRaw = (string) ($request->getData('plain') ?? $task->descriptionRaw);
        $task->due = new \DateTime((string) ($request->getData('due') ?? $task->getDue()->format('Y-m-d H:i:s')));
        $task->setStatus((int) ($request->getData('status') ?? $task->getStatus()));
        $task->setType((int) ($request->getData('type') ?? $task->getType()));
        $task->setPriority((int) ($request->getData('priority') ?? $task->getPriority()));

        return $task;
    }

    /**
     * Validate task element create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool> Returns the validation array of the request
     *
     * @since 1.0.0
     */
    private function validateTicketElementCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['status'] = !TaskStatus::isValidValue((int) $request->getData('status')))
            || ($val['due'] = !((bool) \strtotime((string) $request->getData('due'))))
            || ($val['task'] = !(\is_numeric($request->getData('task'))))
            || ($val['forward'] = !(\is_numeric(empty($request->getData('forward')) ? $request->header->account : $request->getData('forward'))))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create a task element
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketElementCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateTicketElementCreate($request))) {
            $response->set('task_element_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        /**
         * @todo Orange-Management/oms-Tasks#3
         *  Validate that the user is allowed to create a task element for a specific task
         */

        $task    = TaskMapper::get((int) ($request->getData('task')));
        $element = $this->createTicketElementFromRequest($request, $task);
        $task->setStatus($element->getStatus());
        $task->setPriority($element->getPriority());
        $task->setDue($element->due);

        $this->createModel($request->header->account, $element, TaskElementMapper::class, 'taskelement', $request->getOrigin());
        $this->updateModel($request->header->account, $task, $task, TaskMapper::class, 'task', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task element', 'Task element successfully created.', $element);
    }

    /**
     * Method to create task element from request.
     *
     * @param RequestAbstract $request Request
     * @param Task            $task    Task
     *
     * @return TaskElement Returns the task created from the request
     *
     * @since 1.0.0
     */
    private function createTicketElementFromRequest(RequestAbstract $request, Task $task) : TaskElement
    {
        $element = new TaskElement();
        $element->createdBy = new NullAccount($request->header->account);
        $element->due = !empty($request->getData('due')) ? new \DateTime((string) ($request->getData('due'))) : $task->due;
        $element->setPriority((int) ($request->getData('priority') ?? $task->getPriority()));
        $element->setStatus((int) ($request->getData('status')));
        $element->task = $task->getId();
        $element->description = Markdown::parse((string) ($request->getData('plain') ?? ''));
        $element->descriptionRaw = (string) ($request->getData('plain') ?? '');

        $tos = $request->getData('to') ?? $request->header->account;
        if (!\is_array($tos)) {
            $tos = [$tos];
        }

        $ccs = $request->getData('cc') ?? [];
        if (!\is_array($ccs)) {
            $ccs = [$ccs];
        }

        foreach ($tos as $to) {
            $element->addTo(new NullAccount((int) $to));
        }

        foreach ($ccs as $cc) {
            $element->addCC(new NullAccount((int) $cc));
        }

        return $element;
    }

    /**
     * Api method to get a task
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketElementGet(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $task = TaskElementMapper::get((int) $request->getData('id'));
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task element', 'Task element successfully returned.', $task);
    }

    /**
     * Api method to update a task
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiTicketElementSet(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $old = clone TaskElementMapper::get((int) $request->getData('id'));
        $new = $this->updateTicketElementFromRequest($request);
        $this->updateModel($request->header->account, $old, $new, TaskElementMapper::class, 'taskelement', $request->getOrigin());

        /**
         * @todo Orange-Management/oms-Tasks#2
         *  Update task status depending on the new task element or updated task element
         *  The task status is not normalized and relates to the last task element.
         *  Depending on the task status of the last task element also the task status should change.
         */
        //$this->updateModel($request->header->account, $task, $task, TaskMapper::class, 'task', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Task element', 'Task element successfully updated.', $new);
    }

    /**
     * Method to update an task element from a request
     *
     * @param RequestAbstract $request Request
     *
     * @return TaskElement Returns the updated task element from the request
     *
     * @since 1.0.0
     */
    private function updateTicketElementFromRequest(RequestAbstract $request) : TaskElement
    {
        $element = TaskElementMapper::get((int) ($request->getData('id')));
        $element->setDue(new \DateTime((string) ($request->getData('due') ?? $element->getDue()->format('Y-m-d H:i:s'))));
        $element->setStatus((int) ($request->getData('status') ?? $element->getStatus()));
        $element->description = Markdown::parse((string) ($request->getData('plain') ?? $element->descriptionRaw));
        $element->descriptionRaw = (string) ($request->getData('plain') ?? $element->descriptionRaw);

        $tos = $request->getData('to') ?? $request->header->account;
        if (!\is_array($tos)) {
            $tos = [$tos];
        }

        $ccs = $request->getData('cc') ?? [];
        if (!\is_array($ccs)) {
            $ccs = [$ccs];
        }

        foreach ($tos as $to) {
            $element->addTo($to);
        }

        foreach ($ccs as $cc) {
            $element->addCC($cc);
        }

        return $element;
    }
}