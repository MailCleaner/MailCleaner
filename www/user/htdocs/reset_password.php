<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
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
$ret = array();
if (preg_match('/(.+)[@%](\S+)$/', $username, $ret)) {
  $domain = $ret[2];
}
   
// if the domain is explicitely set in the REQUEST
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

// get the view objects
$template_model = 'reset_password.tmpl';
$template_ = new Template($template_model);
$replace = array(
  '__MESSAGE__' => $lang_->print_txt($message)
);
// output result page
$template_->output($replace);
?>
