<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controler for the remove address page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

/**
 * require valid session
 */
require_once('objects.php');
require_once("view/Template.php");
global $sysconf_;
global $lang_;
global $user_;

// check parameters
if (!isset($_GET['add']) || $_GET['add'] == "") {
    die("BADPARAMS");
}
$add = urldecode($_GET['add']);
$message = $lang_->print_txt_param('REMALIASCONFIRM', $add);
// check if user has confirmed
if (isset($_GET['doit'])) {
    // then do it !
    $message = "<font color=\"red\">" . $lang_->print_txt('CANNOTREMOVEMAINADD') . "</font><br/><br/>";
    if ($user_->removeAddress($add)) {
        $message = "<font color=\"red\">" . $lang_->print_txt_param('ALIASREMOVED', $add) . "</font><br/><br/>";
    }
}

// create view
$template_ = new Template('rem_address.tmpl');
$params = $_GET;
$params['doit'] = '1';

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__INCLUDE_JS__' =>
    "<script type=\"text/javascript\" language=\"javascript\">
    function confirm() {
        window.location.href=\"" . $_SERVER['PHP_SELF'] . "?add=$add&doit=1\";
    }
</script>",
    '__MESSAGE__' => $message,
    '__CONFIRM_BUTTON__' => confirm_button(),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
// display page
$template_->output($replace);

/**
 * return the html string for the confirmation button if needed
 * @return  string html button string
 */
function confirm_button()
{
    $lang_ = Language::getInstance('user');
    if (!isset($_GET['doit'])) {
        return "&nbsp;<input type=\"button\" onClick=\"javascript:confirm()\" value=\"" . $lang_->print_txt('CONFIRM') . "\" />";
    }
    return;
}
