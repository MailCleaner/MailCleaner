<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Email managment form
 */

class Default_Form_Manage_EmailActions extends Zend_Form
{
	public $_email;
	protected $_panelname = 'actions';
	
	public function __construct($email)
	{
	    $this->_email = $email;

	    parent::__construct();
	}
	
	
	public function init()
	{
		parent::init();
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'email_form');
	    $panellist = new Zend_Form_Element_Select('emailpanel', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $panellist->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_email->getConfigPanels() as $panel => $panelname) {
        	$panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);
        
        $panel = new Zend_Form_Element_Hidden('panel');
		$panel->setValue($this->_panelname);
		$this->addElement($panel);
		$name = new Zend_Form_Element_Hidden('address');
		$name->setValue($this->_email->getParam('address'));
		$this->addElement($name);
		
	}
	
	public function setParams($request, $email) {
		return true;
	}
	
}