<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the smtp daemons configuration page
 */

/**
 * require admin session, view and smtp dameon configuration handler
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/MTAConfig.php");
require_once("view/Documentor.php");
require_once("config/GreylistConfig.php");

/**
 * session settings
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(['can_configure']);

// get ldap settings
//@todo clean up this ! maybe use a LDAPSettings object
list($ldap_basedn_, $ldap_binduser_, $ldap_bindpass_) = preg_split('/:/', $sysconf_->getPref('ad_param'));

// create and loas incoming smtp daemon configuration
$mta_in = new MTAConfig();
$mta_in->load(1);

$greylistd = new GreylistConfig();
$greylistd->load();

// create access form (incoming)
$aform = new Form('access', 'post', $_SERVER['PHP_SELF']);
$aposted = $aform->getResult();
// save access settings
if ($aform->shouldSave()) {
    foreach ($aposted as $key => $value) {
        $mta_in->setPref($key, $value);
    }
    $accesssaved = $mta_in->save();
    if ($accesssaved == "OKSAVED") {
        $accessmsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $accessmsg = $lang_->print_txt('SAVEERROR') . " (" . $accesssaved . ")";
    }
}

// create ldap callout configuration form
//@todo this will be on a per domain basis, so will be removed from here
$lform = new Form('ldapcallout', 'post', $_SERVER['PHP_SELF']);
$lposted = $lform->getResult();
// save ldap callout setting
if ($lform->shouldSave()) {
    $sysconf_->setPref('ad_server', $lposted['ad_server']);
    $sysconf_->setPref('ad_param', $lposted['ad_basedn'] . ":" . $lposted['ad_binduser'] . ":" . $lposted['ad_bindpass']);
    $ldap_basedn_ = $lposted['ad_basedn'];
    $ldap_binduser_ = $lposted['ad_binduser'];
    $ldap_bindpass_ = $lposted['ad_bindpass'];
    $ldapsaved = $sysconf_->save();
    $_SESSION['restart_manager_']['MTA'] = 1;
    if ($ldapsaved == "OKSAVED") {
        $ldapsavedmsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $ldapsavedmsg = $lang_->print_txt('SAVEERROR') . " (" . $ldapsaved . ")";
    }
}

// create greylistd configuration form
$greyform = new Form('greylistd', 'post', $_SERVER['PHP_SELF']);
$greyposted = $greyform->getResult();

if ($greyform->shouldSave()) {
    foreach ($greyposted as $key => $value) {
        $greylistd->setPref($key, $value);
    }
    $accesssaved = $greylistd->save();
    if ($accesssaved == "OKSAVED") {
        $greysavedmsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $greysavedmsg = $lang_->print_txt('SAVEERROR') . " (" . $accesssaved . ")";
    }
}

// create advanced configuration form
$adform = new Form('advanced', 'post', $_SERVER['PHP_SELF']);
// create and load the selected mta stage
$mta = new MTAConfig();
$selected_stage = 1;
$adposted = $adform->getResult();
if (isset($adposted['stage']) && is_numeric($adposted['stage'])) {
    $selected_stage = $adposted['stage'];
}
$mta->load($selected_stage);

// save mta settings
if ($adform->shouldSave()) {
    foreach ($adposted as $key => $value) {
        $mta->setPref($key, $value);
    }
    $adsaved = $mta->save();
    if ($adsaved == "OKSAVED") {
        $admsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $admsg = $lang_->print_txt('SAVEERROR') . " (" . $adsaved . ")";
    }
}

// create view
$template_ = new Template('config_smtp.tmpl');
$documentor = new Documentor();

// prepare options
$stages = ['pre-filtering (incoming)' => 1, 'post-filtering (outgoing)' => 4];

// prepare replacements
$replace = [
    '__DOC_SMTPACCESS__' => $documentor->help_button('SMTPACCESS'),
    '__DOC_SMTPLDAPCALLOUT__' => $documentor->help_button('SMTPLDAPCALLOUT'),
    "__DOC_GREYLISTCONFIG__" => $documentor->help_button('GREYLISTCONFIG'),
    '__DOC_SMTPADVANCEDCONFIG__' => $documentor->help_button('SMTPADVANCEDCONFIG'),
    '__DOC_SMTP__' => $documentor->help_button('SMTP'),
    "__LANG__" => $lang_->getLanguage(),
    "__FORM_BEGIN_ACCESS__" => $aform->open(),
    "__FORM_CLOSE_ACCESS__" => $aform->close(),
    "__ACCESS_SAVE_STATUS__" => $accessmsg,
    "__FORM_INPUTSMTPRELAY__" => $aform->textarea('relay_from_hosts', 22, 3, preg_replace('/[\s:]+/', "\n", htmlentities($mta_in->getPref('relay_from_hosts')))),
    "__FORM_INPUTHOSTSACCESS__" => $aform->input('smtp_conn_access', 25, htmlentities($mta_in->getPref('smtp_conn_access'))),
    "__FORM_INPUTHOSTREJECT__" => $aform->textarea('host_reject', 22, 3, htmlentities($mta_in->getPref('host_reject'))),
    "__FORM_INPUTSENDERREJECT__" => $aform->textarea('sender_reject', 22, 3, htmlentities($mta_in->getPref('sender_reject'))),
    "__FORM_INPUTVERIFYSENDER__" => $aform->checkbox('verify_sender', '1', htmlentities($mta_in->getPref('verify_sender')), '', 1),
    "__FORM_INPUTENABLETLS__" => $aform->checkbox('use_incoming_tls', '1', htmlentities($mta_in->getPref('use_incoming_tls')), '', 1),
    "__FORM_INPUTTLSCERT__" => $aform->select('tls_certificate', $mta_in->getAvailableCertificates(), $mta_in->getPref('tls_certificate'), ';'),
    "__FORM_INPUTSMTPBANNER__" => $aform->input('smtp_banner', 30, $mta_in->getPref('smtp_banner')),
    "__INPUT_GLOBALMAXMSGSIZE__" => $aform->input('global_msg_max_size', 6, htmlentities($mta_in->getPref('global_msg_max_size'))),
    "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true)",
    "__FORM_BEGIN_LDAPCALLOUT__" => $lform->open(),
    "__FORM_CLOSE_LDAPCALLOUT__" => $lform->close(),
    "__FORM_INPUTLDAPSERVER__" => $lform->input('ad_server', 20, htmlentities($sysconf_->getPref('ad_server'))),
    "__FORM_INPUTBASEDN__" => $lform->input('ad_basedn', 20, htmlentities($ldap_basedn_)),
    "__FORM_INPUTBINDDN__" => $lform->input('ad_binduser', 20, htmlentities($ldap_binduser_)),
    "__FORM_INPUTBINDPASSWORD__" => $lform->password('ad_bindpass', 20, htmlentities($ldap_bindpass_)),
    "__LDAPCALLOUT_SAVE_STATUS__" => $ldapsavedmsg,
    "__FORM_BEGIN_GREYCONFIG__" => $greyform->open(),
    "__FORM_CLOSE_GREYCONFIG__" => $greyform->close(),
    "__FORM_INPUTRETRYMIN__" => $greyform->input('retry_min', 10, htmlentities($greylistd->getPref('retry_min'))),
    "__FORM_INPUTRETRYMAX__" => $greyform->input('retry_max', 10, htmlentities($greylistd->getPref('retry_max'))),
    "__FORM_INPUTEXPIRE__" => $greyform->input('expire', 10, htmlentities($greylistd->getPref('expire'))),
    "__FORM_INPUTAVOIDGREYDOMAINS__" => $greyform->input('avoid_domains', 30, htmlentities($greylistd->getPref('avoid_domains'))),
    "__GREYLIST_SAVE_STATUS__" => $greysavedmsg,
    "__FORM_BEGIN_ADVANCED__" => $adform->open(),
    "__FORM_CLOSE_ADVANCED__" => $adform->close(),
    "__FORM_INPUTSTAGE__" => $adform->select('stage', $stages, $selected_stage, ''),
    "__FORM_INPUTHEADER__" => $adform->textarea('header_txt', 35, 5, $mta->getPref('header_txt')),
    "__FORM_INPUTIMEOUT__" => $adform->input('smtp_receive_timeout', 3, htmlentities($mta->getPref('smtp_receive_timeout'))),
    "__FORM_INPUTMAXCONNECTIONS__"  => $adform->input('smtp_accept_max', 3, htmlentities($mta->getPref('smtp_accept_max'))),
    "__FORM_INPUTMAXCONNECTIONSPERHOSTS__" => $adform->input('smtp_accept_max_per_host', 3, htmlentities($mta->getPref('smtp_accept_max_per_host'))),
    "__FORM_INPUTINGOREBOUNCEAFTER__" => $adform->input('ignore_bounce_after', 3, htmlentities($mta->getPref('ignore_bounce_after'))),
    "__FORM_INPUTTIEMOUTFROZENAFTER__" => $adform->input('timeout_frozen_after', 3, htmlentities($mta->getPref('timeout_frozen_after'))),
    "__FORM_INPUTMAXRCPT__" => $adform->input('max_rcpt', 5, htmlentities($mta->getPref('max_rcpt'))),
    "__FORM_INPUTENABLESYSLOG__" => $adform->checkbox('use_syslog', '1', htmlentities($mta->getPref('use_syslog')), '', 1),
    "__ADVANCED_SAVE_STATUS__" => $admsg,
];

// output page
$template_->output($replace);
