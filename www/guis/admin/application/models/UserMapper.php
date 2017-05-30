<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * User mapper
 */

class Default_Model_UserMapper
{

    protected $_dbTable;
    protected $_isNew = 0;

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
            $this->setDbTable('Default_Model_DbTable_User');
        }
        return $this->_dbTable;
    }

    public function findById($id, Default_Model_User $user) {
      if (!$id) {
      	return;
      }	
      $result = $this->getDbTable()->find($id);
      if (0 == count($result)) {
          return;
      }
      $row = $result->current();
        
      $user->setId($id);
      foreach ($user->getAvailableParams() as $key) {
      	$user->setParam($key, $row->$key);
      }
    }
    
    
    public function find($username, $domain, Default_Model_User $user)
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
            return;
        }
        $row = $result->current();
        $user->setId($row->id);
        $user->setParam('username', $row->username);
        $user->setParam('domain', $row->domain);
        $user->setParam('pref', $row->pref);
    }

    public function fetchAllName($params)
    {
        $entries   = array();
        if (!$params['domain']){
            return $entries;
        }
        
        $str = preg_replace('/\*/', '%', $params['username']);
        $str = preg_replace('/[^0-9a-zA-Z._\-]/', '', $str);

        $domain = new Default_Model_Domain();
        $domain->findByName($params['domain']);
        if ($domain->isAuthExhaustive()) {
            $ret = $domain->fetchUsers($str);
            foreach ($ret as $r) {
                $entry = new Default_Model_User();
                $entry->setParam('username', $r);
                $entries[] = $entry;
            }
        } else {
            $query = $this->getDbTable()->select();
            if (isset($params['order'])) {
                $query->order($params['order']);
            } else {
                $query->order('username ASC');
            }
             
            if (isset($params['limit']) && is_array($params['limit'])) {
                $query->limit($params['limit'][0], $params['limit'][1]);
            }
             
            if (isset($params['username'])) {
                $query->where('username LIKE ?', $str."%");
            }
            $query->where('domain = ?', $params['domain']);
            $resultSet = $this->getDbTable()->fetchAll($query);
            foreach ($resultSet as $row) {
                $entry = new Default_Model_User();
                $entry->setId($row['id']);
                $entry->setParam('username', $row['username']);
                $entries[] = $entry;
            }
        }
        return $entries;
    }
     
     
    public function save(Default_Model_User $user) {
        $data = $user->getParamArray();
        $res = '';
        if (null === ($id = $user->getId())) {
            unset($data['id']);
            if ($user->find($user->getParam('username'), $user->getDomainObject()->getParam('name'))->getId()) {
                throw new Exception('user already exists : '.$user->getParam('username'));
            }
            $res = $this->getDbTable()->insert($data);
            $user->setId($res);
            $this->_isNew = 1;
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
            $res = 1;
        }
        return $res;
    }

    public function delete(Default_Model_User $user) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $user->getId());
        return $this->getDbTable()->delete($where);
    }

    public function isNew() {
        return $this->_isNew;
    }
}
