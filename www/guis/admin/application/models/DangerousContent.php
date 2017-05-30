<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Dangerous content
 */

class Default_Model_DangerousContent
{
    protected $_id;
    protected $_values = array(
       'block_encrypt' => 'no',
       'block_unencrypt' => 'no',
       'allow_passwd_archives' => 'no',
       'allow_partial' => 'no',
       'allow_external_bodies' => 'no',
       'allow_iframe' => 'no',
       'silent_iframe' => 'no',
       'allow_form' => 'yes',
       'silent_form' => 'no',
       'allow_script' => 'yes',
       'silent_script' => 'no',
       'allow_webbugs' => 'disarm',
       'silent_webbugs' => 'no',
       'allow_codebase' => 'no',
       'silent_codebase' => 'no'
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
            $this->setMapper(new Default_Model_DangerousContentMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
   
    public function save()
    {
        return $this->getMapper()->save($this);
    }
}