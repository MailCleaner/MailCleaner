<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the external access configuration page
 */

/**
 * require admin session, view and ExternalAccess objects
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("config/ExternalAccess.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;
global $admin_;

/// list of services to be managed
$services_name = ['web', 'mysql', 'snmp', 'ssh', 'mail', 'soap'];
$services = [];

// check authorizations
$admin_->checkPermissions(['can_configure']);
$save_msg = "";

// load services accesses
foreach ($services_name as $s) {
    $services[$s] = new ExternalAccess();
    $services[$s]->load($s);
}

// create main form
$aform = new Form('access', 'post', $_SERVER['PHP_SELF']);
$aposted = $aform->getResult();
// save settings
if ($aform->shouldSave()) {
    // get ip settings
    foreach ($aposted as $k => $v) {
        if (preg_match('/(\S+)\_(\S+)/', $k, $tmp) && in_array($tmp[1], $services_name)) {
            if ($tmp[2] == "ips") {
                $services[$tmp[1]]->setIPS($v);
                $services[$tmp[1]]->save();
                $services[$tmp[1]]->load($tmp[1]);
            }
        }
    }

    $saved = 'OKSAVED';
    // save services
    foreach ($services_name as $s) {
        $tmpsaved = $services[$s]->save();
        if ($tmpsaved != 'OKSAVED' && $tmpsaved != 'OKADDED') {
            $saved = $tmpsaved;
        }
        $services[$s]->load($s);
    }
    if ($saved == 'OKSAVED' || $saved == 'OKADDED') {
        $save_msg = $lang_->print_txt('SAVESUCCESSFULL');
        $_SESSION['restart_manager_']['FIREWALL'] = 1;
    } else {
        $save_msg = $lang_->print_txt('SAVEERROR') . "(" . $saved . ")";
    }
}

// create view
$template_ = new Template('config_access.tmpl');
$documentor = new Documentor();

// prepare replacements
$replace = [
    '__DOC_ACCESSTITLE__' => $documentor->help_button('ACCESSTITLE'),
    "__LANG__" => $lang_->getLanguage(),
    "__SAVE_STATUS__" => $save_msg,
    "__FORM_BEGIN_ACCESS__" => $aform->open(),
    "__FORM_CLOSE_ACCESS__" => $aform->close(),
    "__WEBGUIPORT__" => $services['web']->getDefaultPort(),
    "__WEBGUIPROTOCOL__" => $services['web']->getDefaultProtocol(),
    "__FORM_INPUTWEBGUIIPS__" => $aform->input('web_ips', 30, $services['web']->getAllowedIPSString()),
    "__DBPORT__" => $services['mysql']->getDefaultPort(),
    "__DBPROTOCOL__" => $services['mysql']->getDefaultProtocol(),
    "__FORM_INPUTDBIPS__" => $aform->input('mysql_ips', 30, $services['mysql']->getAllowedIPSString()),
    "__SNMPDPORT__" => $services['snmp']->getDefaultPort(),
    "__SNMPPROTOCOL__" => $services['snmp']->getDefaultProtocol(),
    "__FORM_INPUTSNMPIPS__" => $aform->input('snmp_ips', 30, $services['snmp']->getAllowedIPSString()),
    "__SSHPORT__" => $services['ssh']->getDefaultPort(),
    "__SSHPROTOCOL__" => $services['ssh']->getDefaultProtocol(),
    "__FORM_INPUTSSHIPS__" => $aform->input('ssh_ips', 30, $services['ssh']->getAllowedIPSString()),
    "__MAILPORT__" => $services['mail']->getDefaultPort(),
    "__MAILPROTOCOL__" => $services['mail']->getDefaultProtocol(),
    "__FORM_INPUTMAILIPS__" => $aform->input('mail_ips', 30, $services['mail']->getAllowedIPSString()),
    "__SOAPPORT__" => $services['soap']->getDefaultPort(),
    "__SOAPPROTOCOL__" => $services['soap']->getDefaultProtocol(),
    "__FORM_INPUTSOAPIPS__" => $aform->input('soap_ips', 30, $services['soap']->getAllowedIPSString()),
    "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true)"
];

// output page
$template_->output($replace);
