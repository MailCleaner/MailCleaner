<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Scheduled tasks settings form
 */

class Default_Form_Tasks extends ZendX_JQuery_Form
{
    protected $_systemconf;

    public function __construct($conf)
    {
        $this->_systemconf = $conf;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'tasks_form');

        $daily = new Zend_Form_Element_Select('cron_time', [
            'label'      => $t->_('Daily tasks run at') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        for ($i = 0; $i < 24; $i++) {
            $str = sprintf('%02d:00:00', $i);
            $daily->addMultiOption($str, $str);
        }
        $daily->setValue($this->_systemconf->getParam('cron_time'));
        $this->addElement($daily);

        $weekly = new Zend_Form_Element_Select('cron_weekday', [
            'label'      => $t->_('Weekly tasks run on') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        foreach (['1' => 'Sunday', '2' => 'Monday', '3' => 'Tuesday', '4' => 'Wednesday', '5' => 'Thursday', '6' => 'Friday', '7' => 'Saturday'] as $k => $v) {
            $weekly->addMultiOption($k, $t->_($v));
        }
        $weekly->setValue($this->_systemconf->getParam('cron_weekday'));
        $this->addElement($weekly);

        $monthly = new Zend_Form_Element_Select('cron_monthday', [
            'label'      => $t->_('Monthly tasks run at day') . " :",
            'required'   => true,
            'filters'    => ['StringTrim']
        ]);

        for ($i = 1; $i < 32; $i++) {
            $monthly->addMultiOption($i, $i);
        }
        $monthly->setValue($this->_systemconf->getParam('cron_monthday'));
        $this->addElement($monthly);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }
}
