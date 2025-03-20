<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Database service form
 */

class Default_Form_Api extends ZendX_JQuery_Form
{
    private $_defaults;

    public function __construct($defaults)
    {
        $this->_defaults = $defaults;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'api_form');

        require_once('Validate/HostList.php');
        $full_admin_ip = new Zend_Form_Element_Textarea('api_fulladmin_ips', [
            'label'    =>  $t->_('Grant these IP/ranges with full admin rights') . " :",
            'required'   => false,
            'rows' => 5,
            'cols' => 30,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $full_admin_ip->addValidator(new Validate_HostList());
        $full_admin_ip->setValue($this->_defaults->getParam('api_fulladmin_ips'));
        $this->addElement($full_admin_ip);

        $admin_ip = new Zend_Form_Element_Textarea('api_admin_ips', [
            'label'    =>  $t->_('Allow authenticated access from these IP/ranges') . " :",
            'required'   => false,
            'rows' => 5,
            'cols' => 30,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $admin_ip->addValidator(new Validate_HostList());
        $admin_ip->setValue($this->_defaults->getParam('api_admin_ips'));
        $this->addElement($admin_ip);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $defaults)
    {
        $t = Zend_Registry::get('translate');

        var_dump($request->getParam('api_fulladmin_ips'));
        $defaults->setParam('api_fulladmin_ips', $request->getParam('api_fulladmin_ips'));
        $defaults->setParam('api_admin_ips', $request->getParam('api_admin_ips'));
        $defaults->save();
    }
}
