<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the antispam configuration page
 */
 
 /**
  * require admin session, view and AntiSpam objects
  */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/AntiSpam.php");
require_once("config/PreFilterList.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

$message = "";

// check authorizations
$admin_->checkPermissions(array('can_configure'));

$pfid = 0;
if (isset($_GET['pf']) && $_GET['pf'] != "" && is_numeric($_GET['pf'])) {
  $pfid = $_GET['pf'];
}

// create edit form
$cform = new Form('config', 'post', $_SERVER['PHP_SELF']);
$cposted = $cform->getResult();
if (isset($cposted['id']) && is_numeric($cposted['id'])) {
  $pfid = $cposted['id'];
}
if ($pfid < 1) {
  die('BADPARAMS');
}

$moduleslist_ = new PreFilterList();
$moduleslist_->load();
$pf = $moduleslist_->getElementAtPosition($pfid);
if (! $pf) {
  die("CANNOTLOADMODULE");
}

if ($cform->shouldSave()) {
  foreach ($cposted as $key => $value) {
    $pf->setPref($key, $value);	 
  }
  $saved = $pf->save($cposted);
  if ($saved == 'OKSAVED') {
    $message = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $message = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}

// create view
$template_ = new Template('edit_prefilter.tmpl');
$documentor = new Documentor();

$template_->addTMPLFile('SPECIFIC', $pf->getSpecificTMPL());

// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__NAME__" => $pf->getPref('name'),
        "__CONFIG_SAVE_STATUS__" => $message,
        "__FORM_BEGIN_CONFIG__" => $cform->open().$cform->hidden('id', $pf->getPref('position')),
        "__FORM_CLOSE_CONFIG__" => $cform->close(),
        "__FORM_INPUTTIMEOUT__" => $cform->input('timeOut', 4, $pf->getPref('timeOut')),
        "__FORM_INPUTMAXSIZE__" => $cform->input('maxSize', 10, $pf->getPref('maxSize')),
        "__FORM_INPUTXHEADER__" => $cform->input('header', 20, $pf->getPref('header')),
        "__FORM_INPUTPUTSPAMHEADER__" => $cform->checkbox('putSpamHeader', 1, $pf->getPref('putSpamHeader'), '', 1),
        "__FORM_INPUTPUTHAMHEADER__" => $cform->checkbox('putHamHeader', 1, $pf->getPref('putHamHeader'), '', 1),
        "__ADD_SPECIFIC__" => ""
);

$spec = $pf->getSpeciticReplace($template_, $cform);
foreach ($spec as $key => $value) {
  $replace[$key] = $value;
}

// output page
$template_->output($replace);
?>
