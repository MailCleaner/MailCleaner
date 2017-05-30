<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Tequila user authentication settings form
 */

class Default_Form_Domain_UserAuthentication_Tequila
{
	protected $_domain;
	
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
		$server = new  Zend_Form_Element_Text('ldapserver', array(
	        'label'    => $t->_('Authentication server')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $server->setValue($this->_domain->getPref('auth_server'));
        $server->addValidator(new Validate_SMTPHostList());
	    $form->addElement($server);
	}
	
	public function setParams($request, $domain) {
	   $domain->setPref('auth_type', 'tequila');
       $domain->setPref('auth_param', $this->getParamsString($array));
	}

    public function setParamsFromArray($array, $domain) {
    }

    public function getParams() {
       return array();
    }
    
    public function getParamsString($params) {
       return '';
    }
}