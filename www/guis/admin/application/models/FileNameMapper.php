<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * File name mapper
 */

class Default_Model_FileNameMapper
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
            $this->setDbTable('Default_Model_DbTable_FileName');
        }
        return $this->_dbTable;
    }
    
    public function find($id, Default_Model_FileName $f)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $f->setId($id);
        foreach ($f->getParamArray() as $key => $value) {
        	$f->setParam($key, $row[$key]);
        }
    }
    
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->order('rule ASC'));
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Default_Model_FileName();
            $entry->find($row->id);
            $entries[] = $entry;
        }
        return $entries;
     }
        
    public function save(Default_Model_FileName $f) {
       $data = $f->getParamArray();
       $res = '';
       if (null === ($id = $f->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
            $f->setId($res);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }
    
    public function delete(Default_Model_FileName $f) {
    	$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $f->getId());
    	return $this->getDbTable()->delete($where);   	
    }
}