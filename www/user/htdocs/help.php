<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the help page
 */

/**
 * require valid session
 */
require_once("objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
global $sysconf_;
global $lang_;
global $user_;

// some defaults
$topic = 'usermanual';

// create view
$template_ = new Template('help.tmpl');

$temdeftopic = $template_->getDefaultValue('MENUDEFAULT');
if ($temdeftopic != "") {
    $topic = $temdeftopic;
}

$topiclist = $template_->getDefaultValue('MENULIST');
if (isset($_GET['t']) && $_GET['t'] && in_array($_GET['t'], preg_split('/,/', $topiclist))) {
    $topic = $_GET['t'];
}

// fetch help topic
$htxt = [];
$helpfile = $sysconf_->SRCDIR_ . "/www/user/htdocs/lang/" . $lang_->getLanguage() . "/help_texts.php";
if (file_exists($helpfile)) {
    include($helpfile);
} else {
    $helpfile = $sysconf_->SRCDIR_ . "/www/user/htdocs/lang/en/help_texts.php";
    if (file_exists($helpfile)) {
        include($helpfile);
    }
}
$templatehelpfile = $sysconf_->SRCDIR_ . "/www/user/htdocs/templates/" . $template_->getModel() . "/lang/" . $lang_->getLanguage() . "/help_texts.php";
if (file_exists($templatehelpfile)) {
    include($templatehelpfile);
}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__PRINT_USERNAME__' => $user_->getName(),
    '__LINK_LOGOUT__' => '/logout.php',

    '__MENULIST__' => getMenuList(),
    '__THISTOPIC__' => $topic,
    '__HELP_TOPIC__' => $lang_->print_txt(strtoupper($topic) . "TOPICTITLE"),
    '__HELP_CONTENT__' => getHelpContent([strtoupper($topic)]),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

// display page
$template_->output($replace);


function getMenuList()
{
    global $template_;
    global $topics;
    global $lang_;
    global $topiclist;

    $ret = "";
    $i = 1;
    foreach (preg_split('/,/', $topiclist) as $topic) {
        $t = $template_->getTemplate('MENUITEM');
        $t = str_replace('__TOPIC__', $topic, $t);
        $t = str_replace('__ID__', $i++, $t);
        $t = str_replace('__TOPIC_TEXT__', $lang_->print_txt(strtoupper($topic) . 'TOPIC'), $t);
        $ret .= $t;
    }
    return $ret;
}

function getHelpContent($carray)
{
    global $htxt;
    global $sysconf_;
    global $user_;
    global $template_;
    global $lang_;

    $ret = "";
    foreach ($carray as $top) {
        if ($top == 'USERMANUAL') {
            $ret .= file_get_contents('http://cdn.mailcleaner.net/downloads/documentations/' . $lang_->getLanguage() . '/body.html');
            ##http://www.mailcleaner.net/downloads/documentations/en/body.html');
        } else {
            $ret .= $htxt[$top];
        }
    }

    ## replace tags
    require_once('config/HTTPDConfig.php');
    $httpd = new HTTPDConfig();
    $httpd->load();
    $url = "http://";
    if ($httpd->getPref('use_ssl')) {
        $url = "https://";
    }
    $url .= $httpd->getPref('servername');

    $domain = $user_->getDomain();
    $spamaddress = $domain->getPref('support_email');
    if ($spamaddress == "") {
        $spamaddress = $sysconf_->getPref('analyse_to');
    }
    $dayskeepspam = $sysconf_->getPref('days_to_keep_spams');
    $supportaddress = $spamaddress;
    $salesaddress = $spamaddress;

    $spamaddress = 'spam@mailcleaner.net';
    $nospamaddress = 'nospam@mailcleaner.net';
    $supportaddress = 'support@mailcleaner.net';
    $salesaddress = 'sales@mailcleaner.net';

    $ret = preg_replace('/__SPAM_EMAIL__/', "<a href=\"mailto:$spamaddress\">$spamaddress</a>", $ret);
    $ret = preg_replace('/__NOSPAM_EMAIL__/', "<a href=\"mailto:$spamaddress\">$spamaddress</a>", $ret);
    $ret = preg_replace('/__SUPPORT_EMAIL__/', "<a href=\"mailto:$supportaddress\">$supportaddress</a>", $ret);
    $ret = preg_replace('/__SALES_EMAIL__/', "<a href=\"mailto:$salesaddress\">$salesaddress</a>", $ret);
    $ret = preg_replace('/__WEBSITE_URL__/', "<a href=\"$url\">$url</a>", $ret);
    $ret = preg_replace('/__SPAMKEEPDAYS__/', $dayskeepspam, $ret);
    $ret = preg_replace('/__ICON_INFO__/', $template_->getDefaultValue('ICONINFO'), $ret);
    $ret = preg_replace('/__ICON_ANALYSE__/', $template_->getDefaultValue('ICONANALYSE'), $ret);
    $ret = preg_replace('/__ICON_FORCE__/', $template_->getDefaultValue('ICONFORCE'), $ret);
    $ret = preg_replace('/__ICON_FORCED__/', $template_->getDefaultValue('ICONFORCED'), $ret);
    $ret = preg_replace('/__ICON_SCOREFILLED__/', $template_->getDefaultValue('SCOREFILLED'), $ret);
    $ret = preg_replace('/__IMAGE_BASE__/', 'templates/' . $template_->getModel() . "/", $ret);
    $matches = [];
    while (preg_match('/__(MANUAL_([A-Z]+)_NAME)__/', $ret, $matches)) {
        $str = $matches[1];
        $ret = preg_replace("/__($str)__/", $htxt[$str], $ret);
        $helpt = $matches[2];
        $link = '/docs/' . $lang_->getLanguage() . "/" . $htxt[$str];
        $file = $sysconf_->SRCDIR_ . '/www/user/htdocs/' . $link;
        $size = 0;
        if (file_exists($file)) {
            $size = filesize($file);
        }
        #    $ret = preg_replace("/__MANUAL_".$helpt."_SIZE__/", format_size($size), $ret);
        #    $ret = preg_replace("/__MANUAL_".$helpt."_LINK__/", $link, $ret);
        $ret = preg_replace("/__MANUAL_" . $helpt . "_SIZE__/", "Remote documentation website", $ret);
        $ret = preg_replace("/__MANUAL_" . $helpt . "_LINK__/", 'http://cdn.mailcleaner.net/downloads/documentations/' . $lang_->getLanguage(), $ret);
    }

    $ou2003name = 'MailCleaner_outlook2003.zip';
    $ou2007name = 'MailCleaner_outlook2007.zip';
    $ou2003relpath = '/plugins/' . $lang_->getLanguage() . '/' . $ou2003name;
    $ou2007relpath = '/plugins/' . $lang_->getLanguage() . '/' . $ou2007name;
    $ou2003abspath = $sysconf_->SRCDIR_ . '/www/user/htdocs/' . $ou2003relpath;
    $ou2003size = 0;
    if (file_exists($ou2003abspath)) {
        $ou2003size = filesize($ou2003abspath);
    }
    $ou2007abspath = $sysconf_->SRCDIR_ . '/www/user/htdocs/' . $ou2007relpath;
    $ou2007size = 0;
    if (file_exists($ou2007abspath)) {
        $ou2007size = filesize($ou2007abspath);
    }

    $ret = preg_replace("/__PLUGIN_OU2003_LINK__/", $ou2003relpath, $ret);
    $ret = preg_replace("/__PLUGIN_OU2007_LINK__/", $ou2007relpath, $ret);
    $ret = preg_replace("/__PLUGIN_OU2003_NAME__/", $ou2003name, $ret);
    $ret = preg_replace("/__PLUGIN_OU2007_NAME__/", $ou2007name, $ret);
    $ret = preg_replace("/__PLUGIN_OU2003_SIZE__/", format_size($ou2003size), $ret);
    $ret = preg_replace("/__PLUGIN_OU2007_SIZE__/", format_size($ou2007size), $ret);

    $ret = preg_replace('/__LINKHELP_(\S+)__(.*)__LINK__/', "<a href=\"" . 'http://cdn.mailcleaner.net/downloads/documentations/' . $lang_->getLanguage() . "\">\\2</a>", $ret);
    #$ret = preg_replace('/__LINKHELP_(\S+)__(.*)__LINK__/', "<a href=\"".$_SERVER['PHP_SELF']."?t=\\1\">\\2</a>", $ret);
    $ret = preg_replace('/__ANCHOR_(\S+)__(.*)__ANCHOR__/', "<a id=\"\\1\">\\2</a>", $ret);
    return $ret;
}

function format_size($s)
{
    global $lang_;
    $ret = "";
    if ($s > 1000 * 1000 * 1000) {
        $ret = sprintf("%.2f " . $lang_->print_txt('GB'), $s / (1000.0 * 1000.0 * 1000.0));
    } elseif ($s > 1000 * 1000) {
        $ret = sprintf("%.2f " . $lang_->print_txt('MB'), $s / (1000.0 * 1000.0));
    } elseif ($s > 1000) {
        $ret = sprintf("%.2f " . $lang_->print_txt('KB'), $s / (1000.0));
    } else {
        $ret = $s . " " . $lang_->print_txt('BYTES');
    }
    return $ret;
}
