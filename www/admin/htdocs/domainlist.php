<? 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the domain edition controller. Display the domain list, allow edition, deletion and so...
 */
 
/**
 * requires admin session, and mainly Domain stuff
 */
require_once("admin_objects.php");
require_once("domain/DomainList.php");
require_once("domain/Domain.php");
require_once("view/Documentor.php");
require_once("view/Form.php");
require_once("view/Template.php");
require_once("connector/AuthManager.php");
require_once("connector/AddressFetcher.php");
require_once("config/AntiSpam.php");

/**
 * session globals
 */
global $admin_;
global $sysconf_;
global $lang_;

// check authorizations
$admin_->checkPermissions(array('can_manage_domains'));

$saved_msg = "";
$added_msg = "";
$deleted_msg = "";
$domainname = "";
$onedomain=0;
$batchadd = false;

// instantiate the main form and get results if any
$dform_ = new Form('domain', 'post', $_SERVER['PHP_SELF']);
$posted = $dform_->getResult();

$sform = new Form('search', 'post', $_SERVER['PHP_SELF']);
$sposted = $sform->getResult();

$selected_domain = new Domain();

// if domainname passed by url
if (isset($_GET['d'])) {
  $domainname = $_GET['d'];
}
// domain selected in search list
if (isset($sposted['selected']) && $sposted['selected'] != "") {
  $domainname = $sposted['selected'];
}
// domain saved, added ?
if ( isset($posted['name']) ) {
	$domainname = $posted['name'];
}

// want multiple domain add ?
if (isset($_GET['ba']) || ( isset($posted['ba']) && $posted['ba']) == '1') {
  $batchadd = true;
}

$domains = preg_split("/[\n\r\s\,]/", $domainname);
foreach ($domains as $domain) {
    if (preg_match("/^\s*$/", $domain) && ( !isset($posted['save_on_submit']) || $dform_->shouldSave())) { continue; };
    
    if (! $admin_->canManageDomain($domain)) {
        continue;
    }
    $onedomain = 1;
    
    unset($selected_domain);
    $selected_domain = new Domain();
    $selected_domain->load($domain);
    
    // check if we have to delete the domain
    if (isset($_GET['m']) && $_GET['m'] == 'd') {
      $deleted = $selected_domain->delete();
      if ($deleted == "OKDELETED") {
        $deleted_msg = $lang_->print_txt('DELETESUCCESSFULL');
        unset($selected_domain);
        $selected_domain = new Domain();
        $onedomain = 0;
      } else {
        $deleted_msg = $lang_->print_txt('DELETEERROR')."(".$deleted.")";    
      };
      continue;
    }
  
    // set preference posted
    foreach ($posted as $key => $value) {
        // if we have to save, replace some variables
        if ($dform_->shouldSave()) {
        	$value = preg_replace('/\_\_DOMAIN\_\_/', $domain, $value);
        }
    	$selected_domain->setPref($key, $value);
    }
    $selected_domain->setPref('d', $domain);
    if ($domain != '0') {
      $selected_domain->setPref('name', $domain);
    }
    if (!$dform_->shouldSave() && $batchadd && $domainname != '0') {
    	$selected_domain->setPref('name', $domainname);
    }
      
    // check if we have to save the domain
    if ($dform_->shouldSave()) {
      $saved = $selected_domain->save();
      if ($saved == 'OKSAVED') {
        $selected_domain->reload();
        unset($selected_domain);
        $selected_domain = new Domain();
        $selected_domain->load($domain);
        $saved_msg .= "<br>$domain: ".$lang_->print_txt('SAVESUCCESSFULL');
      } elseif ($saved == 'OKADDED' || $saved == "") {
        $saved_msg .= "<br>$domain: ".$lang_->print_txt('ADDEDSUCCESSFULL');
        unset($selected_domain);
        $selected_domain = new Domain();
        $selected_domain->load($domain);
      } else {
        $saved_msg = $lang_->print_txt('SAVEERROR')."(".$saved.")";
      }
    }
}
$saved_msg = preg_replace('/^<br>/', '', $saved_msg);

$domains_ = new DomainList();
$domains_->setForm($sform->getName());
$domains_->load();


