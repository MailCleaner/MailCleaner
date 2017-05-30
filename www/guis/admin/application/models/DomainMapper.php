<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain mapper
 */

class Default_Model_DomainMapper
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
            $this->setDbTable('Default_Model_DbTable_Domain');
        }
        return $this->_dbTable;
    }
    
    public function find($id, Default_Model_Domain $domain)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        
        $user = Zend_Registry::get('user');
        if (!$user || (!$user->canManageDomain($row->name) && $row->name != '__global__') ) {
            return;
        }
        
        $domain->setId($id);
        foreach ($domain->getAvailableParams() as $key) {
        	$domain->setParam($key, $row->$key);
        }
    }
    
    public function findByName($name, Default_Model_Domain $domain) {
        $user = Zend_Registry::get('user');
        if (!$user || (!$user->canManageDomain($name) && $name != '__global__')) {
                return null;
        }
        $query = $this->getDbTable()->select();
        $query->where('name = ?', $name);
        $row = $this->getDbTable()->fetchRow($query);
        return $this->find($row['id'], $domain);
    }
    
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->order('name ASC'));
        $entries   = array();
        
        $user = Zend_Registry::get('user');
        
        foreach ($resultSet as $row) {
        	if ($row['name'] == '__global__') {
        		continue;
        	}
        	if (!$user || !$user->canManageDomain($row->name)) {
        		continue;
        	}
            $entry = new Default_Model_Domain();
            $entry->find($row->id);
            $entries[] = $entry;
        }
        return $entries;
     }

     public function getDistinctDomainsCount() {
        $res = 0;
        $query = $this->getDbTable()->select()->from(array('d' => 'domain'), array('dc' => 'count(distinct d.prefs)'))->where("name != '__global__'");
        $row = $this->getDbTable()->fetchRow($query);
        if ($row && isset($row['dc'])) {
          return $row['dc'];
        }
        return $res;
     }
     
     public function fetchAllName($params)
    {
    	$user = Zend_Registry::get('user');
    	
    	$query = $this->getDbTable()->select();
    	if (isset($params['order'])) {
    		$query->order($params['order']);
    	} else {
    		$query->order('name ASC');
    	}
    	
    	if (isset($params['limit']) && is_array($params['limit'])) {
    		$query->limit($params['limit'][0], $params['limit'][1]);
    	}
    	
    	if (isset($params['name'])) {
    		$str = preg_replace('/\*/', '%', $params['name']);
    		$str = preg_replace('/[^0-9a-zA-Z._\-]/', '', $str);
    		$query->where('name LIKE ?', $str."%");
    	}
        $resultSet = $this->getDbTable()->fetchAll($query);
        $entries   = array();
        foreach ($resultSet as $row) {
          if ($row['name'] == '__global__') {
        		continue;
          }
          
          if (!$user || !$user->canManageDomain($row['name'])) {
                continue;
          }
          $entry = new Default_Model_Domain();
          $entry->setId($row['id']);
          $entry->setParam('name', $row['name']);
          $entries[] = $entry;   
        }
        return $entries;
    }
    
    public function save(Default_Model_Domain $conf) {
       $data = $conf->getParamArray();
       $res = '';
       $user = Zend_Registry::get('user');
       if (!$user || !$user->canManageDomain($conf->getParam('name'))) {
       	   throw new Exception('permission denied');
       }
        
       if (null === ($id = $conf->getId())) {
            unset($data['id']);
    		if ($conf->findByName($conf->getParam('name'))->getID()) {
    			throw new Exception('domain already exists');
    		}
            $res = $this->getDbTable()->insert($data);
        } else {
            $res = $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        ## check if default domain is empty. If yes, put me as default
        $defaults = new Default_Model_SystemConf();
        $defaults->load();
        if ($defaults->getParam('default_domain') == '') {
        	$defaults->setParam('default_domain', $conf->getParam('name'));
        	$defaults->save();
        }
        return $res;
    }
    
    public function getAliases(Default_Model_Domain $d) {
    	$query = $this->getDbTable()->select();
    	$query->where('prefs = ?', $d->getParam('prefs'));
    	$resultSet = $this->getDbTable()->fetchAll($query);
    	$entries   = array();
    	foreach ($resultSet as $row) {
    		if ($row->id == $d->getId()) {
    			continue;
    		}
            $entries[] = $row->name;    		
    	}
    	return $entries;
    }
    
    public function delete(Default_Model_Domain $domain) {
        $user = Zend_Registry::get('user');
        if (!$user || !$user->canManageDomain($domain->getParam('name'))) {
           throw new Exception('Not authorized');
        }
       
    	$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $domain->getId());
    	return $this->getDbTable()->delete($where);   	
    }
}
