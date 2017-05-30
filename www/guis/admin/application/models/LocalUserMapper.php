<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Local user mapper
 */

class Default_Model_LocalUserMapper
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
            $this->setDbTable('Default_Model_DbTable_LocalUser');
        }
        return $this->_dbTable;
    }

    public function find($username, $domain, Default_Model_LocalUser $user)
    {
        if ($username == '' || $domain == '') {
            return;
        }
        $user->setParam('username', $username);
        $query = $this->getDbTable()->select();
        $query->where('username = ?', $username);
        $query->where('domain = ?', $domain);
        $result = $this->getDbTable()->fetchAll($query);
        if (0 == count($result)) {
        	$user->setParam('username', $username);
        	$user->setParam('domain', $domain);
            return;
        }
        $row = $result->current();
        $user->setId($row->id);
        $user->setParam('username', $row->username);
        $user->setParam('domain', $row->domain);
        $user->setParam('password', $row->password);
        $user->setParam('realname', $row->realname);
        $user->setParam('email', $row->email);
    }
     
    public function save(Default_Model_LocalUser $user) {
        $data = $user->getParamArray();
        $res = '';
        if (null === ($id = $user->getId())) {
            unset($data['id']);
            $res = $this->getDbTable()->insert($data);
            $user->setId($res);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }

    public function delete(Default_Model_LocalUser $user) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $user->getId());
        return $this->getDbTable()->delete($where);
    }
}