// create view
$template_ = new Template('domainlist.tmpl');
$documentor = new Documentor();

// get templates and default values
$template = $template_->getTemplate('DOMAINLIST');
$per_page = $template_->getDefaultValue('elementPerPage');
if (is_numeric($per_page) && $per_page > 0) {
      $domains_->setNbElementsPerPage($per_page);
}
// and set template conditions
if ($onedomain) {
  $template_->setCondition('DOMAINSELECTED', true);
}
if ($selected_domain->getConnectorSettings() instanceof ConnectorSettings) {
  $template_->setCondition($selected_domain->getConnectorSettings()->getTemplateCondition(), true);
} else {
  $template_->setCondition('LOCALAUTH', true);
}
if ($batchadd) {
  $template_->setCondition('BATCHADD', true);
}
if ($selected_domain->getPref('name') == "" && $batchadd) {
   $template_->setCondition('BATCHADDDOMAIN', true);
}
if ($selected_domain->getPref('name') == "" && !$batchadd) {
   $template_->setCondition('SINGLEADDDOMAIN', true);
}

$antispam_ = new AntiSpam();
$antispam_->load();

if ($antispam_->getPref('enable_whitelists')) {
  $template_->setCondition('SEEWHITELISTENABLER', true);
  $template_->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_warnlists')) {
  $template_->setCondition('SEEWARNLISTENABLER', true);
  $template_->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_blacklists')) {
  $template_->setCondition('SEEBLACKLISTENABLER', true);
  $template_->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_whitelists') && $selected_domain->getPref('enable_whitelists')) {
  $template_->setCondition('SEEWHITELISTEDIT', true);
  $template_->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_warnlists') && $selected_domain->getPref('enable_warnlists') ) {
  $template_->setCondition('SEEWARNLISTEDIT', true);
  $template_->setCondition('SEELISTS', true);
}
if ($antispam_->getPref('enable_blacklists') && $selected_domain->getPref('enable_blacklists') ) {
  $template_->setCondition('SEEBLACKLISTEDIT', true);
  $template_->setCondition('SEELISTS', true);
}

$delivery_types = array($lang_->print_txt('TAGMODE') => '1', $lang_->print_txt('QUARANTINEMODE') => '2', $lang_->print_txt('DROPMODE') => '3');
$frequencies = array($lang_->print_txt('DAILY') => 1, $lang_->print_txt('WEEKLY') => 1,  $lang_->print_txt('MONTHLY') => 1);
$summarytypes = array($lang_->print_txt('SUMMHTML') => 'html', $lang_->print_txt('SUMMTEXT') => 'text');
$sysconf_->getTemplates();

