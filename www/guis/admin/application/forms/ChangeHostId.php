<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Registration form
 */

class Default_Form_ChangeHostId extends ZendX_JQuery_Form
{

    protected $_changeHostIdmgr;
    public $_registered;
    public function __construct($mgr, $registered)
    {
        $this->_changeHostIdmgr = $mgr;
        $this->_registered = $registered;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $config = new MailCleaner_Config();
        $hid = $config->getOption('HOSTID');

        // Disable field if EE registered
        $attribs = [];
        if ($config->getOption('REGISTERED') == "1") {
            $attribs = ['disabled' => 'disabled'];
        }

        $host_id = new  Zend_Form_Element_Text('host_id', [
            'label' => $t->_('Host ID') . " :",
            'required' => true
        ]);
        $host_id->setValue($hid);
        $host_id->addValidator(new Zend_Validate_Digits());
        $this->addElement($host_id);

        $this->setAttrib('id', 'changehostid_form');

        $submit = new Zend_Form_Element_Submit('changehostid', [
            'label'    => $t->_('Submit'),
            'attribs'    => $attribs
        ]);
        $this->addElement($submit);
    }
}
