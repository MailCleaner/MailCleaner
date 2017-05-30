<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain callout form
 */

class Default_Form_DomainAddressverification extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'addressverification';
	protected $_request;
	
	protected $_connectors = array('smtp', 'ldap', 'local', 'none');
	
	public function __construct($domain, $whitelist = NULL, $warnlist = NULL, $blacklist = NULL, $request)
	{
	    $this->_domain = $domain;

	    parent::__construct();
	}
	
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'domain_form');
	    $panellist = new Zend_Form_Element_Select('domainpanel', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $panellist->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_domain->getConfigPanels() as $panel => $panelname) {
        	$panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);
        
        $panel = new Zend_Form_Element_Hidden('panel');
		$panel->setValue($this->_panelname);
		$this->addElement($panel);
		$name = new Zend_Form_Element_Hidden('name');
		$name->setValue($this->_domain->getParam('name'));
		$this->addElement($name);
		
	    $domainname = new  Zend_Form_Element_Text('domainname', array(
            'label'   => $t->_('Domain name')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $domainname->setValue($this->_domain->getParam('name'));
	    require_once('Validate/DomainName.php');
        $domainname->addValidator(new Validate_DomainName());
	    $this->addElement($domainname);	
		
		
		foreach ($this->_connectors as $connector) {
			$connectorformclass = 'Default_Form_Domain_AddressVerification_'.ucfirst($connector);
			$connectorform  = new $connectorformclass($this->_domain);
			$connectorform->addForm($this);
		}
		
		$connectorlist = new Zend_Form_Element_Select('connector', array(
		    'label'      => $t->_('Callout connector')." : ",
                    'title' => $t->_("choose type of callout"),
            'required'   => true,
		    'onchange'   => 'javascript:changeConnector();',
            'filters'    => array('StringTrim')));
        
        foreach ($this->_connectors as $connector) {
        	$connectorlist->addMultiOption($connector, $t->_($connector));
        }
        $connectorlist->setValue($this->_domain->getCalloutConnector());
        $this->addElement($connectorlist);
        
		$test = new Zend_Form_Element_Button('testcallout', array(
		     'label'    => $t->_('Test configuration'),
             'onclick' => 'javascript:stopreloadtest=0;testCallout(\''.$this->_domain->getParam('name').'\', 1);'));
		$this->addElement($test);
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);	
		
	}

    public function setParams($request, $domain) {
    	$connectorformclass = 'Default_Form_Domain_AddressVerification_'.ucfirst($request->getParam('connector'));
    	$connectorform  = new $connectorformclass($domain);
    	$connectorform->setParams($request, $domain);
		return true;
	}
}
