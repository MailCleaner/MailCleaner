<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page for the service stop/start status window
 */

/**
 * require administrative access
 */   
require_once('admin_objects.php');
require_once("view/Template.php");

/**
 * session globals
 */
global $sysconf_;
global $lang_;

// check authorizations
$admin_->checkPermissions(array('can_configure'));

// defaults
$action = 'none';
$daemon = 'NONE';
$host = '127.0.0.1';

// check parameters and set values
if (!isset($_GET['h']) || !is_numeric($_GET['h']) ||
    !isset($_GET['d']) || !preg_match('/^[A-Z0-9]+$/', $_GET['d']) ||
    !isset($_GET['a']) || !preg_match('/stop|start/', $_GET['a'])) {
   $error = "BADARGS";
} else {
    $action = $_GET['a'];
    $daemon = $_GET['d'];
    $host = $sysconf_->getSlaveName($_GET['h']);
}

// output waiting template
$template_ = new Template('daemon_stopstart1.tmpl');

$replace = array(
    '__LANG__' => $lang_->getLanguage(),
    '__ERROR__' => $lang_->print_txt($error),
    '__ACTION_TEXT__' => printAction($action, $daemon)."<script>window.opener.location.reload()</script>",
    '__HOSTNAME__' => $host
);
$template_->output($replace);

// force window refresh by filling the buffers
  flush();
  echo "<!--";
  for($i=0; $i<6000; $i++) {
    echo " ";
  }
  echo "-->";
  flush();
echo "&nbsp;";

// output finished template
$template2_ = new Template('daemon_stopstart2.tmpl');
$replace2 = array(
    '__LANG__' => $lang_->getLanguage(),
    '__ACTION__' => doAction($host, $action, $daemon)     
    );

$template2_->output($replace2);

/**
 * return the html string corresponding the the action to be done (stop or start)
 * @return  string  html string
 */
function printAction($action, $daemon) {
  global $lang_;

  $ret = "";
  switch ($action)  {
    case 'stop':
      return $lang_->print_txt('STOPINGDAEMON').": ".$lang_->print_txt($daemon)." ...";
    case 'restart':
      $ret = $lang_->print_txt('RESTARTINGDAEMON').": ".$lang_->print_txt($daemon);
      if ($daemon == 'HTTPD') {
      	$ret .= $lang_->print_txt('HTTPDRESTARTRELOG');
      } else {
      	$ret .= " ...";
      }
      return $ret;
    default:
      return $lang_->print_txt('STARTINGDAEMON').": ".$lang_->print_txt($daemon)." ...";
  }
  return "";
}

/**
 * actually do the action (stop or start)
 * @param $host   string  host name to ask for stop/start
 * @param $action string  action do ask for (stop or start)
 * @param $daemon string  service to stop or start
 * @return        string  html result string
 */
function doAction($host, $action, $daemon) {
  global $lang_;
  require_once("system/Soaper.php");

  $soaper = new Soaper();
  $ret = $soaper->load($host, 40);
  if ($ret != "OK") {
     return $ret;
  }
  $sid = $soaper->authenticateAdmin();
  if (preg_match('/^[A-Z]+$/', $sid)) {
    return $sid;
  }

  $res = array();
  switch ($action) {
    case 'stop':
      $res = $soaper->queryParam('stopService', array($sid, $daemon));
      break;
    case 'restart':
      $res = $soaper->queryParam('restartService', array($sid, $daemon));
      break;
    default:
      $res = $soaper->queryParam('startService', array($sid, $daemon));
  }
 
  if ($res->status == 'OK') {
    return "<font color=\"green\">".$lang_->print_txt('DONE').".</font>";
  }
  return "<font color=\"red\">".$lang_->print_txt('FAILED')."!</font> (".$res->result.")";
}
?>