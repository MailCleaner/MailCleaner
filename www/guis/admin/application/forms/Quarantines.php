<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Quarantine page form
 */

class Default_Form_Quarantines extends ZendX_JQuery_Form
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
	           
		$this->setAttrib('id', 'quarantines_form');
		        
        $spamret = new  Zend_Form_Element_Text('spamretention', array(
	        'label'    => $t->_('Spam retention time')." :",
		    'required' => false,
            'class'    => 'retentionfield',
            'filters'    => array('StringTrim')));
        $spamret->addValidator(new Zend_Validate_Digits());
	    $spamret->setValue($this->_systemconf->getParam('days_to_keep_spams'));
	    $this->addElement($spamret);
	    
	    $contentret = new  Zend_Form_Element_Text('contentretention', array(
	        'label'    => $t->_('Dangerous content retention time')." :",
                'title'    => $t->_("The longer the quarantine is, the more space you'll require to stock it (in /var)"),
		    'required' => false,
            'class'    => 'retentionfield',
            'filters'    => array('StringTrim')));
        $contentret->addValidator(new Zend_Validate_Digits());
	    $contentret->setValue($this->_systemconf->getParam('days_to_keep_virus'));
	    $this->addElement($contentret);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

}
