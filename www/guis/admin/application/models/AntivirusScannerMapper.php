<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * AntiVirus scanner mapper
 */

class Default_Model_AntivirusScannerMapper
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
            $this->setDbTable('Default_Model_DbTable_AntivirusScanner');
        }
        return $this->_dbTable;
    }

    public function findByName($name, Default_Model_AntivirusScanner $scanner)
    {
        $query = $this->getDbTable()->select();
        $query->where('name = ?', $name);
        $row = $this->getDbTable()->fetchRow($query);
        return $this->find($row['id'], $scanner);
    }

    public function find($id, Default_Model_AntivirusScanner $s)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $s->setId($id);
        foreach ($s->getParamArray() as $key => $value) {
            $s->setParam($key, $row[$key]);
        }
    }

    public function fetchAllActive()
    {
        $query = $this->getDbTable()->select();
        $query->where('active = 1');
        $resultSet = $this->getDbTable()->fetchAll($query);
        $entries   = [];
        foreach ($resultSet as $row) {
            $entry = new Default_Model_AntivirusScanner();
            $entry->find($row['id']);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Default_Model_AntivirusScanner $s)
    {
        $data = $s->getParamArray();
        $res = '';
        if (null === ($id = $s->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ['id = ?' => $id]);
        }
        return $res;
    }
}
