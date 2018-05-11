<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Marin Gilles
 * @copyright 20018, MailCleaner
 *
 * This is the controller for the newsletter release page
 */

/**
 * requires a session
 */
require_once('variables.php');
require_once('view/Language.php');
require_once('system/SystemConfig.php');
require_once('user/WWEntry.php');
require_once('user/Spam.php');
require_once('view/Template.php');
require_once('system/Soaper.php');

/**
 * Gets the sender of the email with a given Exim ID and recipient
 * @param string $exim_id The Exim id of the mail
 * @param string $dest The email address of the recipient
 * @return string The email address of the sender of the email
 */
function get_sender($exim_id, $dest) {
    // Get the mail sender
    $spam_mail = new Spam();
    $spam_mail->loadDatas($exim_id, $dest);
    $spam_mail->loadHeadersAndBody();
    $headers = $spam_mail->getHeadersArray();

    $sender = array();
    preg_match('/[<]?([-0-9a-zA-Z.+_\']+@[-0-9a-zA-Z.+_\']+\.[a-zA-Z-0-9]+)[>]?/', trim($headers['From']), $sender);

    if (!empty($sender[1])) {
        return $sender[1];
    }
    return false;
}

/**
 * Get the IP of the machine to which to send the SOAP requests
 * @param string $exim_id The Exim id of the mail
 * @param string $dest The email address of the recipient
 * @return string $soap_host The IP of the machine
 */
function get_soap_host($exim_id, $dest) {
    $sysconf_ = SystemConfig::getInstance();
    $spam_mail = new Spam();
    $spam_mail->loadDatas($exim_id, $dest);
    $soap_host = $sysconf_->getSlaveName($spam_mail->getData('store_slave'));
    return $soap_host;
}

/**
 * Connect to the machine through the SOAP interface, and send the SOAP queries to
 * liberate the message, and add the mail to the newsletter whitelist
 * @param string $soap_host The machine to which to send the SOAP queries
 * @param string $exim_id The Exim ID of the mail
 * @param string $dest The recipient of the mail
 * @param string $sender The sender of the mail
 * @return bool Status of the operation. If True, everything went well. Else, some
 *              operation failed
 */
function free_and_whitelist_newsletter($soap_host, $exim_id, $dest, $sender) {
    // Resend the newsletter and add recipient and sender to the db through the SOAP interface
    $soaper = new Soaper();
    $ret = @$soaper->load($soap_host);
    if ($ret != "OK") {
        // $res = $ret;
        return False;
    } else {
        // actually force the message
        $res = $soaper->queryParam('forceSpam', array($exim_id, $dest));
        if (! $res == "MSGFORCED") {
            return False;
        }
        // Add message to the db
        $res = $soaper->queryParam('addNewsletterToWhitelist', array($dest, $sender));
        if (! ($res == "OK" || $res == "DUPLICATEENTRY")) {
            return False;
        }
        return True;
    }
}

// get the global objects instances
$lang_ = Language::getInstance('user');

$bad_arg = False;

// set the language from what is passed in url
if (isset($_GET['lang'])) {
  $lang_->setLanguage($_GET['lang']);
  $lang_->reload();
}
if (isset($_GET['l'])) {
  $lang_->setLanguage($_GET['l']);
  $lang_->reload();
}

// Checking if the necessary arguments are here
$in_args = array($_GET['id'], $_GET['a']);
foreach ($in_args as $arg) {
    if (! isset($arg)){
        $bad_arg = True;
    }
}

// Renaming the args for easier reading
$exim_id = $_GET['id'];
$dest = $_GET['a'];

if (!$bad_arg) {
    $sender = get_sender($exim_id, $dest);
    $soap_host = get_soap_host($exim_id, $dest);
    $is_operation_ok = free_and_whitelist_newsletter($soap_host, $exim_id, $dest, $sender);
} else {
    $is_operation_ok = False;
}

// Setting the page text
if ($is_operation_ok) {
    $message_head = "NLRELEASEDHEAD";
    $message = "NLRELEASEDBODY";
} else {
    $message_head = "NLNOTRELEASEDHEAD";
    $message = "NLNOTRELEASEDBODY";
}

// Parse the template
$template_ = new Template('newsletters.tmpl');
$replace = array(
    '__LANG_FORCEMESSAGE__' => $lang_->print_txt($message_head),
    '__MESSAGE__' => $lang_->print_txt($message),
);

// display page
$template_->output($replace);
