<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * New domain form
 */

class Default_Form_DomainAdd extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'general';
	
	public function __construct($domain)
	{
	    $this->_domain = $domain;

	    parent::__construct();
	}
	
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'domain_form');
	    $defaultvalues = new Zend_Form_Element_Select('defaultvalues', array(
            'required'   => false,
		    'label'   =>  $t->_('Use default values from'). ": ",
            'filters'    => array('StringTrim')));
        
	    $defaultvalues->addMultiOption('__global__', $t->_('Global domains settings'));
        foreach ($this->_domain->fetchAllName() as $domain) {
        	$defaultvalues->addMultiOption($domain->getParam('name'), $domain->getParam('name'));
        }
        $defaultvalues->setValue('__global__');
        $this->addElement($defaultvalues);
		
		$domainname = new  Zend_Form_Element_Text('domainname', array(
            'label'   => $t->_('Domain name')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $domainname->setValue($this->_domain->getParam('name'));
	    require_once('Validate/DomainName.php');
        $domainname->addValidator(new Validate_DomainName());
	    $this->addElement($domainname);	
	    
	    $mdomainname = new  Zend_Form_Element_Textarea('mdomainname', array(
            'label'   => $t->_('Domain name')." :",
		    'required' => false,
		    'rows' => 5,
		    'cols' => 30,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $this->addElement($mdomainname);	
		
	    $topdomains = new Zend_Form_Element_Select('topdomains', array(
		    'required' => false));
	    $domains = new Default_Model_Domain();
	    $domains = $domains->fetchAllName();
	    foreach ($domains as $d) {
	        $topdomains->addMultiOption($d->getParam('name'), '.'.$d->getParam('name'));
	    }
	    $topdomains->setValue($this->_params['domain']);
	    $this->addElement($topdomains);
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);	
	}
	
	public function setParams($request, $domain) {
		
	}

}