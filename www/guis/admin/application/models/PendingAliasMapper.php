<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Pending alias request mapper
 */

class Default_Model_PendingAliasMapper
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
            $this->setDbTable('Default_Model_DbTable_PendingAlias');
        }
        return $this->_dbTable;
    }

    public function find($address, Default_Model_PendingAlias $alias)
    {
        $query = $this->getDbTable()->select();
        $query->where('alias = ?', $address);
        $result = $this->getDbTable()->fetchAll($query);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $alias->setId($row->id);
        $alias->setParam('alias', $row->alias);
        $alias->setParam('user', $row->user);
        $alias->setParam('date_in', $row->date_in);
    }
    
    public function fetchAll($params)
    {
        $query = $this->getDbTable()->select();
        if (isset($params['alias'])) {
            $query->where('alias = ?', $params['alias']);
        }
        if (isset($params['user'])) {
            $query->where('user = ?', $params['user']);
        }
        $entries = array();
        $resultSet = $this->getDbTable()->fetchAll($query);
        foreach ($resultSet as $row) {
            $entry = new Default_Model_PendingAlias();
            $entry->setId($row['id']);
            $entry->find($row['alias']);
            $entries[] = $entry;
        }
        return $entries;
    }
     
     
    public function save(Default_Model_PendingAlias $alias) {
        $data = $alias->getParamArray();
        $res = '';
        if (null === ($id = $alias->getId())) {
            unset($data['id']);
            if ($alias->find($alias->getParam('alias'))->getId()) {
                throw new Exception('alias already exists : '.$alias->getParam('alias'));
            }
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }

    public function delete(Default_Model_PendingAlias $alias) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $alias->getId());
        return $this->getDbTable()->delete($where);
    }
}