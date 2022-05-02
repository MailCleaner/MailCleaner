<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class Api_Model_DomainAPI
{

	/**
	 * This function adds a domain with custom parameters or defaults
	 * @param string $name
	 * @param mixed $params
	 * @return mixed array of xml values to be sent back
	 *
	 * params are:
	 *
	 *   General:
	 *    name => string, domain name - cannot be modified
	 *    aliases  => comma or \n separated string of aliases domains (use '' to delete all aliases)
	 *    defaults => can be any existent domain name. 
	 *                Values will be copied from it first as default values, 
	 *                otherwise, default values are taken from the global domains settings
	 *    systemsender => System mail sender address
	 *    falseneg_to => False negative reporting address
	 *    falsepos_to => False positive reporting address
	 *    supportname => Support name
	 *    supportemail => Support email
	 *
	 *   Delivery:
	 *    destination =>  comma or \n separated string of destination server with port
	 *    destination_mode => can be 'loadbalancing' or 'failover'
	 *    destination_usemx => can be 0 or 1
	 *
	 *   Address verification:
	 *    callout_connector => can be 'none', 'smtp', 'ldap'
	 *    callout_server => server to use for callout    
	 *    
     *    For ldap/Active Directory connector (callout_connector must be specified and set to 'ldap'):
     *      c_base_dn => base DN (i.e. dc=yourdomain,dc=com)
     *      c_bind_user => user account for non anonymous bind
     *      c_bind_pass => user password for non anonymous bind
     *      c_group => restrict search on group
     *      c_use_ssl => can be 0 or 1, use SSL encryption
	 *
	 *   Preferences:
	 *    language => can be 'en', 'fr', 'de', 'es'
	 *    summary_frequency => can be 'none', 'daily', 'weekly', 'monthly'
	 *    summary_type => can be 'text' or 'html'
	 *    delivery_type => can be 'drop', 'tag', 'quarantine'
	 *    spam_tag => string to be added on spam subject when using the 'tag' delivery type
	 *    content_tag => string to be added on messages where dangerous content was removed
	 *    file_tag => string to be added on messages where forbidden attached files were removed
	 *    virus_tag => string to be added on messages where viruses where detected and removed
	 *
	 *   Authentication:
	 *    auth_connector => can be 'none', 'imap', 'pop3', 'ldap', 'smtp', 'local'
	 *    auth_server => server to use for authentication using the auth_connector protocol
	 *    auth_use_ssl => can be 0 or 1, use SSL encryption during authentication phase (auth server must support it)
	 *
	 *    For ldap/Active Directory connector:
	 *      a_base_dn => base DN (i.e. dc=yourdomain,dc=com)
	 *      a_bind_user => user account for non anonymous bind
	 *      a_bind_pass => user password for non anonymous bind
	 *      a_user_attr => attribute used to store username (i.e. sAMAccountName for Active Directory)
	 *      a_protocol_version => version of the ldap protocol to use
	 *
	 *    username_modifier => can be 'username', 'username@domain', 'username%domain'
	 *    address_lookup => can be 'username@domain', 'ldap', 'local'
	 *
	 *   Filtering:
	 *     spamwall => can be 0 or 1, enable antispam
	 *     contentwall => can be 0 or 1, enable dangerous content filtering
	 *     viruswall => can be 0 or 1, enable antivirus
	 *     greylist => can be 0 or 1, enable greylisting
	 *     whitelists => can be 0 or 1, enable whitelists
	 *     warnlists => can be 0 or 1, enable warnlists
	 *     notice_wwlists => can be 0 or 1, enable administrator warning when white or warn lists hit
	 *     prevent_spoof => can be 0 or 1, enable antispoofing
	 *     require_incoming_tls => can be 0 or 1, reject unencrypted sessions to this domain
	 *     reject_capital_domain => can be 0 or 1, rejects domain names containing capitals (if set to 0)
	 *    
	 *   Outgoing:
	 *     allow_smtp_auth =>  can be 0 or 1, set if users can authenticate using SMTP for relaying
	 *     require_outgoing_tls => can be 0 or 1, reject unencrypted relaying sessions from this domain
	 *     batv_enable => can be 0 or 1, enable BATV control and signing for this domain
	 *     batv_secret => string, BATV secret key
	 *     dkim_domain => string, domain used to sign messages with
     *     dkim_selector => string, DKIM selector
     *     dkim_private_key => string, DKIM private key
	 *
	 *   Archiving:
	 *     send_to_archiver => can be 0 or 1, set if messages should be sent to the archiver
	 *     send_copy_to => string, email address to send a copy of every message (incoming and outgoing) to
	 *     
	 *  Templates:
	 *     web_template => User Web Interface template
	 *     summary_template => spam quarantine reports template
	 *
	 */
	public function add($name, $params = null) {

		if (!Zend_Registry::isRegistered('user')) {
			Zend_Registry::get('response')->setResponse(401, 'authentication required');
			return false;
		}
		 
		$domain = new Default_Model_Domain();
		try {
			require_once('Validate/DomainName.php');
			$validator = new Validate_DomainName();
			if (!$validator->isValid($name)) {
				throw new Exception('Domain name not valid ('.$name.')');
			}
			$domain->setParam('name', $name);
			
			## check defaults values
            $defdom = new Default_Model_Domain();
            $defdom->findByName('__global__');
			if (isset($params['defaults'])) {
				if (!$validator->isValid($params['defaults'])) {
					throw new Exception('default domain is invalid');
				}
				$defdom->findByName($params['defaults']);
				if (!$defdom->getId()) {
					throw new Exception('default domain does not exists');
				}
			}
            $domain->copyPrefs($defdom);
			
            $this->setupParams($domain, $params);
            
			$domain->save();
			$domain->saveAliases();

		} catch (Exception $e) {
			Zend_Registry::get('response')->setResponse(500, $e->getMessage());
			return false;
		}

		Zend_Registry::get('response')->setResponse(200, 'Domain '.$name.' successfully added');
		return true;
	}

	public function edit($name, $params = null) {
        if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
         
        $domain = new Default_Model_Domain();
        try {
            require_once('Validate/DomainName.php');
            $validator = new Validate_DomainName();
            if (!$validator->isValid($name)) {
                throw new Exception('Domain name not valid ('.$name.')');
            }
            $domain->findByName($name);
            if ($domain->getId()) {
            	$this->setupParams($domain, $params);
            	$domain->save();
            	$domain->saveAliases();
            } else {
            	throw new Exception('Domain does not exists');
            }
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }

        Zend_Registry::get('response')->setResponse(200, 'Domain '.$name.' successfully edited');
        return true;
	}
	
	public function remove($name) {
	    if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        $domain = new Default_Model_Domain();
        try {
            require_once('Validate/DomainName.php');
            $validator = new Validate_DomainName();
            if (!$validator->isValid($name)) {
                throw new Exception('Domain name not valid ('.$name.')');
            }
            $domain->findByName($name);
            if (!$domain->getId()) {
                throw new Exception('Domain does not exists');
            }
            $domain->delete();
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'Domain '.$name.' removed');
        return true;
	}
	public function exists($name) {
	    if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        
        $domain = new Default_Model_Domain();
        try {
            require_once('Validate/DomainName.php');
            $validator = new Validate_DomainName();
            if (!$validator->isValid($name)) {
                throw new Exception('Domain name not valid ('.$name.')');
            }
            $domain->findByName($name);
            if (!$domain->getId()) {
                throw new Exception('Domain does not exists');
            }
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'Domain '.$name.' exists');
        return true;
	}
	
	public function show($name, $params) {
		if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        
        $data = array();
        $domain = new Default_Model_Domain();
        try {
            require_once('Validate/DomainName.php');
            $validator = new Validate_DomainName();
            if (!$validator->isValid($name)) {
                throw new Exception('Domain name not valid ('.$name.')');
            }
            $domain->findByName($name);
            if (!$domain->getId()) {
                throw new Exception('Domain does not exists or you don\'t have sufficient permissions');
            }
            $data = $this->getDomainParams($domain, $params);
        } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'Domain '.$name.' settings', $data);
        return true;
	}
	
	public function domainList() {
		if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        $list = array();
        $domain = new Default_Model_Domain();
        try {
            foreach ($domain->fetchAllName() as $d) {
            	$list[] = $d->getParam('name');
            };
	    } catch (Exception $e) {
            Zend_Registry::get('response')->setResponse(500, $e->getMessage());
            return false;
        }
        Zend_Registry::get('response')->setResponse(200, 'Domains', $list);
        return true;
	}
	
	private function setupParams($domain, $params) {
		## aliases
		if (isset($params['aliases'])) {
			$domain->setAliases(preg_split('/[^a-zA-Z0-9\.\-_]/', $params['aliases']));
		}
		## general options
		foreach (array('systemsender', 'falseneg_to', 'falsepos_to', 'supportname', 'supportemail') as $pref) {
			if (isset($params[$pref])) {
                $domain->setPref($pref, $params[$pref]);
			}
        }
        ## delivery
        $options = $domain->getDestinationActionOptions();
        if (isset($params['destination_mode'])) { 
            $domain->setDestinationOption($params['destination_mode']);
        }
        if (isset($params['destination_usemx'])) {
            $domain->setDestinationUseMX($params['destination_usemx']);
        }
        if (isset($params['destination'])) {
            $domain->setDestinationServersFieldString($params['destination']);
        }
		## Address verification
		if (isset($params['callout_connector'])) {
		    $connectorformclass = 'Default_Form_Domain_AddressVerification_'.ucfirst($params['callout_connector']);
            if (class_exists($connectorformclass)) {
    		    $connectorform  = new $connectorformclass($domain);
    		    $data = array();
    		    foreach (array(
    		            'callout_server' => 'callout_server', 
    		            'c_base_dn' => 'basedn', 
    		            'c_bind_user' => 'binddn', 
                        'c_bind_pass' => 'bindpass',
                        'c_group' => 'group',
                        'c_use_ssl' => 'usessl'
                        ) as $key => $value) {
    		    	if (isset($params[$key])) {
    		    		$data[$value] = $params[$key];
    		    	}
    		    } 
    		    $connectorform->setParamsFromArray($data, $domain);
            } else {
            	throw new Exception('Callout connector does not exists');
            }
		}
		## Preferences
		if (isset($params['language']) && preg_match('/^[a-z][a-z]$/', $params['language'])) {
			$domain->setPref('language', $params['language']);
		}
		if (isset($params['summary_frequency'])) {
			$domain->setSummaryFrequency($params['summary_frequency']);
		}
		if (isset($params['summary_type']) && preg_match('/^(text|html)$/', $params['summary_type'])) {
			$domain->setPref('summary_type', $params['summary_type']);
		}
		$spam_actions = $domain->getSpamActions();
		if (isset($params['delivery_type']) && isset($spam_actions[$params['delivery_type']])) {
			$domain->setPref('delivery_type', $spam_actions[$params['delivery_type']]);
		}
		foreach (array(
		      'spam_tag' => 'spam_tag', 
		      'content_tag' => 'content_subject',
		      'file_tag' => 'file_subject', 
		      'virus_tag' => 'virus_subject') as $keytag => $desttag) {
			if (isset($params[$keytag])) {
				$domain->setPref($desttag, $params[$keytag]);
			}
		}
		## Authentication
		if (isset($params['auth_connector'])) {
             $connectorformclass = 'Default_Form_Domain_UserAuthentication_'.ucfirst($params['auth_connector']);
             if (class_exists($connectorformclass)) {
                 $connectorform  = new $connectorformclass($domain);
                 $data = array();
                 foreach (array(
                        'auth_server' => 'auth_server',
                        'auth_use_ssl' => 'use_ssl', 
                        'a_base_dn' => 'basedn', 
                        'a_bind_user' => 'binddn', 
                        'a_bind_pass' => 'bindpass',
                        'a_user_attr' => 'userattribute',
                        'a_protocol_version' => 'ldapversion') as $key => $value) {
                    if (isset($params[$key])) {
                        $data[$value] = $params[$key];
                    }
                 } 
                 $connectorform->setParamsFromArray($data, $domain);
             } else {
                 throw new Exception('Authentication connector does not exists');
             }
		}
		if ( isset($params['username_modifier']) && preg_match('/^(username|username\@domain|username\%domain)$/', $params['username_modifier'])) {
			switch ($params['username_modifier']) {
				case 'username':
					$domain->setPref('auth_modif', 'username');
					break;
				case 'username@domain':
                    $domain->setPref('auth_modif', 'at_add');
					break;
				case 'username%domain':
                    $domain->setPref('auth_modif', 'percent_add');
					break;
			}
		}
	    if ( isset($params['address_lookup']) && preg_match('/^(username\@domain|ldap|local)$/', $params['address_lookup'])) {
            switch($params['address_lookup']) {
            	case 'username@domain':
            		$domain->setPref('address_fetcher', 'at_login');
            		break;
            	case 'ldap':
                    $domain->setPref('address_fetcher', 'ldap');
            		break;
            	case 'local':
                    $domain->setPref('address_fetcher', 'local');
            		break;        	
            }
        }   
        ## Filtering
        foreach (array('spamwall', 'contentwall', 'viruswall') as $key) {
        	if (isset($params[$key]) && preg_match('/^[01]$/', $params[$key])) {
        		$domain->setPref($key, $params[$key]);
        	}
        }
        if (isset($params['greylist']) && preg_match('/^[01]$/', $params['greylist'])) {
            $domain->setParam('greylist', $params['greylist']);
        }
        foreach (array('whitelists' => 'enable_whitelists', 'warnlists' => 'enable_warnlists', 'notice_wwlists' => 'notice_wwlists_hit') as $key => $storekey) {
        	if (isset($params[$key]) && preg_match('/^[01]$/', $params[$key])) {
        		$domain->setPref($storekey, $params[$key]);
        	}
	}
        foreach (array('prevent_spoof', 'require_incoming_tls', 'reject_capital_domain') as $key) {
        	if ( isset($params[$key]) && preg_match('/^[01]$/', $params[$key])) {
        		$domain->setPref($key, $params[$key]);
        	}
        }
        ## Outgoing
        foreach (array('allow_smtp_auth' => 'allow_smtp_auth', 'require_outgoing_tls' => 'require_outgoing_tls', 'batv_enable' => 'batv_check') as $key => $storekey) {
            if ( isset($params[$key]) && preg_match('/^[01]$/', $params[$key])) {
                $domain->setPref($storekey, $params[$key]);
            }
        }   
        foreach (array('batv_secret' => 'batv_secret','dkim_domain' => 'dkim_domain', 'dkim_selector' => 'dkim_selector', 'dkim_private_key' => 'dkim_pkey') as $key => $storekey) {
            if (isset($params[$key])) {
             	$domain->setPref($storekey, $params[$key]);
            }
        }
        ## Archiving
        if (isset($params['send_to_archiver']) && preg_match('/^[01]$/', $params['send_to_archiver'])) {
        	$domain->setPref('archive_mail', $params['send_to_archiver']);
        }
        if (isset($params['send_copy_to']) && preg_match('/^\S+\@\S+$/', $params['send_copy_to'])) {
        	$domain->setPref('copyto_mail', $params['send_copy_to']);
        }
        ## Templates
        foreach (array('web_template', 'summary_template') as $key) {
        	if (isset($params[$key]) && preg_match('/^[a-zA-Z-_]+$/', $params[$key])) {
        		$domain->setPref($key, $params[$key]);
        	}
        }
	}
	
	private function getDomainParams($domain, $params = array()) {
		$data = array();
		$data['name'] = $domain->getParam('name');
	    ## aliases
        if (empty($params) || in_array('aliases', $params)) {
            $data['aliases'] = implode(',', $domain->getAliases());
        }
	    ## general options
        foreach (array('systemsender', 'falseneg_to', 'falsepos_to', 'supportname', 'supportemail') as $pref) {
            if (empty($params) || in_array($pref, $params)) {
                $data[$pref] = $domain->getPref($pref);
            }
        }
	    ## delivery
        $options = $domain->getDestinationActionOptions();
        if (empty($params) || in_array('destination_mode', $params)) {
            $data['destination_mode'] = $domain->getDestinationMultiMode();
        }
        if (empty($params) || in_array('destination_usemx', $params)) {
            $data['destination_usemx'] = "0";
            if ($domain->getDestinationUseMX() == 'true') {
                $data['destination_usemx'] = "1";
            }
        }
        if (empty($params) || in_array('destination', $params)) {
            $data['destination'] = preg_replace('/\n/', ',', $domain->getDestinationFieldStringForAPI());
        }
        
        ## Address verification
        if (empty($params) || in_array('callout_connector', $params)) {
            $data['callout_connector'] = $domain->getCalloutConnector();
        }
        $connectorformclass = 'Default_Form_Domain_AddressVerification_'.ucfirst($domain->getCalloutConnector());
        if (class_exists($connectorformclass)) {
            $connectorform  = new $connectorformclass($domain);
            $calloutparams = $connectorform->getParams();
            foreach (array(
                'c_base_dn' => 'basedn',
                'c_bind_user' => 'binddn',
                'c_bind_pass' => 'bindpass',
                'callout_server' => 'callout_server',
                'c_group' => 'group',
                'c_use_ssl' => 'usessl'
            ) as $key => $value) {
            	if (isset($calloutparams[$value]) && (empty($params) || in_array($key, $params))) {
            		$data[$key] = $calloutparams[$value];
            	}
            }
        }
	    ## Preferences
        if (empty($params) || in_array('language', $params)) {
            $data['language'] = $domain->getPref('language');
        }
        if (empty($params) || in_array('summary_frequency', $params)) {
        	$data['summary_frequency'] = $domain->getSummaryFrequencyName();
        }
        if (empty($params) || in_array('summary_type', $params)) {
        	$data['summary_type'] = $domain->getPref('summary_type');
        }
        if (empty($params) || in_array('delivery_type', $params)) {
            $data['delivery_type'] = $domain->getSpamActionName();
        }
        foreach (array(
              'spam_tag' => 'spam_tag', 
              'content_tag' => 'content_subject',
              'file_tag' => 'file_subject', 
              'virus_tag' => 'virus_subject') as $keytag => $desttag) {
            if (empty($params) || in_array($keytag, $params)) {
                $data[$keytag] = $domain->getPref($desttag);
            }
        }
        ## Authentication
	    if (empty($params) || in_array('auth_connector', $params)) {
            $data['auth_connector'] = $domain->getAuthConnector();
        }
        $connectorformclass = 'Default_Form_Domain_UserAuthentication_'.ucfirst($domain->getAuthConnector());
        if (class_exists($connectorformclass)) {
            $connectorform  = new $connectorformclass($domain);
            $authparams = $connectorform->getParams();
            foreach (array(
                'a_base_dn' => 'basedn',
                'a_bind_user' => 'binddn',
                'a_bind_pass' => 'bindpass',
                'a_user_attr' => 'userattr',
                'a_protocol_version' => 'version',
                'auth_use_ssl' => 'use_ssl',
                'auth_server' => 'auth_server'
            ) as $key => $value) {
                if (isset($authparams[$value]) && (empty($params) || in_array($key, $params))) {
                    $data[$key] = $authparams[$value];
                }
            }
        }
        
	## Filtering, Templates
        foreach (array('prevent_spoof', 'require_incoming_tls', 'spamwall', 'contentwall', 'viruswall', 'greylist', 'web_template', 'summary_template', 'reject_capital_domain') as $key) {
            if (empty($params) || in_array($key, $params)) {
            	if (!$domain->getPref($key)) {
            		$data[$key] = "0";
            	} else {
                    $data[$key] = $domain->getPref($key);
            	}
            }
        }
        foreach (array('whitelists' => 'enable_whitelists', 'warnlists' => 'enable_warnlists', 'notice_wwlists' => 'notice_wwlists_hit') as $key => $storekey) {
        	if (empty($params) || in_array($key, $params)) {
        		if (!$domain->getPref($storekey)) {
        		    $data[$key] = "0";
                } else {
                    $data[$key] = $domain->getPref($storekey);
                }
        	}
        }
        
        ## Outgoing
        foreach (array('allow_smtp_auth' => 'allow_smtp_auth', 'require_outgoing_tls' => 'require_outgoing_tls', 'batv_enable' => 'batv_check') as $key => $storekey) {
        	$data[$key] = $domain->getPref($storekey);
        }
        foreach (array('batv_secret' => 'batv_secret','dkim_domain' => 'dkim_domain', 'dkim_selector' => 'dkim_selector', 'dkim_private_key' => 'dkim_pkey') as $key => $storekey) {
        	$data[$key] = $domain->getPref($storekey);
        }
        
        ## Archiving
        $data['send_to_archiver'] = $domain->getPref('archive_mail');
        $data['send_copy_to'] = $domain->getPref('copyto_mail');
        
        foreach ($data as $key => $value) {
        	$data[$key] = preg_replace('/\n$/', '', $value);
        }
		return $data;
	}
}
