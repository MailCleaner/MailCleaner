<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the page sending the summary
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once("objects.php");
require_once("user/SpamQuarantine.php");
require_once("view/Form.php");
require_once("view/Template.php");
global $user_;

// check variables
if (!isset($user_) || !$user_ instanceof User) {
    die("NOUSER");
}

// get posted values
$form = new Form('filter', 'GET', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

// get quarantine object
$quarantine = new SpamQuarantine();
$quarantine->setSettings($posted);

// create view
$template_ = new Template('summary.tmpl');

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__MESSAGE__' => $quarantine->doSendSummary(),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

// display page
$template_->output($replace);
