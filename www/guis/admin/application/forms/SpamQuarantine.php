<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Spam quarantine page form
 */

class Default_Form_SpamQuarantine extends ZendX_JQuery_Form
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
        $domainField->addMultiOption('', $t->_('select...'));
        foreach ($domains as $d) {
            $domainField->addMultiOption($d->getParam('name'), $d->getParam('name'));
        }
        $domainField->setValue($this->_params['domain']);
        $this->addElement($domainField);

        $sender = new  Zend_Form_Element_Text('sender', [
            'label' => $t->_('Sender') . " : ",
            'required' => false
        ]);
        $sender->setValue($this->_params['sender']);
        $sender->addValidator('Zend_Validate_EmailAddress');
        $this->addElement($sender);

        $subject = new  Zend_Form_Element_Text('subject', [
            'label' => $t->_('Subject') . " : ",
            'required' => false
        ]);
        $subject->setValue($this->_params['subject']);
        $this->addElement($subject);

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


        $forced = new Zend_Form_Element_Checkbox('forced', [
            'label'   => $t->_('Hide user-released messages'),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        if (isset($this->_params['forced']) && $this->_params['forced']) {
            $forced->setChecked(true);
        }
        $this->addElement($forced);

        $hidedup = new Zend_Form_Element_Checkbox('hidedup', [
            'label'   => $t->_('Show multi-recipient messages only once '),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        if (isset($this->_params['hidedup']) && $this->_params['hidedup']) {
            $hidedup->setChecked(true);
        }
        $this->addElement($hidedup);


        $mpps = [5, 10, 20, 50, 100];
        $mpp = new Zend_Form_Element_Select('mpp', [
            'label' => $t->_('Number of lines displayed') . ' : ',
            'required' => true
        ]);

        foreach ($mpps as $m) {
            $mpp->addMultiOption($m, $m);
        }
        $mpp->setValue(20);
        if (isset($this->_params['mpp']) && $this->_params['mpp']) {
            $mpp->setValue($this->_params['mpp']);
        }
        $this->addElement($mpp);

        $showSpamOnly = new Zend_Form_Element_Checkbox('showSpamOnly', [
            'label'   => $t->_('Show spam only'),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);

        ### newsl
        $showNewslettersOnly = new Zend_Form_Element_Checkbox('showNewslettersOnly', [
            'label'   => $t->_('Show newsletters only'),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);

        if (!empty($this->_params['showSpamOnly'])) {
            $showSpamOnly->setChecked(true);
            $showNewslettersOnly->setChecked(false);
        } elseif (!empty($this->_params['showNewslettersOnly'])) {
            $showNewslettersOnly->setChecked(true);
        }

        $this->addElement($showSpamOnly);
        $this->addElement($showNewslettersOnly);

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Refresh'),
            'onclick' => 'javascript:launchSearch();return false;'
        ]);
        $this->addElement($submit);
    }
}
