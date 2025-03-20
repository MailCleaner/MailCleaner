<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for a custom page
 */

/**
 * require session
 */
require_once("objects.php");
require_once("view/Template.php");

$template = 1;
// check parameters
if (!isset($_GET['t']) || !is_numeric($_GET['t'])) {
    die("BADPARAMS");
}
// get the template file to use
$template = $_GET['t'];

// create view
$template_ = new Template("custom_$template.tmpl");

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// prepare replacements
$replace = [
    '__LANG__' => $lang_->getLanguage(),
    '__PRINT_USERNAME__' => $user_->getPref('username'),
    '__PRINT_MAINADDRESS__' => $user_->getMainAddress(),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
// display page
$template_->output($replace);
