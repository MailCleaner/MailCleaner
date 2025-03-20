<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Localization settings form
 */

class Default_Form_Localization extends ZendX_JQuery_Form
{

    protected $_locale;

    public function __construct($locale)
    {
        $this->_locale = $locale;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'localization_form');
        $zlist = new Zend_Form_Element_Select('zone', [
            'label'      => $t->_('Main zone') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_locale->getZones() as $zk => $zv) {
            $zlist->addMultiOption($zk, $t->_($zv));
        }
        $zlist->setValue($this->_locale->getMainZone());
        $this->addElement($zlist);


        $subzlist = new Zend_Form_Element_Select('selectsubzone', [
            'label'      => $t->_('Sub zone') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        foreach ($this->_locale->getSubZones() as $zk => $zv) {
            $subzlist->addMultiOption($zk, $t->_($zv));
        }
        $subzlist->setValue($this->_locale->getSubZone());
        $this->addElement($subzlist);


        $submit = new Zend_Form_Element_Submit('localesubmit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }
}
