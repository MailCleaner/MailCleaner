<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain general settings form
 */

class Default_Form_DomainGeneral extends Zend_Form
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


            $enabledomain = new Zend_Form_Element_Checkbox('enabledomain', array(
                'label'   =>  'Domain is active :',
                'title' => $t->_("By default, domains are activated"),
                'uncheckedValue' => "0",
                'checkedValue' => "1"));

            if ($this->_domain->getParam('active')) {
                $enabledomain->setChecked(true);
            }
            $this->addElement($enabledomain);


		
		$alias = new Zend_Form_Element_Textarea('aliases', array(
		      'label'    =>  $t->_('Aliases')." :",
		      'title' => $t->_("Add an alias for this domain. Aliases are seen as classical domains by the system"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
		require_once('Validate/DomainList.php');
        $alias->addValidator(new Validate_DomainList());
		$alias->setValue(implode("\n",$this->_domain->getAliases()));
	    $this->addElement($alias);

		$sender = new  Zend_Form_Element_Text('systemsender', array(
            'label'   => $t->_('System sender')." :",
		    'title' => $t->_("Mail address for summaries"),
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    $sender->setValue($this->_domain->getPref('systemsender'));
        require_once('Validate/EmailAddressField.php');
        $sender->addValidator(new Validate_EmailAddressField());
	    $this->addElement($sender);	
	    
	    $falseneg = new  Zend_Form_Element_Text('falseneg_to', array(
	        'label'    => $t->_('False negative address')." :",
		    'title' => $t->_("Mail for false negatives (mails which were not detected as spam when they should have been)"),
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $falseneg->setValue($this->_domain->getPref('falseneg_to'));
        $falseneg->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($falseneg);
	    
	    $falsepos = new  Zend_Form_Element_Text('falsepos_to', array(
	        'label'    => $t->_('False positive address')." :",
		'title' => $t->_("Mail for false positives (mails which were detected as spam when they shouldn\'t have been) (sent from analyze button in summaries)"),
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $falsepos->setValue($this->_domain->getPref('falsepos_to'));
        $falsepos->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($falsepos);
	    
	    $supportname = new  Zend_Form_Element_Text('supportname', array(
	        'label'    => $t->_('Support name')." :",
		'title' => $t->_("Name of the person in charge of the support"),
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $supportname->setValue($this->_domain->getPref('supportname'));
	    $this->addElement($supportname);
	    
	    $supportemail = new  Zend_Form_Element_Text('supportemail', array(
	        'label'    => $t->_('Support email')." :",
                'title' => $t->_("Email for release content"),
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $supportemail->setValue($this->_domain->getPref('supportemail'));
        $supportemail->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($supportemail);
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);	
	}
	
	public function setParams($request, $domain) {
		foreach (array('systemsender', 'falseneg_to', 'falsepos_to', 'supportname', 'supportemail') as $pref) {
		    $domain->setPref($pref, $request->getParam($pref));
		}
		$alias = preg_split('/\n/', $request->getParam('aliases'));
		sort($alias);

		$domain->setParam('active', $request->getParam('enabledomain'));

		return $domain->setAliases($alias);
	}

}
