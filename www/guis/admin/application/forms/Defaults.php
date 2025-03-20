<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Defaults settings form
 */

class Default_Form_Defaults extends ZendX_JQuery_Form
{
    protected $_systemconf;
    protected $_usergui;
    protected $_domains;

    public function __construct($conf, $usergui, $domains)
    {
        $this->_systemconf = $conf;
        $this->_usergui = $usergui;
        $this->_domains = $domains;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'defaults_form');

        $lang = new Zend_Form_Element_Select('language', [
            'label'      => $t->_('User GUI Language') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        $config = MailCleaner_Config::getInstance();
        foreach ($config->getUserGUIAvailableLanguages() as $lk => $lv) {
            $lang->addMultiOption($lk, $t->_($lv));
        }
        $lang->setValue($this->_systemconf->getParam('default_language'));
        $this->addElement($lang);

        $domain = new Zend_Form_Element_Select('domain', [
            'label'      => $t->_('Default domain') . " :",
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_domains as $d) {
            $domain->addMultiOption($d->getParam('name'), $d->getParam('name'));
        }
        $domain->setValue($this->_systemconf->getParam('default_domain'));
        $this->addElement($domain);

        $domainselect = new Zend_Form_Element_Checkbox('showdomainselector', [
            'label'   => $t->_('Display domain selector') . " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        $domainselect->setValue($this->_usergui->getParam('want_domainchooser'));
        $this->addElement($domainselect);

        $sysadmin = new  Zend_Form_Element_Text('sysadmin', [
            'label'   => $t->_('Support address') . " :",
            'title'    => $t->_('Name of the person in charge of the support'),
            'required' => false,
            'size' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $sysadmin->setValue($this->_systemconf->getParam('sysadmin'));
        $sysadmin->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($sysadmin);

        $sender = new  Zend_Form_Element_Text('systemsender', [
            'label'   => $t->_('System sender') . " :",
            'title'    => $t->_('Mail address for summaries'),
            'required' => false,
            'size' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $sender->setValue($this->_systemconf->getParam('summary_from'));
        $sender->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($sender);

        $falseneg = new  Zend_Form_Element_Text('falsenegaddress', [
            'label'    => $t->_('False negative address') . " :",
            'title'    => $t->_('Mail for false negatives (mails which were not detected as spam when they should have been)'),
            'required' => false,
            'size' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $falseneg->setValue($this->_systemconf->getParam('falseneg_to'));
        $falseneg->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($falseneg);

        $falsepos = new  Zend_Form_Element_Text('falseposaddress', [
            'label'    => $t->_('False positive address') . " :",
            'title'    => $t->_('Mail for false positives (mails which were detected as spam when they shouldn\'t have been) (sent from analyze button in summaries)'),
            'required' => false,
            'size' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $falsepos->setValue($this->_systemconf->getParam('falsepos_to'));
        $falsepos->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($falsepos);


        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }
}
