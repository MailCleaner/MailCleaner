<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page that will redirect to the log page of the correct host
 */
 
require_once ("admin_objects.php");
require_once ('view/Language.php');
require_once ('config/Administrator.php');
require_once ('view/Template.php');
require_once ('view/Form.php');
require_once ('system/Soaper.php');
require_once ("view/Documentor.php");
global $sysconf_;

// create the vie objects
$form = new Form('filter', 'post', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

$template_ = new Template('view_logs.tmpl');
$documentor = new Documentor();

// prepare select fields
$hosts = array();
$slaves = $sysconf_->getSlaves();
foreach ($slaves as $slave) {
    $hosts[$slave->getPref('hostname')." (".$slave->getPref('id').")"] = $slave->getPref('hostname');
}

$logs_ = array ($lang_->print_txt('MTA1') => 'mta1', $lang_->print_txt('MTA2') => 'mta2', $lang_->print_txt('MTA4') => 'mta4', $lang_->print_txt('ENGINE') => 'engine', $lang_->print_txt('HTTPD') => 'httpd',);
$dates_ = array ();
$dates_[$lang_->print_txt('TODAY')] = "T";
$actual_timestamp = mktime(0, 0, 0, date("m"), date("d"), date("y"));
for ($i = 1; $i < 50; $i ++) {
    $day = $actual_timestamp - $i * (24 * 3600);
    $dates_[date("D j M Y", $day)] = $i;
}
if (!isset ($posted['date'])) {
    $posted['date'] = 'T';
}

// replace statements
$replace = array (
                '__DOC_VIEWLOGS__' => $documentor->help_button('VIEWLOGS'), 
                '__LANG__' => $lang_->getLanguage(), 
                '__ERROR__' => $lang_->print_txt($error), 
                '__MESSAGE__' => $lang_->print_txt($message), 
                "__FORM_BEGIN_FILTER__" => $form->open(), 
                "__FORM_CLOSE_FILTER__" => $form->close(), 
                "__HOSTLIST__" => $form->select('host', $hosts, $posted['host'], ''), 
                "__LOGLIST__" => $form->select('log', $logs_, $posted['log'], ''), 
                "__DATELIST__" => $form->select('date', $dates_, $posted['date'], ''), 
                "__REFRESH_BUTTON__" => $form->submit('submit', $lang_->print_txt('REFRESH'), ''), 
                "__LOG_LINK__" => getLogPage($posted) 
               );
// display page
$template_->output($replace);

/** get the correct log page
 * @param $posted   array  posted values
 * @return          string html link to the log page
 */
function getLogPage($posted) {
    global $posted;

    $host = $posted['host'];
    $log = $posted['log'];
    $date = $posted['date'];

    $soaper = new Soaper();
    $ret = @$soaper->load($host);
    if ($ret != "OK") {
        return "blank.php";
    }

    $sid = $soaper->authenticateAdmin();
    if ($sid == "SOAPERRORCANNOTEXECUTE") {
        return "";
    }
    
    $query = http_build_query(array('sid' => $sid, 'l' => $log, 'd' => $date));
     if ($host == '127.0.0.1' || $host == 'localhost') {
        return "logs.php?".$query;
    } else {
        $hostip = gethostbyname($host);
        $proto = 'http';
        if (isset ($_SERVER['HTTPS'])) {
            $proto = "https";
        }
        return "$proto://$hostip/admin/logs.php?".$query;
    }
}
?>