<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the email management controller page
 */
 
/**
 * requires admin session, and email stuff
 */
require_once("admin_objects.php");
require_once("system/SystemConfig.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("user/Email.php");
require_once("user/EmailList.php");
require_once("view/Documentor.php");
require_once("config/AntiSpam.php");

/**
 * session globalsy
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
  
// create email object and edition formular
$selected_address = new Email();
$eform = new Form('email', 'post', $_SERVER['PHP_SELF']);
$eposted = $eform->getResult();
// save edited email settings
if ($eform->shouldSave()) {
  $search_Address = $eposted['address'];
  $selected_address = new Email();
  $selected_address->load($search_Address);
  foreach($eposted as $pref => $value) {
    $selected_address->setPref($pref, $value);
  }

  $saved = $selected_address->save();
  if ($saved == 'OKSAVED') {
    $selected_address->load($search_Address);
    $saved_msg = $lang_->print_txt('SAVESUCCESSFULL');
  } elseif ($saved == 'OKADDED') {
    $selected_address->load($search_Address);
    $saved_msg = $lang_->print_txt('ADDEDSUCCESSFULL');
  } else {
    $saved_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
  }
}
// set search parts corresponding of the selected email
if (isset($eposted['search']) && preg_match('/(\S+)\@(\S+)/', $eposted['search'], $matches)) {
  $search_localpart = $matches[1];
  $search_domainpart = $matches[2];
}

// creat search formular
$sform = new Form('search', 'post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();
// set search parameters
if (isset($sposted['localpart']) && isset($sposted['domainpart']) && !isset($search_address)) {
  $search_localpart = $sposted['localpart'];
  $search_domainpart = $sposted['domainpart'];
  $search_Address = $sposted['localpart']."@".$sposted['domainpart'];

  // set and load selected email
  if (isset($sposted['selected']) && $sposted['selected'] != "") {
    $search_Address = urldecode($sposted['selected']);
  }
  $selected_address = new Email();
  $selected_address->load($search_Address);
}
// set the user of this address
if (isset($sposted['user']) && $sposted['user'] != "") {
  $selected_address->setPref('user', $sposted['user']);
}

// delete address settings
if (isset($eposted['m']) && $eposted['m'] == 'd') {
  $deleted = $selected_address->delete();
  if ($deleted == "OKDELETED") {
    $saved_msg = $lang_->print_txt('DELETESUCCESSFULL');
    $tmpe = $selected_address->getPref('address');
    $selected_address = new Email();
    $selected_address->load($tmpe);
    $onedomain = 0;
  } else {
    $saved_msg = $lang_->print_txt('DELETEERROR')."(".$deleted.")";
  }; 
}

// create and load email list
$email_list = new EmailList();
$email_list->setForm($sform->getName());
$email_list->search($search_localpart, $search_domainpart, $remote_search_);

// create view
$template = new Template('emails.tmpl');
$documentor = new Documentor();
if (preg_match('/\S+\@\S+/', $search_Address, $tmp)) {
  $template->setCondition('EMAILSELECTED', true);
}
if ($selected_address->isRegistered()) { 
  $template->setCondition('HASPREF', true);
}
$sep = $template->getDefaultValue('sep');

// set conditions
$antispam_ = new AntiSpam();
$antispam_->load();
$domain = new Domain();
$domain->load($selected_address->getDomain());

if ($antispam_->getPref('enable_whitelists') && !$domain->getPref('enable_whitelists')) {
  $template->setCondition('SEEWHITELISTENABLER', true);
  $template->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_warnlists') && !$domain->getPref('enable_warnlists')) {
  $template->setCondition('SEEWARNLISTENABLER', true);
  $template->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_blacklists') && !$domain->getPref('enable_blacklists')) {
  $template->setCondition('SEEBLACKLISTENABLER', true);
  $template->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_whitelists') && ( $domain->getPref('enable_whitelists') || $selected_address->getPref('has_whitelist'))) {
  $template->setCondition('SEEWHITELISTEDIT', true);
  $template->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_warnlists') && ( $domain->getPref('enable_warnlists') || $selected_address->getPref('has_warnlist'))) {
  $template->setCondition('SEEWARNLISTEDIT', true);
  $template->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_blacklists') && ( $domain->getPref('enable_blacklists') || $selected_address->getPref('has_blacklist'))) {
  $template->setCondition('SEEBLACKLISTEDIT', true);
  $template->setCondition('SEELISTS', true);
}

// prepare options 
$delivery_types = array($lang_->print_txt('TAGMODE') => '1', $lang_->print_txt('QUARANTINEMODE') => '2', $lang_->print_txt('DROPMODE') => '3');
$frequencies = array($lang_->print_txt('DAILY') => 1, $lang_->print_txt('WEEKLY') => 2,  $lang_->print_txt('MONTHLY') => 3, $lang_->print_txt('NONE') => 4);
// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__DOC_EMAILLISTTITLE__" => $documentor->help_button('EMAILLISTTITLE'),
        "__DOC_EMAILSETTINGS__" => $documentor->help_button('EMAILSETTINGS'),
        "__ENTEREDJS__" => "",
        "__EMAILLIST_DRAW__" => $email_list->getList($template->getTemplate('EMAILLIST'), $selected_address->getPref('address')),
        "__LINK_DOSEARCH__" => "javascript:window.document.forms['search'].submit();",
        "__FORM_BEGIN_EMAILSEARCH__" => $sform->open().$sform->hidden('selected', '').$sform->hidden('user','').$sform->hidden('page', $email_list->getPage()),
        "__FORM_CLOSE_EMAILSEARCH__" => $sform->close(),
        "__FORM_ESEARCHLOCALPART__" => $sform->inputjs('localpart', 20, $search_localpart, ''), //'return setEntered(event);'),
        "__FORM_ESEARCHDOMAINPART__" => $sform->select('domainpart',$sysconf_->getFilteredDomains(), $search_domainpart,''),
        "__EMAIL__" => $search_Address,
        "__SETTINGSTARGETENTERED__" => "javascript:window.document.forms['search'].submit();",
        "__FORM_BEGIN_EMAILEDIT__" => $eform->open().$eform->hidden('address', $selected_address->getPref('address')).$eform->hidden('m', 's').$eform->hidden('search', $search_localpart."@".$search_domainpart),
        "__RESETEMAIL_LINK__" => "javascript:window.document.forms['email'].email_m.value='d';window.document.forms['email'].submit();",
        "__FORM_CLOSE_EMAILEDIT__" =>$eform->close(),
        "__FORM_INPUTACTIONONSPAM__" => $eform->radioblock('delivery_type', $delivery_types, $selected_address->getPref('delivery_type'), 'v', 'r'),
        "__FORM_INPUTSPAMTAG__" => $eform->input('spam_tag', 10, $selected_address->getPref('spam_tag')),
        "__FORM_INPUTQUARBOUNCES__" => $eform->checkbox('quarantine_bounces', 1, $selected_address->getPref('quarantine_bounces'), '', 1),
        "__FORM_INPUTSUMFREQUENCY__" => $eform->checkbox('daily_summary', 1, $selected_address->getPref('daily_summary'), '', 1).$lang_->print_txt('DAILY')."&nbsp;&nbsp;".$eform->checkbox('weekly_summary', 1, $selected_address->getPref('weekly_summary'), '', 1).$lang_->print_txt('WEEKLY')."&nbsp;&nbsp;".$eform->checkbox('monthly_summary', 1, $selected_address->getPref('monthly_summary'), '', 1).$lang_->print_txt('MONTHLY'),
        "__FORM_INPUTLANGUAGE__" => $eform->select('language', $lang_->getLanguages('FULLNAMEASKEY'), $selected_address->getPref('language'), ';'),
        "__FORM_INPUTENABLEWHITE__" => $eform->checkbox('has_whitelist', 1, $selected_address->getPref('has_whitelist'), whitelistWarning(), 1),
        "__FORM_INPUTENABLEWARN__" => $eform->checkbox('has_warnlist', 1, $selected_address->getPref('has_warnlist'), '', 1),
	"__FORM_INPUTENABLEBLACK__" => $eform->checkbox('has_blacklist', 1, $selected_address->getPref('has_blacklist'), '', 1),
        "__BELONGTOUSERLINK__" => userBoundTo($selected_address),
        "__ACCESTOSPAMQUARANTINE_LINK__" => "global_spam_quarantine.php?a=".$selected_address->getPref('address'),
        "__ACCESTOCONTENTQUARANTINE_LINK__" => "global_content_quarantine.php?a=".$selected_address->getPref('address'),
        "__SAVE_STATUS__" =>  $saved_msg,
        "__EMAIL_STATUS__" => emailStatus($selected_address),
        "__NBEMAILS__" => $email_list->getNbElements(),
        "__TOTAL_PAGES__ " => $email_list->getNumberOfPages(),
        "__ACTUAL_PAGE__" => $email_list->getPage(),
        "__PAGE_SEP__" => $email_list->getPageSeparator($sep),
        "__PREVIOUS_PAGE__" => $email_list->getPreviousPageLink(),
        "__NEXT_PAGE__" => $email_list->getNextPageLink(),
        "__PAGE_JS__" => $email_list->getJavaScript(),
        "__LINK_EDITWHITELIST__" => "wwlist.php?t=1&a=".$selected_address->getPref('address'),
        "__LINK_EDITWARNLIST__" => "wwlist.php?t=2&a=".$selected_address->getPref('address'),
        "__LINK_EDITBLACKLIST__" => "wwlist.php?t=3&a=".$selected_address->getPref('address')
);

// output page
$template->output($replace);

/**
 * return the user that possess this address
 * @param $email  Email   selected address
 * @return        string  html string displaying the user of the address
 */
function userBoundTo($email) {
  global $lang_;
  if (!isset($email)) {
    return "";
  }
  if ($email->getPref('user') != "") {
    return User::getUsernameFromID($email->getPref('user'));
  }
  return "<font color=\"red\">".$lang_->print_txt('EMAILNOTASSIGNED')."</font>";
}

/**
 * return the settings status of the email
 * @param $email  Email  selected address
 * @return        string html string of the email preferences status
 */
function emailStatus($email) {
  global $lang_;
  if (!$email->isRegistered()) {
    return "<br/><font color=\"red\">".$lang_->print_txt('EMAILHASNOPREFS')."</font><br/>&nbsp;";
  }
}

function whitelistWarning() {
    global $eform;
    global $lang_;
    $js = " if (window.document.forms['".$eform->getName()."'].email_has_whitelist.value=='1') {" .
            " alert ('".$lang_->print_txt('WHITELISTWARNING')."'); }";
    return $js;
}
?>
