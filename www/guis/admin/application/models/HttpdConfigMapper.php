<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Web server settings mapper
 */

class Default_Model_HttpdConfigMapper
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
            $this->setDbTable('Default_Model_DbTable_HttpdConfig');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_HttpdConfig $config)
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

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = [];
        foreach ($resultSet as $row) {
            $entry = new Default_Model_HttpdConfig();
            $entry->setId($row->id);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Default_Model_HttpdConfig $config)
    {
        $data = $config->getParamArray();
        $res = '';
        if (null === ($id = $config->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ['set_id = ?' => $id]);
        }
        return $res;
    }
}
