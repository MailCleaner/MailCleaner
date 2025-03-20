<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the support and help page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once("objects.php");
require_once("view/SupportForm.php");
require_once("view/Template.php");
global $lang_;

$res = "";
// check if support form has been posted
if (isset($_POST['send']) && $_POST['send'] > 0) {
    $sup_form = new SupportForm();
    if ($sup_form->is_ok() == true) {
        $res = $lang_->print_txt($sup_form->send());
    } else {
        $res = $lang_->print_txt('BADFORMFIELDS') . " (" . $lang_->print_txt($sup_form->get_badfield()) . ")";
    }
}

// include support texts
includeSupport('en');
includeSupport($lang_->getLanguage());

$template_ = new Template('support.tmpl');

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__INCLUDE_JS__' => '',
    '__BEGIN_SUPPORT_FORM__' => "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">",
    '__CLOSE_SUPPORT_FORM__' => "<input type=\"hidden\" name=\"send\" value=\"1\"></form>",
    '__COMPANY_FIELD__' => "<input name=\"company\" type=\"text\" id=\"company\" size=\"15\">",
    '__NAME_FIELD__' => "<input name=\"name\" type=\"text\" id=\"name\" size=\"15\">",
    '__FIRSTNAME_FIELD__' => "<input name=\"firstname\" type=\"text\" id=\"firstname\" size=\"15\">",
    '__EMAIL_FIELD__' => "<input name=\"email\" type=\"text\" id=\"email\" size=\"15\">",
    '__PHONE_FIELD__' => "<input name=\"phone\" type=\"text\" id=\"phone\" size=\"15\">",
    '__WAHTCANWEDO__' => "<textarea name=\"whatcanwedo\" cols=\"32\" rows=\"4\" id=\"whatcanwedo\"></textarea>",
    '__SUBMIT_BUTTON__' => "<input type=\"submit\" name=\"Submit\" value=\"" . $lang_->print_txt('SEND') . "\">",
    '__RESET_BUTTON__' => "<input type=\"reset\" name=\"reset\" value=\"" . $lang_->print_txt('CLEAR') . "\">",
    '__MESSAGE__' => $res,
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

$template_->output($replace);


function includeSupport($lang)
{
    global $txt;
    global $sysconf_;
    global $lang_;

    $support_texts = $sysconf_->SRCDIR_ . "/www/user/htdocs/lang/" . $lang . "/support.txt";
    if (file_exists($support_texts)) {
        include($support_texts);

        foreach ($txt as $t => $str) {
            $lang_->setTextValue($t, $str);
        }
        return true;
    }
    return false;
}
