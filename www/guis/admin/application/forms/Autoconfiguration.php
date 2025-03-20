<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka, John Mertz
 * @copyright 2017, Mentor Reka; 2023, John Mertz
 *
 * Auto-configuration settings form
 */

class Default_Form_Autoconfiguration extends ZendX_JQuery_Form
{
    private $MC_AUTOCONF_TAG_FILE = "/spool/mailcleaner/mc-autoconf";

    protected $_autoconfmanager;

    public function __construct($autoconf)
    {
        $this->_autoconfmanager = $autoconf;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');
        $this->setAttrib('id', 'autoconfiguration_form');

        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        $autoconf_enabled = file_exists($config->getOption('VARDIR') . $this->MC_AUTOCONF_TAG_FILE);

        $autoconf = new  Zend_Form_Element_Checkbox('autoconfiguration', [
            'label' => "Enable auto-configuration :",
            'required' => false
        ]);
        $autoconf->setValue($autoconf_enabled);
        $this->addElement($autoconf);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }
}
