<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Logs page form
 */

class Default_Form_Logs extends ZendX_JQuery_Form
{
    protected $_params = [];

    public function __construct($params)
    {

        $this->_params = $params;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'filter_form');

        $months = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];
        $fd = new Zend_Form_Element_Select('fd', [
            'required' => true
        ]);
        for ($d = 1; $d <= 31; $d++) {
            $fd->addMultiOption($d, $d);
        }
        if (isset($this->_params['fm']) && $this->_params['fm']) {
            $fd->setValue($this->_params['fd']);
        }
        $this->addElement($fd);

        $fm = new Zend_Form_Element_Select('fm', [
            'required' => true
        ]);
        $i = 1;
        foreach ($months as $m) {
            $fm->addMultiOption($i++, $t->_($m));
        }
        if (isset($this->_params['fm']) && $this->_params['fm']) {
            $fm->setValue($this->_params['fm']);
        }
        $this->addElement($fm);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Refresh'),
            'onclick' => 'javascript:resubmit=1;launchSearch();return false;'
        ]);
        $this->addElement($submit);
    }
}
