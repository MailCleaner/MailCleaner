<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the parameters page
 *
 * @todo  use Form object
 */

/**
 * require valid session
 */
require_once("objects.php");
require_once("view/Template.php");
require_once("config/AntiSpam.php");
global $sysconf_;
global $lang_;

// get posted values
if (isset($_REQUEST['address']) && $user_->hasAddress($_REQUEST['address'])) {
    $selected_add = $_REQUEST['address'];
} else {
    $selected_add = $user_->getMainAddress();
}

// create Email object to be configured
$sel_add = new Email();
$sel_add->load($selected_add);

// fetch posted user values and save
if (isset($_POST['form']) && $_POST['form'] == 'user') {
    if ($lang_->is_available($_REQUEST['lang'])) {
        $user_->set_language($_REQUEST['lang']);
        $user_->save();
    }
}

// fetch posted emails values and save
if (isset($_POST['form']) && $_POST['form'] == 'address') {
    if (isset($_POST['update_all_addresses']) && $_POST['update_all_addresses'] == 'ok') {
        $user_->getAllAddressModifs();
    } else {
        $sel_add->getModifs();
        $sel_add->save();
    }
    $user_->save();
    $sel_add->load($selected_add);
}

// create view
$template_ = new Template('parameters.tmpl');

# get domain of address
$domain = new Domain();
$domain->load($sel_add->getDomain());
# get antispam global prefs
$antispam_ = new AntiSpam();
$antispam_->load();

if ($antispam_->getPref('enable_whitelists') && ($sel_add->getPref('has_whitelist') || $domain->getPref('enable_whitelists'))) {
    $template_->setCondition('SEEWHITELISTEDIT', true);
}
if ($antispam_->getPref('enable_warnlists') && ($sel_add->getPref('has_warnlist') || $domain->getPref('enable_warnlists'))) {
    $template_->setCondition('SEEWARNLISTEDIT', true);
}
if ($antispam_->getPref('enable_blacklists') && ($sel_add->getPref('has_blacklist') || $domain->getPref('enable_blacklists'))) {
    $template_->setCondition('SEEBLACKLISTEDIT', true);
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
    '__INCLUDE_JS__' =>
    "<script type=\"text/javascript\" language=\"javascript\">
    function fn_changeemail() {
        my_index = window.document.address.address.selectedIndex;
        my_add = window.document.address.address.options[my_index].value;
        window.location.href='parameters.php?address='+my_add;
    }
    function nullfunc() {}
    function remove_add() {
        my_index = window.document.user.address.selectedIndex;
        my_add = window.document.user.address.options[my_index].value;
        window.open('rem_address.php?add='+my_add, '', 'width=500,height=201,toolbar=0,resizable=0,scrollbars=0');
    }
    function add_alias() {
        window.open('add_address.php', '', 'width=500,height=201,toolbar=0,resizable=0,scrollbars=0');
    }
</script>",
    '__BEGIN_USER_FORM__' => "<form name=\"user\" method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\"><input type=\"hidden\" name=\"form\" value=\"user\" />",
    '__SELECTOR_LANG__' => $lang_->html_select(),
    '__CLOSE_USER_FORM__' => "</form>",
    '__CLOSE_ADDRESS_FORM__' => "</form>",
    '__SUBMIT_BUTTON__' => "<input type=\"submit\" value=\"" . $lang_->print_txt('SUBMIT') . "\" />",
    '__ADDRESS_SELECTOR__' => $user_->html_getAddressesSelect($selected_add, 'nullfunc'),
    '__ADD_ALIAS_LINK__' => "javascript:add_alias();",
    '__REMOVE_ALIAS_LINK__' => "javascript:remove_add();",
    '__ALIASES_BUTTONS__' => "<a href=\"add_address.php\" target=\"_blank\"><img src=\"images/plus.gif\" border=\"0\" align=\"absmiddle\" alt=\"" .  $lang_->print_txt('ADDADDRESSALT') . "\"></a>&nbsp;<a href=\"\" onClick=\"remove_add();\"><img src=\"images/minus.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang_->print_txt('REMADDRESSALT') . "\"></a>",
    '__BEGIN_ADDRESS_FORM__' => "<form name=\"address\" method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\"><input type=\"hidden\" name=\"form\" value=\"address\" /><input type=\"hidden\" name=\"language\" value=\"" . $lang_->getLanguage() . "\" />",
    '__ADDRESS_SELECTOR2__' => $user_->html_getAddressesSelect($selected_add, 'fn_changeemail'),
    '__APPLY_ALL_CHECKBOX__' => $user_->html_ApplyAllCheckBox(),
    '__ACTION_RADIOS__' => $sel_add->html_ActionRadio(),
    '__TAG_TEXTFIELD__' => $sel_add->html_TagTextInput(),
    '__SUMFREQ_RADIOS__' => $sel_add->html_SumFreqRadio(),
    '__SUMMARYTYPE__' => $sel_add->html_SumTypeSelect(),
    '__QUARBOUNCES_CHECKBOX__' => $sel_add->html_QuarBouncesCheckbox(),
    '__LINK_EDITWHITELIST__' => "uwwlist.php?t=1&a=" . $sel_add->getPref('address'),
    '__LINK_EDITWARNLIST__' => "uwwlist.php?t=2&a=" . $sel_add->getPref('address'),
    '__LINK_EDITBLACKLIST__' => "uwwlist.php?t=3&a=" . $sel_add->getPref('address'),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

//display page
$template_->output($replace);
