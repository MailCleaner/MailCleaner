<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the main controller for the configuration pages
 */
 
/**
 * require valid session
 */
require_once("objects.php"); 
require_once("view/Template.php");
require_once("view/Form.php");
global $sysconf_;
global $lang_;
global $user_;

// some defaults
// sets the different menu options and corresponding templates and controllers
$topics = array();
$topics['int'] = array('INTERFACETOPIC', 'conf_interface.tmpl', 'ConfigUserInterface');
$topics['addparam'] = array('ADDRESSPARAMTOPIC', 'conf_addressparam.tmpl', 'ConfigUserAddressParam');
$topics['quar'] = array('QUARPARAMTOPIC', 'conf_quarantine.tmpl', 'ConfigUserQuarantine');
if (!$user_->isStub()) {
  // if ldap connector..
  if ($user_->getDomain()->getPref('auth_type') != 'ldap') {
    $topics['addlist'] = array('ADDRESSLISTTOPIC', 'conf_addresslist.tmpl', 'ConfigUserAddressList');
  }
}


# get antispam global prefs
require_once('config/AntiSpam.php');
$antispam_ = new AntiSpam();
$antispam_->load();

if ($antispam_->getPref('enable_warnlists') && $user_->getDomain()->getPref('enable_warnlists')) {
  $topics['warn'] = array('WARNLISTTOPIC', 'conf_warnlist.tmpl', 'ConfigUserWWList');  
}

/*
if ($antispam_->getPref('enable_whitelists') && $user_->getDomain()->getPref('enable_whitelists')) {
} */

$topics['white'] = array('WHITELISTTOPIC', 'conf_whitelist.tmpl', 'ConfigUserWWList');
$topics['black'] = array('BLACKLISTTOPIC', 'conf_blacklist.tmpl', 'ConfigUserWWList');
$topics['wnews'] = array('NEWSLISTTOPIC', 'conf_newslist.tmpl', 'ConfigUserWWList');


$topic = 'int';
if (isset($_GET['t']) && isset($topics[$_GET['t']])) {
  $topic = $_GET['t'];
}
if (!isset($topics[$topic])) {
	$topic = key($topics);
}

// get specific controller
require_once('controllers/Controller.php');
$controller = Controller::factory($topics[$topic][2]);
$controller->processInput();

// create view
$template_ = new Template($topics[$topic][1]);

$replace = array(
    "__PRINT_USERNAME__" => $user_->getName(),
    "__LINK_LOGOUT__" => '/logout.php',
    
    "__MENULIST__" => getMenuList(),
    "__THISTOPIC__" => $topic,
    "__TOPIC_TITLE__" => $lang_->print_txt($topics[$topic][0]."TITLE"),
    '__SELECTOR_LANG__' => $lang_->html_select(),
);

$replace = $controller->addReplace($replace, $template_);

// display page
$template_->output($replace);


function getMenuList() {
  global $template_;	
  global $topics;
  global $lang_;
  
  $ret = "";
  foreach ($topics as $topicname => $topic) {
    $t = $template_->getTemplate('MENUITEM');
  	$t = str_replace('__TOPIC__', $topicname, $t);
    $t = str_replace('__TOPIC_TEXT__', $lang_->print_txt($topic[0]), $t);
    $ret .= $t;
  }
  return $ret;
}
?>
