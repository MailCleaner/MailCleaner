<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the file type protection configuration page
 */

/**
 * require admin session, view and file type settings objects
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/FileTypeList.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

//check authorizations
$admin_->checkPermissions(['can_configure']);

$save_msg = "";
// create and load file type list
$list_ = new FileTypeList();
$list_->load();
// set selected entry
if (isset($_GET['s']) and is_numeric($_GET['s'])) {
    $list_->setSelected($_GET['s']);
}

// create entry edition form
$eform = new Form('edit', 'post', $_SERVER['PHP_SELF']);
$eposted = $eform->getResult();
// save edited entry
if ($eform->shouldSave()) {
    $list_->setSelected($eposted['id']);
    foreach ($eposted as $k => $v) {
        $list_->getFileType($list_->getSelected())->setPref($k, $v);
    }
    $esaved = $list_->getFileType($list_->getSelected())->save();
    $sysconf_ = SystemConfig::getInstance();
    $sysconf_->setProcessToBeRestarted('ENGINE');

    $list_->setSelected(0);
    $list_->load();
    if ($esaved == 'OKSAVED') {
        $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $save_msg = $lang_->print_txt('SAVEERROR') . "(" . $esaved . ")";
    }
}

// create view
$template_ = new Template('filetype.tmpl');
// prepare options
$allow_deny = [$lang_->print_txt('ALLOW') => 'allow', $lang_->print_txt('DENY') => 'deny'];

// prepare replacements
$replace = [
    "__LANG__" => $lang_->getLanguage(),
    "__SAVE_STATUS__" => $save_msg,
    "__FILENAMELIST_DRAW__" => $list_->drawFiletypes($template_->getTemplate('EXTENTIONLIST'), $eform),
    "__FORM_INPUTALLOWDENY__" => $eform->select('status', $allow_deny, 'allow', ';'),
    "__FORM_INPUTRULE__" => $eform->input('type', 10, ''),
    "__FORM_INPUTNAME__" => $eform->input('name', 35, ''),
    "__FORM_INPUTDESCRIPTION__" => $eform->input('description', 35, ''),
    "__SUBMITNEW_LINK__" => "window.document.forms['" . $eform->getName() . "'].submit()",
    "__REMOVE_FULLLINK__" => $_SERVER['PHP_SELF'] . "?m=d&s=",
    "__RELOAD_NAV_JS__" => "opener.parent.frames['navig_frame'].location.reload(true)"
];

// output page
$template_->output($replace);
