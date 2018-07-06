<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * an external access is composed of rules
 */
require_once("config/ExternalAccessRule.php");

/**
 * this class takes care of the external access rules used to configure the firewall
 */
class ExternalAccess
{

  /**
   * actual service
   * @var string
   */
   private $service_ = "";

  /**
   * list of access rules
   * @var array
   */
   private $access_ = array();
   
   
/**
 * load rule set from database
 * @param  $service  string  service to be loaded
 * @return           boolean true on success, false on failure
 */
public function load($service) {
  if (!isset($service) || !isset(ExternalAccessRule::$available_services_[$service]))  { 
    return false; 
  }
  $this->service_ = $service;

  $db_slaveconf = DM_SlaveConfig :: getInstance();
  $this->service_ = $db_slaveconf->sanitize($service);
  $query = "SELECT id FROM external_access WHERE service='".$this->service_."'";

  $list = $db_slaveconf->getList($query);
  if (!is_array($list)) {
    return false;
  }

  unset($this->access_);
  $this->access_ = array();
  foreach ($list as $id) {
    $this->access_[$id] = new ExternalAccessRule($this->service_);
    $this->access_[$id]->load($id);
  }
  
  return true;
}


/**
 * save all the rules in database
 * @return  string  'OKSAVED' on success, error code on failure
 */
public function save() {
    
  foreach ($this->access_ as $key => $access) {
    $ret = $access->save();
    if ($ret != "OKSAVED" && $ret != "OKADDED") {
      return $ret;
    }
  }
  return "OKSAVED";
}

/**
 * return the allowed ips string for the service
 * @return  string  ip addresses allowed for this service
 */
public function getAllowedIPSString() {
  $ret = "";
  foreach ($this->access_ as $access) {
    $ret .= $access->getPref('allowed_ip').":";
  }
  $ret = rtrim($ret);
  $ret = rtrim($ret, '\:');
  return $ret;
}

/**
 * set the allowed ip given in field in the correct rules
 * @param  $ips  string  ip addresses list given
 * @return       boolean true on success, false on failure
 */
public function setIPS($ips) {
  $ip_array = split(':', $ips);
  $tmp = array();

  // first delete rules not needed anymore
  $inserted = 0;
  foreach ($this->access_ as $id => $a) {
    if (!in_array($a->getPref('allowed_ip'), $ip_array)) {
      $a->delete();
      unset($this->access_[$id]);      
    }
  }
  
  // then check for new/existing rules
  foreach($ip_array as $ip) {
    if (!preg_match('/^\d+\.\d+\.\d+\.\d+(\/\d+)?$/', $ip, $tmp)) {
      continue;
    }
   
    // create new rule 
    if (!$this->allowedIP($ip)) {
        $this->access_['_'.$inserted] = new ExternalAccessRule($this->service_);
        $this->access_['_'.$inserted]->setPref('allowed_ip', $ip);
        $inserted++;
    }
  }
}


/**
 * get the default port for the service
 * @return  string  default port
 */
public function getDefaultPort() {
  if (isset(ExternalAccessRule::$available_services_[$this->service_][0])) {
    return  ExternalAccessRule::$available_services_[$this->service_][0];
  }   
  return '0';
}

/**
 * get the default protocol for the service
 * @return  string  default protocol
 */
public function getDefaultProtocol() {
  if (isset(ExternalAccessRule::$available_services_[$this->service_][1])) {
    return  ExternalAccessRule::$available_services_[$this->service_][1];
  }   
  return '';
}

/**
 * check if a rule for this ip exist
 * @param  $ip  string  ip address to check
 * @return      boolean true if exists, false otherwise
 */
private function allowedIP($ip) {
  foreach ($this->access_ as $a) {
    if ($a->getPref('allowed_ip') == $ip) {
        return true;
    } 
  }
  return false;
}

}
?>
