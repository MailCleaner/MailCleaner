<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the force message page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once("system/SystemConfig.php");
require_once("utils.php");
require_once("view/Template.php");
require_once("system/Soaper.php");
require_once("domain/Domain.php");
require_once("user/User.php");

// get global objects instances
$sysconf_ = SystemConfig::getInstance();

// first get default domain
$domain = $sysconf_->getPref('default_domain');
$username = $_REQUEST['u'];
$username = str_replace('\'', '\\\'', $username); // avoid problems with ' in usernames..

// if we can find domain in login name given (such as login@domain)
$ret = [];
if (preg_match('/(.+)[@%](\S+)$/', $username, $ret)) {
    $domain = $ret[2];
}

// if the domain is explicitly set in the REQUEST
if (isset($_REQUEST['domain']) && in_array($_REQUEST['domain'], $sysconf_->getFilteredDomains())) {
    $domain = $_REQUEST['domain'];
}

// create domain object
$domain_ = new Domain();
$domain_->load($domain);

// then format username and create corresponding connector
$username = $domain_->getFormatedLogin($username);

$user = new User();
$user->setDomain($domain);
$user->load($username);

$message = "NONLOCALDOMAIN";
if ($user->isLocalUser()) {
    $message = $user->resetLocalPassword();
}

$lang_ = Language::getInstance('user');
if (isset($_REQUEST['lang'])) {
    $lang_->setLanguage($_REQUEST['lang']);
    $lang_->reload();
}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// get the view objects
$template_model = 'reset_password.tmpl';
$template_ = new Template($template_model);
$replace = [
    '__MESSAGE__' => $lang_->print_txt($message),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
// output result page
$template_->output($replace);
