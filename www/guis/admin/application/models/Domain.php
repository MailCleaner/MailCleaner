<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2017 Mentor Reka <reka.mentor@gmail.com>
 * Domain
 */

class Default_Model_Domain
{
	protected $_id;
	protected $_values = array(
      'name' => '',
      'active' => true,
      'destination'    => '',
      'prefs' => 0,
      'callout' => 'true',
      'adcheck' => 'false',
      'altcallout' => '',
      'addlistcallout' => 'false',
      'greylist' => 'false',
      'forward_by_mx' => 'false',
      'relay_smarthost' => 0,
      'destination_smarthost'    => '',
	);
	protected $_aliases = array();

	protected $_configpanels = array('general', 'delivery',
		'addressverification', 'preferences',
		'authentication', 'filtering', 'advanced',
		'outgoing', 'archiving', 'templates'
	);

	protected $_prefs;
	protected $_default_prefs;
	protected $_destinations = array();
	protected $_destinations_smarthost = array();
	protected $_destination_options = array();
	protected $_destination_options_smarthost = array();
	protected $_destination_port = 25;
	protected $_destination_port_smarthost = 25;
	protected $_destination_usemx = false;
	protected $_destinationtest_finished = false;
	protected $_destination_action_options = array('loadbalancing' => 'randomize', 'failover' => 'no_randomize');
	protected $_destination_action_options_smarthost = array('loadbalancing' => 'randomize', 'failover' => 'no_randomize');

	protected $_calloutconnector = 'smtp';
	protected $_callouttest_finished  = false;

	protected $domain_;
	protected $auth_;
	protected $fetcher_;

	protected $_spamActions = array('tag' => 1, 'quarantine' => 2, 'drop' => 3);
	protected $_summaryFrequencies = array('none' => 0, 'daily' => 1, 'weekly' => 2, 'monthly' => 3);
	protected $_summaryTypes = array('html' => 'html', 'plain text' => 'text', 'digest' => 'digest' ) ;
	protected $_mapper;


	public function setId($id) {
		$this->_id = $id;
	}
	public function getId() {
		return $this->_id;
	}

	public function setParam($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}

	public function getParam($param) {
		$ret = null;
		if (array_key_exists($param, $this->_values)) {
			$ret = $this->_values[$param];
		}
		if ($ret == 'false') {
			return 0;
		}
		return $ret;
	}

	public function setPrefs($prefs) {
		$this->_prefs = $prefs;
	}
	public function getPrefs() {
		return $this->_prefs;
	}

	public function getPref($prefname) {
		$ret = null;
		if (!$this->_prefs) {
			if (!$this->_default_prefs) {
				$pref = new Default_Model_DomainPref();
				$pref->find(1);
				$this->_default_prefs = $pref;
			}
			$ret = $this->_default_prefs->getParam($prefname);
		} else {
			$ret = $this->_prefs->getParam($prefname);
		}
		if ($ret == 'false') {
			return 0;
		}
		return $ret;
	}

	public function setPref($prefname, $prefvalue) {
		return $this->_prefs->setParam($prefname, $prefvalue);
	}

	public function copyPrefs($domain) {
		foreach ($this->_values as $key => $value) {
			if ($key != 'name') {
				$this->setParam($key, $domain->getParam($key));
			}
		}
		$this->loadDestinationRule($domain->getDestinationRule());
		$this->loadDestinationRule_smarthost($domain->getDestinationRule_smarthost());
		if (!$this->_prefs) {
			$this->_prefs = new Default_Model_DomainPref();
		}
		$this->_prefs->copy($domain->getPrefs());
	}

	public function getAvailableParams() {
		$ret = array();
		foreach ($this->_values as $key => $value) {
			$ret[]=$key;
		}
		return $ret;
	}

	public function getParamArray() {
		return $this->_values;
	}

