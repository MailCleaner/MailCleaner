<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2017 Mentor Reka <reka.mentor@gmail.com>
 * Administrator mapper
 */

class Default_Model_AdministratorMapper
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
            $this->setDbTable('Default_Model_DbTable_Administrator');
        }
        return $this->_dbTable;
    }
    
    public function find($username, Default_Model_Administrator $administrator)
    {
    	$query = $this->getDbTable()->select();
    	$query->where('username = ?', $username);
    	$result = $this->getDbTable()->fetchAll($query);
        #$result = $this->getDbTable()->find($username);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $administrator->setId($row->id);
        $administrator->setUsername($row->username);
        $administrator->setParam('username', $row->username);
        $administrator->setDomains($row->domains);
        $administrator->setParam('password', $row->password);
        foreach ($administrator->getRights() as $right) {
        	if ($row->$right) {
        		$administrator->setRight($right, 1);
        	}
        }
    }
   
   public function fetchByDomain($domainName)
   {
        if (isset($domainName) && !empty($domainName)) {
                $query = $this->getDbTable()->select();
                $query->where('domains LIKE ?', "%".$domainName."%");
                $result = $this->getDbTable()->fetchAll($query);

                $parsed_result = array();
                // remove unsolicited admins
                foreach ($result as $admin) {
                        $validate = false;
                        $domains = explode(" ", $admin->domains);
                        foreach($domains as $domain) {
                                if($domain == $domainName) {
                                        $validate = true;
                                        break;
                                }
                        }
                        if($validate)
                                $parsed_result[] = $admin;
                }
                return $parsed_result;
        }
        return NULL;
    }


    public function fetchAllName($params)
    {
    	$query = $this->getDbTable()->select();
    	if (isset($params['order'])) {
    		$query->order($params['order']);
    	} else {
    		$query->order('name ASC');
    	}
    	
    	if (isset($params['limit']) && is_array($params['limit'])) {
    		$query->limit($params['limit'][0], $params['limit'][1]);
    	}
    	
    	if (isset($params['username'])) {
    		$str = preg_replace('/\*/', '%', $params['username']);
    		$str = preg_replace('/[^0-9a-zA-Z._\-@]/', '', $str);
    		$query->where('username LIKE ?', $str."%");
    	}
        $resultSet = $this->getDbTable()->fetchAll($query);
        $entries   = array();
        foreach ($resultSet as $row) {
          $entry = new Default_Model_Administrator();
          $entry->find($row['username']);
          $entries[] = $entry;   
        }
        return $entries;
    }
    
    public function save(Default_Model_Administrator $admin) {
       $data = $admin->getParamArray();
       $res = '';
       if (null === ($id = $admin->getId())) {
            unset($data['id']);
    		if ($admin->find($admin->getParam('username'))->getId()) {
    			throw new Exception('administrator already exists : '.$admin->getParam('username'));
    		}
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $res;
    }
    
    public function delete(Default_Model_Administrator $admin) {
    	$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $admin->getId());
    	return $this->getDbTable()->delete($where);   	
    }
}
