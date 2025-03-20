<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the add address confirmation page
 */

/**
 * require base objects, but no session
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once("system/SystemConfig.php");
require_once("user/AliasRequest.php");
require_once("view/Template.php");

// get the global objects instances
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('user');

// set the language from what is passed in url
if (isset($_GET['lang'])) {
    $lang_->setLanguage($_GET['lang']);
    $lang_->reload();
}
if (isset($_GET['l'])) {
    $lang_->setLanguage($_GET['l']);
    $lang_->reload();
}

// check params
if (!isset($_GET['add']) || !isset($_GET['id'])) {
    die("BADPARAMS");
}

// create request
$alias_request = new AliasRequest(null);
// and do the request
if (isset($_GET['m']) && $_GET['m'] == 'd') {
    // delete confirmation
    $message = $alias_request->remAlias($_GET['id'], $_GET['add']);
} else {
    // accept confirmation
    $message = $alias_request->addAlias($_GET['id'], $_GET['add']);
}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// create view
$template_ = new Template('aa.tmpl');
$replace = [
    '__MESSAGE__' => $lang_->print_txt($message),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
// display page
$template_->output($replace);
