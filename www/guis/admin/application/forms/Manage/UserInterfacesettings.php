<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * User interface form
 */

class Default_Form_Manage_UserInterfacesettings extends Zend_Form
{
	protected $_user;
	protected $_domain;
	protected $_panelname = 'interfacesettings';
	
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
		
		$language = new Zend_Form_Element_Select('language', array(
		    'label'      => $t->_('Language')." : ",
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
        
	    $config = MailCleaner_Config::getInstance();
	    foreach ($config->getUserGUIAvailableLanguages() as $lk => $lv) {
        	$language->addMultiOption($lk, $t->_($lv));
        }
        $language->setValue($this->_user->getPref('language'));
        $this->addElement($language);
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);	
	}
	
	public function setParams($request, $user) {
		foreach (array('language') as $pref) {
            if ($request->getParam($pref)) {
			    $user->setPref($pref, $request->getParam($pref));
		    }	    
		}
		return true;
	}

}
