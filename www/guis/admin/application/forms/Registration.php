<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 * 		  2017 Mentor Reka <reka.mentor@gmail.com>
 * Registration form
 */

class Default_Form_Registration extends ZendX_JQuery_Form
{
	
	protected $_registrationmgr;
	
	public function __construct($mgr) {
		$this->_registrationmgr = $mgr;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'registration_form');
        
       /* $first = new  Zend_Form_Element_Text('firstpart', array(
            'label' => $t->_('Registration number'). " :",
            'class' => 'serialpartfield',
		    'required' => false));
	    $first->setValue($this->_registrationmgr->getSerialPart(1));
	    $first->addValidator(new Zend_Validate_Alnum());
	    $this->addElement($first);
	   
	    $second = new  Zend_Form_Element_Text('secondpart', array(
            'label' => $t->_('Registration number'). " :",
            'class' => 'serialpartfield',
		    'required' => false));
	    $second->setValue($this->_registrationmgr->getSerialPart(2));
	    $second->addValidator(new Zend_Validate_Alnum());
	    $this->addElement($second);
	    
	    $third = new  Zend_Form_Element_Text('thirdpart', array(
            'label' => $t->_('Registration number'). " :",
            'class' => 'serialpartfield',
		    'required' => false));
	    $third->setValue($this->_registrationmgr->getSerialPart(3));
	    $third->addValidator(new Zend_Validate_Alnum());
	    $this->addElement($third);
	*/

            $sysconf = MailCleaner_Config::getInstance();

            $clientid = new  Zend_Form_Element_Text('clientid', array(
            'label' => $t->_('Client ID'). " :",
                    'required' => true));
            $clientid->setValue($sysconf->getOption('CLIENTID'));
            $clientid->addValidator(new Zend_Validate_Alnum());
            $this->addElement($clientid);

            $resellerid = new  Zend_Form_Element_Text('resellerid', array(
            'label' => $t->_('Reseller ID'). " :",
                    'required' => true));
            $resellerid->setValue($sysconf->getOption('RESELLERID'));
            $resellerid->addValidator(new Zend_Validate_Alnum());
            $this->addElement($resellerid);

            $resellerpwd = new  Zend_Form_Element_Password('resellerpwd', array(
            'label' => $t->_('Reseller password'). " :",
                    'required' => false));
            #$resellerpwd->addValidator(new Zend_Validate_Alnum());
            $this->addElement($resellerpwd);
   
		$submit = new Zend_Form_Element_Submit('register', array(
		     'label'    => $t->_('Register')));
		$this->addElement($submit);
		
	}

}
