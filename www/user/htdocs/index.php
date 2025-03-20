<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the main index page
 * It is responsible for the frameset
 */

/**
 * require valid session
 */
require_once("objects.php");
require_once("view/Template.php");
require_once("system/SystemConfig.php");
global $sysconf_;
global $lang_;

/**
 * out if we are not on a master host
 */
if ($sysconf_->ismaster_ < 1) {
    exit;
}

// create view
$template_ = new Template('index.tmpl');

$firstpage = 'quarantine.php';
#if (!$user_->hasPrefs()) {
#  $firstpage = 'configuration.php?t=int';
#}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__LANG__' => $lang_->getLanguage(),
    '__NAVIGATION_PAGE__' => 'navigation.php?m=q',
    '__QUARANTINE_PAGE__' => 'quarantine.php',
    '__PARAMETERS_PAGE__' => 'parameters.php',
    '__SUPPORT_PAGE__' => 'support.php',
    '__FIRST_PAGE__' => $firstpage,
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

// display page
$template_->output($replace);
