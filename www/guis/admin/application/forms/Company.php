<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Company form
 */

class Default_Form_Company extends ZendX_JQuery_Form
{

	protected $_systemconf;
	
	public function __construct($conf) {
		$this->_systemconf = $conf;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'company_form');
		        
        $company = new  Zend_Form_Element_Text('companyname', array(
	        'label'    => $t->_('Company name')." :",
            'size' => 40,
		    'required' => false));
	    $company->setValue($this->_systemconf->getParam('organisation'));
	    $this->addElement($company);
	    
	    $contactname = new  Zend_Form_Element_Text('contactname', array(
	        'label'    => $t->_('Contact name')." :",
            'size' => 40,
		    'required' => false));
	    $contactname->setValue($this->_systemconf->getParam('contact'));
	    $this->addElement($contactname);
	    
	    $contactemail = new  Zend_Form_Element_Text('contactemail', array(
	        'label'    => $t->_('Contact email address')." :",
            'size' => 40,
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $contactemail->setValue($this->_systemconf->getParam('contact_email'));
        $contactemail->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($contactemail);
	    
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

}