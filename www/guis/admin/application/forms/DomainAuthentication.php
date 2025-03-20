<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Domain authentication settings form
 */

class Default_Form_DomainAuthentication extends Zend_Form
{
    protected $_domain;
    protected $_panelname = 'authentication';

    protected $_connectors = ['none', 'imap', 'pop3', 'ldap', 'smtp', 'local', 'radius', 'sql', 'tequila'];

    protected $_addresslookups = ['at_login', 'ldap', 'local', 'text_file', 'param_add', 'mysql'];
    protected $_usernameformats = ['username_only', 'at_add', 'percent_add'];

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
        $panellist = new Zend_Form_Element_Select('domainpanel', [
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);
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
        $domainname = new  Zend_Form_Element_Text('domainname', [
            'label'   => $t->_('Domain name') . " :",
            'required' => false,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $domainname->setValue($this->_domain->getParam('name'));
        require_once('Validate/DomainName.php');
        $domainname->addValidator(new Validate_DomainName());
        $this->addElement($domainname);

        foreach ($this->_connectors as $connector) {
            $connectorformclass = 'Default_Form_Domain_UserAuthentication_' . ucfirst($connector);
            $connectorform  = new $connectorformclass($this->_domain);
            $connectorform->addForm($this);
        }

        $connectorlist = new Zend_Form_Element_Select('connector', [
            'label'      => $t->_('Authentication type') . " : ",
            'title' => $t->_("Choose how users will authenticate on MailCleaner web interface"),
            'required'   => true,
            'onchange'   => 'javascript:changeAuthConnector();',
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_connectors as $connector) {
            $connectorlist->addMultiOption($connector, $t->_('userauthconn_' . $connector));
        }
        $connectorlist->setValue($this->_domain->getPref('auth_type'));
        $this->addElement($connectorlist);


        $usernameformat = new Zend_Form_Element_Select('usernameformat', [
            'label'      => $t->_('Username modifier') . " : ",
            'title' => $t->_("How MailCleaner will send the login to the auth server"),
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_usernameformats as $format) {
            $usernameformat->addMultiOption($format, $t->_('usermod_' . $format));
        }
        $usernameformat->setValue($this->_domain->getPref('auth_modif'));
        $this->addElement($usernameformat);

        $addresslookup = new Zend_Form_Element_Select('addresslookup', [
            'label'      => $t->_('Address lookup') . " : ",
            'title' => $t->_("How MailCleaner fetch or build address for a user"),
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_addresslookups as $lookup) {
            $addresslookup->addMultiOption($lookup, $t->_('addlook_' . $lookup));
        }
        $addresslookup->setValue($this->_domain->getPref('address_fetcher'));
        $this->addElement($addresslookup);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);


        $testusername = new  Zend_Form_Element_Text('testusername', [
            'label'    => $t->_('Test username') . " :",
            'title' => $t->_("Data used to ensure the chosen authentication type is working"),
            'required' => false,
            'filters'    => ['StringTrim']
        ]);
        $this->addElement($testusername);

        $testpassword = new  Zend_Form_Element_Password('testpassword', [
            'label'    => $t->_('Test password') . " :",
            'title' => $t->_("Data used to ensure the chosen authentication type is working"),
            'required' => false
        ]);
        $this->addElement($testpassword);

        $test = new Zend_Form_Element_Button('testuserauth', [
            'label'    => $t->_('Test authentication'),
            'onclick' => 'javascript:stopreloadtest=0;testUserauth(\'' . $this->_domain->getParam('name') . '\', 1);'
        ]);
        $this->addElement($test);
    }

    public function setParams($request, $domain)
    {
        $connectorformclass = 'Default_Form_Domain_UserAuthentication_' . ucfirst($request->getParam('connector'));
        $connectorform  = new $connectorformclass($domain);
        $domain->setPref('auth_modif', $request->getParam('usernameformat'));
        $domain->setPref('address_fetcher', $request->getParam('addresslookup'));
        $connectorform->setParams($request, $domain);
        return true;
    }
}
