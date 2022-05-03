<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the navigation page
 */
 
/**
 * require admin session and view
 */
require_once("admin_objects.php");
require_once("config/Administrator.php");
require_once("system/SystemConfig.php");
require_once("view/Template.php");

/**
 * session globals
 */
global $lang_;
global $admin_;
global $sysconf_;

// create view
$template_ = new Template('navigation.tmpl');

// prepare replacemetns
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__LINK_DOMAIN_LIST__" => "domainlist.php",
        "__LINK_EMAILS__" => "emails.php",
        "__LINK_MANAGEUSERS__" => "users.php",
        "__LINK_GLOBALSPAMQUARANTINE__" => "global_spam_quarantine.php",
        "__LINK_GLOBALVIRUSQUARANTINE__" => "global_content_quarantine.php",
        "__LINK_DEFAULTSCONFIG__" => "config_defaults.php",
        "__LINK_BASECONFIG__" => "config_base.php",
        "__LINK_SMTPCONFIG__" => "config_smtp.php",
        "__LINK_ADMINCONFIG__" => "administrators.php",
        "__LINK_ANTISPAMCONFIG__" => "config_antispam.php",
        "__LINK_ANTIVIRUSCONFIG__" => "config_antivirus.php",
        "__LINK_DANGEROUSCONFIG__" => "config_dangerous.php",
        "__LINK_EXTERNALCONFIG__" => "config_access.php",
        "__LINK_STATUS__" => "monitor_global_status.php",
        "__LINK_STATS__" => "stats.php",
        "__LINK_LOGS__" => "view_logs.php",
        "__LINK_HELP__" => "welcome.php",
        "__RESTART_STATUS__" => getRestartStatus()
);

// output page
$template_->output($replace);

/**
 * get the status of the proccess and display if one needs to be restarted
 * @return  string  processes status
 */
function getRestartStatus() {
    global $sysconf_;
    global $lang_;

    $services = "";

    $sysconf_->loadSlaves();

    if (isset($_SESSION['restart_manager_'])) {
      foreach ($_SESSION['restart_manager_'] as $service => $status) { 
        if (count($_SESSION['restarted'][$service]) >= count($sysconf_->slaves_)) {
           unset($_SESSION['restart_manager_'][$service]);
           unset($_SESSION['restarted'][$service]);
        }
        if ($status > 0) {
          $services .= $service.",";
        }
      }
    }
    $services = rtrim($services);
    $services = rtrim($services, '\,');

    if ($services != "") {
      return $lang_->print_txt('SERVICENEEDRESTART')." (".$services.")";
    }
    return "";
  }
?>