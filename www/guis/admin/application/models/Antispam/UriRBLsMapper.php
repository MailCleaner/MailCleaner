<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * UriRBLs prefilter mapper
 */

class Default_Model_Antispam_UriRBLsMapper
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
            $this->setDbTable('Default_Model_Antispam_DbTable_UriRBLs');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_Antispam_UriRBLs $module)
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

    public function save(Default_Model_Antispam_UriRBLs $module)
    {
        $data = $module->getParamArray();
        $res = '';
        if (null === ($id = $module->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, ["set_id = ?" => $id]);
        }
        return $res;
    }
}
