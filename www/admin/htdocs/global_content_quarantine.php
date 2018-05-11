<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the global content quarantine controller
 */
 
/**
 * requires admin session, and quarantine stuff
 */
require_once("admin_objects.php"); 
require_once("user/ContentQuarantine.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("system/SystemConfig.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_manage_users'));

// set defaults
$nb_messages = 20;

// create search/filter form
$form = new Form('filter', 'post', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

// create and load quarantine
$quarantine = new ContentQuarantine();
$quarantine->setSettings($posted);
$matches = array();
if (isset($_GET['a'])) {
  $quarantine->setSearchAddress($_GET['a']);
}
$quarantine->load();

// create view
$template_ = new Template('global_content_quarantine.tmpl');
$documentor = new Documentor();
// get defaults
$sep = $template_->getDefaultValue('sep');
$nb_messages = $template_->getDefaultValue('msgs_per_page');
$images['ASC']  = $template_->getDefaultValue('ASC');
$images['DESC']  = $template_->getDefaultValue('DESC');
$images['VIRUS'] = $template_->getDefaultValue('VIRUS_IMG');
$images['NAME'] = $template_->getDefaultValue('NAME_IMG');
$images['OTHER'] = $template_->getDefaultValue('OTHER_IMG');
$images['SPAM'] = $template_->getDefaultValue('SPAM_IMG');

$quarantine->setImages($images);

// prepare options
$nb_msgs_choice = array('2' => 2, '5' => 5, '10' => 10, '20' => 20, '50' => 50, '100' => 100);
    
// prepare replacements
$replace = array(
        '__DOC_CONTENTFILTERTITLE__' => $documentor->help_button('CONTENTFILTERTITLE'),
        '__LANG__' => $lang_->getLanguage(),
        '__PAGE_JS__' => $quarantine->getJavascripts($form->getName()),
        '__BEGIN_FILTER_FORM__' => $form->open().$form->hidden('a', $address).$form->hidden('page', $quarantine->getFilter('page')).$form->hidden('order', $quarantine->getOrderString()),
        '__END_FILTER_FORM__' => $form->close(),    
        '__SEARCHID_MSG__' => $lang_->print_txt($error_msg),
        '__LINK_DOIDSEARCH__' => "javascript:window.document.forms['".$form->getName()."'].submit();",    
        '__LAST_NBDAYS__' => $lang_->print_txt_param('FORTHEXLASTDAYS', $form->input('days', 3, $quarantine->getFilter('days'))),
        '__NBMSGAS_SELECT__' => $form->select('msg_per_page', $nb_msgs_choice, $quarantine->getFilter('msg_per_page'), ';'),
        '__SEARCHFROM_FIELD__' => $form->input('from', 20, $quarantine->getFilter('from')),
        '__SEARCHTOLOCAL_FIELD__' => $form->input('to_local', 20, $quarantine->getFilter('to_local')),
        '__SEARCHTODOMAIN_FIELD__' => $form->select('to_domain', $sysconf_->getFilteredDomains(), $quarantine->getFilter('to_domain'), ''),
        '__SEARCHSUBJECT_FIELD__' => $form->input('subject', 40, $quarantine->getFilter('subject')),
        '__REFRESH_BUTTON__' => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),   
        '__SEARCHCONTENTID_FIELD__' => $form->input('searchid', 25, $quarantine->getFilter('searchid')),
        '__SEARCHHOST_FIELD__' => $form->select('slave', $sysconf_->getSlavesName(), $quarantine->getFilter('slave'), ''),	
        '__REFRESH_BUTTON__' => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),
        '__QUARANTINE_LIST__' => $quarantine->getHTMLList($template_->getTemplate('QUARANTINE')),
        '__TOTAL_CONTENTS__' => $lang_->print_txt_param('TOTALMESSAGES', $quarantine->getNbElements()),
        '__ACTUAL_PAGE__' => $quarantine->getFilter('page'),
        '__PAGE_SEP__' => $quarantine->getPagesSeparator($sep),
        '__PREVIOUS_PAGE__' => $quarantine->getPreviousPageLink(),
        '__NEXT_PAGE__' => $quarantine->getNextPageLink(),
        '__TOTAL_PAGES__' => $quarantine->getNBPages(),
        '__ASCDESC_DATE_IMG__' => $quarantine->getOrderImage($images['ASC'], $images['DESC'], 'date'),
        '__ASCDESC_SCORE_IMG__' => $quarantine->getOrderImage($images['ASC'], $images['DESC'], 'score'),
        '__ASCDESC_TO_IMG__' => $quarantine->getOrderImage($images['ASC'], $images['DESC'], 'tolocal'),
        '__ASCDESC_FROM_IMG__' => $quarantine->getOrderImage($images['ASC'], $images['DESC'], 'from'),
        '__ASCDESC_SUBJECT_IMG__' => $quarantine->getOrderImage($images['ASC'], $images['DESC'], 'subject'),
        '__LINK_ORDERDATE__' => $quarantine->getOrderLink('date'),
        '__LINK_ORDERSCORE__' => $quarantine->getOrderLink('score'),
        '__LINK_ORDERTO__' => $quarantine->getOrderLink('tolocal'),
        '__LINK_ORDERFROM__' => $quarantine->getOrderLink('from'),
        '__LINK_ORDERSUBJECT__'=> $quarantine->getOrderLink('subject'),
        '__LINK_EMAILPREFS__' => 'emails.php?'.http_build_query(array('localpart' => $quarantine->getFilter('to_local'), 'domainpart' => $quarantine->getFilter('to_domain')))  
);

// output page
$template_->output($replace);
?>