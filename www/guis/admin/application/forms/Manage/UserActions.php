<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * User action form
 */

class Default_Form_Manage_UserActions extends Zend_Form
{
	protected $_user;
	protected $_domain;
	protected $_panelname = 'actions';
	
	public function __construct($user, $domain)
	{
	    $this->_user = $user;
	    $this->_domain = $domain;

	    parent::__construct();
	}
	
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'user_form');
	    $panellist = new Zend_Form_Element_Select('userpanel', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $panellist->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_user->getConfigPanels() as $panel => $panelname) {
        	$panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);
        
        $panel = new Zend_Form_Element_Hidden('panel');
		$panel->setValue($this->_panelname);
		$this->addElement($panel);
		$name = new Zend_Form_Element_Hidden('username');
		$name->setValue($this->_user->getParam('username'));
		$this->addElement($name);
		$domain = new Zend_Form_Element_Hidden('domain');
		if ($this->_user->getParam('domain')) {
            $domain->setValue($this->_user->getParam('domain'));
		} else {
		    $domain->setValue($this->_domain);
		}
		$this->addElement($domain);
		
		$addresses = new Zend_Form_Element_Select('addresses', array(
	        'label'    => $t->_('Address')." :",
            'required'   => false,
            'filters'    => array('StringTrim')));
        
        foreach ($this->_user->getAddresses() as $address => $ismain) {
        	$addresses->addMultiOption($address, $address);
        }
        $addresses->setValue($this->_user->getPref('addresses'));
        $this->addElement($addresses);
        
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('> go to preferences')));
		$this->addElement($submit);	
	}
	
	public function setParams($request, $user) {
		$email = new Default_Model_Email();
		$email->find($request->getParam('addresses'));
		$params = array(
		   'email' => $request->getParam('addresses'),
		   'address' => $request->getParam('addresses'),
		   'search' => $email->getLocalPart(),
		   'domain' => $email->getDomainObject()->getParam('name'),
		   'type' => 'email'
		);
		Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoSimple('editemail', null, null, $params);
		foreach (array('') as $pref) {
            if ($request->getParam($pref)) {
			    $domain->setPref($pref, $request->getParam($pref));
		    }	    
		}
		return true;
	}

}
