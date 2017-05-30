<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * System configuration
 */

class Default_Model_SystemConf
{
    protected $_id;
    protected $_values = array(
      'organisation' => '',
      'company_name' => '',
      'contact'      => '',
      'contact_email' => '',
      'default_domain' => '',
      'default_language' => 'en',
      'days_to_keep_spams' => 60,
      'days_to_keep_virus' => 60,
      'cron_time'     => '00:00:00',
      'cron_weekday'  => 0,
      'cron_monthday' => 0,
      'sysadmin' => '',
      'summary_from'   => '',
      'falseneg_to'    => '',
      'falsepos_to'    => '',
      'use_syslog'     => 0,
      'syslog_host'    => '',
      'use_archiver'   => 0,
      'archiver_host'  => '',
      'api_fulladmin_ips' => '',
      'api_admin_ips' => ''
    );
    
    protected $_restart_what = array(
       'default_domain' => array('exim_stage1','exim_stage4')
    );
    protected $_to_restart = array();
	
	protected $_mapper;
	
	public function setId($id) {
	   $this->_id = $id;	
	}
	public function getId() {
		return $this->_id;
	}
	
	public function setParam($param, $value) {
		if ($param == 'organisation') {
			$company = preg_replace('/\S/', '', $value);
			$company = strtolower($value);
			$company = preg_replace('/[^a-z0-9]/', '', $company);
			$this->setParam('company_name', $company);
		}
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
		
		## evetually check for services to restart according to parameter changed
		if (isset($this->_restart_what[$param])) {
			foreach ($this->_restart_what[$param] as $s) {
    			$this->_to_restart[] = $s;
			}
		}
	}
	
	public function getParam($param) {
		if (array_key_exists($param, $this->_values)) {
			return $this->_values[$param];
		}
		return null;
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
            $this->setMapper(new Default_Model_SystemConfMapper());
        }
        return $this->_mapper;
    }

    public function load() {
    	return $this->find(1);
    }
    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
   
    public function save()
    {
    	$ret = $this->getMapper()->save($this);
    	
    	## do we need to restart anything
    	$slave = new Default_Model_Slave();
    	foreach ($this->_to_restart as $s) {
    		$params = array('service' => "$s", 'action' => 'restart');
    		$res = $slave->sendSoapToAll('Service_silentStopStart', $params);
    	}
        return $ret;
    }
}