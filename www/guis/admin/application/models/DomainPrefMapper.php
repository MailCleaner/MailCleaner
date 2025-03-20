<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Domain preferences mapper
 */

class Default_Model_DomainPrefMapper
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
            $this->setDbTable('Default_Model_DbTable_DomainPref');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_DomainPref $conf)
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
        if ($conf->getParam('auth_type') == 'mysql') {
            $conf->setParam('auth_type', 'local');
        }
    }

    public function save(Default_Model_DomainPref $conf, $global = false)
    {
        $data = $conf->getParamArray();
        $res = '';
        foreach (['enable_blacklists', 'enable_whitelists', 'enable_warnlists', 'notice_wwlists_hit'] as $key) {
            if (is_null($data[$key])) {
                unset($data[$key]);
            }
        }

        if (null === ($id = $conf->getId())) {
            unset($data['id']);
            if ($global) {
                $data['id'] = 1;
            }
            $res = $this->getDbTable()->insert($data);
            $conf->setId($res);
        } else {
            $res = $this->getDbTable()->update($data, ['id = ?' => $id]);
        }
        return $res;
    }

    public function delete(Default_Model_DomainPref $prefs)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $prefs->getId());
        return $this->getDbTable()->delete($where);
    }
}
