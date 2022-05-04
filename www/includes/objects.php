<?    
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 /**
 * include log, system config and session objects
 */
require_once('variables.php');
require_once('system/SystemConfig.php');        
require_once('user/User.php');
require_once('view/Language.php');

ini_set('arg_separator.output', '&amp;');

/**
 * session objects
 */
global $sysconf_;
global $lang_;
global $log_;

// set log and load SystemConfig singleton
$log_->setIdent('user');
$sysconf_ = SystemConfig::getInstance();

//check user is logged. Redirect if not
if (!isset($_SESSION['user'])) {
  $location = 'login.php';
  if (isset($_REQUEST['d']) && preg_match('/^[0-9a-f]{32}(?:[0-9a-f]{8})?$/i', $_REQUEST['d'])) {
    $location .= "?d=".$_REQUEST['d'];
  }
  if (isset($_REQUEST['p'])) {
    $location .= '&p='.$_REQUEST['p'];
  }
  header("Location: ".$location);
  exit;
} else {
  /*$username = $_SESSION['username'];
  $user_ = new User();
  $user_->load($username);
  if ( (! $user_->getDomain() instanceof Domain ) || $user_->getDomain()->getPref('name') == "") {
  	$domainname = $_SESSION['domain'];
    $d = new Domain();
    if ($d->load($domainname)) {
       $user_->setDomain($d);
    }
  }*/
  $user_ = unserialize($_SESSION['user']);
}
$lang_ = Language::getInstance('user');

/*if (isset($_GET['lang'])) {
  $lang_->lang_ = $_GET['lang'];
  $lang_->reload();
  $_SESSION['lang'] = serialize($lang_);
}*/

// delete user session object
function unregisterAll() {
	unset($_SESSION['user']);
	unset($_SESSION['domain']);
}
?>
