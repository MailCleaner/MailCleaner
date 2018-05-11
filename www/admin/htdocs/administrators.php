<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the administrator configuration page
 */

/**
 * require admin session, view and Administrator objects
 */ 
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/AdminList.php");
require_once("config/Administrator.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// first check authorization
$admin_->checkPermissions(array('can_configure'));

// get the request parameters
$selected_admin = "";
$mode = "";
if (isset($_GET['a']) && $_GET['a'] != "") {
  $selected_admin = $_GET['a'];
}
// modes are n for none, a for add and d for delete
$mode = 'n';
if (isset($_GET['m']) && ( $_GET['m'] == 'd' || $_GET['m'] == 'a' ) ) {
  $mode = $_GET['m'];
}

// create the administrator edition form
$aform = new Form('administrator', 'post', $_SERVER['PHP_SELF']);
$aposted = $aform->getResult(); 
  
// create the search list and form
$sform = new Form('search', 'post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();
if (isset($sposted['selected']) && $sposted['selected'] != "") {
  $selected_admin = $sposted['selected'];
}
if (isset($aposted['selected']) && $aposted['selected'] != "") {
  $selected_admin = $aposted['selected'];
}

// create and load administrator
$admin = new Administrator();
$admin->load($selected_admin);

// delete administrator
if ($mode == 'd') {
  $deleted = $admin->delete();
  if ($deleted == "OKDELETED") {
    $deleted_msg = $lang_->print_txt('DELETESUCCESSFULL');
    $admin->load("");
    $selected_admin = "";
  } else {
    $deleted_msg = $lang_->print_txt('DELETEERROR')."(".$deleted.")";
  }
}
  
// save/add administrator
if ($aform->shouldSave()) {
  foreach( $aposted as $k => $v) {
    $admin->setPref($k, $v);
  }
  $admin->setPasswordConfirmation($aposted['confirm']);
  $asaved = $admin->save();
  $selected_admin = $admin->getPref('username');  
  if ($asaved == 'OKSAVED') {
    $asaved_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } elseif ($asaved == 'OKADDED') {
    $addsaved_msg = $lang_->print_txt('ADDEDSUCCESSFULL');
  } else {
    $asaved_msg = $lang_->print_txt($asaved);
  }
  $admin->load($admin->getPref('username'));
  $selected_admin = $admin->getPref('username');
}

// get the administrator list 
$admin_list = new AdminList();
$admin_list->setForm($sform->getName());
$admin_list->load();

// create view
$template_ = new Template('administrators.tmpl');
$documentor = new Documentor();

$sep = $template_->getDefaultValue('sep');
if ($selected_admin != "" || $mode == 'a') {
  $template_->setCondition('ADMINSELECTED', true);
}

// prepare replacements
$replace = array(
        '__DOC_ADMINTITLE__' => $documentor->help_button('ADMINTITLE'),
	    '__DOC_ADMINACCESS__' => $documentor->help_button('ADMINACCESS'),
	    '__DOC_ADMINAUTHORIZATIONS__' => $documentor->help_button('ADMINAUTHORIZATIONS'),
        "__LANG__" => $lang_->getLanguage(),
        "__ADMINLIST_DRAW__" => $admin_list->getList($template_->getTemplate('ADMINLIST'), $selected_admin),
        "__NBADMINS__" => $admin_list->getNbElements(),
        "__FORM_BEGIN_ADMINSEARCH__" => $sform->open().$sform->hidden('selected', '').$sform->hidden('admin','').$sform->hidden('page', $admin_list->getPage()),
        "__FORM_CLOSE_ADMINSEARCH__" => $sform->close(),
        "__TOTAL_PAGES__" => $admin_list->getNumberOfPages(),
        "__ACTUAL_PAGE__" => $admin_list->getPage(),
        "__PAGE_SEP__" => $admin_list->getPageSeparator($sep),
        "__PREVIOUS_PAGE__" => $admin_list->getPreviousPageLink(),
        "__NEXT_PAGE__" => $admin_list->getNextPageLink(),
        "__PAGE_JS__" => $admin_list->getJavaScript(),   
        "__REMOVE_FULLLINK__" => $_SERVER['PHP_SELF']."?m=d&a=",
        "__LINK_ADDADMIN__" => $_SERVER['PHP_SELF']."?m=a",
        "__FORM_BEGIN_ADMINEDIT__" => $aform->open().$aform->hidden('selected', $selected_admin),
        "__FORM_CLOSE_ADMINEDIT__" => $aform->close(),
        "__ADMIN__" => getAdminName($admin, $aform),
        "__FORM_INPUTPASSWORD__" => $aform->password('password', 15, '******'),
        "__FORM_INPUTCONFIRM__" => $aform->password('confirm', 15, '######'),
        "__FORM_INPUTCANMANAGEUSERS__" => $aform->checkbox('can_manage_users', 1, $admin->getPref('can_manage_users'), '', 1),
        "__FORM_INPUTCANMANAGEDOMAINS__" => $aform->checkbox('can_manage_domains', 1, $admin->getPref('can_manage_domains'), '', 1),
        "__FORM_INPUTDOMAINS__" => $aform->input('domains', 25, $admin->getPref('domains')),
        "__FORM_INPUTCONFIGURE__" =>  $aform->checkbox('can_configure', 1, $admin->getPref('can_configure'), '', 1),
        "__FORM_INPUTCANVIEWSTATS__"  =>  $aform->checkbox('can_view_stats', 1, $admin->getPref('can_view_stats'), '', 1),
        "__SAVE_STATUS__" => $asaved_msg,
        "__ADD_STATUS__" => $addsaved_msg,
        "__DELETE_STATUS__" => $deleted_msg
);

// output page
$template_->output($replace);

/**
 * return the admin name field
 * @param  $admin  Administrator  administrator object
 * @param  $form   Form           edition form
 * @return         string         name or name field
 */
function getAdminName($admin, $form) {

  if ($admin->isNew()) {
    return $form->input('username', 20, $admin->getPref('username'));
  }
  return $admin->getPref('username'); 
}
?>