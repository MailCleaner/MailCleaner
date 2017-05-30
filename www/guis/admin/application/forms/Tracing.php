<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Tracing page form
 */

class Default_Form_Tracing extends ZendX_JQuery_Form
{	
	protected $_params = array();
	
	public function __construct($params) {
		
		$this->_params = $params;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'filter_form');
		
		$search = new  Zend_Form_Element_Text('search', array(
		    'required' => false));
	    $search->setValue($this->_params['search']);
	    $this->addElement($search);
	    
	    $domainField = new  Zend_Form_Element_Select('domain', array(
		    'required' => false));
	    $domain = new Default_Model_Domain();
	    $domains = $domain->fetchAllName();
	    $domainField->addMultiOption('', $t->_('select...'));
	    foreach ($domains as $d) {
	        $domainField->addMultiOption($d->getParam('name'), $d->getParam('name'));
	    }
	    $domainField->setValue($this->_params['domain']);
	    $this->addElement($domainField);
	    
	    $sender = new  Zend_Form_Element_Text('sender', array(
	        'label' => $t->_('Filter by external address')." : ",
	        'size' => 40,
		    'required' => false));
	    $sender->setValue($this->_params['sender']);
	    $this->addElement($sender);
	    
	    $months = array('Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.');
	    $fd = new Zend_Form_Element_Select('fd', array(
		    'required' => true));
	    for ($d = 1; $d <= 31; $d++) {
	        $fd->addMultiOption($d, $d);
	    }
	    if (isset($this->_params['fm']) && $this->_params['fm']) {
            $fd->setValue($this->_params['fd']);
	    }
	    $this->addElement($fd);
	    
	    $fm = new Zend_Form_Element_Select('fm', array(
		    'required' => true));
	    $i = 1;
	    foreach ($months as $m) {
	    	$fm->addMultiOption($i++, $t->_($m));
	    }
	    if (isset($this->_params['fm']) && $this->_params['fm']) {
            $fm->setValue($this->_params['fm']);
	    }
	    $this->addElement($fm);
	    
	    $td = new Zend_Form_Element_Select('td', array(
		    'required' => true));
	    for ($d = 1; $d <= 31; $d++) {
	        $td->addMultiOption($d, $d);
	    }
	    if (isset($this->_params['td']) && $this->_params['td']) {
            $td->setValue($this->_params['td']);
	    }
	    $this->addElement($td);
	    $tm = new Zend_Form_Element_Select('tm', array(
		    'required' => true));
	    $i = 1;
	    foreach ($months as $m) {
	    	$tm->addMultiOption($i++, $t->_($m));
	    }
	    if (isset($this->_params['tm']) && $this->_params['tm']) {
            $tm->setValue($this->_params['tm']);
	    }
	    $this->addElement($tm);
	    
	    
	    $mpps = array(5, 10, 20, 50, 100);
	    $mpp = new Zend_Form_Element_Select('mpp', array(
	        'label' => $t->_('Number of lines displayed').' : ',
		    'required' => true));
	    
	    foreach ($mpps as $m) {
	    	$mpp->addMultiOption($m, $m);
	    }
	    $mpp->setValue(20);
	    if (isset($this->_params['mpp']) && $this->_params['mpp']) {
            $mpp->setValue($this->_params['mpp']);
	    }
	    $this->addElement($mpp); 

            $hiderejected = new Zend_Form_Element_Checkbox('hiderejected', array(
                'label'   => $t->_('Hide rejected messages '),
                'uncheckedValue' => "0",
                'checkedValue' => "1"
                      ));
            if (isset($this->_params['hiderejected']) && $this->_params['hiderejected']) {
                 $hiderejected->setChecked(true);
            }
            $this->addElement($hiderejected);
	    
	    $submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Refresh'),
	         'onclick' => 'javascript:resubmit=1;canceled=0;noshowreload=0;launchSearch();return false;'));
		$this->addElement($submit);
	}

}
