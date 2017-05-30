<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Antivirus settings form
 */

class Default_Form_ContentAntivirus extends ZendX_JQuery_Form
{
	protected $_antivirus;
	public $scanners = array();
	
	public function __construct($av) {
		$this->_antivirus = $av;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
		
		$scanner = new Default_Model_AntivirusScanner();
    	$this->scanners = $scanner->fetchAllActive();
	           
		$this->setAttrib('id', 'antivirusglobalsettings_form');
	    
	    $silent = new Zend_Form_Element_Checkbox('silent', array(
	        'label'   => $t->_('Drop known viruses silently'),
                'title' => $t->_("If MailCleaner meet a known virus, it is dropped without warning"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('silent') == 'yes' ) {
            $silent->setChecked(true);
	    }
	    $this->addElement($silent);
	    
	    $scanner_timeout = new  Zend_Form_Element_Text('scanner_timeout', array(
            'label'   => $t->_('Anti-virus scanners timeout')." :",
            'title' => $t->_("Timeout for the AntiVirus part"),
		    'required' => false,
	        'size' => 4,
	        'class' => 'fieldrighted',
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $scanner_timeout->setValue($this->_antivirus->getParam('scanner_timeout'));
        $scanner_timeout->addValidator(new Zend_Validate_Int());
	    $this->addElement($scanner_timeout);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $av) {
		if ($request->getParam('silent')) {
			$av->setParam('silent', 'yes');
		} else {
			$av->setParam('silent', 'no');
		}
        $av->setParam('scanner_timeout', $request->getParam('scanner_timeout'));
        
        $av->save();
	}
}
