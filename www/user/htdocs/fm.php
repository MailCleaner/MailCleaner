<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the force message page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
  return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once("system/SystemConfig.php");
require_once("utils.php");
require_once("view/Template.php");
require_once("system/Soaper.php");
require_once("config/AntiSpam.php");
require_once("user/Spam.php");

// get global objects instances
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('user');
if (isset($_GET['lang'])) {
  $lang_->setLanguage($_GET['lang']);
  $lang_->reload();
}
if (isset($_GET['l'])) {
  $lang_->setLanguage($_GET['l']);
  $lang_->reload();
}

// check parameters
if (!isset($_GET['id']) || !isset($_GET['a']) || !isset($_GET['s'])) {
  die ("BADPARAMS");
}
if (!is_exim_id($_GET['id']) || !is_email($_GET['a']) || !is_numeric($_GET['s'])) {
  die ("BADPARAMS");
}

$soaper = new Soaper();
$ret = @$soaper->load($sysconf_->getSlaveName($_GET['s']));
if ($ret != "OK") {
  $res = $ret;
} else {
  // actually force the message
  $res = $soaper->queryParam('forceSpam', array($_GET['id'], $_GET['a']));
  $res = preg_replace('/^(\S*)\s.*/', '$1', $res);
} 

// get the view objects
$template_model = 'fm.tmpl';
if (isset($_GET['pop']) && $_GET['pop'] == 'up') {
  $template_model = 'fm_pop.tmpl';
}
$template_ = new Template($template_model);
$replace = array(
  '__MESSAGE__' => $lang_->print_txt($res)
);

$replace['__ACTIONS__'] = '';

// Check for whitelist permission
$dom = $_GET['a'];
$dom = preg_replace('/^.*@([^@]*)$/', '$1', $dom);
$antispam_ = new AntiSpam();
$antispam_->load();
$domain = new Domain();
$domain->load($dom);
$can_whitelist = ( $domain->getPref('enable_whitelists') || (($domain->getPref('enable_whitelists') == null) && $antispam_->getPref('enable_whitelists')) );

// Get sender
$spam_mail = new Spam();
$spam_mail->loadDatas($_GET['id'],$_GET['a']);
if (isset($_GET['n']) && $_GET['n'] == 1) {
  $spam_mail->loadHeadersAndBody();
  $from = $spam_mail->getHeadersArray()['From'];
  preg_match_all('/[<]?([-0-9a-zA-Z.+_\']+@[-0-9a-zA-Z.+_\']+\.[a-zA-Z-0-9]+)[>]?/', trim($from), $original_sender);
  $original_sender = $original_sender[0][sizeof($original_sender[0])-1];
} else {
  $original_sender = $spam_mail->getData("sender");
}
$sender = extractSender($original_sender);
$single_use = detectSingleUseAddress($original_sender);
if ($sender || $single_use) {
  if ($sender) {
    $target = '<input type="radio" name="t" value="' . $original_sender . '"><label for="' . $original_sender . '">' . $original_sender . ' (' . $lang_->print_txt('ORIGINALSENDER') . ')</label><br>';
    $target .= '<input type="radio" name="t" value="' . $sender . '" checked="checked"><label for="' . $sender . '">' . $sender . ' (' . $lang_->print_txt('SENDERVARIATIONS') . ')</label><br>';
  } else {
    $target = '<input type="radio" name="t" value="' . $original_sender . '" checked="checked"><label for="' . $original_sender . '">' . $original_sender . ' (' . $lang_->print_txt('ORIGINALSENDER') . ')</label><br>';
  }
  if ($single_use) {
    $domain = preg_replace('/.*(@[^@)]*)$/', '$1', $original_sender);
    $target .= '<input type="radio" name="t" value="' . $domain . '"><label for="' . $domain . '">' . $domain . ' (' . $lang_->print_txt('ENTIREDOMAIN') . ')</label><br>';
  }
} else {
  $target .= '<input type="radio" name="t" value="' . $original_sender . '" checked="checked" style="display: none;">';
}

// Enumerate permitted action buttons
if (isset($_GET['n']) && $_GET['n'] == 1) {
  $replace['__MESSAGE__'] .= '<hr style="font-size: 35px;" /><p><b>' . $lang_->print_txt('ADDITIONALACTION') . '</b></p>';
  $news = '<input type="button" class="button" id="newslist" onclick="location = \'/newslist.php?id=' . $_GET['id'] . '&a=' . urlencode($_GET['a']) . '&t=\' + encodeURI(document.querySelector(\'input[name=t]:checked\').value);" value="' . $lang_->print_txt("NEWSLISTTOPIC") . '"></input>';
  if ($can_whitelist) {
    $replace['__MESSAGE__'] .= '<p>' . $lang_->print_txt('ADDNEWSWHITELIST') . '</p>' . $target;
    $news .= '<input type="button" class="button" id="newswhitelist" onclick="location = \'/newswhitelist.php?id=' . $_GET['id'] . '&a=' . urlencode($_GET['a']) . '&t=\' + encodeURI(document.querySelector(\'input[name=t]:checked\').value);" value="' . $lang_->print_txt("NEWSLISTTOPIC") . ' + ' . $lang_->print_txt("WHITELISTTOPIC") . '" />';
  } else {
    $replace['__MESSAGE__'] .= '<p>' . $lang_->print_txt('ADDNEWSLIST') . '</p>' . $target;
  }
  $replace['__ACTIONS__'] .= $news;
} else {
  if ($can_whitelist) {
    $replace['__MESSAGE__'] .= '<hr style="font-size: 35px;" /><p><b>' . $lang_->print_txt('ADDITIONALACTION') . '</b></p>';
    $replace['__MESSAGE__'] .= '<p>' . $lang_->print_txt('ADDWHITELIST') . '</p>' . $target;
    $replace['__ACTIONS__'] = '<input type="button" class="button" id="whitelist" onclick="location = \'/whitelist.php?id=' . $_GET['id'] . '&a=' . urlencode($_GET['a']) . '&t=\' + encodeURI(document.querySelector(\'input[name=t]:checked\').value);" value="' . $lang_->print_txt("WHITELISTTOPIC") . '" />';
  }
}

// output result page
$template_->output($replace);
?>
