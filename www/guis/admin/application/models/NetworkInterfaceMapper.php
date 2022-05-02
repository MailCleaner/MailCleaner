<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Network interface mapper
 */

class Default_Model_NetworkInterfaceMapper
{
	protected $_interface_file = '/etc/network/interfaces';
	
    public function find($if, Default_Model_NetworkInterface $interface)
    {
    	$interface->setName($if);
    	
    	## parse network file for interface
    	if (file_exists($this->_interface_file)) {
    		$interface_file = file($this->_interface_file);

    		$in_if = 0;
    		$in_virtual = '';
            foreach ($interface_file as $line) {
            	if (preg_match('/^\s*iface\s+(\S+)\s+(inet|inet6)\s+(static|manual)/', $line, $matches)) {
            		$ifname = $matches[1];
                	$current_class = $matches[2];
                	$ifmode = $matches[3];
                	$in_virtual = '';
            		if (preg_match("/^(\S+)\:\d+/", $ifname, $matches)) {
            		    $in_virtual = $matches[1];
            		} else {
                	    if ($ifname == $if) {
                	        $in_if = 1;
            	            $interface->setCurrentClass($current_class);
            	            $interface->setIfMode($ifmode);
            	        } else {
            	    	    $in_if = 0;
            	        }
            		}
            	}
            	if ($in_virtual == $interface->getName() && preg_match('/^\taddress\s+(\S+)/', $line, $matches)) {
                   $addr = $matches[1];
                   if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $addr)) {
            	     $interface->addIPv4VirtualAddress($addr);
                   }
            	   continue;
            	}
            	if (!$in_if)
            	   continue;

            	if (preg_match('/^\s*(\S+)\s+(.*)/', $line, $matches) && $in_virtual == '') {
            		$key = $matches[1];
            	    $interface->setParam($key, $matches[2]);   
            	}
            }
    	}
    	
    	## fetch ifconfig values for auto-assigned addresses
    	$ifconfig = `/sbin/ifconfig`;
    	$current_int = '';
    	foreach (preg_split('/\n/', $ifconfig) as $line) {
    		if (preg_match('/^(\S+)/', $line, $matches)) {
    			$current_int = $matches[1];
    		}
    		if ($current_int == $interface->getName()) {
         		if (preg_match('/^\s*inet addr:(\S+)/', $line, $matches)) {
        			$interface->setIPv4Param('auto_address', $matches[1]);
    	    	}
    		    if (preg_match('/^\s*inet6 addr: (\S+)\s+Scope:Global/', $line, $matches)) {
    			    $interface->setIPv6Param('auto_address', $matches[1]);
    		    }
    		}
    	}
    }
    
    public function fetchAll() {
        $entries = array();
        ## first check for configured interfaces
        if (file_exists($this->_interface_file)) {
          $interface_file = file($this->_interface_file);

          foreach ($interface_file as $line) {
          	if (preg_match('/^\s*iface\s+(\S+)\s+(inet|inet6)\s+(static|manual|dhcp)/', $line, $matches)) {
          		$if_name = $matches[1];
          		if (preg_match('/:\d+/', $if_name)) {
          			continue;
          		}
          		if (!isset($entries[$if_name])) {
              		$entries[$if_name] = new Default_Model_NetworkInterface();
              		$entries[$if_name]->find($if_name);
          		}
          	}
          }
          
        }
        
        ## next check for physical interfaces not configured
        $proc_test_physical_file = '/proc/sys/net/ipv4/neigh/__INTERFACE__';
        $physical_name = 'eth__ID__';
        $physical_ifs = array();
        for ($i=0; $i < 10; $i++) {
        	$if_name = preg_replace('/__ID__/', $i, $physical_name);
        	$if_test_file = preg_replace('/__INTERFACE__/', $if_name, $proc_test_physical_file);
        	if (file_exists($if_test_file) && (!isset($entries[$if_name]))) {
        		if (!isset($entries[$if_name])) {
            		$entries[$if_name] = new Default_Model_NetworkInterface();
            		$entries[$if_name]->setName($if_name);
        		}
        	}
        }
        ksort($entries);        
        return $entries;
    }
    
    public function save($model) {
    	
    	if (Zend_Registry::get('restrictions')->isRestricted('NetworkInterface', 'submit')) {
    		return 'NOK could not save due to restricted feature';
    	}
    	
    	$tmpfile = '/tmp/mc_initerfaces.tmp';

        $has_ipv6 = 0;
        foreach ($this->fetchAll() as $interface) {
            if ($interface->getIPv6Param('mode') != 'disabled') {
                $has_ipv6 = 1;
            }
	}
    	
    	$txt = "auto lo\n";
        $txt .= "iface lo inet loopback\n";
        if ($has_ipv6) {
            $txt .= "\tpre-up modprobe ipv6\n";
            $txt .= "\tpost-up echo 0 > /proc/sys/net/ipv6/conf/lo/disable_ipv6\n";
        } else {
            $txt .= "\tpost-up echo 1 > /proc/sys/net/ipv6/conf/lo/disable_ipv6\n";
        }
    	
    	foreach ($this->fetchAll() as $interface) {
    		if ($interface->getName() == $model->getName()) {
    			continue;
    		}
    		$txt .= "\n";
    		$txt .= $interface->getConfigText();
    	}
    	$txt .= "\n";
    	$txt .= $model->getConfigText();
	$txt .= "source /etc/network/interfaces.d/*.conf\n";
    	$written = file_put_contents($tmpfile, $txt);
    	if ($written) {
    	   $soapres = Default_Model_Localhost::sendSoapRequest('Config_saveInterfaceConfig', null);
           return $soapres;
    	}
    	return 'NOK could not write config file';
    }
}
