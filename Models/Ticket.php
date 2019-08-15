<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package    Modules\Support\Models
 * @copyright  Dennis Eichhorn
 * @license    OMS License 1.0
 * @version    1.0.0
 * @link       https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Support\Models;

use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskType;

/**
 * Issue class.
 *
 * @package    Modules\Support\Models
 * @license    OMS License 1.0
 * @link       https://orange-management.org
 * @since      1.0.0
 */
class Ticket
{

    private $id = 0;

    private $task = null;

    public function __construct()
    {
        $this->task = new Task();
        $this->task->setType(TaskType::HIDDEN);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getTask() : Task
    {
        return $this->task;
    }

    public function setTask(Task $task) : void
    {
        $this->task = $task;
    }
}
