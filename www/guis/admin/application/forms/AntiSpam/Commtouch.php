<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Commtouch form
 */

class Default_Form_AntiSpam_Commtouch extends Default_Form_AntiSpam_Default
{
    protected $_viewscript = 'forms/antispam/CommtouchForm.phtml';
    public $_rbl_checks = [];

    public function getViewScriptFile()
    {
        return $this->_viewscript;
    }

    public function __construct($module)
    {
        parent::__construct($module);
    }

    public function init()
    {
        parent::init();

        $as = new Default_Model_Antispam_Commtouch();
        $as->find(1);

        $t = Zend_Registry::get('translate');

        $ctasdLicense = new  Zend_Form_Element_Text('ctasdLicense', [
            'label'   => $t->_('Ctasd Licence') . " :",
            'required' => false,
            'size' => 40,
            'filters'    => ['StringTrim']
        ]);
        $ctasdLicense->setValue($as->getParam('ctasdLicense'));
        $this->addElement($ctasdLicense);

        $ctipdLicense = new  Zend_Form_Element_Text('ctipdLicense', [
            'label'   => $t->_('Ctipd Licence') . " :",
            'required' => false,
            'size' => 40,
            'filters'    => ['StringTrim']
        ]);
        $ctipdLicense->setValue($as->getParam('ctipdLicense'));
        $this->addElement($ctipdLicense);

        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
    }

    public function setParams($request, $module)
    {
        parent::setParams($request, $module);

        $as = new Default_Model_Antispam_Commtouch();
        $as->find(1);

        $as->setparam('ctasdLicense', $request->getParam('ctasdLicense'));
        $as->setparam('ctipdLicense', $request->getParam('ctipdLicense'));

        $as->save();
    }
}
