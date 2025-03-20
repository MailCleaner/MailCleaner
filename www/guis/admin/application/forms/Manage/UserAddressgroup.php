<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User address group form
 */

class Default_Form_Manage_UserAddressgroup extends Default_Form_ElementList
{
    protected $_user;
    protected $_domain;
    protected $_panelname = 'addressgroup';
    public $_addresses = [];

    public function __construct($user, $domain)
    {
        $this->_user = $user;
        $this->_domain = $domain;
        $this->_addresses = $this->_user->getAddressesObjects(true);

        parent::__construct($this->_addresses, 'Default_Model_Email');
    }

    public function setList($list)
    {
        $this->_addresses = $list;
        $this->init();
    }

    public function init()
    {
        parent::init();
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

        $this->getElement('add')->setLabel($t->_('< Add address to group'));
        $this->getElement('disable')->setLabel($t->_('Activate pending request'));
        $this->getElement('remove')->setLabel($t->_('Remove address from group'));

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $domain)
    {
        if ($request->getParam('add') != "") {
            $address = $request->getParam('addelement');
            ## check address is not empty
            if (!$address) {
                throw new Exception('provide element value');
            }
            ## check address format validity
            $validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS |
                Zend_Validate_Hostname::ALLOW_LOCAL);
            if (!$validator->isValid($address)) {
                throw new Exception('this address is not valid');
            }
            ## check format and extract domain part
            if (!preg_match('/^[^@]+\@(\S+)$/', $address, $matches)) {
                throw new Exception('this address is not valid');
            }
            $domain = $matches[1];
            ## check domains is filtered
            $d = new Default_Model_Domain();
            $d->findByName($domain);
            if (!$d->getId()) {
                throw new Exception('this address cannot be filtered');
            }

            ## ok, create address
            $email = new Default_Model_Email();
            $email->find($address);

            ## check if already exists
            if ($email->getId()) {
                ## check it doesn't belong to someone else
                if ($email->getParam('user') != 0 && $email->getParam('user') != $this->_user->getId()) {
                    throw new Exception('address already linked to another account');
                    ## check if not already linked to this account
                } elseif ($email->getParam('user') == $this->_user->getId()) {
                    throw new Exception('address already linked to this account');
                }
            }
            ## ok, link and save
            $email->setParam('address', $request->getParam('addelement'));
            $email->setParam('user', $this->_user->getId());
            $email->save();
        }

        if ($request->getParam($this->_prefix . 'disable') != "") {
            foreach ($this->_list as $element) {
                if ($request->getParam('list_select_' . $element->getId())) {
                    $element->setParam('user', $this->_user->getId());
                    $element->setStatus();
                    $element->save();
                }
            }
        }
        if ($request->getParam('remove') != "") {
            foreach ($this->_list as $element) {
                if ($request->getParam('list_select_' . $element->getId())) {
                    $element->setParam('user', 0);
                    $element->save();
                }
            }
        }

        return true;
    }
}
