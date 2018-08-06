<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * Email newsletter form
 */

class Default_Form_Manage_EmailNewslist extends Default_Form_ElementList
{
	protected $_email;
	protected $_panelname = 'newslist';
	public $_wwlist = array();

	public function __construct($email)
	{
	    $this->_email = $email;
	    $wwelement = new Default_Model_WWElement();
	    $this->_wwlist = $wwelement->fetchAll($this->_email->getParam('address'), 'wnews');

	    parent::__construct($this->_wwlist, 'Default_Model_WWElement');
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
		$this->setAddedValues(array('recipient' => $email->getParam('address'), 'type' => 'wnews'));
		$this->manageRequest($request);
		$this->addFields($this);
		return true;
	}

}
