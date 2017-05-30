<? 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
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

$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__NAVIGATION_PAGE__" => 'navigation.php?m=q',
        "__QUARANTINE_PAGE__" => 'quarantine.php',
        "__PARAMETERS_PAGE__" => 'parameters.php',
        "__SUPPORT_PAGE__" => 'support.php',
        "__FIRST_PAGE__" => $firstpage
);

// display page
$template_->output($replace);
?>