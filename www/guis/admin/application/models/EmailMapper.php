<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Email mapper
 */

class Default_Model_EmailMapper
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
            $this->setDbTable('Default_Model_DbTable_Email');
        }
        return $this->_dbTable;
    }

    public function find($address, Default_Model_Email $email)
    {
    	if (!$address) {
    		$address = '';
    	}
        $query = $this->getDbTable()->select();
        $query->where('address = ?', $address);
        $result = $this->getDbTable()->fetchAll($query);
        $email->setParam('address', $address);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $email->setId($row->id);
        $email->setParam('address', $row->address);
        $email->setParam('user', $row->user);
        $email->setParam('pref', $row->pref);
    }

    public function fetchAllRegistered($params) {
        $ret = array();
        $query = $this->getDbTable()->select();
        if ($params['user']) {
           $query->where("user = ?", $params['user']);
        }
        ## removed to display registered address not in same domain
        #if ($params['domain']) {
        #   $query->where("address LIKE ?", "%@".$params['domain']);
        #}
        $query->order('address ASC');
        $resultSet = $this->getDbTable()->fetchAll($query);
        foreach ($resultSet as $row) {
            $ret[] = $row->address;
        }
        return $ret;
    }
    
    
    public function fetchAllName($params)
    {
        $entries   = array();
        if (!$params['domain']){
            return $entries;
        }
        
        $str = '';
        if (isset($params['address'])) {
            $str = preg_replace('/\*/', '%', $params['address']);
            $str = preg_replace('/[^0-9a-zA-Z._\-]/', '', $str);
        }

        $domain = new Default_Model_Domain();
        $domain->findByName($params['domain']);
        if ($domain->isFetcherExhaustive()) {
            $ret = $domain->fetchEmails($str);
            foreach ($ret as $r) {
                $entry = new Default_Model_Email();
                $entry->setParam('address', $r);
                $entries[] = $entry;
            }
        } else {
            $query = $this->getDbTable()->select();
            if (isset($params['order'])) {
                $query->order($params['order']);
            } else {
                $query->order('address ASC');
            }
             
            if (isset($params['limit']) && is_array($params['limit'])) {
                $query->limit($params['limit'][0], $params['limit'][1]);
            }
             
            if (isset($params['address'])) {
                $query->where('address LIKE ?', $str."%@".$params['domain']);
            }
            $resultSet = $this->getDbTable()->fetchAll($query);
            foreach ($resultSet as $row) {
                $entry = new Default_Model_Email();
                $entry->setId($row['id']);
                $entry->setParam('address', $row['address']);
                $entries[] = $entry;
            }
        }
        return $entries;
    }
     
     
    public function save(Default_Model_Email $email) {
        $data = $email->getParamArray();
        $res = '';
        if (null === ($id = $email->getId())) {
            unset($data['id']);
            if ($email->find($email->getParam('address'))->getId()) {
                throw new Exception('address already exists : '.$email->getParam('address'));
            }
            $res = $this->getDbTable()->insert($data);
            $this->_isNew = 1;
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }

    public function delete(Default_Model_Email $email) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $email->getId());
        return $this->getDbTable()->delete($where);
    }

    public function isNew() {
        return $this->_isNew;
    }
}
