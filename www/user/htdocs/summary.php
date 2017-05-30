<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the page sending the summary
 */ 
require_once("objects.php");
require_once("user/SpamQuarantine.php");
require_once("view/Form.php");
require_once("view/Template.php");
global $user_;

// check variables
if (!isset($user_) || ! $user_ instanceof User) {
  die ("NOUSER");
}

// get posted values
$form = new Form('filter', 'GET', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

// get quarantine object
$quarantine = new SpamQuarantine();
$quarantine->setSettings($posted);  

// create view
$template_ = new Template('summary.tmpl');
$replace = array(
  '__MESSAGE__' => $quarantine->doSendSummary()
);

// display page
$template_->output($replace);
?>
