<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the send to analyse page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once('utils.php');
require_once("view/Template.php");
require_once("system/Soaper.php");

// get global objects instances
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('user');
$askfirst = true;

// check parameters
if (!isset($_GET['id']) || !isset($_GET['a']) || !isset($_GET['s'])) {
    die("BADPARAMS");
}
if (!is_exim_id($_GET['id']) || !is_email($_GET['a']) || !is_numeric($_GET['s'])) {
    die("BADPARAMS");
}

$cancelclose = $lang_->print_txt('CLOSE');

// check user has confirmed
if (isset($_GET['doit'])) {
    $soaper = new Soaper();
    $ret = @$soaper->load($sysconf_->getSlaveName($_GET['s']));
    if ($ret != "OK") {
        $res = $ret;
    } else {
        $res = $soaper->queryParam('sendToAnalyse', [$_GET['id'], $_GET['a']]);
    }
    $askfirst = false;
    $message = $lang_->print_txt($res);
} else {
    $askfirst = true;
    $message = $lang_->print_txt('CONFSENDANALYSE') . "<br />" . $lang_->print_txt('AREYOUSURE');
    $cancelclose = $lang_->print_txt('CANCEL');
}
// create view
$template_model = 'send_to_analyse.tmpl';
if (isset($_GET['pop']) && $_GET['pop'] == 'up') {
    $template_model = 'send_to_analyse_pop.tmpl';
}

$template_ = new Template($template_model);
if ($askfirst) {
    $template_->setCondition('askfirst', true);
}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// prepare replacements
$replace = [
    '__INCLUDE_JS__' => "<script type=\"text/javascript\" charset=\"utf-8\">
                        function confirmation() {
                          window.location.href=\"" . $_SERVER['PHP_SELF'] . "?" . preg_replace('/&/', ';', $_SERVER['QUERY_STRING'] . "&doit=1") . "\";
                       }
                       </script>",
    '__MESSAGE__' => $message,
    '__CONFIRM_BUTTON__' => confirm_button($askfirst),
    '__CANCELCLOSE__' => $cancelclose,
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
// display page
$template_->output($replace);

/**
 * return the confirm button code if needed
 * @param  $askfirst  boolean  do we need to ask or not
 * @return            string   html button string if needed, or "" if not
 */
function confirm_button($askfirst)
{
    $lang_ = Language::getInstance('user');
    if ($askfirst) {
        return "<input type=\"button\" id=\"confirm\" class=\"button\" onclick=\"javascript:confirmation();\" value=\"" . $lang_->print_txt('SUBMIT') . "\" />";
    }
    return "";
}
