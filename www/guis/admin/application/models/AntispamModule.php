<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Antispam generic module configuration
 */

class Default_Model_AntispamModule
{
    protected $_id;
    protected $_values = array(
       'name' => '',
       'active' => 1,
       'position' => 0,
       'neg_decisive' => 0,
       'pos_decisive' => 0,
       'decisive_field' => 'none',
       'timeOut' => 10,
       'maxSize' => 500000,
       'header' => '',
       'putHamHeader' => 0,
       'putSpamHeader' => 0,
       'visible' => 1
     );
	
	protected $_mapper;
	protected $_original_position;
	
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
            $this->setMapper(new Default_Model_AntispamModuleMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        $this->_original_position = $this->getParam('position');
        return $this;
    }
    
    public function findByName($name) 
    {
    	$this->getMapper()->findByName($name, $this);
        $this->_original_position = $this->getParam('position');
    	return $this;
    }
    
    public function fetchAll($query = NULL)
    {
    	return $this->getMapper()->fetchAll($query);
    }
   
    public function saveNewPosition($position) {
    	$this->setParam('position', $position);
    	$this->_original_position = $position;
    	$this->save();
    }
   
    public function save()
    {
    	if ($this->getParam('position') != $this->_original_position) {
    		$where = 'position <= '.max($this->_original_position, $this->getParam('position'))." AND position >= ".min($this->_original_position, $this->getParam('position')). " AND position != ".$this->_original_position;
            $modules_tochange = $this->fetchAll($where);
    		if ($this->_original_position < $this->getParam('position')) {
    			foreach ($modules_tochange as $m) {
    				$m->saveNewPosition(($m->getParam('position')-1));
    			}
    		} else {
    			foreach ($modules_tochange as $m) {
    				$m->saveNewPosition(($m->getParam('position')+1));
    			}
    		}
    	}
        return $this->getMapper()->save($this);
    }
    
    public function isDecisive() {
    	return $this->getParam('neg_decisive') || $this->getParam('pos_decisive');
    }
    public function setDecisive($value) {
     $this->setParam('neg_decisive', 0);
     $this->setParam('pos_decisive', 0);
     if ($value) {
     	if ($this->getParam('decisive_field') == 'both') {
     		$this->setParam('neg_decisive', 1);
            $this->setParam('pos_decisive', 1);
     	} else {
     		$this->setParam($this->getParam('decisive_field'), 1);
     	}
     }
    }
}
