<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 */

class Default_Form_DomainAdvanced extends Zend_Form {
	protected $_domain;
	protected $_panelname = 'advanced';
	
	public function __construct($domain) {
	    $this->_domain = $domain;

	    parent::__construct();
	}
	
	
	public function init() {
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'domain_form');
		$panellist = new Zend_Form_Element_Select('domainpanel', array(
			'required'   => false,
			'filters'    => array('StringTrim'))
		);
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
			'filters'    => array('StringToLower', 'StringTrim'))
		);
		$domainname->setValue($this->_domain->getParam('name'));
		require_once('Validate/DomainName.php');
		$domainname->addValidator(new Validate_DomainName());
		$this->addElement($domainname);	

		$wwelement = new Default_Model_WWElement();
		require_once('Validate/IpList.php');

		$black_ip_dom = new Zend_Form_Element_Textarea('black_ip_dom', array(
			'label'		=>  $t->_('Blacklist those IPs at SMTP stage')." :",
			'title'		=> $t->_("List of IPs or subnets to be rejected at SMTP stage for the current domain"),
			'required'	=> false,
			'rows'		=> 5,
			'cols'		=> 30,
			'filters'	=> array('StringToLower', 'StringTrim'))
		);
		$black_ip_dom->addValidator(new Validate_IpList());
		$black_ip_dom->setValue($wwelement->fetchAllField($this->_domain->getParam('name'), 'black-ip-dom', 'sender'));
		$this->addElement($black_ip_dom);

		$spam_ip_dom = new Zend_Form_Element_Textarea('spam_ip_dom', array(
			'label'    =>  $t->_('Blacklist those IPs at SpamC stage')." :",
			'title' => $t->_("List of IPs or subnets to be blocked at SpamC stage for the current domain"),
			'required'   => false,
			'rows' => 5,
			'cols' => 30,
			'filters'    => array('StringToLower', 'StringTrim')));
		$spam_ip_dom->addValidator(new Validate_IpList());
		$spam_ip_dom->setValue($wwelement->fetchAllField($this->_domain->getParam('name'), 'spam-ip-dom', 'sender'));
		$this->addElement($spam_ip_dom);

		$white_ip_dom = new Zend_Form_Element_Textarea('white_ip_dom', array(
			'label'    =>  $t->_('Whitelist those IPs at SMTP stage')." :",
			'title' => $t->_("List of IPs or subnets to be whitelisted at SMTP stage for the current domain"),
			'required'   => false,
			'rows' => 5,
			'cols' => 30,
			'filters'    => array('StringToLower', 'StringTrim')));
		$white_ip_dom->addValidator(new Validate_IpList());
		$white_ip_dom->setValue($wwelement->fetchAllField($this->_domain->getParam('name'), 'white-ip-dom', 'sender'));
		$this->addElement($white_ip_dom);


		$wh_spamc_ip_dom = new Zend_Form_Element_Textarea('wh_spamc_ip_dom', array(
			'label'    =>  $t->_('Whitelist those IPs at SpamC stage')." :",
			'title' => $t->_("List of IPs or subnets to be whitelisted at SpamC stage for the current domain"),
			'required'   => false,
			'rows' => 5,
			'cols' => 30,
			'filters'    => array('StringToLower', 'StringTrim')));
		$wh_spamc_ip_dom->addValidator(new Validate_IpList());
		$wh_spamc_ip_dom->setValue($wwelement->fetchAllField($this->_domain->getParam('name'), 'wh-spamc-ip-dom', 'sender'));
		$this->addElement($wh_spamc_ip_dom);



		$submit = new Zend_Form_Element_Submit('submit', array(
			'label'    => $t->_('Submit'))
		);
		$this->addElement($submit);	
	}

	public function setParams($request, $domain) {
		$wwelement = new Default_Model_WWElement();
		$wwelement->setBulkSender($domain->getParam('name'), $request->getParam('black_ip_dom'), 'black-ip-dom');
		$wwelement->setBulkSender($domain->getParam('name'), $request->getParam('spam_ip_dom'), 'spam-ip-dom');
		$wwelement->setBulkSender($domain->getParam('name'), $request->getParam('white_ip_dom'), 'white-ip-dom');
		$wwelement->setBulkSender($domain->getParam('name'), $request->getParam('wh_spamc_ip_dom'), 'wh-spamc-ip-dom');

		return true;
	}
}
