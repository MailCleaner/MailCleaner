<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User quarantine display settings form
 */

class Default_Form_Manage_UserQuarantinedisplay extends Zend_Form
{
    protected $_user;
    protected $_domain;
    protected $_panelname = 'quarantinedisplay';

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
        $panellist = new Zend_Form_Element_Select('userpanel', [
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);
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

        $addresses = new Zend_Form_Element_Select('gui_default_address', [
            'label'    => $t->_('Address displayed by default') . " :",
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_user->getAddresses() as $address => $ismain) {
            $addresses->addMultiOption($address, $address);
        }
        $addresses->setValue($this->_user->getPref('gui_default_address'));
        $addresses->addValidator(new Zend_Validate_EmailAddress());
        $this->addElement($addresses);

        $nblines = new Zend_Form_Element_Select('gui_displayed_spams', [
            'label'    => $t->_('Number of lines displayed') . " :",
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ([5, 10, 20, 50, 100] as $nb) {
            $nblines->addMultiOption($nb, $nb);
        }
        $nblines->setValue($this->_user->getPref('gui_displayed_spams'));
        $nblines->addValidator(new Zend_Validate_Int());
        $this->addElement($nblines);

        $nbdays = new  Zend_Form_Element_Text('gui_displayed_days', [
            'label'    => $t->_('Number of days displayed') . " :",
            'size' => 5,
            'required' => false
        ]);
        $nbdays->setValue($this->_user->getPref('gui_displayed_days'));
        $nbdays->addValidator(new Zend_Validate_Int());
        $this->addElement($nbdays);

        $hideforced = new Zend_Form_Element_Checkbox('gui_mask_forced', [
            'label'   => $t->_('Hide user-released messages'),
            'title' => $t->_('Hide the mails released by users'),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        $hideforced->setValue($this->_user->getPref('gui_mask_forced'));
        $this->addElement($hideforced);


        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $user)
    {
        foreach (['gui_default_address', 'gui_displayed_spams', 'gui_displayed_days'] as $pref) {
            if ($request->getParam($pref)) {
                $user->setPref($pref, $request->getParam($pref));
            }
        }

        $user->setPref('gui_mask_forced', $request->getParam('gui_mask_forced'));
        return true;
    }
}
