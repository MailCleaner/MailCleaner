<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Network interface
 */

class Default_Model_NetworkInterface
{
	protected $_ifname;
	protected $_params;
	
	protected $_ipv4_params = array(
	    'mode' => 'disabled',
	    'address' => '0.0.0.0',
	    'network' => '',
	    'netmask' => '',
	    'gateway' => '',
	    'broadcast' => '',
	    'mtu' => 1500,
	    'metric' => 1,
	    'pre-up' => array('modprobe ipv6'),
	    'post-up' => array('echo 1 > /proc/sys/net/ipv6/conf/all/disable_ipv6'),
	    'auto_address' => '',
	    'virtual_addresses' => array()
	);
	
	protected $_ipv6_params = array (
	    'mode' => 'disabled',
	    'address' => '',
	    'netmask' => '',
	    'gateway' => '',
	    'pre-up' => array(),
	    'post-up' => array(),
	    'auto_address' => ''
	);
	protected $_current_class = '';
	
	protected $_mapper;

	public function __construct() {}
	
	public function setCurrentClass($class) {
		if ($class == 'inet' || $class == 'inet6') {
			$this->_current_class = $class;
		}
	}
	protected function setTypedParam(&$type_array, $param, $value) {
		if ($param == 'pre-up') {
			if (!in_array($value, $type_array['pre-up']) && !preg_match('/(disable_ipv6|accept_ra|autoconf)$/', $value)) {
    			array_push($type_array['pre-up'], $value);
			}
		} elseif ($param == 'post-up') {
			if (!in_array($value, $type_array['post-up']) && !preg_match('/(disable_ipv6|accept_ra|autoconf)$/', $value)) {
			    array_push($type_array['post-up'], $value);
			}
		} elseif ($param == 'virtual_addresses') {
			if (is_array($value)) {
				$type_array[$param] = $value;
			} else {
				$type_array[$param] = preg_split("/[\s;:,]+/", $value);
			}
		} elseif (isset($type_array[$param])) {
			$type_array[$param] = $value;
		}
	}
	
	public function addIPv4VirtualAddress($address) {
		if (!in_array($address, $this->_ipv4_params['virtual_addresses'])) {
    		array_push($this->_ipv4_params['virtual_addresses'], $address);
		}
	}
	public function setIPv4Param($param, $value) {
		$this->setTypedParam($this->_ipv4_params, $param, $value);
	}
	public function getIPv4Param($param) {
		return $this->_ipv4_params[$param];
	}
	public function getIPv6Param($param) {
		return $this->_ipv6_params[$param];
	}
	public function setIPv6Param($param, $value) {
		$this->setTypedParam($this->_ipv6_params, $param, $value);
	}
	
	public function setParam($param, $value) {
		if ($this->_current_class == 'inet') {
			$this->setTypedParam($this->_ipv4_params, $param, $value);
		} elseif ($this->_current_class == 'inet6') {
			$this->setTypedParam($this->_ipv6_params, $param, $value);
		}
	}
	
	public function getParameter($param) {
		if (isset($this->_params[$param])) {
			return $this->_params[$param];
		}
		return '';
	}
	
