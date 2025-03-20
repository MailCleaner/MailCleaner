<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the dangerous content configuration page
 */

/**
 * require admin session, view and dangerous content objects
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/DangerousContent.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(['can_configure']);

// create and load dangerous content objects
$dangerous_ = new DangerousContent();
$dangerous_->load();

$save_msg = "";
// create main form
$dform = new Form('dangerous', 'post', $_SERVER['PHP_SELF']);
$dposted = $dform->getResult();
// save settings
if ($dform->shouldSave()) {
    foreach ($dposted as $k => $v) {
        $dangerous_->setPref($k, $v);
    }
    $saved = $dangerous_->save();
    if ($saved == 'OKSAVED') {
        $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $save_msg = $lang_->print_txt('SAVEERROR') . "(" . $saved . ")";
    }
}

// create view
$template_ = new Template('config_dangerous.tmpl');
$documentor = new Documentor();

// set options and field values
$allow_disarm_block = [
    $lang_->print_txt('ALLOW') => 'yes',
    $lang_->print_txt('DISARM') => 'disarm',
    $lang_->print_txt('BLOCK') => 'no'
];
$allow_disarm = [
    $lang_->print_txt('ALLOW') => 'yes',
    $lang_->print_txt('DISARM') => 'disarm'
];
$allow_block = [
    $lang_->print_txt('ALLOW') => 'yes',
    $lang_->print_txt('BLOCK') => 'no'
];
$neg_allow_block = [
    $lang_->print_txt('ALLOW') => 'no',
    $lang_->print_txt('BLOCK') => 'yes'
];

// prepare replacements
$replace = [
    '__DOC_DANGEROUSTITLE__' => $documentor->help_button('DANGEROUSTITLE'),
    '__DOC_DANGEROUSHTMLCHECKS__' => $documentor->help_button('DANGEROUSHTMLCHECKS'),
    '__DOC_DANGEROUSFORMATCHECKS__' => $documentor->help_button('DANGEROUSFORMATCHECKS'),
    '__DOC_DANGEROUSATTACHCHECKS__' => $documentor->help_button('DANGEROUSATTACHCHECKS'),
    "__LANG__" => $lang_->getLanguage(),
    "__SAVE_STATUS__" => $save_msg,
    "__FORM_BEGIN_DANGEROUS__" => $dform->open(),
    "__FORM_CLOSE_DANGEROUS__" => $dform->close(),
    "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true);",
    "__FORM_INPUTIFRAMETAGS__" => $dform->select('allow_iframe', $allow_disarm_block, $dangerous_->getPref('allow_iframe'), ';'),
    "__FORM_INPUTIFRAMESILENT__" => $dform->checkbox('silent_iframe', 'yes', $dangerous_->getPref('silent_iframe'), '', 1),
    "__FORM_INPUTFORMTAGS__" => $dform->select('allow_form', $allow_disarm_block, $dangerous_->getPref('allow_form'), ';'),
    "__FORM_INPUTFORMSILENT__" => $dform->checkbox('silent_form', 'yes', $dangerous_->getPref('silent_form'), '', 1),
    "__FORM_INPUTSCRIPTTAGS__" => $dform->select('allow_script', $allow_disarm_block, $dangerous_->getPref('allow_script'), ';'),
    "__FORM_INPUTSCRIPTSILENT__" => $dform->checkbox('silent_script', 'yes', $dangerous_->getPref('silent_script'), '', 1),
    "__FORM_INPUTCODEBASETAGS__" => $dform->select('allow_codebase', $allow_disarm_block, $dangerous_->getPref('allow_codebase'), ';'),
    "__FORM_INPUTCODEBASESILENT__" => $dform->checkbox('silent_codebase', 'yes', $dangerous_->getPref('silent_codebase'), '', 1),
    "__FORM_INPUTWEBBUGS__" => $dform->select('allow_webbugs', $allow_disarm, $dangerous_->getPref('allow_webbugs'), ';'),
    "__FORM_INPUTHTMLWLIPS__" => $gform->textarea('html_wl_ips', 30, 5, $antispam_->getPref('html_wl_ips')),
    "__FORM_INPUTPASSWORDARCHIVES__" => $dform->select('allow_passwd_archives', $allow_block, $dangerous_->getPref('allow_passwd_archives'), ';'),
    "__FORM_INPUTPARTIALMESSAGE__" => $dform->select('allow_partial', $allow_block, $dangerous_->getPref('allow_partial'), ';'),
    "__FORM_INPUTEXTERNALBODIES__" => $dform->select('allow_external_bodies', $allow_block, $dangerous_->getPref('allow_external_bodies'), ';'),
    "__FORM_INPUTENCYPTEDMESSAGES__" => $dform->select('block_encrypt', $neg_allow_block, $dangerous_->getPref('block_encrypt'), ';'),
    "__FORM_INPUTUNENCRYPTEDMESSAGES__" => $dform->select('block_unencrypt', $neg_allow_block, $dangerous_->getPref('block_unencrypt'), ';'),
    "__LINK_FILENAMECHECKS__" => "config_filename.php",
    "__LINK_FILETYPECHECKS__" => "config_filetype.php"
];

// output page
$template_->output($replace);
