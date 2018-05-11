<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2015-2017 Mentor Reka <reka.mentor@gmail.com>
 *		  2015-2017 Florian Billebault <florian.billebault@gmail.com>
 * This is the controller for the quarantine page
 */
 
/**
 * require valid session and quarantine objects
 */
require_once("objects.php"); 
require_once("user/SpamQuarantine.php");
require_once("view/Template.php");
require_once("view/Form.php");

global $sysconf_;
global $lang_;

// some defaults
$nb_messages = 20;

// create view
$template_ = new Template('quarantine.tmpl');
$sep = $template_->getDefaultValue('sep');
$asc_img = $template_->getDefaultValue('ASC_IMG');
$desc_img = $template_->getDefaultValue('DESC_IMG');
$template = $template_->getTemplate('QUARANTINE');
$crit_template = $template_->getTemplate('CRITERIAS');
$crit_sep = $template_->getDefaultValue('CRITERIAS_SEP');

// get posted values
$form = new Form('filter', 'post', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

// create and load quarantine
$quarantine = new SpamQuarantine();
$quarantine->setSearchAddress($user_->getMainAddress());
if ($user_->getPref('gui_default_address') != "" && $user_->hasAddress($user_->getPref('gui_default_address'))) {
  $quarantine->setSearchAddress($user_->getPref('gui_default_address'));
}
if (isset($_SESSION['requestedaddress']) && $user_->hasAddress($_SESSION['requestedaddress'])) {
  $quarantine->setSearchAddress($_SESSION['requestedaddress']);
  unset($_SESSION['requestedaddress']);
}
$quarantine->setFilter('msg_per_page', $user_->getPref('gui_displayed_spams'));
$quarantine->setFilter('days', $user_->getPref('gui_displayed_days'));
if (is_numeric($user_->getTmpPref('gui_displayed_days'))) {
	$quarantine->setFilter('days', $user_->getTmpPref('gui_displayed_days'));
}
$quarantine->setFilter('mask_forced', $user_->getPref('gui_mask_forced'));
$quarantine->setFilter('group_quarantines', $user_->getPref('gui_group_quarantines'));
$quarantine->setFilter('addresses', $user_->getAddresses());
$quarantine->setSettings($posted);
  $template_->setCondition('filtered', 0);
if ($quarantine->isFiltered()) {
  $template_->setCondition('filtered', 1);
}
$template_->setCondition('group_quarantines', 0);
if ($quarantine->getFilter('group_quarantines')) {
  $template_->setCondition('group_quarantines', 1);
}
$quarantine->load();

//create stat graphs
$pie_id=uniqid();
require_once('view/graphics/Pie.php');
$pie_stats = new Pie();
$pie_stats->setFilename('/stats/'.$pie_id.".png");
$pie_stats->setSize(100, 50);

$pie_stats->addValue($quarantine->getStat('spams'), 'spams', array(0x66, 0x33, 0xFF));
$pie_stats->addValue($quarantine->getStat('virus')+$quarantine->getStat('content'), 'dangerous', array(0xEB, 0x97, 0x48));
$pie_stats->addValue($quarantine->getStat('clean'), 'clean', array(0x54, 0xEB, 0x48));

$pie_stats->generate();

// prepare replacements   
$nb_msgs_choice = array('2' => 2, '5' => 5, '10' => 10, '20' => 20, '50' => 50, '100' => 100);
$user_addresses_ = $user_->getAddressesForSelect();
$get_query = http_build_query(array('a' => $quarantine->getSearchAddress(), 'days' => $quarantine->getFilter('days'), 'mask_forced' => $quarantine->getFilter('mask_forced')));

$displayed_infos = $lang_->print_txt_mparam('DISPLAYEDINFOS', array($quarantine->getFilter('days'), 'configuration.php?t=quar'));
if (!isset($address)) {
    $address = '';
}

// UserInfoBox
require_once ('helpers/DataManager.php');
$file_conf = DataManager :: getFileConfig($sysconf_ :: $CONFIGFILE_);

$is_enterprise = $file_conf['REGISTERED'] == '1';
$content='';
$user_pref_lang=$lang_->getLanguage();
$default_filename='mc-info-box-user-en.php';
$filename='mc-info-box-user-'.$user_pref_lang.'.php';

if ($is_enterprise) {
        // MailCleaner Staff CONTENT
        $mcmanager='http://mcmanager.mailcleaner.net/';
        $url_to_get=$mcmanager.$filename;

        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url_to_get,
                CURLOPT_FAILONERROR => true
        ));
        $result = curl_exec($curl);
        if ($result === false) {
                curl_close($curl);
                // The mc-info-box-user-<lang> file doesn't exists
                // We try to get the default file (in en)
                $url_to_get=$mcmanager.$default_filename;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => $url_to_get,
                        CURLOPT_FAILONERROR => true
                ));
                $result2 = curl_exec($curl);
                curl_close($curl);
                if ($result2 === false) {
                        $content="<h4>No text or no access to remote server MailCleaner server. Please inform your administrator.</h4>";
                } else {
                        if (isset($result2)) {
                                $content = $result2;
                        }
                }
        }
        else {
                if (isset($result))
                        $content = $result;
        }
}

