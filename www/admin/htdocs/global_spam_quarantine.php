<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the global spam quarantine controller
 */
 
/**
 * requires admin session, and quarantine stuff
 */
require_once("admin_objects.php"); 
require_once("user/SpamQuarantine.php");
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
$quarantine = new SpamQuarantine();
$quarantine->setSettings($posted);
$matches = array();
if (isset($_GET['a'])) {
  $quarantine->setSearchAddress($_GET['a']);
}
$quarantine->load();

// create view
$template_ = new Template('global_spam_quarantine.tmpl');
$documentor = new Documentor();
// get defaults
$sep = $template_->getDefaultValue('sep');
$nb_messages = $template_->getDefaultValue('msgs_per_page');
$asc_img = $template_->getDefaultValue('ASC_IMG');
$desc_img = $template_->getDefaultValue('DESC_IMG');

// prepare options
$nb_msgs_choice = array('2' => 2, '5' => 5, '10' => 10, '20' => 20, '50' => 50, '100' => 100);
// prepare replacements
$replace = array(
        '__DOC_SPAMFILTERTITLE__' => $documentor->help_button('SPAMFILTERTITLE'),
        '__LANG__' => $lang_->getLanguage(),
        '__PAGE_JS__' => $quarantine->getJavascripts($form->getName()),
        '__BEGIN_FILTER_FORM__' => $form->open().$form->hidden('a', $address).$form->hidden('page', $quarantine->getFilter('page')).$form->hidden('order', $quarantine->getOrderString()),
        '__END_FILTER_FORM__' => $form->close(),
        '__LAST_NBDAYS__' => $lang_->print_txt_param('FORTHEXLASTDAYS', $form->input('days', 3, $quarantine->getFilter('days'))),
        '__MASK_FORCED_BOX__' => $form->checkbox('mask_forced', '1', $quarantine->getFilter('mask_forced'), '', 1),
        '__MASK_BOUNCES_BOX__' => $form->checkbox('mask_bounces', '1', $quarantine->getFilter('mask_bounces'), '', 1),
        '__NBMSGAS_SELECT__' => $form->select('msg_per_page', $nb_msgs_choice, $quarantine->getFilter('msg_per_page'), ';'),
        '__SEARCHFROM_FIELD__' => $form->input('from', 20, $quarantine->getFilter('from')),
        '__SEARCHTOLOCAL_FIELD__' => $form->input('to_local', 20, $quarantine->getFilter('to_local')),
        '__SEARCHTODOMAIN_FIELD__' => $form->select('to_domain', $sysconf_->getFilteredDomains(), $quarantine->getFilter('to_domain'), ''),
        '__SEARCHSUBJECT_FIELD__' => $form->input('subject', 40, $quarantine->getFilter('subject')),
        '__REFRESH_BUTTON__' => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),
        '__QUARANTINE_LIST__' => $quarantine->getHTMLList($template_),
        '__TOTAL_SPAMS__' => $lang_->print_txt_param('TOTALSPAMS', $quarantine->getNbSpams()),
        '__ACTUAL_PAGE__' => $quarantine->getFilter('page'),
        '__PAGE_SEP__' => $quarantine->getPagesSeparator($sep),
        '__PREVIOUS_PAGE__' => $quarantine->getPreviousPageLink(),
        '__NEXT_PAGE__' => $quarantine->getNextPageLink(),
        '__TOTAL_PAGES__' => $quarantine->getNBPages(),
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
        '__LINK_EMAILPREFS__' => 'emails.php?'.http_build_query(array('localpart' => $quarantine->getFilter('to_local'), 'domainpart' => $quarantine->getFilter('to_domain')))
);

//output page
$template_->output($replace);
?>