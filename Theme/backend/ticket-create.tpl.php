<?php
/**
 * Orange Management
 *
 * PHP Version 7.0
 *
 * @category   TBD
 * @package    TBD
 * @author     OMS Development Team <dev@oms.com>
 * @author     Dennis Eichhorn <d.eichhorn@oms.com>
 * @copyright  2013 Dennis Eichhorn
 * @license    OMS License 1.0
 * @version    1.0.0
 * @link       http://orange-management.com
 */
/**
 * @var \phpOMS\Views\View $this
 */
echo $this->getData('nav')->render(); ?>

<section class="box w-50">
    <header><h1><?= $this->l11n->getText('Support', 'Backend', 'Ticket') ?></h1></header>
    <div class="inner">
        <form action="<?= \phpOMS\Uri\UriFactory::build('/{/lang}/api/reporter/template'); ?>" method="post">
            <table class="layout wf-100">
                <tbody>
                <tr><td><label for="iTitle"><?= $this->l11n->getText('Support', 'Backend', 'Department') ?></label>
                <tr><td><select></select>
                <tr><td><label for="iTitle"><?= $this->l11n->getText('Support', 'Backend', 'Topic') ?></label>
                <tr><td><select></select>
                <tr><td><label for="iTitle"><?= $this->l11n->getText('Support', 'Backend', 'Title') ?></label>
                <tr><td><input id="iTitle" name="name" type="text" required>
                <tr><td><label for="iTitle"><?= $this->l11n->getText('Support', 'Backend', 'Description') ?></label>
                <tr><td><textarea required></textarea>
                <tr><td><label for="iFile"><?= $this->l11n->getText('Support', 'Backend', 'Files') ?></label>
                <tr><td><input id="iFile" name="fileVisual" type="file" multiple><input id="iFileHidden" name="files" type="hidden">
                <tr><td><input type="submit" value="<?= $this->l11n->getText(0, 'Backend', 'Create') ?>">
            </table>
        </form>
    </div>
</section>
