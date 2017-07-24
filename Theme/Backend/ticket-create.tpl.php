<?php
/**
 * Orange Management
 *
 * PHP Version 7.1
 *
 * @category   TBD
 * @package    TBD
 * @author     OMS Development Team <dev@oms.com>
 * @copyright  Dennis Eichhorn
 * @license    OMS License 1.0
 * @version    1.0.0
 * @link       http://orange-management.com
 */
/**
 * @var \phpOMS\Views\View $this
 */
echo $this->getData('nav')->render(); ?>

<section class="box w-50">
    <header><h1><?= $this->getHtml('Ticket'); ?></h1></header>
    <div class="inner">
        <form action="<?= \phpOMS\Uri\UriFactory::build('{/base}/{/lang}/api/reporter/template'); ?>" method="post">
            <table class="layout wf-100">
                <tbody>
                <tr><td><label for="iTitle"><?= $this->getHtml('Department'); ?></label>
                <tr><td><select></select>
                <tr><td><label for="iTitle"><?= $this->getHtml('Topic'); ?></label>
                <tr><td><select></select>
                <tr><td><label for="iTitle"><?= $this->getHtml('Title'); ?></label>
                <tr><td><input id="iTitle" name="name" type="text" required>
                <tr><td><label for="iTitle"><?= $this->getHtml('Description'); ?></label>
                <tr><td><textarea required></textarea>
                <tr><td><label for="iFile"><?= $this->getHtml('Files'); ?></label>
                <tr><td><input id="iFile" name="fileVisual" type="file" multiple><input id="iFileHidden" name="files" type="hidden">
                <tr><td><input type="submit" value="<?= $this->getHtml('Create', 0, 0); ?>">
            </table>
        </form>
    </div>
</section>
