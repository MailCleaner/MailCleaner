<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the network reconfiguration page
 */
 
/**
 * require admin session, view and network settings
 */
require_once("admin_objects.php");
require_once("view/Template.php");
require_once("view/Form.php");
require_once("system/SystemConfig.php");
require_once("config/NetworkConfig.php");
require_once("config/HTTPDConfig.php");

/**
 * session globals
 */
global $lang_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_configure'));

// load network interfaces settings
$netconf = new NetworkConfig();
$netconf->load();

// load http daemon settings
$httpd = new HTTPDConfig();
$httpd->load();

// create network form
$nform = new Form('network', 'post', "network_config.php");
$nposted = $nform->getResult();
// save network configuration or redirect
if ($nform->shouldSave()) {
  foreach($nposted as $key => $value) {
    $netconf->setPref($key, $value);
  }
} else {
  header("Location: config_base.php?if=".$nposted['interface']);
}
// get the network interface to be configured
$interface = $netconf->getFirstInterface();
if (isset($nposted['interface'])) {
  $interface = $nposted['interface'];
}
// get redirect scheme
$base_uri = "http://";
if ($httpd->getPref('use_ssl')) {
  $base_uri = "https://";
}

// create view
$template_ = new Template('network_config1.tmpl');

// prepare replacements
$replace = array(
        "__REDIRCHANGED_URL__" => "<a href=\"".$base_uri.$netconf->getInterface($interface)->getProperty('ip')."/admin/\" target=\"_parent\">".$base_uri.$netconf->getInterface($interface)->getProperty('ip')."/admin/</a>",
);
// output page
$template_->output($replace);

// force page output by filling buffers
flush();
echo "<!--";
for($i=0; $i<600000; $i++) {
  echo " ";
}
echo "-->";
flush();

// create second view
$template_ = new Template('network_config2.tmpl');
unset($replace);
// prepare replacements
$replace = array(
        "__APPLYCHANGES__" => apply($netconf),
        "__REDIRNOTCHANGED_URL__" => "<a href=\"/admin/config_base.php\" target=\"_self\">".$lang_->print_txt('CONFIGURATION')."</a>"
);
// output page
$template_->output($replace);

/**
 * actually apply network settings
 * @param  $netconf  NetWorkConfig  network config to be applied
 * @return           string         OKSAVED on success, error message on failure
 */
function apply($netconf) {
  global $lang_;

  flush();
  while (@ob_end_flush());
  $netsaved = $netconf->save();
  if ($netsaved == "OKSAVED") {
    $netmsg = $lang_->print_txt('SAVESUCCESSFULL');
  } else {
    $netmsg = $lang_->print_txt('SAVEERROR')." (".$netsaved.")";
  }

  return $netmsg;
}
?>