<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Reporting page form
 */

class Default_Form_Reporting extends ZendX_JQuery_Form
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

        $search = new  Zend_Form_Element_Text('search', [
            'required' => false
        ]);
        $search->setValue($this->_params['search']);
        $this->addElement($search);

        $domainField = new  Zend_Form_Element_Select('domain', [
            'required' => false
        ]);
        $domain = new Default_Model_Domain();
        $domains = $domain->fetchAllName();
        $user = Zend_Registry::get('user');
        if ($user  && $user->canManageDomain('*')) {
            $domainField->addMultiOption('', $t->_('all domains...'));
        }
        foreach ($domains as $d) {
            $domainField->addMultiOption($d->getParam('name'), $d->getParam('name'));
        }
        $domainField->setValue($this->_params['domain']);
        $this->addElement($domainField);

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

        $td = new Zend_Form_Element_Select('td', [
            'required' => true
        ]);
        for ($d = 1; $d <= 31; $d++) {
            $td->addMultiOption($d, $d);
        }
        if (isset($this->_params['td']) && $this->_params['td']) {
            $td->setValue($this->_params['td']);
        }
        $this->addElement($td);
        $tm = new Zend_Form_Element_Select('tm', [
            'required' => true
        ]);
        $i = 1;
        foreach ($months as $m) {
            $tm->addMultiOption($i++, $t->_($m));
        }
        if (isset($this->_params['tm']) && $this->_params['tm']) {
            $tm->setValue($this->_params['tm']);
        }
        $this->addElement($tm);

        $sorts = [
            'msgs' => 'number of messages received',
            'spams' => 'number of spams received',
            'spamspercent' => 'percent of spam received',
            'viruses' => 'number of viruses received',
            'users' => 'number of users',
            'what' => 'item name'
        ];
        $sort = new Zend_Form_Element_Select('sort', [
            'label' => $t->_('Sort by') . " : ",
            'required' => false
        ]);
        foreach ($sorts as $sortkey => $sortname) {
            $sort->addMultiOption($sortkey, $t->_($sortname));
        }
        if (isset($this->_params['sort'])) {
            $sort->setValue($this->_params['sort']);
        }
        $this->addElement($sort);

        $tops = [3, 10, 20, 100, 1000, 10000];
        $top = new Zend_Form_Element_Select('top', [
            'label' => $t->_('Top items shown') . " : ",
            'required' => false
        ]);
        foreach ($tops as $topv) {
            $top->addMultiOption($topv, $topv);
        }
        if (isset($this->_params['top']) && $this->_params['top']) {
            $top->setValue($this->_params['top']);
        }
        $this->addElement($top);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Refresh'),
            'onclick' => 'javascript:resubmit=1;launchSearch();return false;'
        ]);
        $this->addElement($submit);
    }
}
