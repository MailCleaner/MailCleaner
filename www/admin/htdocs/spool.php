<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller page that will display the spool
 */

/**
 * requires systemconfig, admin and soap/slave stuff
 */
require_once('variables.php');
require_once('system/SystemConfig.php');
require_once('view/Language.php');
require_once('config/Administrator.php');
require_once('system/Soaper.php');
require_once('view/Template.php');
require_once('system/Spooler.php');

/**
 * global variables
 */
$lang = Language::getInstance('admin');

$error = "";
$spool = 0;
$message = "";
// check params and set values
if (!isset($_GET['s'])) {
    $error = "BADSPOOL (" . $_GET['s'] . ")";
    die($error);
}
$spoolid = $_GET['s'];

if (!isset($_GET['sid']) || !preg_match('/^[a-z0-9]+$/', $_GET['sid'])) {
    $error = "BADSESSIONID (" . $_GET['sid'] . ")";
    die($error);
}
$sid = $_GET['sid'];

// connect to local soap service
$soaper = new Soaper();
if (!$soaper->load('127.0.0.1')) {
    die("CANNOTCONTACTSOAP");
}
// check session
$admin_name = $soaper->getSessionUser($sid);
if ($admin_name == "") {
    die('BADSESSION');
}
// and create the session user
$admin_ = new Administrator();
if (!$admin_->load($admin_name)) {
    die("BADSESSIONUSER");
}
$_SESSION['admin'] = serialize($admin_);

// create spooler object
$spool = new Spooler();
if (!$spool->load($spoolid)) {
    die("CANNOTLOADSPOOL");
}

// should we start a full queue run
if (isset($_GET['m']) && $_GET['m'] == 'fa') {
    $status = $spool->runQueue();
    if ($status != 'OK') {
        $error = $status;
    }
}
// should we force only one message
if (isset($_GET['m']) && $_GET['m'] == 'fo') {
    $status = $spool->forceOne($_GET['mid']);
    if ($status != 'OK') {
        $error = $status;
    }
}
// check the queue run status
$message = $spool->queueRunStatus();

// create view
$template_ = new Template('spool.tmpl');
$sysconf = SystemConfig::getInstance();
$images['FORCEMSG'] = $template_->getDefaultValue('FORCEMSG_IMG');
$template = $template_->getTemplate('MESSAGE');

$template_->setCondition('CANFORCE', true);
if ($spoolid == "MTA2") {
    $template_->setCondition('CANFORCE', false);
}

// prepare replacements
$replace = [
    '__LANG__' => $lang->getLanguage(),
    '__ERROR__' => $lang->print_txt($error),
    '__MESSAGE__' => $lang->print_txt($message),
    '__HOSTNAME__' => $sysconf->getSlaveName($sysconf->getPref('hostid')),
    '__SPOOL__' => $lang->print_txt($spoolid),
    '__DRAW_SPOOL__' => $spool->draw($template, $images, $sid),
    '__COUNT__' => $spool->getCount(),
    '__LINK_REFRESH__' => "javascript:window.location.href='" . $_SERVER['PHP_SELF'] . "?s=$spoolid&sid=$sid&p=$pid';",
    '__LINK_FORCEALL__' => "javascript:window.location.href='" . $_SERVER['PHP_SELF'] . "?s=$spoolid&sid=$sid&m=fa&p=$pid';"
];

// output page
$template_->output($replace);
