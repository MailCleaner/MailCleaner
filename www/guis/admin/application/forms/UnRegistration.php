<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka, John Mertz
 * @copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>; 2023, John Mertz
 *
 * UnRegistration form
 */

class Default_Form_UnRegistration extends ZendX_JQuery_Form
{

    protected $_unregistrationmgr;
    protected $_currentLicense;

    public function __construct($mgr, $current_license)
    {
        $this->_unregistrationmgr = $mgr;
        $this->_currentLicense = $current_license;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');
        $required = $this->_currentLicense == "1" ? true : false;

        // Only EE version has to confirm by rsp
        $rsp = new  Zend_Form_Element_Password('rsp', [
            'label' => $t->_('Reseller password') . " :",
            'required' => $required
        ]);
        $rsp->setValue('');
        $rsp->addValidator(new Zend_Validate_Alnum());
        $this->addElement($rsp);

        $this->setAttrib('id', 'unregister_form');

        $submit = new Zend_Form_Element_Submit('unregister', [
            'label'    => $t->_('Unregister')
        ]);
        $this->addElement($submit);
    }
}
