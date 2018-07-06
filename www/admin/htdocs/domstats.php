 <?
 /**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the dynamic statistics display
 */
 
 /**
  * require admin session and view
  */
require_once("admin_objects.php");
require_once("system/SystemConfig.php");
require_once("system/Slave.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("system/SoapTypes.php");
require_once("domain/Domain.php");

/**
 * session globals
 */
global $admin_;
global $sysconf_;
global $lang_;

// not allowed if we are not a master
if ($sysconf_->ismaster_ < 1) { exit; }
// check authorizations
$admin_->checkPermissions(array('can_view_stats'));

$gcounts = array('msgs' => 0, 'spams' => 0, 'viruses' => 0, 'users' => 0);

// instantiate the main form and get results if any
$form_ = new Form('search', 'post', $_SERVER['PHP_SELF']);
$posted = $form_->getResult();
if (!isset($posted['start'])) {
  $posted['start'] = `date +%Y%m%d`;  
}
if (!isset($posted['stop'])) {
  $posted['stop'] = `date +%Y%m%d`;  
}
if (isset($posted['domain']) && isset($posted['start']) && isset($posted['stop'])) {
    
  // get and check each slaves
  $slaves = $sysconf_->getSlaves();
  foreach ($slaves as $slave) { 
    if (!$slave->isAvailable()) {
      $gstatus = 0;
      continue;
    }

    // get counts
    $counts = $slave->getStats($posted['domain'], $posted['start'], $posted['stop']); 
    $gcounts['msgs'] += $counts->msg;
    $gcounts['spams'] += $counts->spam;
    $gcounts['viruses'] += $counts->virus;
    $gcounts['users'] += $counts->user;
  }
  
  $d = new Domain();
  $d->load($posted['domain']);
}


// create view
$template_ = new Template('domstats.tmpl');

// prepare replacements
$replace = array(
        "__FORM_BEGIN_SEARCH__" => $form_->open(),
        "__FORM_CLOSE_SEARCH__" => $form_->close(),
        "__FORM_INPUTDOMAIN__" => $form_->select('domain', $sysconf_->getFilteredDomains(), $posted['domain'], ';'),
        "__FORM_INPUTSTART__" => $form_->input('start', 10, $posted['start']),
        "__FORM_INPUTSTOP__" => $form_->input('stop', 10, $posted['stop']),
        "__FORM_INPUTSUBMIT__" => $form_->submit('submit', $lang_->print_txt('REFRESH'), ''),
        "__NB_MESSAGES__" => $gcounts['msgs'],
        "__NB_SPAMS__" => $gcounts['spams'],
        "__NB_VIRUSES__" => $gcounts['viruses'],
        "__NB_USERS__" => $gcounts['users'],
        "__HASGREYLIST__" => hasPref('greylist'),
        "__HASCALLOUT__" => hasPref('callout'),
        "__SERVER_SELF__" => $_SERVER['PHP_SELF']
);

// output page
$template_->output($replace);


function hasPref($pref) {
  global $d;
  if (!isset($d) && ! $d instanceof Domain) {
  	 return "";
  }
  if ($d->getPref($pref) == "true") {
  	return "YES";
  }
  return "<font color=\"red\">NO</font>";
}
?>
