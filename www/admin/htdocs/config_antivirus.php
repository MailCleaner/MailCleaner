<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the antivirus configuration page
 */
 
 /**
  * require admin session, view and AntiVirus objects
  */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/AntiVirus.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_configure'));

// create and load antivirus configuration handler
$antivirus_ = new AntiVirus();
$antivirus_->load();

$save_msg = "";
// create scanners form
$sform = new Form('scanners', 'post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();
// save settings
if ($sform->shouldSave()) {
  foreach($sposted as $k => $v) {
    if (preg_match('/(\S+)\_(\S+)/', $k, $tmp)) {
      $tmp[1] = preg_replace('/UUU/', '-', $tmp[1]);
      $antivirus_->setScannerPref($tmp[1], $tmp[2], $v);
    }
  }
  $saved = $antivirus_->save();

  if ($saved == 'OKSAVED') {
    $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $save_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}

// create main settings form
$cform = new Form('settings', 'post', $_SERVER['PHP_SELF']);
$cposted = $cform->getResult();
// save settings
if ($cform->shouldSave()) {
  foreach($cposted as $k => $v) {
    $antivirus_->setPref($k, $v);
  }
  $csaved = $antivirus_->save();

  if ($csaved == 'OKSAVED') {
    $csave_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $csave_msg = $lang_->print_txt('SAVEERROR')."(".$csaved.")";
  }
}

// create view
$template_ = new Template('config_antivirus.tmpl');
$documentor = new Documentor();

$usetnefoptions = array($lang_->print_txt('NOTNEF') => 'no', $lang_->print_txt('ADDTNEF') => 'add', $lang_->print_txt('REPLACETNEF') => 'replace');
// prepare replacements
$replace = array(
        '__DOC_ANTIVIRUSSCANNERS__' => $documentor->help_button('ANTIVIRUSSCANNERS'),
        '__DOC_ANTIVIRUSSETTINGS__' => $documentor->help_button('ANTIVIRUSSETTINGS'),
        "__LANG__" => $lang_->getLanguage(),
        "__SAVE_STATUS__" => $save_msg,
        "__FORM_BEGIN_SCANNERS__" => $sform->open(),
        "__FORM_CLOSE_SCANNERS__" => $sform->close(),
        "__SCANNERSLIST_DRAW__" => $antivirus_->drawScanners($template_->getTemplate('SCANNERSLIST'), $sform),
        "__SCANNERS_SAVE_STATUS__" => $save_msg,	
        "__FORM_BEGIN_SETTINGS__" => $cform->open(),
        "__FORM_CLOSE_SETTINGS__" => $cform->close(),
        "__SETTINGS_SAVE_STATUS__" => $csave_msg,
        "__FORM_INPUTSILENT__" => $cform->checkbox('silent', 'yes', $antivirus_->getPref('silent'), '', 1),
        "__FORM_INPUTMAXMESSAGESIZE__" => $cform->input('max_message_size', 8, $antivirus_->getPref('max_message_size')),
        "__FORM_INPUTMACATTACHSIZE__" => $cform->input('max_attach_size', 8, $antivirus_->getPref('max_attach_size')),
        "__FORM_INPUTMAXARCHIVEDEPTH__" => $cform->input('max_archive_depth', 3, $antivirus_->getPref('max_archive_depth')),
        "__FORM_INPUTEXPANDTNEF__" => $cform->checkbox('expand_tnef', 'yes', $antivirus_->getPref('expand_tnef'), '', 1),
        "__FORM_INPUTDELIVERBADTNEF__" => $cform->checkbox('deliver_bad_tnef', 'yes', $antivirus_->getPref('deliver_bad_tnef'), '', 1),
        "__FORM_INPUTUSETNEFCONTENT__" => $cform->select('usetnefcontent', $usetnefoptions, $antivirus_->getPref('usetnefcontent'), ';', 1),
        "__FORM_INPUTSENDNOTICES__" => $cform->checkbox('send_notices', 'yes', $antivirus_->getPref('send_notices'), '', 1),
        "__FORM_INPUTSENDNOTICESTO__" => $cform->input('notices_to', 20, $antivirus_->getPref('notices_to')),
        "__FORM_INPUTSCANERTIMEOUT__" => $cform->input('scanner_timeout', 3, $antivirus_->getPref('scanner_timeout')),
        "__FORM_INPUTFILETIMEOUT__" => $cform->input('file_timeout', 3, $antivirus_->getPref('file_timeout')),
        "__FORM_INPUTTNETIMEOUT__" => $cform->input('tnef_timeout', 3, $antivirus_->getPref('tnef_timeout')),
        "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true)"
);

// output page
$template_->output($replace);
?>