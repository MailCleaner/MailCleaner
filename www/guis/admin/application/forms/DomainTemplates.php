<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain templates form
 */

class Default_Form_DomainTemplates extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'templates';
	
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
	    		
		$webtemplate = new Zend_Form_Element_Select('web_template', array(
            'required'   => false,
		    'label'      => $t->_('Web user GUI')." : ",
                    'title' => $t->_("Template name for the user's web interface"),
            'filters'    => array('StringTrim')));
        
        foreach ($this->_domain->getWebTemplates() as $template) {
        	$webtemplate->addMultiOption($template, $template);
        }
        $webtemplate->setValue($this->_domain->getPref('web_template'));
        $this->addElement($webtemplate);
        
        $sumtemplate = new Zend_Form_Element_Select('summary_template', array(
            'required'   => false,
		    'label'      => $t->_('Quarantine summary')." : ",
                    'title' => $t->_("Template name for the emails quarantine"),
            'filters'    => array('StringTrim')));
        
        foreach ($this->_domain->getSummaryTemplates() as $template) {
        	$sumtemplate->addMultiOption($template, $template);
        }
        $sumtemplate->setValue($this->_domain->getPref('summary_template'));
        $this->addElement($sumtemplate);
        
        
        $reptemplate = new Zend_Form_Element_Select('report_template', array(
            'required'   => false,
            'label'      => $t->_('Content protection reports')." : ",
            'title' => $t->_("Template name for content protection's reports"),
            'filters'    => array('StringTrim')));

        foreach ($this->_domain->getReportTemplates() as $template) {
                $reptemplate->addMultiOption($template, $template);
        }
        $reptemplate->setValue($this->_domain->getPref('report_template'));
        $this->addElement($reptemplate);


                $submit = new Zend_Form_Element_Submit('submit', array(
                     'label'    => $t->_('Submit')));
                $this->addElement($submit);
        }

    public function setParams($request, $domain) {
    	foreach (array('web_template', 'summary_template', 'report_template') as $p) {
    	    $domain->setPref($p, $request->getParam($p));
    	}
		return true;
	}
}
