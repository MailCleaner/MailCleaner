<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User/Email preferences mapper
 */

class Default_Model_UserPrefMapper
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
            $this->setDbTable('Default_Model_DbTable_UserPref');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_UserPref $conf)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();

        $conf->setId($id);
        foreach ($conf->getAvailableParams() as $key) {
            $conf->setParam($key, $row->$key);
        }
    }

    public function save(Default_Model_UserPref $conf)
    {
        $data = $conf->getParamArray();
        $res = '';
        if (null === ($id = $conf->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
            $conf->setId($res);
        } else {
            $res = $this->getDbTable()->update($data, ['id = ?' => $id]);
        }
        return $res;
    }

    public function delete(Default_Model_UserPref $prefs)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $prefs->getId());
        return $this->getDbTable()->delete($where);
    }
}
