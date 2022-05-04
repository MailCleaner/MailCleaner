<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
require_once("system/SystemConfig.php");
require_once("Auth.php");
require_once("view/Language.php");
require_once("config/Administrator.php");
require_once("connector/AuthManager.php");

/**
 * constant definitions
 */
define(AUTHLOGFILE, "mc_auth.log");

/**
 * this class take care of setting the administration authentication connector
 */
class AdminLoginDialog {

/**
 * Authmanager object used
 * @var AuthManager
 */
private $auth_;

/**
 * constructor, if defined, set the posted value and find out the other variables (f.e. domain)
 */
public function __construct() {
    
  // first, unset any Auth session, this ensure that user is really logged out
  unset($_SESSION['_authsession']);
  // get global objects instances
  $sysconf = SystemConfig :: getInstance();

  // check if a username has been posted
  if (isset($_REQUEST['username'])) {
    $this->username_ = $_REQUEST['username'];
    $this->username_ = str_replace('\'', '\\\'', $this->username_); // avoid problems with ' in usernames..
  
    $this->auth_ = AuthManager::getAuthenticator('admin');
    $this->auth_->create(null);
  } else {
    // create AuthManager instance but no authenticator
    $this->auth_ = AuthManager::getAuthenticator(null);
    $this->auth_->create(null);
  }
}

/**
 * start the authentication
 * This check authentication and if, success, register the session and redirect to the index page
 * @return   boolean  don't return on success, false on failure
 */
public function start()
{
  global $sysconf_;
  $this->sysconf_ = $sysconf_;
  // check connector
  if (!isset($this->auth_) || !$this->auth_ instanceof AuthManager) {
    return false;
  }

  // and start authentication objects
  $this->auth_->start();
  // ok, now check if user has given a good login/password pair !
  if ($this->auth_->doAuth($this->username_)) {
    // just in case...
    if ($this->username_ == '') {
       return false;
    }
    $admin = new Administrator();
    $admin->load($this->username_);
    $_SESSION['admin'] = serialize($admin);
    if (is_writable($this->sysconf_->VARDIR_."/log/apache/".AUTHLOGFILE)) {
      if ($logfile = fopen($this->sysconf_->VARDIR_."/log/apache/".AUTHLOGFILE, "a")) {
        fwrite($logfile, "[".date("d/M/Y:H:i:s O")."] login SUCCESSFUL for user: ".$this->username_." - ".$_SERVER['REMOTE_ADDR']."\n");
        fclose($logfile);
      }
    }
    header("Location: index.php");
    exit();
  } else {
    // log authentication failure (with IP)
    if (isset ($_POST['username'])) {
      if (is_writable($this->sysconf_->VARDIR_."/log/apache/".AUTHLOGFILE)) {
        if ($logfile = fopen($this->sysconf_->VARDIR_."/log/apache/".AUTHLOGFILE, "a")) {
          fwrite($logfile, "[".date("d/M/Y:H:i:s O")."] login FAILED for user: ".$_POST['username']." - ".$_SERVER['REMOTE_ADDR']."\n");
          fclose($logfile);
        }
      }
    }
  }
}

/**
 * get the html string displaying the status of the login
 * @return  string  status html string
 */
public function printStatus()
{
  $ret = "";
  if (!isset($this->auth_)) {
       return "";
  }
  $lang = Language::getInstance('user');
  if ($this->auth_->getStatus() == -3) {
    return "<font color=\"red\">".$lang->print_txt('BADLOGIN')."</font>";
  } else if ($this->auth_->getStatus() == -2) {
    return "<font color=\"red\">".$tlang->print_txt('SESSIONEXPIRED')."</font>";        
  }
  return "";
}

}
?>
