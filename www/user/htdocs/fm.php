<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the reset password page
 */
require_once('variables.php');
require_once("view/Language.php");
require_once("system/SystemConfig.php");
require_once("utils.php");
require_once("view/Template.php");
require_once("system/Soaper.php");

// get global objects instances
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('user');
if (isset($_GET['lang'])) {
  $lang_->setLanguage($_GET['lang']);
  $lang_->reload();
}
if (isset($_GET['l'])) {
  $lang_->setLanguage($_GET['l']);
  $lang_->reload();
}

// check parameters
if (!isset($_GET['id']) || !isset($_GET['a']) || !isset($_GET['s'])) {
  die ("BADPARAMS");
}
if (!is_exim_id($_GET['id']) || !is_email($_GET['a']) || !is_numeric($_GET['s'])) {
  die ("BADPARAMS");
}

$soaper = new Soaper();
$ret = @$soaper->load($sysconf_->getSlaveName($_GET['s']));
if ($ret != "OK") {
	$res = $ret;
} else {
    // actually force the message
    $res = $soaper->queryParam('forceSpam', array($_GET['id'], $_GET['a']));
} 

// get the view objects
$template_model = 'fm.tmpl';
if (isset($_GET['pop']) && $_GET['pop'] == 'up') {
  $template_model = 'fm_pop.tmpl';
}
$template_ = new Template($template_model);
$replace = array(
  '__MESSAGE__' => $lang_->print_txt($res)
);
// output result page
$template_->output($replace);
?>
