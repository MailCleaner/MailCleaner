<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * White/Warn list element
 */

class Default_Model_WWElement
{
    protected $_id;
    protected $_values = array(
      'sender' => '',
      'recipient'    => '',
      'type' => 'warn',
      'expiracy' => '',
      'status' => 0,
      'comments' => '',
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
		$ret = null;
		if (array_key_exists($param, $this->_values)) {
			$ret = $this->_values[$param];
		}
	    if ($ret == 'false') {
			return 0;
		}
		return $ret;
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
            $this->setMapper(new Default_Model_WWElementMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
    
    public function fetchAll($destination, $type) {
    	return $this->getMapper()->fetchAll($destination, $type);
    }
    
    public function fetchAllField($destination, $type, $field) {
    	return $this->getMapper()->fetchAllField($destination, $type, $field);
    }


    public function setBulkSender($domain, $senders, $type) {
    	return $this->getMapper()->setBulkSender($domain, $senders, $type);
    }
    
    public function setSpamcOvercharge($domain, $comment, $type) {
    	return $this->getMapper()->setSpamcOvercharge($domain, $comment, $type);
    }
    
    public function save()
    {
        return $this->getMapper()->save($this);
    }
    
    public function delete()
    {
    	return $this->getMapper()->delete($this);
    }
    
   public function getStatus() {
    	if ($this->getParam('status')) {
    		return 1;
    	}
    	return 0;
    }
    
    public function setValue($value) {
    	$this->setParam('sender', $value);
    }
    
    public function setComment($comment) {
    	if ($comment == '') {
    		$comment = "-";
    	}
    	$this->setParam('comments', $comment);
    }
    
    public function getComment() {
    	if ($this->getParam('comments') != '-') {
          return $this->getParam('comments');
    	}
    	return '';
    }
    
    public function disable() {
    	$this->setParam('status', 0);
    	$this->save();
    }
    public function enable() {
    	$this->setParam('status', 1);
    	$this->save();
    }
}
