<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class Api_Model_AddressAPI
{

	/**
	 * These functions manage a email address with custom parameters
	 * General call format is:
	 * @param mixed $params
	 * @return mixed array of xml values to be sent back
	 *
	 * params are:
	 *
	 *   General:
	 *    address => email address
	 *  
	 *   Settings:
	 *    action_on_spam => can be drop, tag or quarantine, action on spam
	 *    quarantine_bounce => can be 0 or 1, always block bounce messages
	 *    spam_tag => a string, used to prepend to the subject when the message is a spam and the action is set to tag
	 *    summary_frequency => can be none, daily, weekly or monthly, preferred mail quarantine reports frequency
	 *    summary_type => can be html, text, digest, format of mail quarantine reports
	 *    send_reports_to => address to send mail quarantine reports to
	 *    user => full username (with @domain) the address is (should be) linked to
	 *    
	 *   Archiving:
	 *     send_to_archiver => can be 0 or 1, set if messages should be sent to the archiver
	 *     send_copy_to => string, email address to send a copy of every message (incoming and outgoing) to
     *
	 *  For addressList(), you can pass the following parameters
	 *    search => string, allows to list only users starting with this search string
	 */

	public function exists($params) {
	    if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        
        $email = null;
        try {
            $email = $this->findEmail($params);
            if (!$email->getId() && $email->getParam('address') != '') {
                throw new Exception('Address does not exists');
            }
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'address '.$email->getParam('address').' in domain '.$email->getDomain().' exists');
        return true;
	}
	
	public function add($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		
		$email = null;
		try {
			$email = $this->findEmail($params);
			if ($email->getId()) {
				throw new Exception('Address already exists');
			}
			$this->setupParams($email, $params);
		} catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, 'Cannot add address: '.$e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'address '.$email->getParam('address').' in domain '.$email->getDomain().' added');
        return true;
	}
	
	public function edit($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		
		$email = null;
		try {
			$email = $this->findEmail($params);
			if (!$email->getId()) {
				throw new Exception('Address does not exists');
			}
			$this->setupParams($email, $params);
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot edit address: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'address '.$email->getParam('address').' in domain '.$email->getDomain().' edited');
		return true;
	}
	
	public function delete($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$email = null;
		try {
			$email = $this->findEmail($params);
			if (!$email->getId()) {
				throw new Exception('Address does not exists');
			}
			$email->delete();
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot delete address: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'address '.$email->getParam('address').' in domain '.$email->getDomain().' deleted');
		return true;
	}
	
	public function addressList($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$list = array();
		try {
			if (!isset($params['domain'])) {
				throw new Exception('Domain not provided');
			}		
			$email = new Default_Model_Email();
			$search = '';
			if (isset($params['search'])) {
				$search = $params['search'];
			}
			$emails = $email->fetchAllName(array('domain' => $params['domain'], 'address' => $search));
			foreach ($emails as $em) {
				$list[] = $em->getParam('address');
			}
		}  catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot list addresses: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'Addresses', $list);
		return true;
	}
	
	public function show($params) {
		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		$email = null;
		$settings = array();
		try {
			$email = $this->findEmail($params);
			if (!$email->getId()) {
				throw new Exception('Address does not exists');
			}
			$settings = $this->getParams($email, $params);
		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, 'Cannot show address: '.$e->getMessage());
			return false;
		}
		Zend_Registry::get('response')->setResponse(200, 'address '.$email->getParam('address'), $settings);
		return true;
	}
	
	private function findEmail($params) {
        $email = new Default_Model_Email();
		if (isset($params['address'])) {
            $email->find($params['address']);
		}
        return $email;
	}
	
	private function setupParams($email, $params) {
		
		if (isset($params['action_on_spam']) && preg_match('/^(drop|tag|quarantine)$/', $params['action_on_spam'])) {
			switch ($params['action_on_spam']) {
				case 'drop':
					$email->setPref('delivery_type', 3);
					break;
				case 'tag':
					$email->setPref('delivery_type', 1);
					break;
				case 'quarantine':
					$email->setPref('delivery_type', 2);
					break;
			}
		}
		
		if (isset($params['quarantine_bounce'])) {
			if ($params['quarantine_bounce']) {
				$email->setPref('quarantine_bounces', 1);
			} else {
				$email->setPref('quarantine_bounces', 0);
			}
		}
		
		if (isset($params['spam_tag'])) {
		    $email->setPref('spam_tag', $params['spam_tag']);
		}
		
		if (isset($params['summary_frequency']) && preg_match('/^(none|daily|weekly|monthly)$/', $params['summary_frequency'])) {
			$email->setSummaryFrequency($params['summary_frequency']);
		}
		if (isset($params['summary_type']) && preg_match('/^(text|html|digest)$/', $params['summary_type'])) {
			$email->setPref('summary_type', $params['summary_type']);
		}
		
		if (isset($params['send_reports_to']) && (preg_match('/^\S+\@\S+$/', $params['send_reports_to']) || $params['send_reports_to'] =='') ) {
			$email->setPref('summary_to', $params['send_reports_to']);
		}
		
		if (isset($params['allow_newsletters']) && preg_match('/^[01]$/', $params['allow_newsletters'])) {
			$email->setPref('allow_newsletters', $params['allow_newsletters']);
		}

		## Archiving
		if (isset($params['send_to_archiver']) && preg_match('/^[01]$/', $params['send_to_archiver'])) {
			$email->setPref('archive_mail', $params['send_to_archiver']);
		}
		if (isset($params['send_copy_to']) && preg_match('/^\S+\@\S+$/', $params['send_copy_to'])) {
			$email->setPref('copyto_mail', $params['send_copy_to']);
		}

        $user = null;
        if (isset($params['user']) && $params['user'] != '') {
            $domain = '';
            if (isset($params['domain'])) {
                $domain = $params['domain'];
			} elseif (preg_match('/^(\S+)\@(\S+)$/', $params['user'], $matches)) {
                $domain = $matches[2];
            }
            $username = $params['user'];
            if ($username != '' && $domain != '') {
                $user = new Default_Model_User();
                $user->find($username, $domain);
            }
            if (!$user->getId()) {
                throw new Exception('User does not exist');
            }
        }

		if (!$email->save()) {
			throw new Exception('Error while saving user data');
		}
	
        if ($user) {
            try {
                $user->addAddress($email->getParam('address'), false, false);
                $user->save();
            } catch (Exception $e) {
                throw new Exception ($e->getMessage()); 
            }
        }
	}
	
	private function getParams($email, $params = array()) {
		$data = array();
		$data['address'] = $email->getParam('address');
		$data['domain'] = $email->getDomain();
		switch ($email->getPref('delivery_type')) {
			case 1:
				$data['action_on_spam'] = 'tag';
				break;
			case 2:
				$data['action_on_spam'] = 'quarantine';
				break;
			case 3:
				$data['action_on_spam'] = 'drop';
				break;
			default:
				$data['action_on_spam'] = 'unavailable';
		}
		foreach (array('spam_tag', 'summary_type') as $pref) {
		   $data[$pref] = $email->getPref($pref);
		}
		$data['summary_frequency'] = $email->getSummaryFrequency();
		$data['send_reports_to'] = $email->getPref('summary_to').'';
		if ($email->getPref('quarantine_bounces')) {
    		$data['quarantine_bounce'] = '1';
		} else {
			$data['quarantine_bounce'] = '0';
		}
        ## Archiving
        $data['send_to_archiver'] = $email->getPref('archive_mail');
        $data['send_copy_to'] = $email->getPref('copyto_mail').'';
        
		if ($email->getLinkedUser()) {
			$data['user'] = $email->getLinkedUser()->getParam('username');
		}
        $data['allow_newsletters'] = $email->getPref('allow_newsletters');
		return $data;
	}
}