	public function setName($ifname) {
		$this->_ifname = $ifname;
	}
	public function getName() {
		return $this->_ifname;
	}
    
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_NetworkInterfaceMapper());
        }
        return $this->_mapper;
    }

    public function find($interface)
    {
        $this->getMapper()->find($interface, $this);
        return $this;
    }
    
    public function fetchAll()
    {
        return $this->getMapper()->fetchAll();
    }
    
    public function fetchFirst($interfaces)
    {    	
    	if (!is_array($interfaces) || !count($interfaces)) {
        	$entries = $this->fetchAll();
    	} else {
    		$entries = $interfaces;
    	}
    	if (count($entries) > 0) {
    	    return current($entries);
    	}
    	return new Default_Model_NetworkInterface();
    }
    
    public function save()
    {
    	return $this->getMapper()->save($this);
    }
    
    public function setIfMode($mode) {
      if ($this->_current_class == 'inet') {
        $this->setIPv4Param('mode', 'disabled');
      	if ($mode == 'static' || $mode == 'dhcp') {
      		$this->setIPv4Param('mode', $mode);
      	}
      } elseif ($this->_current_class == 'inet6') {
      	$this->setIPv6Param('mode', 'disabled');
      	if ($mode == 'static' || $mode == 'manual') {
      		$this->setIPv6Param('mode', $mode);
      	}
      }
    }
    public function getIfMode($class) {
    	if ($class == 'inet' || $class='ipv4') {
    		return $this->getIPv4Param('mode');
    	} elseif ($class == 'inet6'|| $class='ipv6') {
    		return $this->getIPv6Param('mode');
    	}
    }
    
    public function getConfigText() {
      $ret = '';
      $ipcalc_cmd = "/usr/bin/ipcalc -nb ".$this->getIPv4Param('address')." ".$this->getIPv4Param('netmask');
      $ipcalc_res = `$ipcalc_cmd`;
      foreach (preg_split('/\n/', $ipcalc_res) as $line) {
      	// search for netmask
      	if (preg_match('/Netmask:\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
      		$this->setIPv4Param('netmask', $matches[1]);
      	}
        // search for Broadcast
      	if (preg_match('/Broadcast:\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
      		$this->setIPv4Param('broadcast', $matches[1]);
      	}
        // search for network
      	if (preg_match('/Network:\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
      		$this->setIPv4Param('network', $matches[1]);
      	}
      }
      
      if ($this->getIPv4Param('mode') != 'disabled' || $this->getIPv6Param('mode') != 'disabled') {
          $ret .= "auto ".$this->getName()."\n";
          $ret .= "allow-hotplug ".$this->getName()."\n";
      }
      
      ## create IPv4 configuration
      if ($this->getIPv4Param('mode') == 'static') {
          $ret .= "iface ".$this->getName()." inet static\n";
          foreach (array('address', 'netmask', 'network', 'broadcast', 'gateway') as $key) {
      	      if ($this->getIPv4Param($key) != "") {
      		      $ret .= "\t$key ".$this->getIPv4Param($key)."\n";
      	      }
          }
      } elseif ($this->getIPv4Param('mode') == 'dhcp') {
          $ret .= "iface ".$this->getName()." inet dhcp\n";
      }
      if ($this->getIPv6Param('mode') == 'disabled' && $this->getIPv4Param('mode') != 'disabled') {
          if (file_exists("/proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6")) {
              $ret .= "\tpre-up echo 1 > /proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6"."\n";
          }
      }
      
      $sub_int = 0;
      foreach ($this->getIPv4Param('virtual_addresses') as $add) {
          if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/', $add)) {
              continue;
          }
      	  $ret .= "auto ".$this->getName().":".$sub_int."\n";
      	  $ret .= 'iface '.$this->getName().":".$sub_int." inet static\n";
      	  $ret .= "\taddress ".$add."\n";
      	  $ret .= "\tnetmask ".$this->getIPv4Param('netmask')."\n";
      	  $sub_int++;
      }
      ### create IPv6 configuration
      $ret .= "\n";
      if ($this->getIPv6Param('mode') == 'static') {
      	  $ret .= "iface ".$this->getName()." inet6 static\n";
          if (file_exists("/proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6")) {
      	      $ret .= " \tpre-up echo 0 > /proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6\n";
          }
      	  foreach (array('address', 'netmask', 'gateway') as $key) {
      	  	  if ($this->getIPv6Param($key) != "") {
      	  	  	$ret .= "\t$key ".$this->getIPv6Param($key)."\n";
      	  	  }
      	  }
      	  $ret .= " \tpost-up echo 0 > /proc/sys/net/ipv6/conf/".$this->getName()."/accept_ra\n";
      	  $ret .= " \tpost-up echo 0 > /proc/sys/net/ipv6/conf/".$this->getName()."/autoconf\n";
          if (file_exists("/proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6")) {
      	      $ret .= " \tpost-up echo 0 > /proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6\n";
          }
      } elseif ($this->getIPv6Param('mode') == 'manual') {
      	  $ret .= "iface ".$this->getName()." inet6 manual\n";
          if (file_exists("/proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6")) {
      	      $ret .= " \tpre-up echo 0 > /proc/sys/net/ipv6/conf/".$this->getName()."/disable_ipv6\n";
          }
      	  $ret .= " \tpre-up echo 1 > /proc/sys/net/ipv6/conf/".$this->getName()."/accept_ra\n";
      	  $ret .= " \tpre-up echo 1 > /proc/sys/net/ipv6/conf/".$this->getName()."/autoconf\n";
      }
      
      return $ret;
    }
}
