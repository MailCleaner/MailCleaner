<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the user management controller page
 */
 
/**
 * requires admin session, and user stuff
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Documentor.php");
require_once("view/Form.php");
require_once("user/User.php");
require_once("domain/Domain.php");
require_once("user/UserList.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

/**
 * this flag enable remote search whith ldap/sql connector
 */  
$remote_search_ = true;

// check authorizations
$admin_->checkPermissions(array('can_manage_users'));

// default values
$search_username = "";
$search_domain = "";
$saved_msg = "";

// create user edition formulat
$uform = new Form('user', 'post', $_SERVER['PHP_SELF']);
$uposted = $uform->getResult();
// save edited user settings
if ($uform->shouldSave()) {
  $search_username = $uposted['searchusername'];
  $selected_username = $uposted['username'];
  $search_domain = $uposted['domain'];
  // create and load user with domain
  $search_user = new User();
  $search_user->setDomain($search_domain);
  $d = new Domain();
  $d->load($search_domain);
  $login = $d->getFormatedLogin($selected_username);
  $search_user->load($login);
  foreach( $uposted as $key => $val) {
    $search_user->setPref($key, $val);
  }
  if (isset($uposted['newaddress']) && ($uposted['newaddress'] != "")) {
    $search_user->addAddress($uposted['newaddress']);
  }
  if ($uposted['m'] == 'da') {
    $search_user->removeAddress($uposted['addresses']);
  } elseif ($uposted['m'] == 'sm') {
    $search_user->setMainAddress($uposted['addresses']);
  } elseif ($uposted['m'] == 'du') {
    $saved = $search_user->delete();
    $search_user->load($login);
  }

  if ($uposted['m'] != 'du') {
    $saved = $search_user->save();
  }
  if ($saved == "OKSAVED" || $saved == "OKADDED") {
    $saved_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } elseif ($saved == "OKDELETED") {
    $saved_msg = $lang_->print_txt('DELETESUCCESSFULL');
  } else {
    $saved_msg = $lang_->print_txt('SAVEERROR')." (".$saved.")";
  }
  $search_user->load($login);
} 

// create search/filter formular
$sform = new Form('search', 'post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();
if (isset($sposted['username']) && isset($sposted['domain'])&& !isset($search_user)) {
  $search_username = $sposted['username'];
  $selected_username = $search_username;
  $search_domain = $sposted['domain'];

  if (isset($sposted['selected']) && $sposted['selected'] != "") {
    $selected_username = $sposted['selected'];
  }
  $search_user = new User();
  $search_user->setDomain($search_domain);
  $d = new Domain();
  $d->load($search_domain);
  $login = $d->getFormatedLogin($selected_username);
  $search_user->load($login);
}

// create and load user list
$user_list = new UserList();
$user_list->setForm($sform->getName());
$user_list->search($search_username, $search_domain, $remote_search_);

// create view  
$template_ = new Template('users.tmpl');
$documentor = new Documentor();
// get template defaults
$sep = $template_->getDefaultValue('sep');
if (isset($search_user) && $search_user->getPref('username') != "") { 
  $template_->setCondition('USERSELECTED', true);    
}
if (isset($search_user) && $search_user->canModifyAddressList()) { 
  $template_->setCondition('CANMODIFYADDRESSLIST', true);
}
if (isset($search_user) && $search_user->isLocalUser()) { 
  $template_->setCondition('LOCALUSER', true);
}

// if no user selected  
if (!isset($search_user)) {
  $search_user = new User();
}

// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__DOC_USERLISTTITLE__" => $documentor->help_button('USERLISTTITLE'),
        "__DOC_USERSETTINGS__" => $documentor->help_button('USERSETTINGS'),
        "__ENTEREDJS__" => "",
        "__USERLIST_DRAW__" => $user_list->getList($template_->getTemplate('USERLIST'), $selected_username) ,
        "__SETTINGSTARGETENTERED__" => "javascript:window.document.forms['search'].search_selected.value='$selected_username';window.document.forms['search'].submit();",
        "__FORM_BEGIN_USERSEARCH__" => $sform->open().$sform->hidden('selected', '').$sform->hidden('page', $user_list->getPage()),
        "__FORM_CLOSE_USERSEARCH__" => $sform->close(),
        "__FORM_USEARCHDOMAINPART__" => $sform->select('domain',$sysconf_->getFilteredDomains(), $search_domain,''),
        "__FORM_USEARCHUSERNAME__" => $sform->inputjs('username', 20, $search_username, ''),
        "__LINK_DOSEARCH__" => "javascript:window.document.forms['search'].submit();",
        "__NBUSERS__" => $user_list->getNbElements(),
        "__TOTAL_PAGES__ " => $user_list->getNumberOfPages(),
        "__ACTUAL_PAGE__" => $user_list->getPage(),
        "__PAGE_SEP__" =>  $user_list->getPageSeparator($sep),
        "__PREVIOUS_PAGE__" => $user_list->getPreviousPageLink(),
        "__NEXT_PAGE__" => $user_list->getNextPageLink(),
        "__PAGE_JS__" => $user_list->getJavaScript(),
        "__USER__" => $search_user->getPref('username'),
        "__FORM_BEGIN_USEREDIT__" => $uform->open().$uform->hidden('username', $search_user->getPref('username')).$uform->hidden('domain', $search_domain).$uform->hidden('searchusername', $search_username).$uform->hidden('m', 's'),
        "__FORM_CLOSE_USEREDIT__" => $uform->close(),
        "__SAVE_STATUS__" => $saved_msg,
        "__USER_STATUS__" => userStatus($search_user),
        "__FORM_INPUTLANGUAGE__" => $uform->select('language', $lang_->getLanguages('FULLNAMEASKEY'), $search_user->getPref('language'), ';'),
        "__FORM_INPUTADDRESSES__" => $uform->select('addresses', $search_user->getAddressesForSelect(), $search_user->getMainAddress(), ';'),
        "__FORM_INPUTADDADDRESS__" => $uform->input('newaddress', 30, ''),
        "__FORM_INPUTPASSWORD__" => $uform->password('password', 20, 'notchanged'),
        "__FORM_INPUTREALNAME__" => $uform->input('realname', 20, $search_user->getData('realname')),
        "__LINK_SETASMAIN__" => "javascript:window.document.forms['user'].user_m.value='sm';window.document.forms['user'].submit();",
        "__LINK_REMOVEADDRESS__" => "javascript:window.document.forms['user'].user_m.value='da';window.document.forms['user'].submit();",
        "__RESETUSER_LINK__" => "javascript:window.document.forms['user'].user_m.value='du';window.document.forms['user'].submit();"
);

// output page
$template_->output($replace);

/**
 * return the user status string
 * @param  $user User   user
 * @return       string html string for user status
 */
function userStatus($user) {
  global $lang_;
  
  if(!$user->isRegistered()) {
   return "<br/><font color=\"red\">".$lang_->print_txt('USERHASNOPREFS')."</font><br/>&nbsp;";
  }
}
?>
