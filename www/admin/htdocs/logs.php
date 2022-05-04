<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page that will display the log page
 */
 
/**
 * require admin session, and Slave/Soap stuff
 */
require_once('variables.php');
require_once('view/Language.php');
require_once('config/Administrator.php');
require_once('system/Soaper.php');
require_once('view/Template.php');
require_once('view/Form.php');
require_once('system/Slave.php');

// globals
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('admin');

// log files available
$logfiles = array(
            'mta1' => 'exim_stage1/mainlog',
            'mta2' => 'exim_stage2/mainlog',
            'mta4' => 'exim_stage4/mainlog',
            'engine' => 'mailscanner/infolog',
            'httpd' => 'apache/access.log'
           );

$error = "";
$message = "";
$nblines = 10;

// create view objects
$vform = new Form('log', 'post', $_SERVER['PHP_SELF']);
$posted = $vform->getResult();

// check parameters posted
if (isset($posted['l']) && $posted['l'] != "") {
  $log = $posted['l'];
} else {
  if ( !isset($_REQUEST['l']) || !isset($logfiles[$_REQUEST['l']])) {
    die ('BADLOGFILE');
  }
  $log = $_REQUEST['l'];
}
if ($posted['d'] && $posted['d'] != "") {
  $date = $posted['d'];
} else {
  if (!isset($_REQUEST['d']) || ( $_REQUEST['d'] != 'T' && !is_numeric($_REQUEST['d']) )) {
    die ('BADDATE');
  }
  $date = $_REQUEST['d'];
}

// check session
if ($posted['sid'] && $posted['sid'] != "") {
  $sid = $posted['sid'];
} else {
  if (!isset($_REQUEST['sid']) || !preg_match('/^[a-z0-9]+$/', $_REQUEST['sid'])) {
    $error = "BADSESSIONID (".$_REQUEST['sid'].")";
    die($error);
  }
  $sid = $_REQUEST['sid'];
}
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

// create view
$template_ = new Template('logs.tmpl');
$nblines = $template_->getDefaultValue('LOG_LINES');

// prepare options
$lines = array (
    '10' => '10',
    '20' => '20',
    '50' => '50',
    '200' => '200',
    '1000' => '1000',
    '5000' => '5000');
$lines[$lang_->print_txt('ALL')] = 'all';

// prepare replacements
$replace = array(
    '__LANG__' => $lang_->getLanguage(),
    '__ERROR__' => $lang_->print_txt($error),
    '__MESSAGE__' => $lang_->print_txt($message),
    '__HOSTNAME__' => $sysconf_->getSlaveName($sysconf_->getPref('hostid')),
    '__BEGIN_VIEW_FORM__' => $vform->open().$vform->hidden('sid', $sid).$vform->hidden('d', $date).$vform->hidden('l', $log),
    '__CLOSE_VIEW_FORM__' => $vform->close(),
    '__REFRESH__' => $vform->submit('submit', $lang_->print_txt('REFRESH'), ''),
    '__SEARCH__' => $vform->input('search', 30, $posted['search']),
    '__INPUT_LOGLINES__' => $vform->select('loglines', $lines, $posted['loglines'], '', 1),
    '__VIEW_LOG__' => viewLog($log, $date, $posted)
);

// display page
$template_->output($replace);

/**
 * view the log content
 * @param $log    string  log type to display
 * @param $date   numeric date of log to be display (actually the log number)
 * @param $posted array   posted value (for search)
 * @return        string  log contents
 */
function viewLog($log, $date, $posted) {
  global $logfiles;
  $sysconf_ = SystemConfig::getInstance();
  $lang_ = Language::getInstance('admin');

  // check search parameters
  if (isset($posted['loglines']) && ( is_numeric($posted['loglines']) || $posted['loglines'] == 'all')) {
    $loglines = $posted['loglines']; 
  } else {
    $posted['loglines'] = 10;
  }
  if (isset($posted['search']) and $posted['search'] != "") {
    $search = $posted['search'];
  }

  $ext = "";
  $cmd = "cat";
  if (is_numeric($date)) { $date = $date - 1;  $ext = ".".$date; }
  if (is_numeric($date) && $date > 0) {
   $ext .= ".gz";
   $cmd = "zcat";
  }

  if ($posted['loglines'] != 'all') {
    $cmd = $cmd." ".$sysconf_->VARDIR_."/log/".$logfiles[$log].$ext." | tail -n".$posted['loglines'];
  } else { 
    $cmd = $cmd." ".$sysconf_->VARDIR_."/log/".$logfiles[$log].$ext;
  }
  if (isset($search) && $search != "") {
    $cmd = $cmd." | grep ".escapeshellcmd($search);
  }
  $ret = `$cmd`;
  return $ret;
}
?>
