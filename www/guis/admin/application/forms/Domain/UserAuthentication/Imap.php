<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * IMAP user authentication settings form
 */

class Default_Form_Domain_UserAuthentication_Imap
{
	protected $_domain;
	protected $_settings = array(
                        "use_ssl" => false);
	
	public function __construct($domain)
	{
	    $this->_domain = $domain;
	}
	
	public function addForm($form) {
		$name = new Zend_Form_Element_Hidden('connector');
		$name->setValue('none');
		$form->addElement($name);
		
		$t = Zend_Registry::get('translate');
		
		require_once('Validate/SMTPHostList.php');
		$server = new  Zend_Form_Element_Text('authserver', array(
	        'label'    => $t->_('Authentication server')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $server->setValue($this->_domain->getPref('auth_server'));
        $server->addValidator(new Validate_SMTPHostList());
	    $form->addElement($server);
	    
	    $this->_settings = $this->getParams();
	    
	    $imapusesslcheck = new Zend_Form_Element_Checkbox('imapusessl', array(
	        'label'   => $t->_('Use SSL'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	              
	    if ($this->_settings['use_ssl']) {
            $imapusesslcheck->setChecked(true);
	    }
	    $form->addElement($imapusesslcheck);
	    
	}
	
	public function setParams($request, $domain) {
	   $array = array(
	      'auth_server' => $request->getParam('authserver'),
	      'use_ssl' => $request->getParam('imapusessl')
	   );
	   $this->setParamsFromArray($array, $domain);
	}
	
    public function setParamsFromArray($array, $domain) {
       $domain->setPref('auth_type', 'imap');
       $domain->setPref('auth_server', $array['auth_server']);
       $domain->setPref('auth_param', $this->getParamsString($array));
    }

    public function getParams() {
    	$params = array('use_ssl' => 0);
        if ($this->_domain->getAuthConnector() != 'imap') {
           return $params;
        }
        if (preg_match('/([^:]*)/', $this->_domain->getPref('auth_param'), $matches)) {
            $params['use_ssl'] = $matches[1];
        }
        foreach ($params as $key => $value) {
            $params[$key] = preg_replace('/__C__/', ':', $value);
       }
       $params['auth_server'] = $this->_domain->getPref('auth_server');
       return $params;
    }
    
    public function getParamsString($params) {
       $str = implode(':', array($params['use_ssl']));
       return $str;
    }
	
}