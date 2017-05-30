<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * LDAP user authentication settings form
 */
 
class Default_Form_Domain_UserAuthentication_Ldap 
{
	protected $_domain;
	protected $_settings = array(
                        "basedn" =>'',
                        "binddn" => '',
                        "bindpw" => '',
                        "userattr" => '',
                        "useroc" => '',
                        "userfilter" => '',
                        "debug" => 'false',
	                    'use_ssl' => false,
                        "version" => 3,
                        "referrals" => false
                      );
	
	public function __construct($domain)
	{
	    $this->_domain = $domain;
	}
	
	public function addForm($form) {
		$name = new Zend_Form_Element_Hidden('connector');
		$name->setValue('ldap');
		$form->addElement($name);
		
	    $t = Zend_Registry::get('translate');
		
		require_once('Validate/SMTPHostList.php');
		$server = new  Zend_Form_Element_Text('ldapserver', array(
	        'label'    => $t->_('LDAP server')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $server->setValue($this->_domain->getPref('auth_server'));
        $server->addValidator(new Validate_SMTPHostList());
	    $form->addElement($server);
	    
	    $this->_settings = $this->getParams();
	    	    
	    $basedn = new  Zend_Form_Element_Text('basedn', array(
	        'label'    => $t->_('Base DN')." :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    $basedn->setValue($this->_settings['basedn']);
	    $form->addElement($basedn);
	    
	    $binddn = new  Zend_Form_Element_Text('binddn', array(
	        'label'    => $t->_('Bind user')." :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    $binddn->setValue($this->_settings['binddn']);
	    $form->addElement($binddn);
	    
	    $bindpass = new  Zend_Form_Element_Password('bindpass', array(
	        'label'    => $t->_('Bind password')." :",
		    'required' => false,
	        'renderPassword' => true,
		    'filters'    => array('StringTrim')));
	    $bindpass->setValue($this->_settings['bindpw']);
	    $form->addElement($bindpass);
	    
	    $userattr = new  Zend_Form_Element_Text('userattribute', array(
	        'label'    => $t->_('User attribute')." :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    $userattr->setValue($this->_settings['userattr']);
	    $form->addElement($userattr);
	    
	    $ldapusesslcheck = new Zend_Form_Element_Checkbox('ldapusessl', array(
	        'label'   => $t->_('Use SSL'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	              
	    if ($this->_settings['use_ssl']) {
            $ldapusesslcheck->setChecked(true);
	    }
	    $form->addElement($ldapusesslcheck);
	    
	    $version = new Zend_Form_Element_Select('ldapversion', array(
            'label'      => $t->_('Protocol version')." :",
            'required'   => false,
            'filters'    => array('StringTrim')));
        
        foreach (array(2, 3) as $value) {
        	$version->addMultiOption($value, $value);
        }
        $version->setValue($this->_settings['version']);
        $form->addElement($version);
	}
	
	public function setParams($request, $domain) {
		$array = array(
		   'basedn' => $request->getParam('basedn'),
		   'userattribute' => $request->getParam('userattribute'),
		   'binddn' => $request->getParam('binddn'),
		   'bindpass' => $request->getParam('bindpass'),
		   'use_ssl' => $request->getParam('ldapusessl'),
		   'ldapversion' => $request->getParam('ldapversion'),
		   'auth_server' => $request->getParam('ldapserver')
		);
		$this->setParamsFromArray($array, $domain);
	}
	
    public function setParamsFromArray($array, $domain) {
    	$domain->setPref('auth_type', 'ldap');
    	if (isset($array['auth_server'])) {
    		$domain->setPref('auth_server', $array['auth_server']);
    	}
    	$domain->setPref('auth_param', $this->getParamsString($array));
    }
        
    public function getParams() {
       $ldapparams = $this->_settings;
       if ($this->_domain->getAuthConnector() != 'ldap') {
           return $ldapparams;
       }
       if (preg_match('/([^:]*):([^:]*):([^:]*):([^:]*):([^:]*):([^:]*)/', $this->_domain->getPref('auth_param'), $matches)) {
            $ldapparams['basedn'] = $matches[1];
            $ldapparams['userattr'] = $matches[2];
            $ldapparams['binddn'] = $matches[3];
            $ldapparams['bindpw'] = $matches[4];
            $ldapparams['use_ssl'] = $matches[5];
            $ldapparams['version'] = $matches[6];
            $ldapparams['auth_server'] = $this->_domain->getPref('auth_server');
        }
        foreach ($ldapparams as $key => $value) {
            $ldapparams[$key] = preg_replace('/__C__/', ':', $value);
        }
        return $ldapparams;
    }
    
    public function getParamsString($params) {
    	$fields = array('basedn', 'userattribute', 'binddn', 'bindpass', 'use_ssl', 'ldapversion');
    	$str = '';
    	foreach ($fields as $key) {
    		if (isset($params[$key])) {
    			$params[$key] = preg_replace('/:/', '__C__', $params[$key]);
    		} else {
    			$params[$key] = $this->_settings[$key];
    		}
    		$str .= ':'.$params[$key];
    	}
    	$str = preg_replace('/^:/', '', $str);
    	return $str;
    }
	
}
