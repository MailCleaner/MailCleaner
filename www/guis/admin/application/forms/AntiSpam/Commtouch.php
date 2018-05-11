<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Commtouch form
 */

class Default_Form_AntiSpam_Commtouch extends Default_Form_AntiSpam_Default
{
	protected $_viewscript = 'forms/antispam/CommtouchForm.phtml';
	public $_rbl_checks = array();
	
	public function getViewScriptFile() {
		return $this->_viewscript;
	}
	
	public function __construct($module) {
		parent::__construct($module);
	}
	
	public function init() {
		parent::init();
		
		$as = new Default_Model_Antispam_Commtouch();
		$as->find(1);

                $t = Zend_Registry::get('translate');

                $ctasdLicense = new  Zend_Form_Element_Text('ctasdLicense', array(
                            'label'   => $t->_('Ctasd License')." :",
                            'required' => false,
                            'size' => 40,
                            'filters'    => array('StringTrim')));
                $ctasdLicense->setValue($as->getParam('ctasdLicense'));
                $this->addElement($ctasdLicense);     

                $ctipdLicense = new  Zend_Form_Element_Text('ctipdLicense', array(
                            'label'   => $t->_('Ctipd License')." :",
                            'required' => false,
                            'size' => 40,
                            'filters'    => array('StringTrim')));
                $ctipdLicense->setValue($as->getParam('ctipdLicense'));
                $this->addElement($ctipdLicense);
		
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	        $view=$layout->getView();
		
	}
	
	public function setParams($request, $module) {
		parent::setParams($request, $module);
		
		$as = new Default_Model_Antispam_Commtouch();
		$as->find(1);

                $as->setparam('ctasdLicense', $request->getParam('ctasdLicense'));
                $as->setparam('ctipdLicense', $request->getParam('ctipdLicense'));
		
		$as->save();
	}
	
}