$replace = array(
  "__DOC_DOMAINLISTTITLE__" => $documentor->help_button('DOMAINLISTTITLE'),
  "__DOC_DOMAINNAME__" => $documentor->help_button('DOMAINNAME'),
  "__DOC_DOMAINDELIVERY__" => $documentor->help_button('DOMAINDELIVERY'),
  "__DOC_DOMAINFILTERING__" => $documentor->help_button('DOMAINFILTERING'),
  "__DOC_DOMAINPREFERENCES__" => $documentor->help_button('DOMAINPREFERENCES'),
  "__DOC_USERAUTHENTICATION__" => $documentor->help_button('USERAUTHENTICATION'),
  "__DOC_DOMAINTEMPLATES__" => $documentor->help_button('DOMAINTEMPLATES'), 
  "__DOC_WHITEWARNLIST__" => $documentor->help_button('WHITEWARNLIST'),
  "__DOMAINLIST_DRAW__" => $domains_->getList($template, $selected_domain->getPref('name')),
  "__REMOVE_FULLLINK__" => $_SERVER['PHP_SELF']."?m=d&d=",
  "__FORM_BEGIN_DOMAINEDIT__" => $dform_->open().$dform_->hidden('d', $selected_domain->id_).$dform_->hidden('ba', $batchadd),
  "__FORM_CLOSE_DOMAINEDIT__" => $dform_->close(),
  "__FORM_INPUTDOMAINNAME__" => $dform_->input('name', 20, $selected_domain->getPref('name')),
  "__FORM_INPUTDOMAINNAMES__" => $dform_->textarea('name', 30, 5, $selected_domain->getPref('name')),
  "__FORM_INPUTDESTINATION__" => $dform_->input('destination', 20, $selected_domain->getPref('destination')).":".$dform_->input('destinationport', 4, $selected_domain->getPort()),
  "__FORM_INPUTFORWARDBYMX__" => $dform_->checkbox('forward_by_mx', 'true', $selected_domain->getPref('forward_by_mx'), 'useMX()', 1 ),
  "__FORM_INPUTGREYLIST__" => $dform_->checkbox('greylist', 'true', $selected_domain->getPref('greylist'), '', 1 ),
  "__FORM_INPUTDELIVERYTYPE__" => $dform_->select('delivery_type', $delivery_types, $selected_domain->getPref('delivery_type'), ';'),
  "__FORM_INPUTCALLOUT__" => $dform_->checkbox('callout', 'true', $selected_domain->getPref('callout'), '', 1),
  "__FORM_INPUTALTCALLOUT__" => $dform_->input('altcallout', 15, $selected_domain->getPref('altcallout')),
  "__FORM_INPUTADCALLOUT__" => $dform_->checkbox('adcheck', 'true', $selected_domain->getPref('adcheck'), '', 1),
  "__FORM_INPUTANTIVIRUS__" => $dform_->checkbox('viruswall', '1', $selected_domain->getPref('viruswall'), '', 1),
  "__FORM_INPUTANTIVIRUSTAG__" => $dform_->input('virus_subject', 10, $selected_domain->getPref('virus_subject')),
  "__FORM_INPUTANTISPAM__" => $dform_->checkbox('spamwall', '1', $selected_domain->getPref('spamwall'), '', 1),
  "__FORM_INPUTANTISPAMTAG__" => $dform_->input('spam_tag', 10, $selected_domain->getPref('spam_tag')),
  "__FORM_INPUTCONTENT__" => $dform_->checkbox('contentwall', '1', $selected_domain->getPref('contentwall'), '', 1),
  "__FORM_INPUTCONTENTTAG__" => $dform_->input('content_subject', 10, $selected_domain->getPref('content_subject')),
  "__FORM_INPUTENABLEWHITELIST__" => $dform_->checkbox('enable_whitelists', 1, $selected_domain->getPref('enable_whitelists'), whitelistWarning(), 1),
  "__FORM_INPUTENABLEWARNLIST__" => $dform_->checkbox('enable_warnlists', 1, $selected_domain->getPref('enable_warnlists'), '', 1),
  "__FORM_INPUTENABLEBLACKLIST__" => $dform_->checkbox('enable_blacklists', 1, $selected_domain->getPref('enable_blacklists'), '', 1),
  "__FORM_INPUTENABLEWWNOTICE__" => $dform_->checkbox('notice_wwlists_hit', 1, $selected_domain->getPref('notice_wwlists_hit'), wwHitWarning(), 1),
  "__FORM_INPUTLANGUAGE__" => $dform_->select('language', $lang_->getLanguages('FULLNAMEASKEY'), $selected_domain->getPref('language'), ';'),
  "__FORM_INPUTSUMFREQ__" => $dform_->checkbox('daily_summary', 1, $selected_domain->getPref('daily_summary'), '', 1).$lang_->print_txt('DAILY')."&nbsp;&nbsp;".$dform_->checkbox('weekly_summary', 1, $selected_domain->getPref('weekly_summary'), '', 1).$lang_->print_txt('WEEKLY')."&nbsp;&nbsp;".$dform_->checkbox('monthly_summary', 1, $selected_domain->getPref('monthly_summary'), '', 1).$lang_->print_txt('MONTHLY'),
  "__FORM_INPUTSUMTYPE__" => $dform_->select('summary_type', $summarytypes, $selected_domain->getPref('summary_type'), ';' ),
  "__FORM_SUBMITJS__" => $dform_->submitJS(),
  "__FORM_INPUTCONNECTOR__" => $dform_->select('auth_type', AuthManager::getAvailableConnectors(), $selected_domain->getPref('auth_type'), ''),
  "__FORM_INPUTUSESSL__" => doConnectorField($selected_domain, $dform_, 'conn_usessl').doConnectorField($selected_domain, $dform_, 'conn_usessl'),
  "__FORM_INPUTLDAPUSESSL__" => doConnectorField($selected_domain, $dform_, 'conn_usessl'),
  "__FORM_INPUTIMAPPOPUSESSL__" => doConnectorField($selected_domain, $dform_, 'conn_usessl'),
  "__FORM_INPUTAUTHSERVER__" => doConnectorField($selected_domain, $dform_, 'conn_server'),
  "__FORM_INPUTAUTHPORT__" => doConnectorField($selected_domain, $dform_, 'conn_port'),
  "__FORM_INPUTLDAPBASEDN__" => doConnectorField($selected_domain, $dform_, 'conn_basedn'),
  "__FORM_INPUTLDAPUSERATTR__" => doConnectorField($selected_domain, $dform_, 'conn_useratt'),
  "__FORM_INPUTLDAPBINDDN__" => doConnectorField($selected_domain, $dform_, 'conn_binduser'),
  "__FORM_INPUTLDAPBINDPASS__" => doConnectorField($selected_domain, $dform_, 'conn_bindpassword'),
  "__FORM_INPUTLDAPVERSION__" => doConnectorField($selected_domain, $dform_, 'conn_version'),
  "__FORM_INPUTRADIUSSECRET__" => doConnectorField($selected_domain, $dform_, 'conn_secret'),
  "__FORM_INPUTRADIUSTYPE__" => doConnectorField($selected_domain, $dform_, 'conn_authtype'),
  "__FORM_INPUTSQLUSER__" => doConnectorField($selected_domain, $dform_, 'conn_user'),
  "__FORM_INPUTSQLPASS_" => doConnectorField($selected_domain, $dform_, 'conn_pass'),
  "__FORM_INPUTTEQUILAUSESSL__" => doConnectorField($selected_domain, $dform_, 'conn_usessl'),
  "__FORM_INPUTTEQUILAFIELDS__" => doConnectorField($selected_domain, $dform_, 'conn_fields'),
  "__FORM_INPUTTEQUILAURL__" => doConnectorField($selected_domain, $dform_, 'conn_url'),
  "__FORM_INPUTTEQUILALOGINFIELD__" => doConnectorField($selected_domain, $dform_, 'conn_loginfield'),
  "__FORM_INPUTTEQUILAREALNAME__" => doConnectorField($selected_domain, $dform_, 'conn_realnameformat'),
  "__FORM_INPUTTEQUILALLOWSFILTER__" => doConnectorField($selected_domain, $dform_, 'conn_allowsfilter'),
  "__FORM_INPUTUSERNAMEFORMAT__" => $dform_->select('auth_modif',LoginFormatter::getAvailableFormatters(),$selected_domain->getPref('auth_modif'), ';'),
  "__FORM_INPUTADDRESSFETCHER__" => $dform_->select('address_fetcher',AddressFetcher::getAvailableFetchers(), $selected_domain->getPref('address_fetcher'), ';'),
  "__FORM_INPUTPRESHAREDKEY__" => $dform_->input('presharedkey', 30, $selected_domain->getPref('presharedkey')),
  "__FORM_INPUTSMTPAUTH__" => $dform_->checkbox('allow_smtp_auth', 1, $selected_domain->getPref('allow_smtp_auth'), '', 1),
  "__FORM_INPUTWEBTEMPLATE__" => $dform_->select('web_template', $sysconf_->web_templates_, $selected_domain->getPref('web_template'), ';'),
  "__FORM_INPUTSUMTEMPLATE__" => $dform_->select('summary_template', $sysconf_->summary_templates_, $selected_domain->getPref('summary_template'), ';'),
  "__FORM_INPUTREPORTTEMPLATE__" => $dform_->select('report_template', $sysconf_->report_templates_, $selected_domain->getPref('report_template'), ';'),
  "__FORM_INPUTWARNHITTEMPLATE__" => $dform_->select('warnhit_template', $sysconf_->warnhit_templates_, $selected_domain->getPref('warnhit_template'), ';'),
  "__FORM_INPUTSUPPORTADDRESS__" => $dform_->input('support_email', 20, $selected_domain->getPref('support_email')),
  "__SAVE_STATUS__" => $saved_msg,
  "__DELETE_STATUS__" => $deleted_msg,
  "__ADD_STATUS__" => $added_msg,
  "__LINK_ADDDOMAIN__" => $_SERVER['PHP_SELF']."?d=0",
  "__INCLUDE_USEMX_JS__" => useMXJS(),
  "__FORM_BEGIN_DOMAINSEARCH__" => $sform->open().$sform->hidden('selected', '').$sform->hidden('name','').$sform->hidden('page', $domains_->getPage()),
  "__FORM_CLOSE_DOMAINSEARCH__" => $sform->close(),
  "__NBDOMAINS__" => $domains_->getNbElements(),
  "__TOTAL_PAGES__" => $domains_->getNumberOfPages(),
  "__ACTUAL_PAGE__" => $domains_->getPage(),
  "__PAGE_SEP__" => $domains_->getPageSeparator($sep),
  "__PREVIOUS_PAGE__" => $domains_->getPreviousPageLink(),
  "__NEXT_PAGE__" => $domains_->getNextPageLink(),
  "__PAGE_JS__" => $domains_->getJavaScript(),
  "__LINK_EDITWHITELIST__" => "wwlist.php?t=1&a=@".$selected_domain->getPref('name'),
  "__LINK_EDITWARNLIST__" => "wwlist.php?t=2&a=@".$selected_domain->getPref('name'),
  "__LINK_EDITBLACKLIST__" => "wwlist.php?t=3&a=@".$selected_domain->getPref('name'),
  "__LINK_GOBATCHADD__" => $_SERVER['PHP_SELF']."?d=0&ba",
   
);

