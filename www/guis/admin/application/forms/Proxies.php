<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Proxies settings form
 */

class Default_Form_Proxies extends ZendX_JQuery_Form
{

    protected $_proxymanager;

    public function __construct($proxymng)
    {
        $this->_proxymanager = $proxymng;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'proxies_form');

        require_once('Validate/HostList.php');
        $http = new  Zend_Form_Element_Text('httpproxy', [
            'label' => $t->_('HTTP proxy') . " :",
            'required' => false
        ]);
        $http->setValue($this->_proxymanager->getHttpProxy());
        $http->addValidator(new Validate_HostList());
        $this->addElement($http);

        $smtp = new  Zend_Form_Element_Text('smtpproxy', [
            'label' => $t->_('SMTP proxy') . " :",
            'required' => false
        ]);
        $smtp->setValue($this->_proxymanager->getSmtpProxy());
        $smtp->addValidator(new Validate_HostList());
        $this->addElement($smtp);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }
}
