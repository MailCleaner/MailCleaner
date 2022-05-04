<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the system defaults configuration page
 */
 
 /**
  * require admin session, view and system objects
  */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_configure'));

$save_msg = "";
// create mail form
$dform = new Form('defaults', 'post', $_SERVER['PHP_SELF']);
$dposted = $dform->getResult();
//save settings
if ($dform->shouldSave()) {
  foreach($dposted as $k => $v) {
    $sysconf_->setPref($k, $v);
  }
  $saved = $sysconf_->save();
  if ($saved == 'OKSAVED') {
    $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $save_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}

// create view
$template_ = new Template('config_defaults.tmpl');
$documentor = new Documentor();

// create types and options
$hours = array();
for ($i = 0; $i < 24; $i++) {
  $str = sprintf('%02d:00:00', $i);
  $hours[$str] = $str;
}
$i = 1;
$weekdays = array();
foreach($lang_->print_txt('WEEKDAYS') as $day) {
  $weekdays[$day] = $i++;
}
$monthdays = array();
for ($i = 1; $i < 32; $i++) {
  $monthdays[$i] = $i;
}

// prepare replacements
$replace = array(
        '__DOC_DEFAULTSTITLE__' => $documentor->help_button('DEFAULTSTITLE'),
        '__DOC_DEFAULTSDOMAINS__' => $documentor->help_button('DEFAULTSDOMAINS'),
        '__DOC_DEFAULTSADDRESSES__' => $documentor->help_button('DEFAULTSADDRESSES'),
        '__DOC_DEFAULTSQUARANTINES__' => $documentor->help_button('DEFAULTSQUARANTINES'),
        '__DOC_DEFAULTSTASKS__' => $documentor->help_button('DEFAULTSTASKS'),
        "__LANG__" => $lang_->getLanguage(),
        "__SAVE_STATUS__" => $save_msg,
        "__FORM_BEGIN_DEFAULTS__" => $dform->open(),
        "__FORM_CLOSE_DEFAULTS__" => $dform->close(),
        "__FORM_INPUTLANGUAGE__" => $dform->select('default_language', $lang_->getLanguages('FULLNAMEASKEY'), $sysconf_->getPref('default_language'), ';'),
        "__FORM_INPUTDOMAIN__" => $dform->select('default_domain', $sysconf_->getFilteredDomains(), $sysconf_->getPref('default_domain'), ';'),
        "__FORM_INPUTREPORTSPAMADDRESS__" => $dform->input('analyse_to', 25, htmlentities($sysconf_->getPref('analyse_to'))),
        "__FORM_INPUTSYSTEMSENDER__" => $dform->input('summary_from', 25, htmlentities($sysconf_->getPref('summary_from'))),
        "__FORM_INPUTDAYSTOKEEPSPAMS__" => $dform->input('days_to_keep_spams', 3, htmlentities($sysconf_->getPref('days_to_keep_spams'))),
        "__FORM_INPUTDAYSTOKEEPVIRUSES__" => $dform->input('days_to_keep_virus', 3, htmlentities($sysconf_->getPref('days_to_keep_virus'))),
        "__FORM_INPUTHOURS__" => $dform->select('cron_time', $hours, $sysconf_->getPref('cron_time'), ';'),
        "__FORM_INPUTWEEKDAYS__" => $dform->select('cron_weekday', $weekdays, $sysconf_->getPref('cron_weekday'), ';'),
        "__FORM_INPUTMONTHDAYS__" => $dform->select('cron_monthday', $monthdays, $sysconf_->getPref('cron_monthday'), ';'),
        "__FORM_INPUTSYSHOST__" => $dform->input('syslog_host', 25, htmlentities($sysconf_->getPref('syslog_host'))),
        "__FORM_INPUTWANTCHOOSER__" => $dform->checkbox('want_domainchooser', 1, $sysconf_->getPref('want_domainchooser'), '', 1)
);

// output page
$template_->output($replace);
?>
