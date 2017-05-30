<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * default antispam settings form
 */

class Default_Form_AntiSpam_Default extends ZendX_JQuery_Form
{
	protected $_viewscript = '';
	protected $_module;
	
	public function getViewScriptFile() {
		return $this->_viewscript;
	}
	
	public function __construct($module) {
		$this->_module = $module;
		parent::__construct();
	}
	
	public function init() {
		
	    $t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'module_form');
		
		$active = new Zend_Form_Element_Checkbox('active', array(
	        'label'   => $t->_('Enable module'). " :",
                'title' => $t->_("Enable module detection"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    $active->setValue($this->_module->getParam('active'));
	    $this->addElement($active);
	    
	    $decisive = new Zend_Form_Element_Checkbox('decisive', array(
	        'label'   => $t->_('Module is decisive'). " :",
                'title' => $t->_("The module's advice is taken into account"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    $decisive->setValue($this->_module->isDecisive());
	    $this->addElement($decisive);
	    
	    $position = new Zend_Form_Element_Select('position', array(
            'label'      => $t->_('Position in filter chain')." :",
            'title' => $t->_("Rank of the filter in the execution order"),
            'required'   => false,
            'filters'    => array('StringTrim')));
        
        for ($i = 1; $i <= count($this->_module->fetchAll()); $i++) {
        	$position->addMultiOption($i, $i);
        }
        $position->setValue($this->_module->getParam('position'));
        $this->addElement($position);
	    
        $timeout = new  Zend_Form_Element_Text('timeOut', array(
	        'label'    => $t->_('Maximum check time')." :",
                'title' => $t->_("Timeout for the module"),
		    'required' => false,
		    'size' => 4,
            'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $timeout->setValue($this->_module->getParam('timeOut'));
        $timeout->addValidator(new Zend_Validate_Int());
	    $this->addElement($timeout);
	    
	    $maxsize = new  Zend_Form_Element_Text('maxSize', array(
	        'label'    => $t->_('Maximum message size')." :",
                'title' => $t->_("Messages below this size limit are not analyzed"),
		    'required' => false,
		    'size' => 8,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $maxsize->setValue($this->_module->getParam('maxSize'));
        $maxsize->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxsize);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
	}
	
	public function setParams($request, $module) {
		$module->setParam('active', $request->getParam('active'));
		$module->setParam('timeOut', $request->getParam('timeOut'));
		$module->setParam('maxSize', $request->getParam('maxSize'));
		$module->setParam('position', $request->getParam('position'));
		$module->setDecisive($request->getParam('decisive'));
	}
	
}
