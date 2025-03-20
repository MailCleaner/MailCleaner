<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the logout page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

/**
 * require session
 */
require_once("objects.php");
require_once("view/LoginDialog.php");
require_once("view/Template.php");
require_once("config/HTTPDConfig.php");
global $sysconf_;
global $lang_;

// create view
$template_ = new Template('logout.tmpl');

$http = new HTTPDConfig();
$http->load();

$http_sheme = 'http';
$port = '';
if ($http->getPref('use_ssl')) {
    $http_sheme = 'https';
    if ($http->getPref('https_port') != 443) {
        $port = ':' . $http->getPref('https_port');
    }
} else {
    if ($http->getPref('http_port') != 80) {
        $port = ':' . $http->getPref('http_port');
    }
}

// Check if this is a registered version
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// prepare replacements
$replace = [
    '__BASE_URL__' => $_SERVER['SERVER_NAME'],
    '__BEENLOGGEDOUT__' => $lang_->print_txt_param('BEENLOGGEDOUT', $http_sheme . "://" . $_SERVER['SERVER_NAME'] . $port),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
//display page
$template_->output($replace);

// and do the job !
unregisterAll();
