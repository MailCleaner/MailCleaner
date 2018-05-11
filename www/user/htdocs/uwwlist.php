<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the file type protection configuration page
 */
 
 /**
  * require user session, view and file type settings objects
  */
require_once("objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("user/WWList.php");

global $user_;

// create add form
$aform = new Form('add', 'post', $_SERVER['PHP_SELF']);
$aposted = $aform->getResult();
$eform = new Form('edit', 'post', $_SERVER['PHP_SELF']);
$eposted = $eform->getResult();
// search form for pagination
$sform = new Form('search','post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();

if ($aform->shouldSave()) {
  $address = $aposted['a'];
  if ($address == "") {
  	$address = 0;
  }
  $type_get = $aposted['type'];
} elseif ($eform->shouldSave()) {
  $address = $eposted['a'];
  if ($address == "") {
    $address = 0;
  }
  $type_get = $eposted['type'];
} elseif ($sform->shouldSave()) {
  $address = $sposted['a'];
  if ($address == "") {
    $address = 0;
  }
  $type_get = $sposted['t'];  
} else {
  $address = $_REQUEST['a'];
  $type_get = $_REQUEST['t'];
}

// get the global objects instances
$sysconf_ = SystemConfig::getInstance();
$addstatus = "";
$deletestatus = "";
$per_page = 50;

// check address/domain parameters first
$mode = 'badmode';
$domain = "";
if (preg_match('/^([a-zA-Z0-9\_\-\.]+)\@([a-zA-Z0-9\_\-\.]+)$/', $address, $matches) && $user_->hasAddress($address)) {
	$mode = "address";
}
if ($mode == "badmode") {
	die ("BADPARAMS $address");
}

// check list type parameter
$type = 'warn';
if ($type_get=='1' || $type_get=='white') 
	$type = 'white';
if($type_get == '3' || $type_get =='black')
	$type = 'black';

// check if should delete
if ($_GET['d'] && is_numeric($_GET['d'])) {
  $entry = new WWEntry();
  if ($entry->load($_GET['d'])) {
  	$deleted = $entry->delete();
    if ($deleted == "OKDELETED") {
      $deleted_msg = $lang_->print_txt('DELETESUCCESSFULL');
    } else {
      $deleted_msg = $lang_->print_txt('DELETEERROR')."(".$deleted.")";    
    };
  }      
}

// create add form
$aform = new Form('add', 'post', $_SERVER['PHP_SELF']);
$aposted = $aform->getResult();
if ($aform->shouldSave()) {
  // should add entry
  $new = new WWEntry();
  $new->load(0);
  foreach($aposted as $k => $v) {
    $new->setPref($k, $v);
  }
  $new->setPref('type', $type);
  $new->setPref('recipient', $address);
  $addstatus = $new->save();
  if ($addstatus == "OKADDED") {
  	$addstatus = $lang_->print_txt("ADDEDSUCCESSFULL");
  } elseif ($addstatus == 'RECORDALREADYEXISTS') {
    $addstatus = $lang_->print_txt('RECORDALREADYEXISTS');
  } else {
  	$addstatus = $lang_->print_txt("ADDEDERROR")." ($addstatus)";
  }
}   

// load list
$wwlist = new WWList();
$wwlist->setForm($sform->getName());
if (isset($eposted['type'])) {
	$type = $eposted['type'];
}
$wwlist->load($address, $type);

// check if should edit an entry
if ($_GET['s'] && is_numeric($_GET['s']) && !$_GET['d']) {
  $wwlist->setEntryToEdit($_GET['s'], $eform);
}
if ($eform->shouldSave()) {
  // should save
  $edited = $wwlist->getEntry($eposted['id']);
  foreach($eposted as $k => $v) {
    $edited->setPref($k, $v);
  }
  $savestatus = $edited->save();
  if ($savestatus == "OKSAVED") {  	
    $savestatus = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
  	$savestatus = $lang_->print_txt('SAVEERROR'). " ($savestatus)";
  }
}

// create view
$template_ = new Template('wwlist.tmpl');
$template = $template_->getTemplate('WWENTRYLIST');
$per_page = $template_->getDefaultValue('elementPerPage');
if (is_numeric($per_page) && $per_page > 0) {
  $wwlist->setNbElementsPerPage($per_page);
}

$active_inactive = array($lang_->print_txt('ACTIVE') => '1', $lang_->print_txt('INNACTIVE') => '0');
$replace = array(
  "__WWENTRYLISTDRAW__" => $wwlist->getList($template, null),
  "__DELETE_STATUS__" => $deleted_msg,
  "__ADD_STATUS__" => $addstatus,
  "__SAVE_STATUS__" => $savestatus,
  "__NBENTRIES__" => $wwlist->getNbElements(),
  "__TOTAL_PAGES__" => $wwlist->getNumberOfPages(),
  "__ACTUAL_PAGE__" => $wwlist->getPage(),
  "__PAGE_SEP__" => $wwlist->getPageSeparator($sep),
  "__PREVIOUS_PAGE__" => $wwlist->getPreviousPageLink(),
  "__NEXT_PAGE__" => $wwlist->getNextPageLink(),
  "__PAGE_JS__" => $wwlist->getJavaScript(),
  "__WWLIST_FOR__" => getWWListHeader(),
  "__FORM_BEGIN_WWENTRYADD__" => $aform->open().$aform->hidden('a', $address).$aform->hidden('type', $type_get),
  "__FORM_CLOSE_WWENTRYADD__" => $aform->close(),
  "__FORM_INPUTSENDER__" => $aform->input("sender", 30, ''),
  "__FORM_INPUTSTATUS__" => $aform->select('status', $active_inactive, '1', ';'),
  "__FORM_INPUTCOMMENT__" => $aform->input("comments", 35, ''),
  "__FORM_INPUTSUBMIT__" => "window.document.forms['".$aform->getName()."'].submit()",
  "__REMOVE_FULLLINK__" => $_SERVER['PHP_SELF']."?a=".$address."&t=".$type_get."&d=",
  "__EDIT_BASELINK__" => $_SERVER['PHP_SELF']."?a=".$address."&t=".$type_get,
  "__FORM_BEGIN_SEARCH__" => $sform->open().$sform->hidden('page', $wwlist->getPage()).$sform->hidden('t', $type).$sform->hidden('a', $address),
  "__FORM_CLOSE_SEARCH__" => $sform->close(),
  
);

$template_->output($replace);

function getWWListHeader() {
  global $type_get;
  global $address;
  global $lang_;

  if ($type_get == 2 | $type_get == 'warn') {
    if ($address == '0') {
      return  $lang_->print_txt('WARNLISTFORGLOBAL');
    }
  	return $lang_->print_txt_param('WARNLISTFOR', $address);
  }
  else if($type_get == 1 | $type_get == 'white') {
	if ($address == '0') {
    		return  $lang_->print_txt('WHITELISTFORGLOBAL');
	}
  	return $lang_->print_txt_param('WHITELISTFOR', $address);
  }
  else if ($type_get == 3 | $type_get == 'black') {
	if ($address == '0') {
                return  $lang_->print_txt('BLACKLISTFORGLOBAL');
        }
        return $lang_->print_txt_param('BLACKLISTFOR', $address);
  }
}
?>
