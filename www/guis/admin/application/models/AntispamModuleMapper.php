<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * AntiSpam generic module configuration mapper
 */

class Default_Model_AntispamModuleMapper
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
            $this->setDbTable('Default_Model_DbTable_AntispamModule');
        }
        return $this->_dbTable;
    }
    
    public function find($id, Default_Model_AntispamModule $module)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $module->setId($id);
        foreach ($module->getParamArray() as $key => $value) {
        	$module->setParam($key, $row[$key]);
        }
    }
    
    public function findByName($name, Default_Model_AntispamModule $module)
    {
    	$query = $this->getDbTable()->select();
        $query->where('name = ?', $name);
        $row = $this->getDbTable()->fetchRow($query);
        return $this->find($row['id'], $module);
    }
    
    public function fetchAll($where)
    {
        $resultSet = $this->getDbTable()->fetchAll($where, 'position ASC');
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Default_Model_AntispamModule();
            $entry->setId($row->id);
            $entry->find($row->id);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function save(Default_Model_AntispamModule $module) {
       $data = $module->getParamArray();
       $res = '';
       if (null === ($id = $module->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, array("id = ?" => $id));
        }
        return $res;
    }
}