	public function setMapper($mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}

	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Default_Model_DomainMapper());
		}
		return $this->_mapper;
	}

	public function find($id)
	{
		$this->getMapper()->find($id, $this);
		$pref = new Default_Model_DomainPref();
		$pref->find($this->getParam('prefs'));
		$this->setPrefs($pref);
		$this->loadAliases();
		$this->loadDestinationRule($this->getParam('destination'));
		$this->loadDestinationRule_smarthost($this->getParam('destination_smarthost'));
		$this->loadCalloutConnector();
		return $this;
	}
	public function findByName($name) {
		$this->getMapper()->findByName($name, $this);
		$pref = new Default_Model_DomainPref();
		if ($name == '__global__') {
			$pref->find(1);
            $this->setParam('name', $name);
		} else {
			$pref->find($this->getParam('prefs'));
		}
		$this->setPrefs($pref);
		$this->loadAliases();
		$this->loadDestinationRule($this->getParam('destination'));
		$this->loadDestinationRule_smarthost($this->getParam('destination_smarthost'));
		$this->loadCalloutConnector();
		return $this;
	}

        public function getDistinctDomainsCount() {
                return $this->getMapper()->getDistinctDomainsCount();        
        }

	public function fetchAll() {
		return $this->getMapper()->fetchAll();
	}

	public function fetchAllName($params = NULL) {
		return $this->getMapper()->fetchAllName($params);
	}
	 
	public function save()
	{
		if (!$this->_prefs) {
			$this->_prefs = new Default_Model_DomainPref();
		}
                if ($this->getParam('name') == '__global__') {
                    $this->_prefs->save(true);
                } else {
		    $this->_prefs->save();
                }
		$this->setParam('prefs', $this->_prefs->getId());
		$this->setParam('destination', $this->getDestinationRule());
		$this->setParam('destination_smarthost', $this->getDestinationRule_smarthost());
		
                if ($this->getParam('name') == '') {
                  return;
                } 
                $givenparams = $this->getParamArray();
		$ret = $this->getMapper()->save($this);

        $slave = new Default_Model_Slave();
        ## first dump all domain's preferences
		$params = array('what' => 'domains', 'domain' => $this->getParam('name'));
		$res = $slave->sendSoapToAll('Service_silentDump', $params);

        ## then save main domains list in case we're adding
        $params = array('what' => 'domains');
        $res = $slave->sendSoapToAll('Service_silentDump', $params);
		
		return $ret;
	}

	public function delete()
	{
		if ( ! count($this->_aliases) > 0) {
			$this->_prefs->delete();
		}
		$ret = $this->getMapper()->delete($this);
		
		// Check if this domains has stats (counts) and delete them
                $counts_path = "/var/mailcleaner/spool/mailcleaner/counts/";
                $domain_counts = $counts_path . trim($this->getParam('name'));
                if (file_exists($domain_counts) && is_dir($domain_counts)) {
                        exec('rm -rf '.escapeshellarg($domain_counts));
                }

		$params = array('what' => 'domains');
        $slave = new Default_Model_Slave();
        $res = $slave->sendSoapToAll('Service_silentDump', $params);
        
		return $ret;
	}

	public function getConfigPanels() {
		$panels = array();
		$t = Zend_Registry::get('translate');
		foreach ($this->_configpanels as $panel) {
			$panels[$panel] = $t->_($panel);
		}
		return $panels;
	}

	public function getPreviousPanel($panel) {
		for ($i=0; $i < count($this->_configpanels); $i++) {
			if ($i > 0 && $this->_configpanels[$i] == $panel) {
				return $this->_configpanels[$i-1];
			}
		}
		return '';
	}

	public function getNextPanel($panel) {
		for ($i=0; $i < count($this->_configpanels); $i++) {
			if ($i < count($this->_configpanels)-1 && $this->_configpanels[$i] == $panel) {
				return $this->_configpanels[$i+1];
			}
		}
		return '';
	}

	protected function loadAliases() {
		$ret = array();
		if (!$this->getParam('name')) {
			return $ret;
		}
		$this->_aliases = $this->getMapper()->getAliases($this);
	}

	public function getAliases() {
		return $this->_aliases;
	}

	public function setAliases($aliases) {
		if (!array($aliases)) {
			return true;
		}
		$this->_aliases = array();
		require_once('Validate/DomainName.php');
		$validator = new Validate_DomainName();
		foreach ($aliases as $alias) {
			$alias = preg_replace('/[\n\s]/', '', $alias);
			if ($alias == '') {
				continue;
			}
			$alias = strtolower($alias);
			if (!$validator->isValid($alias)) {
				throw new Exception("Invalid domain name ($alias)");
			}
			$domain = new Default_Model_Domain();
			$domain->findByName($alias);
			if ($domain->getParam('name') && $domain->getParam('prefs') != $this->getParam('prefs')) {
				throw new Exception("Domain already configured ($alias)");
			}
			if (! in_array($alias, $this->_aliases)) {
				$this->_aliases[] = $alias;
			}
		}

	}

	public function saveAliases() {
		require_once('Validate/DomainName.php');
		$validator = new Validate_DomainName();
		 
		foreach ($this->_aliases as $alias) {
			if ($validator->isValid($alias)) {
				$domain = new Default_Model_Domain();
				$domain->findByName($alias);
				$domain->setParam('name', $alias);
				$domain->setAsAliasOf($this);
			}
		}
		 
		## delete removed aliases
		$listed = $this->getMapper()->getAliases($this);
		foreach ($listed as $l) {
			if (!in_array($l, $this->_aliases) && $l != $this->getParam('name')) {
				$domain = new Default_Model_Domain();
				$domain->findByName($l);
				$domain->delete();
			}
		}
                $params = array('what' => 'domains');
                $slave = new Default_Model_Slave();
                $res = $slave->sendSoapToAll('Service_silentDump', $params);
	}

	public function setAsAliasOf($domain) {
		foreach(array('destination', 'callout', 'altcallout', 'adcheck', 'forward_by_mx', 'greylist', 'prefs', 'active', 'destination_smarthost') as $param) {
			$this->setParam($param, $domain->getParam($param));
		}
		return $this->getMapper()->save($this);
	}

	/**
	 * Destination managment
	 */
	public function loadDestinationRule($rule) {
		$servers = $rule;
		$options = '';
		$this->_destinations = array();
		if (preg_match('/^"?(\S+)"?\s+(.*)/', $rule, $matches)) {
			$servers = $matches[1];
			$options = $matches[2];
		}
		 
		$ports = array('25' => 0);
		$servers = preg_replace('/::+/', '%', $servers);
		#if (preg_match('/\/(mx|MX)/', $servers)) {
		#	$this->setDestinationUseMX(true);
		#	$servers = preg_replace('/\/(mx|MX)/', '', $servers);
		#}

		$servers = preg_replace('/\//', '%', $servers);
		foreach (preg_split('/:/', $servers) as $server) {
			$server = preg_replace('/\s/', '', $server);
			$server = preg_replace('/\%+/', '::', $server);
                        if (preg_match('/^:/', $server)) {
                            continue;
                        }
			$s = array('host' => $server, 'port' => '');
			if (preg_match('/^(\S+)::(\d+)$/', $server, $matches) && $matches[1] != '') {
				$s['host'] = $matches[1];
				$s['port'] = $matches[2];
				if (!isset($ports["$matches[2]"])) {
					$ports["$matches[2]"] = 0;
				}
				$ports["$matches[2]"]++;
			} else {
				if ($server != "+") {
					$ports['25']++;
				};
			}
                        if ($s['host'] != '') {
			  $this->_destinations[] = $s;
                        }
		}
		## find default port:
		arsort($ports);
		$this->_destination_port = key($ports);
		 
		$i = 0;
		foreach ($this->_destinations as $dest) {
			if ($dest['port'] == $this->_destination_port) {
				$this->_destinations[$i]['port'] = '';
			}
			$i++;
		}
	
		foreach (preg_split("/\s+/", $options) as $option) {
			$this->setDestinationOption($option);
		}
	}

	/**
	 * Destination managment
	 */
	public function loadDestinationRule_smarthost($rule) {
		$servers = $rule;
		$options = '';
		$this->_destinations_smarthost = array();
		if (preg_match('/^"?(\S+)"?\s+(.*)/', $rule, $matches)) {
			$servers = $matches[1];
			$options = $matches[2];
		}
		 
		$ports_smarthost = array('25' => 0);
		$servers = preg_replace('/::+/', '%', $servers);
		#if (preg_match('/\/(mx|MX)/', $servers)) {
		#	$this->setDestinationUseMX(true);
		#	$servers = preg_replace('/\/(mx|MX)/', '', $servers);
		#}

		$servers = preg_replace('/\//', '%', $servers);
		foreach (preg_split('/:/', $servers) as $server) {
			$server = preg_replace('/\s/', '', $server);
			$server = preg_replace('/\%+/', '::', $server);
                        if (preg_match('/^:/', $server)) {
                            continue;
                        }
			$s = array('host' => $server, 'port' => '');
			if (preg_match('/^(\S+)::(\d+)$/', $server, $matches) && $matches[1] != '') {
				$s['host'] = $matches[1];
				$s['port'] = $matches[2];
				if (!isset($ports["$matches[2]"])) {
					$ports["$matches[2]"] = 0;
				}
				$ports["$matches[2]"]++;
			} else {
				if ($server != "+") {
					$ports['25']++;
				};
			}
                        if ($s['host'] != '') {
			  $this->_destinations_smarthost[] = $s;
                        }
		}
		## find default port:
		arsort($ports);
		$this->_destination_port_smarthost = key($ports);
		 
		$i = 0;
		foreach ($this->_destinations_smarthost as $dest) {
			if ($dest['port'] == $this->_destination_port_smarthost) {
				$this->_destinations_smarthost[$i]['port'] = '';
			}
			$i++;
		}
	
		foreach (preg_split("/\s+/", $options) as $option) {
			$this->setDestinationOption($option);
		}
	}


	public function setDestinationOption($option) {
                foreach ($this->_destination_action_options as $key => $value) {
                   if ($value == $option || $key == $option) {
                        if (!in_array($value, $this->_destination_options)) {
                            $this->_destination_options = array();
			    $this->_destination_options[$value] = $value;
                        }
                   }
		}
	}
	public function setDestinationOption_smarthost($option) {
                foreach ($this->_destination_action_options_smarthost as $key => $value) {
                   if ($value == $option || $key == $option) {
                        if (!in_array($value, $this->_destination_options_smarthost)) {
                            $this->_destination_options_smarthost = array();
			    $this->_destination_options_smarthost[$value] = $value;
                        }
                   }
		}
	}

	public function getDestinationActionOptions() {
		return $this->_destination_action_options;
	}
	public function getDestinationActionOptions_smarthost() {
		return $this->_destination_action_options_smarthost;
	}

	public function getActiveDestinationOptions() {
		return $this->_destination_options;
	}
	public function getActiveDestinationOptions_smarthost() {
		return $this->_destination_options_smarthost;
	}
	public function getDestinationMultiMode() {
		$str = '';
		foreach ($this->getActiveDestinationOptions() as $act_value) {
			$str .= ",".implode(',', array_keys($this->_destination_action_options, $act_value));
		}
		return preg_replace('/,/', '', $str);
	}

	public function getDestinationMultiMode_smarthost() {
		$str = '';
		foreach ($this->getActiveDestinationOptions_smarthost() as $act_value) {
			$str .= ",".implode(',', array_keys($this->_destination_action_options_smarthost, $act_value));
		}
		return preg_replace('/,/', '', $str);
	}

	public function getDestinationFieldString() {
		$str = '';
		foreach ($this->_destinations as $destination) {
			if ($destination['port'] == '') {
				$destination['port'] = $this->_destination_port;
			}
			if (preg_match('/^([^:]+):+(\d+)/', $destination['host'], $matches)) {
				$destination['port'] = $matches[2];
				$destination['host'] = $matches[1]; 
			}
			$hoststr = $destination['host'].':'.$destination['port'];
			if ($destination['host'] == '+') {
				$hoststr = $destination['host'];
			}
			if ($destination['port'] == $this->_destination_port) {
				$hoststr = $destination['host'];
			}
			$str .= $hoststr."\n";
		}
		$str = preg_replace("/\n$/", '', $str);
		 
		return $str;
	}

	public function getDestinationFieldString_smarthost() {
		$str = '';
		foreach ($this->_destinations_smarthost as $destination_smarthost) {
			if ($destination_smarthost['port'] == '') {
				$destination_smarthost['port'] = $this->_destination_port_smarthost;
			}
			if (preg_match('/^([^:]+):+(\d+)/', $destination_smarthost['host'], $matches)) {
				$destination_smarthost['port'] = $matches[2];
				$destination_smarthost['host'] = $matches[1]; 
			}
			$hoststr = $destination_smarthost['host'].':'.$destination_smarthost['port'];
			if ($destination_smarthost['host'] == '+') {
				$hoststr = $destination_smarthost['host'];
			}
			if ($destination_smarthost['port'] == $this->_destination_port_smarthost) {
				$hoststr = $destination_smarthost['host'];
			}
			$str .= $hoststr."\n";
		}
		$str = preg_replace("/\n$/", '', $str);
		 
		return $str;
	}


        public function getDestinationFieldStringForAPI() {
                $str = '';
                foreach ($this->_destinations as $destination) {
                        if ($destination['port'] == '') {
                                $destination['port'] = $this->_destination_port;
                        }
                        if (preg_match('/^([^:]+):+(\d+)/', $destination['host'], $matches)) {
                                $destination['port'] = $matches[2];
                                $destination['host'] = $matches[1];
                        }
                        $hoststr = $destination['host'].':'.$destination['port'];
                        $str .= $hoststr.",";
                }
                $str = preg_replace("/,$/", '', $str);

                return $str;
        }

	public function setDestinationServersFieldString($string) {
		$this->_destinations = array();
		foreach (preg_split('/[^a-zA-Z0-9.\-_:]/', $string) as $line) {
			if (preg_match('/^\s*$/', $line)) {
				continue;
			}
			$line = preg_replace('/\s+/', '', $line);
			$s = array('host' => $line, 'port' => '');
			if (preg_match('/^(\S+):+(\d+)$/', $line, $matches)) {
				$s['host'] = $matches[1];
				$s['port'] = $matches[2];
			} else {
				$s['host'] = $line;
			}
			$this->_destinations[] = $s;
		}
		 
	}

        public function setDestinationServersFieldString_smarthost($string) {
		$this->_destinations_smarthost = array();
		foreach (preg_split('/[^a-zA-Z0-9.\-_:]/', $string) as $line) {
			if (preg_match('/^\s*$/', $line)) {
				continue;
			}
			$line = preg_replace('/\s+/', '', $line);
			$s = array('host' => $line, 'port' => '');
			if (preg_match('/^(\S+):+(\d+)$/', $line, $matches)) {
				$s['host'] = $matches[1];
				$s['port'] = $matches[2];
			} else {
				$s['host'] = $line;
			}
			$this->_destinations_smarthost[] = $s;
		}
		 
	}
	 
	public function getDestinationPort() {
		return $this->_destination_port;
	}
	public function setDestinationPort($port) {
		$this->_destination_port = $port;
	}
	public function getDestinationPort_smarthost() {
		return $this->_destination_port_smarthost;
	}
	public function setDestinationPort_smarthost($port) {
		$this->_destination_port_smarthost = $port;
	}

	public function getDestinationRule() {
		$str = '';
		foreach ($this->_destinations as $destination) {
			if ($destination['port'] == '') {
				$destination['port'] = $this->_destination_port;
			}
			#if ($this->getDestinationUseMX() && $destination['host'] != '+') {
			#	$destination['host'] = $destination['host'].'/mx';
			#}
		    if (preg_match('/^([^:]+):+(\d+)/', $destination['host'], $matches)) {
                $destination['port'] = $matches[2];
                $destination['host'] = $matches[1]; 
            }
			$hoststr = $destination['host'].'::'.$destination['port'];
			if ($destination['host'] == '+') {
				$hoststr = $destination['host'];
			}
			$str .= $hoststr.":";
		}
		$str = preg_replace("/\s*:\s*$/", '', $str);
		$str .= " byname ";
		foreach ($this->_destination_options as $option) {
			$str.= $option." ";
		}
		$str = preg_replace('/\s$/', '', $str);
		$str = preg_replace('/^\:.*/', '', $str);
		 
		return $str;
	}

	public function getDestinationRule_smarthost() {
		$str = '';
		foreach ($this->_destinations_smarthost as $destination_smarthost) {
			if ($destination_smarthost['port'] == '') {
				$destination_smarthost['port'] = $this->_destination_port_smarthost;
			}
			#if ($this->getDestinationUseMX() && $destination['host'] != '+') {
			#	$destination['host'] = $destination['host'].'/mx';
			#}
		    if (preg_match('/^([^:]+):+(\d+)/', $destination_smarthost['host'], $matches)) {
                $destination_smarthost['port'] = $matches[2];
                $destination_smarthost['host'] = $matches[1]; 
            }
			$hoststr = $destination_smarthost['host'].'::'.$destination_smarthost['port'];
			if ($destination_smarthost['host'] == '+') {
				$hoststr = $destination_smarthost['host'];
			}
			$str .= $hoststr.":";
		}
		$str = preg_replace("/\s*:\s*$/", '', $str);
		$str .= " byname ";
		foreach ($this->_destination_options_smarthost as $option) {
			$str.= $option." ";
		}
		$str = preg_replace('/\s$/', '', $str);
		$str = preg_replace('/^\:.*/', '', $str);
		 
		return $str;
	}


	public function getDestinationUseMX() {
                return $this->getParam('forward_by_mx');
                #if ($this->_destination_usemx) {
                if ( ($this->getParam('forward_by_mx') || $this->getParam('forward_by_mx') == 'true') && $this->getParam('forward_by_mx') != 'false' ) {
                   return 'true';
                }
                return 'false';
	}
	public function setDestinationUseMX($value) {
                $this->setParam('forward_by_mx', false); 
                if ($value) {
                   $this->setParam('forward_by_mx', true);
                }
                return;
		#$this->_destination_usemx = false;
                $this->setParam('forward_by_mx') == 'false';
		if ( ($value || $value == 'true') && $value != 'false') {
			#$this->_destination_usemx = true;
                        $this->setParam('forward_by_mx') == 'true';
		}
	}

	public function testDestinationsSMTP() {
		return 'OK passed';
	}
	 
	public function getDestinationServers() {
		return $this->_destinations;
	}

	public function getDestinationServers_smarthost() {
		return $this->_destinations_smarthost;
	}
	 
	public function destinationTestFinished() {
		return $this->_destinationtest_finished;
	}
	public function getDestinationTestStatus($reset) {
		$servers = $this->getDestinationServers();
		if (count($servers) < 1 || (count($servers) == 1 && $servers[0]['host'] == ''))  {
			$this->_destinationtest_finished = true;
			return array('nodestinationset' => array('status' => 'NOK', 'message' => 'nodestinationset'));
		}

		$file = sys_get_temp_dir()."/destinationSMTP.status";
		if (file_exists($file) && $reset) {
			unlink($file);
		}
		$content = array();
		if (file_exists($file)) {
			$content = file($file);
		}
		$whats = array();
		foreach ($content as $line) {
			if (preg_match('/\s*(\S+)\s*:\s*(OK|OKWAIT|OKSTART|NOK)\s+(.*)/', $line, $matches)) {
				$whats[$matches[1]]['status'] = $matches[2];
				$whats[$matches[1]]['message'] = $matches[3];
			}
		}

		foreach($servers as $s) {
			if ($s['port'] != '') {
				$shost = $s['host'].':'.$s['port'];
			} else {
				$shost = $s['host'].':'.$this->_destination_port;
			}
			if (!array_key_exists($shost, $whats)) {
				$whats[$shost]['status'] = 'OKSTART';
				$whats[$shost]['message'] = 'testing...';
				$str = $shost.":".$whats[$shost]['status']." ".$whats[$shost]['message']."\n";
				file_put_contents($file, $str, FILE_APPEND);
				return $whats;
			} elseif ($whats[$shost]['status'] == 'OKSTART') {
				$whats[$shost] = array('status' => 'OKWAIT', 'message' => 'testing...');
				// TODO: actually test destinations !!
				$server = new Default_Model_SMTPDestinationServer($shost);
				$whats[$shost] = $server->testDomain($this->getParam('name'));
				#$whats[$s['host']] = array('status' => 'OK', 'message' => 'done.');
				$str = $shost.":".$whats[$shost]['status']." ".$whats[$shost]['message']."\n";
				file_put_contents($file, $str, FILE_APPEND);
				return $whats;
			}
		}
		$this->_destinationtest_finished = true;
		return $whats;
	}
	 
	/*
	 * Callout managment
	 */
	public function loadCalloutConnector() {
                $this->_calloutconnector = 'none';
		if (preg_match ('/true/', $this->getParam('adcheck')) ) {
			$this->_calloutconnector = 'ldap';
		}
        if (preg_match ('/true/', $this->getParam('addlistcallout')) ) {
            $this->_calloutconnector = 'local';
        }
        if (preg_match ('/true/', $this->getParam('callout')) ) {
            $this->_calloutconnector = 'smtp';
        }
	}
	public function getCalloutConnector() {
		return $this->_calloutconnector;
	}
	public function setCalloutConnector($connector) {
		$this->_calloutconnector = $connector;
	}
	 
	public function getCalloutTestStatus($reset) {
		if ($this->getParam('callout') == 'false' && $this->getParam('adcheck') == 'false') {
			$this->_callouttest_finished = true;
			return array('nocalloutset' => array('status' => 'OK', 'message' => 'done.'));
		}

		$servers = $this->getDestinationServers();
		if (count($servers) < 1 || (count($servers) == 1 && $servers[0]['host'] == ''))  {
			$this->_callouttest_finished = true;
			return array('nodestinationset' => array('status' => 'NOK', 'message' => 'nodestinationset'));
		}
		$action = array(
             'sendingtorandom' => array('address' => Default_Model_SMTPDestinationServer::getRandomString(20).'@'.$this->getParam('name'), 'expected' => 'NOK'), 
             'sendingtopostmaster' => array('address' => 'postmaster@'.$this->getParam('name'), 'expected' => 'OK'));
		$file = sys_get_temp_dir()."/callouttest.status";
		if (file_exists($file) && $reset) {
			unlink($file);
		}
		$content = array();
		if (file_exists($file)) {
			$content = file($file);
		}
		$whats = array();
		foreach ($content as $line) {
			if (preg_match('/\s*(\S+)\s*:\s*(OK|OKWAIT|OKSTART|NOK)\s+(.*)/', $line, $matches)) {
				$whats[$matches[1]]['status'] = $matches[2];
				$whats[$matches[1]]['message'] = $matches[3];
			}
		}

		foreach($action as $a => $data) {
			if (!array_key_exists($a, $whats)) {
				$whats[$a]['status'] = 'OKSTART';
				$whats[$a]['message'] = 'testing...';
				$str = $a.":".$whats[$a]['status']." ".$whats[$a]['message']."\n";
				file_put_contents($file, $str, FILE_APPEND);
				return $whats;
			} elseif ($whats[$a]['status'] == 'OKSTART') {
				$whats[$a] = array('status' => 'OKWAIT', 'message' => 'testing...');
				$server = new Default_Model_SMTPDestinationServer('localhost:25');
				$whats[$a] = $server->testCallout($data['address'], $data['expected']);
				$str = $a.":".$whats[$a]['status']." ".$whats[$a]['message']."\n";
				file_put_contents($file, $str, FILE_APPEND);
				return $whats;
			}
		}
		$this->_callouttest_finished = true;
		return $whats;
	}
	public function calloutTestFinished() {
		return $this->_callouttest_finished;
	}
	 
	/**
	 * Default preferences
	 */
	public function getSpamActions() {
		return $this->_spamActions;
	}
	public function getSpamActionName() {
		foreach ($this->_spamActions as $key => $value) {
			if ($value == $this->getPref('delivery_type')) {
				return $key;
			}
		}
	}
	public function getSummaryFrequencies() {
		return $this->_summaryFrequencies;
	}
	public function getSummaryFrequency() {
		if ($this->getPref('daily_summary')) { return $this->_summaryFrequencies['daily']; }
		if ($this->getPref('weekly_summary')) { return $this->_summaryFrequencies['weekly']; }
		if ($this->getPref('monthly_summary')) { return $this->_summaryFrequencies['monthly']; }
		return $this->_summaryFrequencies['none'];
	}
    public function getSummaryFrequencyName() {
        if ($this->getPref('daily_summary')) { return 'daily'; }
        if ($this->getPref('weekly_summary')) { return 'weekly'; }
        if ($this->getPref('monthly_summary')) { return 'monthly'; }
        return 'none';
    }
	public function setSummaryFrequency($frequency) {
		foreach (array('daily_summary', 'weekly_summary', 'monthly_summary') as $f) {
			$this->setPref($f, '0');
		}
		if ($frequency == 1 || $frequency == 'daily') { $this->setPref('daily_summary', '1'); }
		if ($frequency == 2 || $frequency == 'weekly') { $this->setPref('weekly_summary', '1'); }
		if ($frequency == 3 || $frequency == 'monthly') { $this->setPref('monthly_summary', '1'); }
	}
	public function getSummaryTypes() {
		return $this->_summaryTypes;
	}
	 
	/**
	 * user authentication
	 */
	public function getAuthConnector() {
		if ($this->getPref('auth_type') != '') {
			return $this->getPref('auth_type');
		}
		return 'none';
	}
	 
	private function loadAuthConnector($create = 1) {
		if ($this->getAuthConnector() == 'none') {
			return;
		}
		## use old stuff !
		unset($_SESSION['_authsession']);
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../../../../classes'),
		get_include_path(),
		)));
		require_once('Log.php');
		require_once('domain/Domain.php');
		require_once('helpers/DM_SlaveConfig.php');
		require_once('system/SystemConfig.php');
		require_once('connector/AuthManager.php');
		$MCLOGLEVEL = PEAR_LOG_WARNING;
		$conf_ = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
		global $sysconf_;
		$sysconf_ = SystemConfig::getInstance();

		$domain_ = new Domain();
		$domain_->load($this->getParam('name'));

		$this->auth_ = AuthManager::getAuthenticator($this->getAuthConnector());
                if ($create) {
		  $this->auth_->create($domain_);
                }
	}
	 
	public function isAuthExhaustive() {
		if (!$this->auth_) {
			$this->loadAuthConnector(0);
		}
		if (!$this->auth_) {
			return false;
		}
		return $this->auth_->isExhaustive();
	}
	 
	public function isAuthLocal() {
		if ($this->getPref('auth_type') == 'local') {
			return true;
		}
		return false;
	}
	 
	public function isFetcherLocal() {
		if ($this->getPref('address_fetcher') == 'local') {
			return true;
		}
		return false;
	}
	 
	public function getAddresFetcher() {
		if ($this->getPref('address_fetcher') != '') {
			return $this->getPref('address_fetcher');
		}
		return 'none';
	}
	 
	private function loadAddressFetcher() {
		if ($this->getPref('address_fetcher') == 'none') {
			return;
		}
		## use old stuff !
		unset($_SESSION['_authsession']);
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../../../../classes'),
		get_include_path(),
		)));
		require_once('Log.php');
		require_once('connector/AddressFetcher.php');
		require_once('helpers/DM_SlaveConfig.php');
		require_once('system/SystemConfig.php');
		require_once('connector/AuthManager.php');
		$MCLOGLEVEL = PEAR_LOG_WARNING;
		$conf_ = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
		global $sysconf_;
		$sysconf_ = SystemConfig::getInstance();

		$this->fetcher_ = AddressFetcher::getFetcher($this->getAddresFetcher());
	}

        public function isFetcherExhaustive() {
                if (!$this->fetcher_) {
                        $this->loadAddressFetcher();
                }
                if (!$this->fetcher_) {
                        return false;
                }
                return $this->fetcher_->isExhaustive();
        }
 
	 
	public function loadOldDomain() {
		if ($this->domain_) {
            return $this->domain_;
		}
		## use old stuff !
		unset($_SESSION['_authsession']);
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../../../../classes'),
		get_include_path(),
		)));
		require_once('Log.php');
		require_once('connector/AddressFetcher.php');
		require_once('helpers/DM_SlaveConfig.php');
		require_once('system/SystemConfig.php');
		require_once('connector/AuthManager.php');
                require_once('domain/Domain.php');
		$MCLOGLEVEL = PEAR_LOG_WARNING;
		$conf_ = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
		global $sysconf_;
		$sysconf_ = SystemConfig::getInstance();

		$this->domain_ = new Domain();
		$this->domain_->load($this->getParam('name'));

		return $this->domain_;
	}
	 
	public function fetchUsers($username) {
		if (!$this->fetcher_) {
			$this->loadAddressFetcher();
		}
		if (!$this->fetcher_) {
			return array();
		}
		$this->loadOldDomain();
		$ret = $this->fetcher_->searchUsers($username, $this->domain_);
		return $ret;
	}
	 
	public function fetchEmails($address) {
		if (!$this->fetcher_) {
			$this->loadAddressFetcher();
		}
		if (!$this->fetcher_) {
			return array();
		}
		$this->loadOldDomain();
		$ret = $this->fetcher_->searchEmails($address, $this->domain_);
		return $ret;
	}
	 
	public function testUserAuth($username, $password) {
		//TODO: test authentication

		if ($this->getPref('auth_type') == 'none') {
			return array('status' => 'OK', 'message' =>  'no authentication set.', 'addresses' => array(), 'errors' => array());
		}
		## use old stuff !
		unset($_SESSION['_authsession']);
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../../../../classes'),
		get_include_path(),
		)));
		require_once('Log.php');
		require_once('domain/Domain.php');
		require_once('helpers/DM_SlaveConfig.php');
		require_once('system/SystemConfig.php');
		require_once('connector/AuthManager.php');
		$MCLOGLEVEL = PEAR_LOG_WARNING;
		$conf_ = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
		global $sysconf_;
		$sysconf_ = SystemConfig::getInstance();

		$domain_ = new Domain();
		$domain_->load($this->getParam('name'));
		 
		$username = $domain_->getFormatedLogin($username);
		$_POST['username'] = utf8_decode($username);
		$_POST['password'] = utf8_decode($password);
		$auth_ = AuthManager::getAuthenticator($domain_->getPref('auth_type'));
		$auth_->create($domain_);

		$auth_->start();
		if ($auth_->doAuth($username)) {
			require_once('user/User.php');
			$user = new User();
			$user->setDomain($this->getParam('name'));
			$user->load($username);
			 
			$addresses = $user->getAddresses();
			$addlist = array();
			foreach ($addresses as $add => $ismain) {
				$addlist[] = $add;
			}
			return array('status' => 'OK', 'message' =>  'passed', 'addresses' => $addlist, 'errors' => array());
		}

		return array('status' => 'NOK', 'message' =>  'failed.', 'addresses' => array(), 'errors' => $auth_->getMessages());
	}
	 
	/*
	 * templates
	 */
	public function getWebTemplates() {
		$config = MailCleaner_Config::getInstance();
		$path = $config->getOption('SRCDIR')."/www/user/htdocs/templates";
		return $this->getTemplates($path);
	}
	 
	public function getSummaryTemplates() {
		$config = MailCleaner_Config::getInstance();
		$path = $config->getOption('SRCDIR')."/templates/summary/";
		return $this->getTemplates($path);
	}

        public function getReportTemplates() {
                $config = MailCleaner_Config::getInstance();
                $path = $config->getOption('SRCDIR')."/templates/reports/";
                return $this->getTemplates($path);
        }
	 
	protected function getTemplates($path) {
		$ret = array('default');
		if (is_dir($path)) {
			if ($dh = opendir($path)) {
				while (($sub = readdir($dh)) !== false) {
					if (!preg_match('/^\./', $sub) && is_dir($path."/".$sub)) {
						if (!in_array($sub, $ret) && $sub != 'CVS') {
							$ret[] = $sub;
						}
					}
				}
			}
		}
		return $ret;
	}

        public function getAdmin() {
                $admin = new Default_Model_AdministratorMapper();
                $results = $admin->fetchByDomain($this->getParam('name'));
                return $results;
        }
}
