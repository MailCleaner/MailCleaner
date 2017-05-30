<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the file name protection configuration page
 */
 
 /**
  * require admin session, view and file name settings objects
  */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/FileNameList.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_configure'));

$save_msg = "";
// create and load filename lists
$list_ = new FileNameList();
$list_->load();
// set selected value
if (isset($_GET['s']) and is_numeric($_GET['s'])) {
  $list_->setSelected($_GET['s']);
}
// delete filename
if (isset($_GET['m']) && $_GET['m'] == 'd') {
  $deleted = $list_->getFileName($list_->getSelected())->delete();
  if ($deleted == "OKDELETED") {
    $save_msg = $lang_->print_txt('DELETESUCCESSFULL');
    $list_->setSelected(0);
    $list_->load();
  } else {
    $save_msg = $lang_->print_txt('DELETEERROR')."(".$deleted.")";
  }
}

// create new entry formular
$nform = new Form('new', 'post', $_SERVER['PHP_SELF']);
$nposted = $nform->getResult();
// save new entry (add)
if ($nform->shouldSave()) {
  $new = new FileName();
  foreach($nposted as $k => $v) {
	$new->setPref($k, $v); 
  }
  $saved = $new->save();
  $list_->setSelected(0);
  $list_->addFileName($new);
  $list_->load();
  if ($saved == 'OKSAVED' || $saved == 'OKADDED') {
    $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $save_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}

// create entry edition formular
$eform = new Form('edit', 'post', $_SERVER['PHP_SELF']);
$eposted = $eform->getResult();
// save edited entry
if ($eform->shouldSave()) {
  $list_->setSelected($eposted['id']);
  foreach($eposted as $k => $v) {
    $list_->getFileName($list_->getSelected())->setPref($k, $v);
  }
  $esaved = $list_->getFileName($list_->getSelected())->save();
  $sysconf_ = SystemConfig::getInstance();
  $sysconf_->setProcessToBeRestarted('ENGINE');
    
  $list_->setSelected(0);
  $list_->load();
  if ($esaved == 'OKSAVED' || $esaved == 'OKADDED') {
    $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $save_msg = $lang_->print_txt('SAVEERROR')."(".$esaved.")";
  }
}

// create view
$template_ = new Template('filename.tmpl');
//prepare options
$allow_deny = array($lang_->print_txt('ALLOW') => 'allow', $lang_->print_txt('DENY') => 'deny');

// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__SAVE_STATUS__" => $save_msg,
        "__FILENAMELIST_DRAW__" => $list_->drawFilenames($template_->getTemplate('EXTENTIONLIST'), $eform),
        "__FORM_BEGIN_NEW__" => $nform->open(),
        "__FORM_CLOSE_NEW__" => $nform->close(),
        "__FORM_INPUTALLOWDENY__" => $nform->select('status', $allow_deny, 'allow', ';'),
        "__FORM_INPUTRULE__" => $nform->input('rule', 10, ''),
        "__FORM_INPUTNAME__" => $nform->input('name', 35, ''),
        "__FORM_INPUTDESCRIPTION__" => $nform->input('description', 35, ''),
        "__SUBMITNEW_LINK__" => "window.document.forms['".$nform->getName()."'].submit()",
        "__REMOVE_FULLLINK__" => $_SERVER['PHP_SELF']."?m=d&s=",
        "__RELOAD_NAV_JS__" => "opener.parent.frames['navig_frame'].location.reload(true)"
);

// output page
$template_->output($replace);
?>