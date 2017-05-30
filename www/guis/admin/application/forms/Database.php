<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Database service form
 */

class Default_Form_Database extends ZendX_JQuery_Form
{
	protected $_firewallrule;
	
	public function __construct($fw) {
		$this->_firewallrule = $fw;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'database_form');
	   	    
	    require_once('Validate/HostList.php');
		$allowed_ip = new Zend_Form_Element_Textarea('allowed_ip', array(
		      'label'    =>  $t->_('Allowed IP/ranges')." :",
                      'title' => $t->_("IP/range allowed to request the MailCleaner databases"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $allowed_ip->addValidator(new Validate_HostList());
		$allowed_ip->setValue($this->_firewallrule->getParam('allowed_ip'));
		$this->addElement($allowed_ip);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

	public function setParams($request, $fwrule) {
		$t = Zend_Registry::get('translate');
		
		$fwrule->setParam('allowed_ip', $request->getParam('allowed_ip'));
		$fwrule->save();
	}
	
}
