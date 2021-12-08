<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the reasons list display page
 */  

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
  return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once("user/ReasonSet.php");
require_once("view/Template.php");

$sysconf = SystemConfig::getInstance();

// get the reason set object
$rs_ = new ReasonSet();
if (!isset($_GET['id']) || !isset($_GET['a']) || !isset($_GET['s'])) {
  die ("BADPARAMS");
}
$rs_->getReasons($_GET['id'], $_GET['a'], $sysconf->getSlaveName($_GET['s']));

// set defaults
$heightfactor = 24;
$heightlimit = 500;

// create view
$template_ = new Template('reasons.tmpl');
$heightfactor = $template_->getDefaultValue('heightfactor');
$heightlimit = $template_->getDefaultValue('heightlimit');

// prepare replacements
$replace = array(
  '__HEIGHT__' => get_window_height($heightfactor, $heightlimit, $rs_->getNbReasons()),
  '__TOTAL_SCORE__' => round($rs_->getTotalScore(),2) ,
  '__REASONS_LIST__' => $rs_->getHtmlList($template_->getTemplate('REASON'))
);
//display page
$template_->output($replace);

/**
 * calculate the window height corresponding of the number of criterias
 * @param $factor  numeric  factor corresponding of each line height
 * @param $limit   numeric  maximum height allowed
 * @param $n       numeric  number of lines
 * @return         numeric  height of window
 */
function get_window_height($factor, $limit, $n) {
  $ret = $limit;
  if ($n < ($limit/$factor)) { 
    $ret = $n*$factor;
  }
  return $ret;
}
?>
