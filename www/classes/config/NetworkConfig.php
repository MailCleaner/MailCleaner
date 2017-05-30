<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * we can have multiple network interfaces
 */
require_once('config/IFace.php');


define(RESOLV_FILE, "/etc/resolv.conf");
define(IFACE_DEV_FILE, "/proc/net/dev");

/**
 * this class take car of the whole network configuration of the system
 */
class NetworkConfig
{
  /**
   * list of interface available on system
   * @var  array
   */
  private $interfaces_ = array();

  /**
   * global network properties
   * @var  $array
   */
  private $props_ = array(
                      'dns_servers' => "",
                      'search_domains' => ""
                   );

 
  /**
   * actually configured interface (in order not to restart all interfaces)
   * @var  string
   */
  private $config_if_;


  /**
   * set a preference
   * @param  $pref  string  preference name
   * @param  $value mixed   preference value
   * @return        boolean true on success, false on failure
   */
  public function setProperty($pref, $value) {
    if (isset($this->props_[$pref])) {
      $this->props_[$pref] = $value;
      return true;
    }
    return false;
  }
   
  /**
   * get a preference value
   * @param  $pref  string preference name
   * @return        mixed  preference value
   */
  public function getProperty($pref) {
    if (isset($this->props_[$pref])) {
      return $this->props_[$pref];
    }
    return "";
  }
  
  /**
   * set the interface that has just been configured
   * @param $if  string  interface name
   * @return     boolean true on success, false on failure
   */
  public function setConfiguredIF($if) {
    if (isset($this->interfaces_[$if])) {
      $this->config_if_ = $if;
      return true;
    }
    return false;
  }
  
  /**
   * add an interface
   * @param  $iface  string  interface name
   * @return         boolean true on success,false on failure
   */
  private function addInterface($iface) {
    if (!is_string($iface)) {
      return false;
    }
      
    $this->interfaces_[$iface] = new Iface($iface);
    $this->interfaces_[$iface]->load();
    return true;
  }
   
  /**
   * get the interface object given by name
   * @param  $iface  string  interface name 
   * @return         IFace   interface object
   */
  public function getInterface($iface) {
    if (isset($this->interfaces_[$iface])) {
        return $this->interfaces_[$iface];
    }
    return null;
  }
  
  /**
   * load all interfaces available
   * @return  boolean  true on success, false on failure
   */
  public function load() {
    
    // search for available interfaces
    $lines = file(IFACE_DEV_FILE);
    $matches = array();
    foreach($lines as $line) {
      if (preg_match('/^\s*(\S+\d+)\:/', $line, $matches)) {
       $this->addInterface($matches[1]);
      }
    }

    // search for global network settings
    unset($lines);
    $lines = file(RESOLV_FILE);
    $search_domains = array();
    $dns_servers = array();
    foreach( $lines as $line) {
      if (preg_match('/^search\s+(\S+)/', $line, $matches)) {
        $search_domains[$matches[1]] = true;
      }
      if (preg_match('/^nameserver\s+(\S+)/', $line, $matches)) {
        $dns_servers[$matches[1]] = true;
      }
    }
    $this->setProperty('search_domains', $search_domains);
    $this->setProperty('dns_servers', $dns_servers);
  }
 
