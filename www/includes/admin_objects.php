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
require_once('Log.php');        
require_once('variables.php');
require_once('system/SystemConfig.php');
require_once('view/Language.php');
require_once('config/Administrator.php');

/**
 * session objects
 */
global $lang_;
global $sysconf_;
global $admin_;
global $log_;
    
// set log and load SystemConfig singleton
$log_->setIdent('admin');
$sysconf_ = SystemConfig::getInstance();

//check user is logged. Redirect if not
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
} else {
  // load admin session object
  $admin_ = unserialize($_SESSION['admin']);
}
$lang_ = Language::getInstance('admin');

// delete admin session object
function unregisterAll() {
  unset($_SESSION['admin']);
}
?>
