<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain default preferences form
 */

class Default_Form_DomainPreferences extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'preferences';
	
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
		
		$language = new Zend_Form_Element_Select('language', array(
		    'label'      => $t->_('Language')." : ",
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $language->addValidator(new Zend_Validate_Alnum());
        
	    $config = MailCleaner_Config::getInstance();
	    foreach ($config->getUserGUIAvailableLanguages() as $lk => $lv) {
        	$language->addMultiOption($lk, $t->_($lv));
        }
        $language->setValue($this->_domain->getPref('language'));
        $this->addElement($language);

        $groupquarantines = new Zend_Form_Element_Checkbox('gui_group_quarantines', array(
            'label'   => $t->_('Group user\'s quarantines'). " :",
            'title' => $t->_("If a user has many mail addresses, all his quarantines will be grouped"),
            'uncheckedValue' => "0",
            'checkedValue' => "1" 
                  )); 
    
        if ($this->_domain->getPref('gui_group_quarantines')) {
            $groupquarantines->setChecked(true);
        }   
        $this->addElement($groupquarantines);
        
        
        $action = new Zend_Form_Element_Select('delivery_type', array(
            'label'     => $t->_('Action on spams')." : ",
            'title' => $t->_("Quarantine sends the detected messages in the user's quarantine / Tag delivers the email in a classical way but adds a tag in the subject of the email / Drop drops the concerned emails"),
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $action->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_domain->getSpamActions() as $key => $value) {
        	$action->addMultiOption($value, $t->_($key));
        }
        $action->setValue($this->_domain->getPref('delivery_type'));
        $this->addElement($action);
        
        $spamtag = new  Zend_Form_Element_Text('spam_tag', array(
            'label'   => $t->_('Subject spam tag')." :",
            'title' => $t->_("Tag added in the beginning of the subjet of a spam if you are in tag mode"),
		    'required' => false,));
	    $spamtag->setValue($this->_domain->getPref('spam_tag'));
	    $this->addElement($spamtag);
	    
	    $contenttag = new  Zend_Form_Element_Text('content_subject', array(
            'label'   => $t->_('Subject dangerous content tag')." :",
            'title' => $t->_("Tag added in the beginning of the subjet of a email with dangerous content if you are in tag mode"),
		    'required' => false,));
	    $contenttag->setValue($this->_domain->getPref('content_subject'));
	    $this->addElement($contenttag);
	    
	    $filetag = new  Zend_Form_Element_Text('file_subject', array(
            'label'   => $t->_('Subject dangerous file tag')." :",
            'title' => $t->_("Tag added in the beginning of the subjet of a email containing a dangerous file if you are in tag mode"),
		    'required' => false,));
	    $filetag->setValue($this->_domain->getPref('file_subject'));
	    $this->addElement($filetag);
	    
	    $virustag = new  Zend_Form_Element_Text('virus_subject', array(
            'label'   => $t->_('Subject virus tag')." :",
            'title' => $t->_("Tag added in the beginning of the subjet of a email containing a virus if you are in tag mode"),
		    'required' => false,));
	    $virustag->setValue($this->_domain->getPref('virus_subject'));
	    $this->addElement($virustag);
        
	    $frequency = new Zend_Form_Element_Select('summaryfrequency', array(
            'label'     => $t->_('Summary frequency')." : ",
            'title' => $t->_("Choose the spam summaries send frequency"),
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
        
        foreach ($this->_domain->getSummaryFrequencies() as $key => $value) {
        	$frequency->addMultiOption($value, $t->_($key));
        }
        $frequency->setValue($this->_domain->getSummaryFrequency());
        $this->addElement($frequency);
        
        $sumtype = new Zend_Form_Element_Select('summary_type', array(
            'label'     => $t->_('Summary type')." : ",
            'title' => $t->_("Choose the spam summaries type"),
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
        
        foreach ($this->_domain->getSummaryTypes() as $key => $value) {
        	$sumtype->addMultiOption($value, $t->_($key));
        }
        $sumtype->setValue($this->_domain->getPref('summary_type'));
        $this->addElement($sumtype);

        $summaryto = new  Zend_Form_Element_Text('summary_to', array(
                'label'    => $t->_('Default summary recipient')." :",
                'title' => $t->_("Define a unique recipient for this domain"),
                'required' => false,
                'filters'    => array('StringToLower', 'StringTrim')));
        $summaryto->setValue($this->_domain->getPref('summary_to'));
        $summaryto->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($summaryto);
        
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
	}
	
    public function setParams($request, $domain) {
		foreach (array('language', 'gui_group_quarantines', 'summary_type', 'summary_to', 'virus_subject', 'file_subject', 'content_subject', 'spam_tag', 'delivery_type') as $pref) {
            if ($request->getParam($pref) || is_string($request->getParam($pref)) ) {
			    $domain->setPref($pref, $request->getParam($pref));
		    }	    
		}
		$domain->setSummaryFrequency($request->getParam('summaryfrequency'));
	}

}