  /**
   * save the network configuration
   * @return   string  OKSAVED on success, error code on failure 
   */
  public function save() {
    $res_a = array();
    $res = "";

    $sudocmd = "/usr/bin/sudo";
    if (file_exists("/usr/sudo/bin/sudo")) {
      $sudocmd = "/usr/sudo/bin/sudo";
    }

    // first, shut down interface
    if (isset($this->config_if_) && $this->config_if_ != "") {
      $cmd = "$sudocmd /sbin/ifdown ".escapeshellarg($this->config_if_);
      exec($cmd, $res_a, $res);
      if ($res != 0) {
        return "CANNOTBRINGINTERFACEDOWN";
      }
    }
    
    // then edit the interfaces file
    $file = fopen(INTERFACE_FILE, 'w');
    if (!$file) { 
      return "CANNOTOPENINTERFACESFILE"; 
    }
    fwrite($file, "auto lo\n");
    fwrite($file, "iface lo inet loopback\n");

    // add configuration for each active interfaces
    foreach($this->interfaces_ as $k => $v) {
      $str = "\n".$this->interfaces_[$k]->getConfigString(); 
      fwrite($file, $str);
    }
    fclose($file);

    // edit the resolv file
    $file = fopen(RESOLV_FILE, 'w');
    if (!$file) { 
      return "CANNOTOPENRESOLVFILE"; 
    }
    // and add search domains
    foreach($this->getProperty('search_domains') as $sd => $active) {
      if (fwrite($file, "search $sd\n") === FALSE) {
        return "CANNOTWRITERESOLVFILE";
      };
    }
    // add dns servers
    foreach($this->getProperty('dns_servers') as $s => $active) {
      fwrite($file, "nameserver $s\n");
    }
    fclose($file);

    // and finally, restart interface
   if (isset($this->config_if_) && $this->config_if_ != "") {
     $cmd = "$sudocmd /sbin/ifup ".escapeshellarg($this->config_if_);
     exec($cmd, $res_a, $res);
     if ($res != 0) {
       return "CANNOTBRINGINTERFACEUP";
     }
   }
   return "OKSAVED";
  }

  /**
   * return the name of the first interface available
   * @return   string  first interface's name
   */
  public function getFirstInterface() {
    if (count($this->interfaces_) == 0) {
      return "";
    }
    return key($this->interfaces_);
  }

  /**
   * return the dns servers string, comma separated list
   * @return  string  snd servers list
   */
  public function getDNSString() {
    $ret  = "";
    foreach ($this->getProperty('dns_servers') as $server => $active) {
      $ret .= "$server, ";
    }
    $ret = rtrim($ret);
    $ret = rtrim($ret, '\,');
    return $ret;
  }
  
  /**
   * return the search domains list, comma separated list
   * @return  string  search domains list
   */
  public function getSearchDomainsString() {
    $ret = "";
    foreach ($this->getProperty('search_domains') as $domain => $active) {
      if ($active) {
        $ret .= "$domain, ";
      }
    }
    $ret = rtrim($ret);
    $ret = rtrim($ret, '\,');
    return $ret;
  }

  /**
   * set preferences
   * @param   $pref   string  preferences name
   * @param   $value  string  preference values
   * @return          boolean true on success, false on failure
   */
  public function setPref($pref, $value) {
    switch ($pref) {
      case "interface":
        $this->config_if_ = $value;
        break;
      case "ip":
      case "netmask":
      case "gateway":
        $this->getInterface($this->config_if_)->setProperty($pref, $value);
      case "dnsservers":
        $this->setDNSServers($value);
        break;
      case "searchdomains":
        $this->setSearchDomains($value);
        break;
      default:
        break;
    }
  }
  
  /**
   * set the dns server from the posted string (comma separated)
   * @param  $servers  string  the posted servers string
   * @return           boolean true on success, false on failure
   */
  private function setDNSServers($servers) {
     if (!is_string($servers)) {
        return false;
     }
     $dns = split('\,', $servers);
     $servs = array();
     foreach ($dns as $s) {
        $servs[trim($s)] = true;
     }
     $this->setProperty('dns_servers', $servs);
     return true;
  }

  /**
   * set the search domains
   * @param  $domains  string  domains list to be searched
   * @return           boolean true on success, false on failure
   */
  private function setSearchDomains($domains) {
     if (!is_string($domains)) {
        return false;
     }
     $d = split('\,', $domains);
     $doms = array();
     foreach ($d as $s) {
       $doms[trim($s)] = true;
     }
     $this->setProperty('search_domains', $doms);
     return true; 
  }
  
  /**
   * return the interfaces as an array
   * @return   array  interfaces
   */
  public function getInterfaces() {
    $ret = array();
    foreach ($this->interfaces_ as $if => $o) {
      $ret[$if] = $if;  
    }
    return $ret;
  }
}
?>
