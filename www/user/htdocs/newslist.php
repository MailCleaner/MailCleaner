<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author John Mertz
 * @copyright 2021, MailCleaner
 *
 * This is the controler for the newsletter whitelist page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
  return 200;
}

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
 * Gets the sender (From of the body) of the email with a given Spam object
 * @param Spam object $spam_mail The mail concerned
 * @return string The from email address of the sender of the email
 */
function get_sender_address_body($spam_mail) {
    // Get the mail sender
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
 * Get the IP of the master machine for SOAP requests
 * @return string $soap_host The IP of the machine
 */
function get_master_soap_host() {
    $sysconf_ = SystemConfig::getInstance();
    foreach ($sysconf_->getMastersName() as $master){
        return $master;
    }
}

/**
 * Connects to the machine and sends a soap request.
 * @param string $host Host machine receiving the request
 * @param string $request SOAP request
 * @param array $params Parameters of the request
 * @param array $allowed_response Authorized responses
 * @return bool Status of the request. If True, everything went well
 */
function send_SOAP_request($host, $request, $params) {
    $soaper = new Soaper();
    $ret = @$soaper->load($host);
    if ($ret == "OK") {
        return $soaper->queryParam($request, $params);
    } else {
        return "FAILEDCONNMASTER";
    }
}

// get the global objects instances
$lang_ = Language::getInstance('user');

// set the language from what is passed in url
if (isset($_GET['lang'])) {
  $lang_->setLanguage($_GET['lang']);
  $lang_->reload();
}
if (isset($_GET['l'])) {
  $lang_->setLanguage($_GET['l']);
  $lang_->reload();
}


// Cheking if the necessary arguments are here
$in_args = array('id', 'a');
foreach ($in_args as $arg) {
    if (!isset($_GET[$arg])){
        $bad_arg = $arg;
    }
}



// Renaming the args for easier reading
$exim_id = $_GET['id'];
$dest = $_GET['a'];

if (!isset($bad_arg)) {

    // Get the Spam mail
    $spam_mail = new Spam();
    $spam_mail->loadDatas($exim_id, $dest);
    if (!$spam_mail->loadHeadersAndBody()) {
        $is_sender_added_to_news = $lang_->print_txt('CANNOTLOADMESSAGE');
    } else {

        if (isset($_GET['t']) && $_GET['t'] != '') {
            $sender = $_GET['t'];
        } else {
            // Get both sender and from addresses
            $sender = get_sender_address_body($spam_mail);
        }

        $slave = get_soap_host($exim_id, $dest);
        $master = get_master_soap_host();

        $is_sender_added_to_news = send_SOAP_request(
            $master,
            "addToNewslist",
            array($dest, $sender)
        );
        if ($is_sender_added_to_news != 'OK') {
            $is_sender_added_to_news = $lang_->print_txt($is_sender_added_to_news);
        }
    }

} else {
    $is_sender_added_to_news = $lang_->print_txt_param('BADARGS', $bad_arg);
}

// Parse the template
$template_ = new Template('add_rule.tmpl');
$replace = array();

// Setting the page text
if ($is_sender_added_to_news == 'OK') {
    $replace['__HEAD__'] = $lang_->print_txt('NEWSLISTHEAD');
    $replace['__MESSAGE__'] = $lang_->print_txt('NEWSLISTBODY');
} else {
    $replace['__HEAD__'] = $lang_->print_txt('NOTNEWSLISTHEAD');
    $replace['__MESSAGE__'] = $lang_->print_txt('NOTNEWSLISTBODY') . ' ' . $is_sender_added_to_news;
}

// display page
$template_->output($replace);
