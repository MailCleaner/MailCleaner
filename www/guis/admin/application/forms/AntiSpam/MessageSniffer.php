<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * MessageSniffer form
 */

class Default_Form_AntiSpam_MessageSniffer extends Default_Form_AntiSpam_Default
{
	protected $_viewscript = 'forms/antispam/MessageSnifferForm.phtml';
	public $_rbl_checks = array();
	
	public function getViewScriptFile() {
		return $this->_viewscript;
	}
	
	public function __construct($module) {
		parent::__construct($module);
	}
	
	public function init() {
		parent::init();
		
		$as = new Default_Model_Antispam_MessageSniffer();
		$as->find(1);

                $t = Zend_Registry::get('translate');

                $licenseid = new  Zend_Form_Element_Text('licenseid', array(
                            'label'   => $t->_('License ID')." :",
                            'required' => false,
                            'size' => 10,
                            'filters'    => array('StringTrim')));
                $licenseid->setValue($as->getParam('licenseid'));
                $this->addElement($licenseid);     

                $authentication = new  Zend_Form_Element_Text('authentication', array(
                            'label'   => $t->_('Authentication')." :",
                            'required' => false,
                            'size' => 20,
                            'filters'    => array('StringTrim')));
                $authentication->setValue($as->getParam('authentication'));
                $this->addElement($authentication);
		
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	        $view=$layout->getView();
		
	}
	
	public function setParams($request, $module) {
		parent::setParams($request, $module);
		
		$as = new Default_Model_Antispam_MessageSniffer();
		$as->find(1);

                $as->setparam('licenseid', $request->getParam('licenseid'));
                $as->setparam('authentication', $request->getParam('authentication'));
		
		$as->save();
	}
	
}
