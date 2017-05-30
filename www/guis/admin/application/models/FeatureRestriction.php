<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Administrator
 */

class Default_Model_FeatureRestriction
{
	protected $_id;
	protected $_values = array(
      'section' => '',
	  'feature' => '',
	  'target_level' => '',
	  'restricted' => 0
    );
	protected $_restrictions = null;
	protected $_mapper;
	
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
	
	public function getParamArray() {
		return $this->_values;
	}
		
	public function getId() {
		return $this->_id;
	}
		
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_FeatureRestrictionMapper());
        }
        return $this->_mapper;
    }
    
    public function load($params = NULL) {
    	$this->_restrictions = $this->getMapper()->fetchAll($params);
    	return true;
    }
    
    public function isRestricted($section, $feature) {
    	if (!$this->_restrictions) {
    		$this->load();
    	}
    	if (isset($this->_restrictions[$section][$feature]) && $this->_restrictions[$section][$feature]['restricted']) {
    		
    		## check level
    		if ($this->_restrictions[$section][$feature]['target'] == 'administrator') {
     		    return true;
         	}
         	$user = Zend_Registry::get('user');
         	$role = $user->getUserType();

         	if (!preg_match('/^(administrator|manager|hotline)$/', $role)) {
         		return true;
         	}
         	if ($this->_restrictions[$section][$feature]['target'] == $role) {
         		return true;
         	}
         	if ($this->_restrictions[$section][$feature]['target'] == 'manager' && $role != 'administrator') {
         		return true;
         	}
         	if ($this->_restrictions[$section][$feature]['target'] == 'hotline' && ($role != 'administrator' && $role == 'manager')) {
         		return true;
         	}
    	}
    	return false;
    }
}
