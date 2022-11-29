<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the login page
 */
 
if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
  return 200;
}

/**
 * require the login objects
 */
require_once('variables.php');
require_once("view/Language.php");
require_once("view/LoginDialog.php");
require_once("view/Template.php");

ini_set('error_reporting', E_ALL & ~E_STRICT);

// get global objects instances
$sysconf_ = SystemConfig :: getInstance();
$lang_ = Language :: getInstance('user');
// do not let user log in if we are not a master !
if ($sysconf_->ismaster_ < 1) { 
  exit; 
}

// get the main Login object    
$login_ = new LoginDialog();
// and start (if logged, we should be redirected here)
$login_->start();

// create view
$template_ = new Template('login.tmpl');

$username = htmlspecialchars($_POST['username']);

$login_reminder = "";
$login_status = $login_->printStatus();
$template_->setCondition('LOCALAUTH', false);
if ($login_status != "") {
  $login_reminder = 'LOGININFO';
  if ($login_->isLocal()) {
      $template_->setCondition('LOCALAUTH', true);
  }
  $template_->setCondition('BADCREDENTIALS', true);
}

// Check if this is a registered version
require_once ('helpers/DataManager.php');
$file_conf = DataManager :: getFileConfig($sysconf_ :: $CONFIGFILE_);

$is_enterprise = $file_conf['REGISTERED'] == '1';
if ($is_enterprise) {
        $mclink="https://www.mailcleaner.net";
        $mclinklabel="www.mailcleaner.net";
} else {
        $mclink="https://www.mailcleaner.org";
        $mclinklabel="www.mailcleaner.org";
}

$template_->setCondition('DOMAINCHOOSER', $login_->hasDomainChooser());
$replace = array(
        "__PRINT_STATUS__" => $lang_->print_txt($login_status),
        "__PRINT_LOGININFO__" => $lang_->print_txt($login_reminder),
	    "__BEGIN_LOGIN_FORM__" => "<form method=\"post\" id=\"login\" action=\"".htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8')."\"><div><input type=\"hidden\" name=\"lang\" value=\"".$lang_->getLanguage()."\" /></div>\n",
	    "__END_LOGIN_FORM__" => "</form>\n",
	    "__LOGIN_FIELD__" => "<input class=\"fieldinput\" type=\"text\" name=\"username\" id=\"usernamefield\" size=\"20\" value=\"".$username."\" />",
	    "__OU_FIELD__" => "<input class=\"fieldinput\" type=\"text\" name=\"ou\" size=\"20\" />",
      "__PASSWORD_FIELD__" => "<input class=\"fieldinput\" type=\"password\" name=\"password\" id=\"passwordfield\" size=\"20\" />",
      "__DOMAIN_CHOOSER__" => $login_->printDomainChooser(),
      "__LANGUAGE_CHOOSER__" => $login_->printLanguageChooser($lang_->getLanguage()),
      "__SUBMIT_BUTTON__" => "<input type=\"submit\" name=\"".$lang_->print_txt('SUBMIT')."\" id=\"submitbutton\" value=\"".$lang_->print_txt('SUBMIT')."\" />",
	    "__MCLINK__" => $mclink,
	    "__MCLINKLABEL__" => $mclinklabel,
	    "__USERNAME__" => $username
       );

// display page
$template_->output($replace);

?>
