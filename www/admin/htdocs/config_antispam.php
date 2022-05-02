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

// check authorizations
$admin_->checkPermissions(array('can_configure'));

// create and load antispam configuration handler
$antispam_ = new AntiSpam();
$antispam_->load();

$gsave_msg = "";
// create global form
$gform = new Form('global', 'post', $_SERVER['PHP_SELF']);
$gposted = $gform->getResult();
// save settings
if ($gform->shouldSave()) {
  foreach($gposted as $k => $v) {
    $antispam_->setPref($k, $v);
  }
  $saved = $antispam_->save();
  if ($saved == 'OKSAVED') {
    $gsave_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $gsave_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}

$msave_msg = "";
// create modules form
$mform = new Form('modules', 'post', $_SERVER['PHP_SELF']);
$mposted = $mform->getResult();

// create and load modules list
$moduleslist_ = new PreFilterList();
$moduleslist_->load();
if (isset($_GET['up']) and isset($_GET['m']) and is_numeric($_GET['m']) and $_GET['m'] > 1) {
  $moduleslist_->orderElement($_GET['m'], $_GET['m']-1);
  if ($moduleslist_->save()) {
  	$message = "MODULESORDERSAVED";
  }
}
if (isset($_GET['down']) and isset($_GET['m']) and is_numeric($_GET['m']) and $_GET['m'] < $moduleslist_->getNumberOfElements()) {
  $moduleslist_->orderElement($_GET['m'], $_GET['m']+1);
  if ($moduleslist_->save()) {
    $message = "MODULESORDERSAVED";
  }
}

//save settings
if ($mform->shouldSave()) {
  $message = "MODULESLISTSAVED";
  for ($i=1; $i <= $moduleslist_->getNumberOfElements(); $i++) {
  	$pf = $moduleslist_->getElementAtPosition($i);
    if (!$pf) {
       continue;
    }
    
    $prefs = array('active', 'neg_decisive', 'pos_decisive');
    foreach($prefs as $pref) {
      if (isset($mposted[$pref."_".$i])) {
      	$pf->setPref($pref, $mposted[$pref."_".$i]);
      }
    }
    if ( !$pf->save($mposted) ) {
    	$message = "MODULESLISTNOTSAVED";
    }
  }
}

// create view
$template_ = new Template('config_antispam.tmpl');
$documentor = new Documentor();

$template_->setCondition('CANADD', 1);
// prepare replacements
$replace = array(
        '__DOC_ANTISPAMTITLE__' => $documentor->help_button('ANTISPAMSETTINGS'),
        '__DOC_ANTISPAMMODULES__' => $documentor->help_button('ANTISPAMMODULES'),
        "__LANG__" => $lang_->getLanguage(),
        "__MODULESMESSAGE__" => $lang_->print_txt($message),
        "__GLOBALSSAVE_STATUS__" => $gsave_msg,
        "__FORM_BEGIN_GLOBAL__" => $gform->open(),
        "__FORM_CLOSE_GLOBAL__" => $gform->close(),
        "__FORM_INPUTFRIENDLYLANGUAGES__" => $gform->input('ok_locales', 15, $antispam_->getPref('ok_locales')),
        "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true)",
        "__FORM_INPUTENABLEWHITELIST__" => $gform->checkbox('enable_whitelists', 1, $antispam_->getPref('enable_whitelists'), whitelistWarning(), 1),
        "__FORM_INPUTENABLEWARNLIST__" => $gform->checkbox('enable_warnlists', 1, $antispam_->getPref('enable_warnlists'), '', 1),
	"__FORM_INPUTENABLEBLACKLIST__" => $gform->checkbox('enable_blacklists', 1, $antispam_->getPref('enable_blacklists'), '', 1),
        "__FORM_INPUTUSESYSLOG__" => $gform->checkbox('use_syslog', 1, $antispam_->getPref('use_syslog'), '', 1),
        "__FORM_INPUTTRUSTEDIPS__" => $gform->textarea('trusted_ips', 30, 5, $antispam_->getPref('trusted_ips')),
        "__LINK_EDITWHITELIST__" => "wwlist.php?t=1&a=0",
        "__LINK_EDITWARNLIST__" => "wwlist.php?t=2&a=0",
        "__LINK_EDITBLACKLIST__" => "wwlist.php?t=3&a=0",
        "__LINK_EDITNEWSLIST__" => "wwlist.php?t=4&a=0",
        "__LINK_EDITPREFILTER__" => "edit_prefilter.php?pf=",
        
        "__FORM_BEGIN_MODULES__" => $mform->open(),
        "__FORM_CLOSE_MODULES__" => $mform->close(),
        "__MODULESSAVE_STATUS__" => $msave_msg,
        "__MODULESLIST_DRAW__" => $moduleslist_->getList($template_, $mform),
);

// output page
$template_->output($replace);

function whitelistWarning() {
    global $gform;
    global $lang_;
	$js = " if (window.document.forms['".$gform->getName()."'].antispam_enable_whitelists.value=='1') {" .
            " alert ('".$lang_->print_txt('WHITELISTWARNING')."'); }";
    return $js;
}

?>
