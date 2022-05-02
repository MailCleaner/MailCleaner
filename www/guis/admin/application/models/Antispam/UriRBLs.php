<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * UriRBLs prefilter
 */

class Default_Model_Antispam_UriRBLs
{
    protected $_id;
    protected $_values = array(
        'listedemailtobespam' => 1,
        'listeduristobespam' => 1,
        'rbls' => '',
	'resolve_shorteners' => 1,
        'avoidhosts' => ''
      );
	
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
            $this->setMapper(new Default_Model_Antispam_UriRBLsMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
    
    public function findByName($name) 
    {
    	$this->getMapper()->findByName($name, $this);
    	return $this;
    }
   
    public function save()
    {
        return $this->getMapper()->save($this);
    }
    
    public function useRBL($rbl) {
    	return preg_match('/\b'.$rbl.'\b/', $this->getParam('rbls'));
    }
}
