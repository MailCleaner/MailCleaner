<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 */

class Default_Form_DomainSpamcovercharge extends Zend_Form
{
    protected $_domain;
    protected $_panelname = 'spamcovercharge';

    public function __construct($domain)
    {
        $this->_domain = $domain;

        parent::__construct();
    }


    public function init()
    {
        $this->setMethod('post');

        $t = Zend_Registry::get('translate');
        $user_role = Zend_Registry::get('user')->getUserType();

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

        $wwelement = new Default_Model_WWElement();

        $spamcovercharge = new Zend_Form_Element_Textarea('spamcovercharge', [
            'label'        =>  $t->_('Adjust these SpamC rules :</br>Example :</br>MC_LOTS_OF_MONEY -1.0'),
            'title'        => $t->_("You need to enter the exact name of a SpamC rule and an associated score. Keep in mind that the original score will also be applied"),
            'required'    => false,
            'rows'        => 10,
            'cols'        => 30,
            'filters'    => ['StringTrim']
        ]);
        $spamcovercharge->setValue($wwelement->fetchAllField('@' . $this->_domain->getParam('name'), 'SpamC', 'comments'));
        /*
        * if ($user_role != 'administrator') {
        *   $spamcovercharge->setAttrib('disabled', true);
        *   $spamcovercharge->setAttrib('readonly', true);
        * }
        */
        $this->addElement($spamcovercharge);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        if ($user_role != 'administrator') {
            $submit->setAttrib('disabled', true);
            $submit->setAttrib('readonly', true);
        }
        $this->addElement($submit);
    }

    public function setParams($request, $domain)
    {
        $wwelement = new Default_Model_WWElement();
        $wwelement->setSpamcOvercharge('@' . $domain->getParam('name'), $request->getParam('spamcovercharge'), 'SpamC');

        return true;
    }
}
