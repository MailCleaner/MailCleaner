<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the add address confirmation page
 */
 
/**
 * require base objects, but no session
 */
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
  die ("BADPARAMS");
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

// create view
$template_ = new Template('aa.tmpl');
$replace = array(
        '__MESSAGE__' => $lang_->print_txt($message)
);
// display page
$template_->output($replace);
?>