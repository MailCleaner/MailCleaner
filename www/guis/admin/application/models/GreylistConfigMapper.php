<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Greylist daemon configuration mapper
 */

class Default_Model_GreylistConfigMapper
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
            $this->setDbTable('Default_Model_DbTable_GreylistConfig');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_GreylistConfig $greylist)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $greylist->setId($id);
        foreach ($greylist->getParamArray() as $key => $value) {
            $greylist->setParam($key, $row[$key]);
        }
    }

    public function save(Default_Model_GreylistConfig $greylist)
    {
        $data = $greylist->getParamArray();
        $res = '';
        if (null === ($id = $greylist->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ['set_id = ?' => $id]);
        }
        return $res;
    }
}
