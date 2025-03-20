<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Admin settings form
 */

class Default_Form_AdminEdit extends ZendX_JQuery_Form
{

    public $_admin;

    public function __construct($admin)
    {
        $this->_admin = $admin;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'admin_edit');

        $newusername = new  Zend_Form_Element_Text('newusername', [
            'required' => false
        ]);
        require_once('Validate/AdminName.php');
        $newusername->addValidator(new Validate_AdminName());
        $this->addElement($newusername);

        $username = new Zend_Form_Element_Hidden('username');
        $username->setValue($this->_admin->getParam('username'));
        $this->addElement($username);

        $password = new  Zend_Form_Element_Password('password', [
            'label'    => $t->_('Password') . " :",
            'renderPassword' => true,
            'required' => true
        ]);
        if ($this->_admin->getParam('password') != '') {
            $password->setValue('_keeppassword1_');
        }
        $this->addElement($password);

        $confirm = new  Zend_Form_Element_Password('confirm', [
            'label'    => $t->_('Confirm') . " :",
            'renderPassword' => true,
            'required' => true
        ]);
        if ($this->_admin->getParam('password') != '') {
            $confirm->setValue('_keeppassword2_');
        }
        $this->addElement($confirm);

        $roleselect = new Zend_Form_Element_Select('role', [
            'label'      => $t->_('Role') . " :",
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_admin->getRoles() as $r) {
            $roleselect->addMultiOption($r['name'], $t->_($r['name']));
        }
        $roleselect->setValue($this->_admin->getUserType());
        $this->addElement($roleselect);

        require_once('Validate/DomainList.php');
        $domains = new Zend_Form_Element_Textarea('domains', [
            'label'    =>  $t->_('Manage Domains') . " :",
            'required'   => false,
            'rows' => 5,
            'cols' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $domains->addValidator(new Validate_DomainList());
        $domains->setValue(implode("\n", $this->_admin->getDomainsArray()));
        $this->addElement($domains);

        $allowsubdomains = new Zend_Form_Element_Checkbox('allow_subdomains', [
            'label'   => $t->_('Allow access to subdomains') . " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        if ($this->_admin->getRight('allow_subdomains')) {
            $allowsubdomains->setChecked(true);
        }
        $this->addElement($allowsubdomains);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $admin)
    {

        $t = Zend_Registry::get('translate');

        if ($request->getParam('password') == '') {
            throw new Exception($t->_('Please provide a password'));
        }
        if ($request->getParam('password') != '_keeppassword1_') {
            if ($request->getParam('password') != $request->getParam('confirm')) {
                throw new Exception($t->_('Password and confirmation does not match'));
            }
            $admin->setPassword($request->getParam('password'));
        }
        $admin->setUserType($request->getParam('role'));
        if ($request->getParam('role') != '' && $request->getParam('role') != 'administrator') {
            if (preg_match('/^\s*$/', $request->getParam('domains'))) {
                throw new Exception($t->_('Please provide manageable domains'));
            }
            $admin->setParam('domains', preg_replace('/[,\s\r\n]+/', ' ', $request->getParam('domains')));
        }
        if ($request->getParam('newusername')) {
            $admin->setParam('username', $request->getParam('newusername'));
        }
        if ($request->getParam('allow_subdomains')) {
            $admin->setRight('allow_subdomains', '1');
        } else {
            $admin->setRight('allow_subdomains', '0');
        }
        $admin->save();
    }
}
