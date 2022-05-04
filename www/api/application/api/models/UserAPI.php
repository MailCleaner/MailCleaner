<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class Api_Model_UserAPI
{

	/**
	 * These functions manage a user with custom parameters
	 * General call format is:
	 * @param mixed $params
	 * @return mixed array of xml values to be sent back
	 *
	 * params are:
	 *
	 *   General:
	 *    user => user name, can be only local part or username with domain in the form of 'username[@%]domain' - cannot be modified
	 *    domain  => domain the user belongs to - cannot be modified
	 *  
	 *   Interface display:
	 *    language =>  preferred language
	 *
	 *   Quarantine display:
	 *    gui_default_address => address to be displayed first in interface
	 *    gui_displayed_spams => integer, number of spams to display per page, should be one of 5, 10, 20, 50 or 100
	 *    gui_displayed_days => integer, number of day to display in quarantine
	 *    gui_mask_forced => can be 0 or 1, mask or show messages already forced
	 *    
	 *   Address group:
	 *    addresses => comma separated list of email address linked to this user
	 *
	 *  For userList(), you can pass the following parameters
	 *    search => string, allows to list only users starting with this search string
	 */

	public function exists($params) {
	    if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        
        $user = null;
        try {
            $user = $this->findUser($params);
            if (!$user->getId()) {
                throw new Exception('User does not exists');
            }
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'user '.$user->getParam('username').' in domain '.$user->getParam('domain').' exists');
        return true;
	}
	
	public function add($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		
		$user = null;
		try {
			$user = $this->findUser($params);
			if ($user->getId()) {
				throw new Exception('User already exists');
			}
			$this->setupParams($user, $params);
		} catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, 'Cannot add user: '.$e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'user '.$user->getParam('username').' in domain '.$user->getParam('domain').' added');
        return true;
	}
	
	public function edit($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		
		$user = null;
		try {
			$user = $this->findUser($params);
			if (!$user->getId()) {
				throw new Exception('User does not exists');
			}
			$this->setupParams($user, $params);
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot edit user: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'user '.$user->getParam('username').' in domain '.$user->getParam('domain').' edited');
		return true;
	}
	
	public function delete($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$user = null;
		try {
			$user = $this->findUser($params);
			if (!$user->getId()) {
				throw new Exception('User does not exists');
			}
			$user->delete();
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot delete user: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'user '.$user->getParam('username').' in domain '.$user->getParam('domain').' deleted');
		return true;
	}
	
	public function userList($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$list = array();
		try {
		    if (!isset($params['domain'])) {
			    throw new Exception('Domain not provided');
		    }
			$user = new Default_Model_User();
			$search = '';
			if (isset($params['search'])) {
				$search = $params['search'];
			}
			$users = $user->fetchAllName(array('domain' => $params['domain'], 'username' => $search));
			foreach ($users as $u) {
				$list[] = $u->getParam('username');
			}
		}  catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot list users: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'Users', $list);
		return true;
	}
	
	public function show($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$user = null;
		$settings = array();
		try {
			$user = $this->findUser($params);
			if (!$user->getId()) {
				throw new Exception('User does not exists');
			}
			$settings = $this->getParams($user, $params);
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot show user: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'user '.$user->getParam('username'), $settings);
		return true;
	}
	
	private function findUser($params) {
		$domain = '';
		$username = '';
		if (isset($params['domain'])) {
			$domain = $params['domain'];
		}
		if (isset($params['user'])) {
			if (preg_match('/^(\S+)[\@\%](\S+)/', $params['user'], $matches)) {
				$username = $matches[1];
				$domain = $matches[2];
			} else {
				$username = $params['user'];
			}
		}
		$user = new Default_Model_User();
		
		$user->find($username, $domain);
		return $user;
	}
	
	private function setupParams($user, $params) {
		if (isset($params['language']) && preg_match('/^[-a-z_]+$/', $params['language'])) {
			$user->setPref('language', $params['language']);
		}
		
		$added_addresses = array();
		if (isset($params['addresses'])) {
		   $as = preg_split('/,/', $params['addresses']);
		   foreach ($as as $a) {
		   	   if ($user->isAddressEligible($a)) {
		   	   	   array_push($added_addresses, $a);
		   	   } else {
		   	   	   throw new Exception('Address '.$a.' is not eligible');
		   	   }
		   }
		}
		
		if (isset($params['gui_default_address'])) {
			$addresses = $user->getAddresses();
			if (!in_array($params['gui_default_address'], $addresses) && !in_array($params['gui_default_address'], $added_addresses)) {
				throw new Exception('Requested default address does not exists for this user');
			} else {
				$user->setPref('gui_default_address', $params['gui_default_address']);
			}
		}
		
		if (isset($params['gui_displayed_spams']) && is_numeric($params['gui_displayed_spams'])) {
			$user->setPref('gui_displayed_spams', $params['gui_displayed_spams']);
		}
		if (isset($params['gui_displayed_days']) && is_numeric($params['gui_displayed_days'])) {
			$user->setPref('gui_displayed_days', $params['gui_displayed_days']);
		}
		
		if (isset($params['gui_mask_forced']) && is_numeric($params['gui_mask_forced'])) {
			if ($params['gui_mask_forced']) {
				$user->setPref('gui_mask_forced', 1);
			} else {
				$user->setPref('gui_mask_forced', 0);
			}
		}
		
		if (!$user->save()) {
			throw new Exception('Error while saving user data');
		}
		foreach ($added_addresses as $a) {
			$user->addAddress($a);
		}
	}
	
	private function getParams($user, $params = array()) {
		$data = array();
		$data['username'] = $user->getParam('username');
		$data['domain'] = $user->getParam('domain');
		foreach (array('gui_default_address', 'gui_displayed_spams', 'gui_displayed_days', 'gui_mask_forced') as $pref) {
		   $data[$pref] = $user->getPref($pref);
		}
		$data['addresses'] = array();
		foreach ($user->getAddresses() as $add) {
       		array_push($data['addresses'], $add);
		} 
		return $data;
	}
}