$template_->output($replace);


function doConnectorField($d, $f, $t)
{
  if (!isset($d)) { return;}
  
  $settings = $d->getConnectorSettings();
  if (!isset($settings)) { return; }
  
  $matches = array();
  if (!preg_match('/^conn_(\S+)/', $t, $matches)) { return; }
  $type = $settings->getFieldType($matches[1]);
  switch ($type[0]) {
      case "text":
        $ret .= $f->input('conn_'.$matches[1], $type[1], $settings->getSetting($matches[1]));
        break;
      case "password":
        $ret .= $f->password('conn_'.$matches[1], $type[1], $settings->getSetting($matches[1]));
        break;
      case "checkbox":
        $ret .= $f->checkbox('conn_'.$matches[1], $type[1], $settings->getSetting($matches[1]), ';', 1);
        break;
      case "select":
        $ret .= $f->select('conn_'.$matches[1], $type[1], $settings->getSetting($matches[1]), ';');
        break;
      case "hidden":
        $ret .= $f->hidden('conn_'.$matches[1], $settings->getSetting($matches[1]));
        break;
  }
  return $ret;
}

function useMXJS() {
  global $dform_;
  $ret = "";
  $ret = "function useMX() {
            if (window.document.forms['".$dform_->getName()."'].".$dform_->getName()."_forward_by_mx.value == 'false') {
              window.document.forms['".$dform_->getName()."'].".$dform_->getName()."_destination.disabled=false;
	      window.document.forms['".$dform_->getName()."'].".$dform_->getName()."_destinationport.disabled=false;
	    } else {
	      window.document.forms['".$dform_->getName()."'].".$dform_->getName()."_destination.disabled=true;
              window.document.forms['".$dform_->getName()."'].".$dform_->getName()."_destinationport.disabled=true;
	    }
	  }";
  return $ret;
}

function whitelistWarning() {
    global $dform_;
    global $lang_;
    $js = " if (window.document.forms['".$dform_->getName()."'].domain_enable_whitelists.value=='1') {" .
            " alert ('".$lang_->print_txt('WHITELISTWARNING')."'); }";
    return $js;
}

function wwHitWarning() {
    global $dform_;
    global $lang_;
    $js = " if (window.document.forms['".$dform_->getName()."'].domain_notice_wwlists_hit.value=='1') {" .
            " alert ('".$lang_->print_txt('WWHITWARNING')."'); }";
    return $js;
}
?>
