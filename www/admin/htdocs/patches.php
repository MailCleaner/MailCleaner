<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page that will display the patch status
 */

/**
 * requires systemconfig, soaper/slave and administrative stuff
 */
require_once('variables.php');
require_once('system/SystemConfig.php');
require_once('view/Language.php');
require_once('config/Administrator.php');
require_once('system/Soaper.php');
require_once('view/Template.php');
require_once('view/Form.php');
require_once('system/Slave.php');

/**
 * global variables
 */
$lang = Language::getInstance('admin');
$error = "";
$message = "";

// check session parameter
if (!isset($_GET['sid']) || !preg_match('/^[a-z0-9]+$/', $_GET['sid'])) {
  $error = "BADSESSIONID (".$_GET['sid'].")";
  die($error);
}
$sid = $_GET['sid'];

// connect to local soap service
$soaper = new Soaper();
if (!$soaper->load('127.0.0.1')) {
  die("CANNOTCONTACTSOAP");
}
// check session
$admin_name = $soaper->getSessionUser($sid);
if ($admin_name == "") {
  die('BADSESSION');
}
// and create the session user
$admin_ = new Administrator();
if (!$admin_->load($admin_name)) {
  die("BADSESSIONUSER");
}
$_SESSION['admin'] = serialize($admin_);

// check if an update is already running
if (isUpdateRunning()) {
  $message = "UPDATERUNNING";
} else {
  // if we need to run an update or not
  if (isset($_GET['m']) && $_GET['m']=='fu') {
    echo "starting..";
    $cmd = "/usr/sudo/bin/sudo ".$sysconf_->SRCDIR_."/scripts/cron/mailcleaner_cron.pl > /dev/null & echo \$!";
    $pid = `$cmd`;
    if (!preg_match('/^\s*(\d+)\s*$/', $pid) || $pid == 0) {
      $error = "CANNOTRUNUPDATE";
    }
  }
}

// and instanciate view objects
$pform = new Form('patchlog', 'post', $_SERVER['PHP_SELF']);
$template_ = new Template('patch.tmpl');
$sysconf = SystemConfig::getInstance();
$nblines = $template_->getDefaultValue('LOG_LINES');

// prepare replacements
$replace = array(
    '__LANG__' => $lang_->getLanguage(),
    '__ERROR__' => $lang_->print_txt($error),
    '__MESSAGE__' => $lang_->print_txt($message),
    '__HOSTNAME__' => $sysconf->getSlaveName($sysconf->getPref('hostid')),
    '__LINK_REFRESH__' => "javascript:window.location.href='".$_SERVER['PHP_SELF']."?sid=$sid';",
    '__LINK_FORCEUPDATE__' => "javascript:window.location.href='".$_SERVER['PHP_SELF']."?sid=$sid&m=fu';",
    '__BEGIN_VIEW_FORM__' => $pform->open(),
    '__CLOSE_VIEW_FORM__' => $pform->close(),
    '__VIEW_PATCHES_LOG__' => viewPatchLog($nblines),
    '__LASTPATCH__' => $soaper->query('getLastPatch', array())
);

// output page
$template_->output($replace);

/**
 * return lines of the patch log file
 * @param $nblines  numeric  number of lines to retrieve
 * @return          string   log text
 */
function viewPatchLog($nblines) {
 $sysconf = SystemConfig::getInstance();

 $log = "";
 $filename = $sysconf->VARDIR_."/log/mailcleaner/update.log";
 $lines = file($filename);
 $length = count($lines);
 for ($i = $length-$nblines; $i<=$length; $i++) {
   $log .= $lines[$i];
 }
 return $log;
}

/**
 * check if an update process is already running
 * @return  boolean  true if running, false otherwise
 */
function isUpdateRunning() {
  $cmd = "pgrep -f 'mailcleaner_cron.pl'";
  $res = `$cmd`;
  if ($res != "") {
    return true;
  }
  return false;
}
?>