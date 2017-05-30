<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Proxies settings form
 */

class Default_Form_Proxies extends ZendX_JQuery_Form
{
	
	protected $_proxymanager;
	
	public function __construct($proxymng) {
		$this->_proxymanager = $proxymng;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'proxies_form');
        
		require_once('Validate/HostList.php');
        $http = new  Zend_Form_Element_Text('httpproxy', array(
            'label' => $t->_('HTTP proxy'). " :",
		    'required' => false));
	    $http->setValue($this->_proxymanager->getHttpProxy());
	    $http->addValidator(new Validate_HostList());
	    $this->addElement($http);
	   
	    $smtp = new  Zend_Form_Element_Text('smtpproxy', array(
	        'label' => $t->_('SMTP proxy'). " :",
		    'required' => false));
	    $smtp->setValue($this->_proxymanager->getSmtpProxy());
	    $smtp->addValidator(new Validate_HostList());
	    $this->addElement($smtp);
	   
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

}