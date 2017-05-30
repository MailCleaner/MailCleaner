<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Date and time settings form
 */

class Default_Form_DateAndTime extends ZendX_JQuery_Form
{
	protected $_ntp;
	
	public function __construct($ntp) {
		$this->_ntp = $ntp;
		parent::__construct();
	}
	
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'dateandtimeform');
	    
        $now = Zend_Date::now();
        
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
		
		$date = new ZendX_JQuery_Form_Element_DatePicker(
                    'date',
                    array(
                      'label'    => $t->_('Date'). " :")
                );
	   $date->addValidator(new Zend_Validate_Date());
       $date->addDecorator('ViewHelper');
       
       $this->addElement($date);
       
       $locale = Zend_Registry::get('Zend_Locale');
       $hour = new  Zend_Form_Element_Text('hour', array(
		    'required' => false,
            'class' => 'timefield',
		    'filters'    => array('Alnum')));
	   $hour->addValidator(new Zend_Validate_Int());
	   $hour->addValidator(new Zend_Validate_LessThan(24));
	   $this->addElement($hour);
		
	   $minute = new  Zend_Form_Element_Text('minute', array(
		    'required' => false,
            'class' => 'timefield',
		    'filters'    => array('Alnum')));
	   $minute->addValidator(new Zend_Validate_Int());
	   $minute->addValidator(new Zend_Validate_LessThan(60));
	   $this->addElement($minute);
		
       $second = new  Zend_Form_Element_Text('second', array(
		    'required' => false,
            'class' => 'timefield',
		    'filters'    => array('Alnum')));
	   $second->addValidator(new Zend_Validate_Int());
	   $second->addValidator(new Zend_Validate_LessThan(60));
	   $this->addElement($second);	   
	   
	   $saveandsync = new Zend_Form_Element_Submit('saveandsync', array(
		                    'label'    => $t->_('Save and sync now')));
	   $this->addElement($saveandsync);
	   
	   $hsaveandsync = new Zend_Form_Element_Hidden('hsaveandsync');
	   $hsaveandsync->setValue('0');
	   $this->addElement($hsaveandsync);
		
	   require_once('Validate/HostList.php');
	   $ntpserver = new  Zend_Form_Element_Text('ntpserver', array(
	        'label'    => $t->_('NTP server'). " :",
		    'required' => false,
            'class' => 'serverlistfield'));
	   $ntpserver->setValue($this->_ntp->getServersString());
	   $ntpserver->addValidator(new Validate_HostList());
	   $this->addElement($ntpserver);
	   
	   $usentp = new Zend_Form_Element_Checkbox('usentp', array(
	        'label'   => $t->_('Use time server'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	   $usentp->setValue($this->_ntp->useNTP());
	   $this->addElement($usentp);
	           
		$submit = new Zend_Form_Element_Submit('datetimesubmit', array(
		     'label'    => $t->_('Set time and date')));
		$this->addElement($submit);
		
		$this->updateDateTime();
	}

	public function updateDateTime() {
		$now = Zend_Date::now();
		
		$this->getElement('date')->setValue($now->get(Zend_Date::DATES));
        $this->getElement('hour')->setValue($now->get(Zend_Date::HOUR));
        $this->getElement('minute')->setValue($now->get(Zend_Date::MINUTE));
        $this->getElement('second')->setValue($now->get(Zend_Date::SECOND));
	}
}