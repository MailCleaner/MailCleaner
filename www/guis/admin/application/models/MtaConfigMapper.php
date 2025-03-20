<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SMTP server settings mapper
 */

class Default_Model_MtaConfigMapper
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
            $this->setDbTable('Default_Model_DbTable_MtaConfig');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_MtaConfig $mta)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $mta->setId($id);
        foreach ($mta->getParamArray() as $key => $value) {
            $mta->setParam($key, $row[$key]);
        }
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = [];
        foreach ($resultSet as $row) {
            $entry = new Default_Model_MtaConfig();
            $entry->setId($row->id);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Default_Model_MtaConfig $mta)
    {
        $data = $mta->getParamArray();
        $res = '';
        if (null === ($id = $mta->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ['stage = ?' => $id]);
        }
        return $res;
    }
}
