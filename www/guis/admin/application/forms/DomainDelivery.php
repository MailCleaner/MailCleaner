<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain delivery form
 */

class Default_Form_DomainDelivery extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'delivery';
	
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
        
		require_once('Validate/SMTPHostList.php');
		$servers = new Zend_Form_Element_Textarea('servers', array(
		      'label'    =>  $t->_('Destination servers')." :",
                      'title' => $t->_("Name or IP address of the server which will handle the mails once the have been cleaned"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $servers->addValidator(new Validate_SMTPHostList());
		$servers->setValue($this->_domain->getDestinationFieldString());
		$this->addElement($servers);
		
		$port = new  Zend_Form_Element_Text('port', array(
	        'label'    => $t->_('Destination port')." :",
		    'required' => false,
		    'size' => 4,
		    'filters'    => array('Alnum', 'StringTrim')));
	    $port->setValue($this->_domain->getDestinationPort());
        $port->addValidator(new Zend_Validate_Int());
	    $this->addElement($port);
		
	    $multiple = new Zend_Form_Element_Select('multipleaction', array(
            'label'      => $t->_('Use multiple servers as')." :",
            'title' => $t->_("Choose method to deliver mails to destination server"),
            'required'   => false,
            'filters'    => array('StringTrim')));
        
        foreach ($this->_domain->getDestinationActionOptions() as $key => $value) {
        	$multiple->addMultiOption($key, $t->_($key));
                $options = $this->_domain->getDestinationActionOptions();
                if (in_array($options[$key], $this->_domain->getActiveDestinationOptions())) {
                    $multiple->setValue($key);
                }
        }
        #$multiple->setValue('');
        $this->addElement($multiple);
        
        $usemx = new Zend_Form_Element_Checkbox('usemx', array(
	    'label'   => $t->_('Use MX resolution'). " :",
            'title' => $t->_("If destination servers have MX record in internal"),
            'uncheckedValue' => "0",
	    'checkedValue' => "1"
	));
	if ($this->_domain->getDestinationUseMX()) {
            $usemx->setChecked(true);
	}
	$this->addElement($usemx);
        
        $test = new Zend_Form_Element_Button('testdestinationSMTP', array(
		     'label'    => $t->_('Test destinations'),
             'onclick' => 'javascript:stopreloadtest=0;testDestinationSMTP(\''.$this->_domain->getParam('name').'\', 1);'));
		$this->addElement($test);
		
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);	
		
	}
	
    public function setParams($request, $domain) {
    	$domain->setDestinationPort($request->getParam('port'));
    	$domain->setDestinationOption($request->getParam('multipleaction'));
    	$domain->setDestinationUseMX($request->getParam('usemx'));
    	$domain->setDestinationServersFieldString($request->getParam('servers'));

        if ($request->getParam('servers') == '' && !$domain->getDestinationUseMX()) {
            throw new Exception('no valid destination server provided');
        }
		return true;
	}

}
