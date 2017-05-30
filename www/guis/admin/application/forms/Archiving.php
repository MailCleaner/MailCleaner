<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Logging settings form
 */

class Default_Form_Archiving extends ZendX_JQuery_Form
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
	           
		$this->setAttrib('id', 'archiving_form');
		        
		$type = new Zend_Form_Element_Select('archiving_type', array(
		            'label'      => $t->_('Archiving mode')." :",
		            'required'   => true,
		            'filters'    => array('StringTrim')));
		
		$type->addMultiOption('none', $t->_('none'));
		$type->addMultiOption('external', $t->_('external'));
		
		$type->setValue('none');
		if ($this->_systemconf->getParam('use_archiver')) {
			$type->setValue('external');
			if ($this->_systemconf->getParam('archiver_host') == 'localhost') {
				$type->setValue('internal');
			}
		}
		$this->addElement($type);
			    
	    $archiverhost = new  Zend_Form_Element_Text('archiver_host', array(
            'label'   => $t->_('External archiver server')." :",
            'size' => 40,
		    'filters'    => array('StringToLower', 'StringTrim')));
    	$archiverhost->setValue($this->_systemconf->getParam('archiver_host'));
    	require_once('Validate/HostWithPort.php');
        $archiverhost->addValidator(new Validate_HostWithPort());
	    $this->addElement($archiverhost);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $config) {
		switch ($request->getParam('archiving_type')) {
			case 'external':
			        if ($request->getParam('archiver_host') == '') {
			          throw new Exception('External archiver host must be provided');
			        }
			        $config->setParam('archiver_host', $request->getParam('archiver_host'));
			        $config->setParam('use_archiver', 1);
		            break;
		    case 'internal':
		            throw new Exception('Not yet implemented');
			        $config->setParam('archiver_host', 'localhost');
			        $config->setParam('use_archiver', 1);
		            break;
		    default:
		            $config->setParam('use_archiver', 0);
		}
	}

}