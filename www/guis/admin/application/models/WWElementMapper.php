<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * White/Warn list element mapper
 */

class Default_Model_WWElementMapper
{
	
    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Default_Model_DbTable_WWElement');
        }
        return $this->_dbTable;
    }
    
    public function find($id, Default_Model_WWElement $element)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        
        $element->setId($id);
        foreach ($element->getAvailableParams() as $key) {
        	$element->setParam($key, $row->$key);
        }
    }
    
    public function fetchAll($destination, $type)
    {
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('recipient = ?', $destination)->where('type = ?', $type)->order('recipient ASC'));
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Default_Model_WWElement();
            $entry->find($row->id);
            $entries[] = $entry;
        }
        return $entries;
     }
    
    public function fetchAllField($destination, $type, $field)
    {
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('recipient = ?', $destination)->where('type = ?', $type)->order('recipient ASC'));
        foreach ($resultSet as $row) {
            $returnString .= $row->$field . "\n";
        }
        return $returnString;
     }

     public function setBulkSender($domain, $senders, $type) {
	$senders_array = explode("\r\n", $senders);

	// To ensure, we remove dropped entries, we drop all and recreate all
	$this->getDbTable()->delete("recipient='$domain' and type='$type'");

	if ($senders != '') {
		foreach ($senders_array as $sender) {
			$this->getDbTable()->insert(
				array(
					'sender'	=> $sender,
					'recipient'	=> $domain,
					'type'		=> $type,
					'expiracy'	=> '',
					'status'	=> 1,
				)
	
			);
		}
	}
     }


    public function save(Default_Model_WWElement $element) {
       $data = $element->getParamArray();
       $res = '';
       if (null === ($id = $element->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
            $element->setId($res);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }
    
    public function delete(Default_Model_WWElement $element) {
    	$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $element->getId());
    	return $this->getDbTable()->delete($where);   	
    }
}
