<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Firewall access mapper
 */

class Default_Model_FirewallRuleMapper
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
            $this->setDbTable('Default_Model_DbTable_FirewallRule');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_FirewallRule $config)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $config->setId($id);
        foreach ($config->getParamArray() as $key => $value) {
            $config->setParam($key, $row[$key]);
        }
    }

    public function findByService($service, Default_Model_FirewallRule $config)
    {
        $query = $this->getDbTable()->select();
        $query->where('service = ?', $service);
        $row = $this->getDbTable()->fetchRow($query);
        return $this->find($row['id'], $config);
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = [];
        foreach ($resultSet as $row) {
            $entry = new Default_Model_FirewallRule();
            $entry->setId($row->id);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Default_Model_FirewallRule $config)
    {
        $data = $config->getParamArray();
        $res = '';
        if (null === ($id = $config->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ['id = ?' => $id]);
        }
        return $res;
    }
}