// Customer CONTENT
// Get the infobox file according to the user language.
// If there is no file for the language, we take the default language: en
// Finally, we merge the MailCleaner Staff content with the Customer content if exists
$local_directory='/var/tmp/';
$file_to_get=$local_directory.$filename;
$default_file_to_get=$local_directory.$default_filename;
if (file_exists($file_to_get)) {
	$tmp=file_get_contents($file_to_get);
	if (isset($tmp))
		$content = $content.$tmp;
} else if (file_exists($default_file_to_get)) {
	$tmp=file_get_contents(default_file_to_get);
	if (isset($tmp))
		$content = $content.$tmp;
} else {
	// No file found on local
}
$msgtodisplay = isset($content) && !empty($content) ? true : false;
$template_->setCondition('MSGTODISPLAY', $msgtodisplay);

$replace = array(
    '__MSGTODISPLAY__' => $msgtodisplay,
    '__INFOBOX_USER__' => $content,
    '__INCLUDE_JS_SCRIPTS__' => $quarantine->getJavascripts($form->getName()),
	'__LANGP_QUARANTINETITLE__' => $lang_->print_txt_param('QUARANTINETITLE', $quarantine->getSearchAddress()),
    '__BEGIN_FILTER_FORM__' => $form->open().$form->hidden('a', $address).$form->hidden('page', $quarantine->getFilter('page')).$form->hidden('order', $quarantine->getOrderString()),
    '__END_FILTER_FORM__' => $form->close(),//	
	'__SEND_SUMMARY_LINK__' => "summary.php?".$get_query,
	'__PURGE_LINK__' => "purge.php?".$get_query,
    '__REFRESH_BUTTON__' => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),
    '__SEARCH_BUTTON__' => $form->submit('submit', $lang_->print_txt('SEARCH'), ''),
    '__REFRESH_LINK__' => "javascript:window.document.forms['".$form->getName()."'].submit();",
    '__EMAIL_ADDRESS__' => addslashes($quarantine->getSearchAddress()),
    '__ADDRESS_SELECTOR__' => $form->select('a', $user_addresses_, $quarantine->getSearchAddress(), "javascript:page(1)", $quarantine->getFilter('group_quarantines')),
    '__LAST_NBDAYS__' => $lang_->print_txt_param('FORTHEXLASTDAYS', $form->input('days', 3, $quarantine->getFilter('days'))),
    '__NB_DAYS__' => $quarantine->getFilter('days'),
    '__MASK_FORCED__' => $quarantine->getFilter('mask_forced'),
	'__TOTAL_SPAMS__' =>  $lang_->print_txt_param('TOTALSPAMS', $quarantine->getNBSpams()),
	'__QUARANTINE_LIST__' => $quarantine->getHTMLList($template_),
    '__MASK_FORCED_BOX__' => $form->checkbox('mask_forced', '1', $quarantine->getFilter('mask_forced'), '', 1),
    '__NBMSGAS_SELECT__' => $form->select('msg_per_page', $nb_msgs_choice, $quarantine->getFilter('msg_per_page'), ';'),
    '__SEARCHFROM_FIELD__' => $form->input('from', 18, $quarantine->getFilter('from')),
    '__SEARCHSUBJECT_FIELD__' => $form->input('subject', 18, $quarantine->getFilter('subject')),
	'__ACTUAL_PAGE__' => $quarantine->getFilter('page'),
    '__CURRENT_PAGE__' => $lang_->print_txt_mparam('CURRENTPAGE', array($quarantine->getFilter('page'), $quarantine->getNBPages())),
	'__TOTAL_PAGES__' => $quarantine->getNBPages(),
	'__PREVIOUS_PAGE__' => $quarantine->getPreviousPageLink(),
	'__PAGE_SEP__' => $quarantine->getPagesSeparator($sep),
	'__NEXT_PAGE__' => $quarantine->getNextPageLink(),
    '__PAGES__' => $quarantine->getPagesLinks(10),
    '__PURGEINFOS__' => $lang_->print_txt_param('PURGEINFOS', $sysconf_->getPref('days_to_keep_spams')),
    '__DISPLAYEDINFOS__' => $displayed_infos,
    '__ASCDESC_DATE_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'date'),
    '__ASCDESC_SCORE_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'globalscore'),
    '__ASCDESC_TO_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'tolocal'),
    '__ASCDESC_FROM_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'from'),
    '__ASCDESC_SUBJECT_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'subject'),
    '__ASCDESC_FORCED_IMG__' => $quarantine->getOrderImage($asc_img, $desc_img, 'forced'),
    '__LINK_ORDERDATE__' => $quarantine->getOrderLink('date'),
    '__LINK_ORDERSCORE__' => $quarantine->getOrderLink('globalscore'),
    '__LINK_ORDERTO__' => $quarantine->getOrderLink('tolocal'),
    '__LINK_ORDERFROM__' => $quarantine->getOrderLink('from'),
    '__LINK_ORDERSUBJECT__'=> $quarantine->getOrderLink('subject'),
    '__LINK_ORDERFORCED__' => $quarantine->getOrderLink('forced'),
    '__ORDER_FIELD__' => $quarantine->getOrderName(),
    '__STATS_MSGS__' => $lang_->print_txt_param('USERMESGSSTAT', $quarantine->getStat('msgs')),
    '__STATS_CLEAN__' => $lang_->print_txt_param('USERCLEANSTAT', $quarantine->getStat('clean'))." (".sprintf("%.2f",$quarantine->getStat('pclean'))."%)",
    '__STATS_SPAMS__' => $lang_->print_txt_param('USERSPAMSSTAT', $quarantine->getStat('spams'))." (".sprintf("%.2f",$quarantine->getStat('pspams'))."%)",
    '__STATS_DANGEROUS__' => $lang_->print_txt_param('USERSDANGEROUSSTAT', $quarantine->getStat('content')+$quarantine->getStat('virus'))." (".sprintf("%.2f",$quarantine->getStat('pvirus')+$quarantine->getStat('pcontent'))."%)",
    '__STATS_PIE__' => "/stats/$pie_id.png",
    "__PRINT_USERNAME__" => $user_->getName(),
    "__LINK_LOGOUT__" => '/logout.php',
    "__LINK_THISPAGE__" => $_SERVER['PHP_SELF'],
    "__QUARANTINE_SUMMARY__" => $lang_->print_txt_param('QUARANTINESUMMARY', $quarantine->getNBSpams())." (".$lang_->print_txt_param('ORDEREDBYPARAM', $lang_->print_txt($quarantine->getOrderTag())).")",
    "__SEARCH_SUMMARY__" => $lang_->print_txt_param('SEARCHSUMMARY', $quarantine->getNBSpams())." (".$lang_->print_txt_param('ORDEREDBYPARAM', $lang_->print_txt($quarantine->getOrderTag())).")",
    "__CRITERIA_SUMMARY__" => $quarantine->getHTMLCriterias($crit_template, $crit_sep),
    "__GROUPQUARANTINES__" => $form->checkbox('group_quarantines', '1', $quarantine->getFilter('group_quarantines'), 'javascript=groupAddresses();', 1),
);

// display page
$template_->output($replace);
?>
