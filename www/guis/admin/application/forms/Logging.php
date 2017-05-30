<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Logging settings form
 */

class Default_Form_Logging extends ZendX_JQuery_Form
{
	protected $_systemconf;
	
	public function __construct($conf) {
		$this->_systemconf = $conf;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'logging_form');
		        
        $usesyslog = new Zend_Form_Element_Checkbox('use_syslog', array(
	        'label'   => $t->_('Use syslog logging'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    $usesyslog->setValue($this->_systemconf->getParam('use_syslog'));
	    $this->addElement($usesyslog);
	    
	    $sysloghost = new  Zend_Form_Element_Text('syslog_host', array(
            'label'   => $t->_('Syslog server')." :",
		    'filters'    => array('StringToLower', 'StringTrim')));
    	$sysloghost->setValue($this->_systemconf->getParam('syslog_host'));
        $sysloghost->addValidator(new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_LOCAL | Zend_Validate_Hostname::ALLOW_IP));
	    $this->addElement($sysloghost);
	    
	    #if (! $this->_systemconf->getParam('use_syslog')) {
	    #	$sysloghost->setAttrib('disabled', 'disabled');
	    #}
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

}