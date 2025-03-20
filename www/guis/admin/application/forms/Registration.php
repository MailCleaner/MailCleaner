<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2017 Mentor Reka <reka.mentor@gmail.com>
 *                2025 John Mertz <git@john.me.tz>
 * Registration form
 */

class Default_Form_Registration extends ZendX_JQuery_Form
{

    protected $_registrationmgr;

    public function __construct($mgr)
    {
        $this->_registrationmgr = $mgr;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'registration_form');

        $sysconf = MailCleaner_Config::getInstance();

        $clientid = new  Zend_Form_Element_Text('clientid', [
            'label' => $t->_('Client ID') . " :",
            'required' => true
        ]);
        $clientid->setValue($sysconf->getOption('CLIENTID'));
        $clientid->addValidator(new Zend_Validate_Alnum());
        $this->addElement($clientid);

        $resellerid = new  Zend_Form_Element_Text('resellerid', [
            'label' => $t->_('Reseller ID') . " :",
            'required' => true
        ]);
        $resellerid->setValue($sysconf->getOption('RESELLERID'));
        $resellerid->addValidator(new Zend_Validate_Alnum());
        $this->addElement($resellerid);

        $resellerpwd = new  Zend_Form_Element_Password('resellerpwd', [
            'label' => $t->_('Reseller password') . " :",
            'required' => false
        ]);
        $this->addElement($resellerpwd);

        $submit = new Zend_Form_Element_Submit('register', [
            'label'    => $t->_('Register')
        ]);
        $this->addElement($submit);
    }
}
