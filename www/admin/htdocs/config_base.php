<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the base system configuration page
 */

/**
 * require admin session, view and configurations objects
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("system/SystemConfig.php");
require_once("config/TimeConfig.php");
require_once("config/NetworkConfig.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(['can_configure']);

// create and load time configuration object
$timeconf = new TimeConfig();
$timeconf->load();

// create and load network configuration
$netconf = new NetworkConfig();
$netconf->load();

// create network configuration form
$nform = new Form('network', 'post', "network_config.php");
$nposted = $nform->getResult();
// save network configuration
if ($nform->shouldSave()) {
    foreach ($nposted as $key => $value) {
        $netconf->setPref($key, $value);
    }
    $netsaved = $netconf->save();
    if ($netsaved == "OKSAVED") {
        $netmsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $netmsg = $lang_->print_txt('SAVEERROR') . " (" . $netsaved . ")";
    }
}
// check selected interface
$interface = $netconf->getFirstInterface();
if (isset($_GET['if'])) {
    $interface = $_GET['if'];
}
if (isset($nposted['interface'])) {
    $interface = $nposted['interface'];
}

// create date and time form
$tform = new Form('datetime', 'post', $_SERVER['PHP_SELF']);
$tposted = $tform->getResult();
// save date and time settings
if ($tform->shouldSave()) {
    foreach ($tposted as $key => $value) {
        $timeconf->setPref($key, $value);
    }
    $timeconf->getDate();
    $timesaved = $timeconf->save();
    if ($timesaved == "OKSAVED") {
        $timemsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $timemsg = $lang_->print_txt('SAVEERROR') . " (" . $timesaved . ")";
    }
}

// create proxies form
$pform = new Form('proxies', 'post', $_SERVER['PHP_SELF']);
$pposted = $pform->getResult();
// save proxies configuration
if ($pform->shouldSave()) {
    foreach ($pposted as $key => $value) {
        $sysconf_->setPref($key, $value);
    }
    $proxysaved = $sysconf_->save();
    if ($proxysaved == "OKSAVED") {
        $proxymsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $proxymsg = $lang_->print_txt('SAVEERROR') . " (" . $proxysaved . ")";
    }
}

// create password form
$rform = new Form('rootpass', 'post', $_SERVER['PHP_SELF']);
$rposted = $rform->getResult();
// save password
if ($rform->shouldSave()) {
    $rootpasssaved = $sysconf_->setRootPassword($rposted['password'], $rposted['confirm']);
    if ($rootpasssaved == "OKSAVED") {
        $rootpassmsg = $lang_->print_txt('SAVESUCCESSFULL');
    } else {
        $rootpassmsg = $rootpasssaved;
    }
}

// create view
$template_ = new Template('config_base.tmpl');
$documentor = new Documentor();

// prepare replacements
$replace = [
    '__DOC_BASENETCONFIG__' => $documentor->help_button('BASENETCONFIG'),
    '__DOC_BASEDATETIMECONFIG__' => $documentor->help_button('BASEDATETIMECONFIG'),
    '__DOC_BASEPROXIESCONFIG__' => $documentor->help_button('BASEPROXIESCONFIG'),
    '__DOC_BASEROOTPASS__' => $documentor->help_button('BASEROOTPASS'),
    "__LANG__" => $lang_->getLanguage(),
    "__INCLUDE_TIME_JS__" => timeJS($tform),
    "__FORM_BEGIN_NETWORK__" => $nform->open(),
    "__FORM_CLOSE_NETWORK__" => $nform->close(),
    "__FORM_BEGIN_DATETIME__" => $tform->open(),
    "__FORM_CLOSE_DATETIME__" => $tform->close(),
    "__FORM_INPUTUSENTP__" => $tform->checkbox('usentp', 1, $timeconf->getUseNTP(), 'useNTPServer()', 1),
    "__FORM_INPUTNTPSERVER__" => $tform->input('ntpservers', 25, $timeconf->getServers()),
    "__FORM_INPUTDAY__" => $tform->input('day', 2, date('d')),
    "__FORM_INPUTMONTH__" => $tform->input('month', 2, date('m')),
    "__FORM_INPUTYEAR__" => $tform->input('year', 4, date('Y')),
    "__FORM_INPUTHOUR__" => $tform->input('hour', 2, date('H')),
    "__FORM_INPUTMINUTE__" => $tform->input('minute', 2, date('i')),
    "__FORM_INPUTSECOND__" => $tform->input('second', 2, date('s')),
    "__NTP_SAVE_STATUS__" => $timemsg,
    "__FORM_INPUTINTERFACE__" => $nform->select('interface', $netconf->getInterfaces(), $interface, ''),
    "__FORM_INPUTIP__" => $nform->input('ip', 15, $netconf->getInterface($interface)->getProperty('ip')),
    "__FORM_INPUTNETMASK__" => $nform->input('netmask', 15, $netconf->getInterface($interface)->getProperty('netmask')),
    "__FORM_INPUTGATEWAY__" => $nform->input('gateway', 15, $netconf->getInterface($interface)->getProperty('gateway')),
    "__FORM_INPUTDNSSERVERS__" => $nform->input('dnsservers', 25, $netconf->getDNSString()),
    "__FORM_INPUTSEARCHDOMAIN__" => $nform->input('searchdomains', 25, $netconf->getSearchDomainsString()),
    "__NETWORK_SAVE_STATUS__" => $netmsg,
    "__NETSUBMITACTION__" => "window.document.forms['" . $nform->getName() . "'].submit();",
    "__FORM_BEGIN_PROXIES__" => $pform->open(),
    "__FORM_CLOSE_PROXIES__" => $pform->close(),
    "__FORM_INPUTHTTPPROXY__" => $pform->input('http_proxy', 20, $sysconf_->getPref('http_proxy')),
    "__FORM_SMTPPROXY__" => $pform->input('smtp_proxy', 20, $sysconf_->getPref('smtp_proxy')),
    "__PROXIES_SAVE_STATUS__" => $proxymsg,
    "__FORM_BEGIN_ROOTPASS__" => $rform->open(),
    "__FORM_CLOSE_ROOTPASS__" => $rform->close(),
    "__FORM_INPUTROOTPASS__" => $rform->password('password', 20, '*******'),
    "__FORM_INPUTCONFIRM__" => $rform->password('confirm', 20, '#######'),
    "__ROOTPASS_SAVE_STATUS__" => $rootpassmsg
];

// output page
$template_->output($replace);

/**
 * get the javascript for the date and time fields activation
 * @param  $form  Form   form where to use the javascript
 * @return        string javascript
 */
function TimeJS($tform)
{
    $ret = "
function useNTPServer() {
    if (window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_usentp.value == 1) {
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_ntpservers.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_day.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_month.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_year.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_hour.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_minute.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_second.disabled=true;
    } else {
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_ntpservers.disabled=true;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_day.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_month.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_year.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_hour.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_minute.disabled=false;
      window.document.forms['" . $tform->getName() . "']." . $tform->getName() . "_second.disabled=false;
    }
}";
    return $ret;
}
