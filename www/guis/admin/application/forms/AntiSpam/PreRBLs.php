<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * PreRBLs form
 */

class Default_Form_AntiSpam_PreRBLs extends Default_Form_AntiSpam_Default
{
    protected $_viewscript = 'forms/antispam/PreRBLsForm.phtml';
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

        $as = new Default_Model_Antispam_PreRBLs();
        $as->find(1);

        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $rbllist = new Default_Model_DnsLists();
        $rbllist->load();

        $spam_list_to_be_spam = new Zend_Form_Element_Select('spamhits', [
            'label'      => $t->_('List hits to be spam') . " :",
            'title'    => $t->_("Number of RBLs (below) to meet to be considered as spam by this module"),
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);

        for ($i = 1; $i <= count($rbllist->getRBLs('IPRBL BSRBL DNSRBL')); $i++) {
            $spam_list_to_be_spam->addMultiOption($i, $i);
        }
        $spam_list_to_be_spam->setValue($as->getParam('spamhits'));
        $this->addElement($spam_list_to_be_spam);

        foreach ($rbllist->getRBLs('IPRBL BSRBL DNSRBL') as $rbl) {
            $userbl = new Zend_Form_Element_Checkbox('use_rbl_' . $rbl['name'], [
                'label' => $rbl['dnsname'],
                'uncheckedValue' => "0",
                'checkedValue' => "1"
            ]);
            if ($as->useRBL($rbl['name'])) {
                $userbl->setChecked(true);
            }
            $this->addElement($userbl);
            $this->_rbl_checks[] = $userbl;
        }

        $avoidgoodspf = new Zend_Form_Element_Checkbox('avoidgoodspf', [
            'label'   => $t->_('Avoid checking for good SPF') . " :",
            'title' => $t->_('Bypass good SPF check'),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        $avoidgoodspf->setValue($as->getParam('avoidgoodspf'));
        $this->addElement($avoidgoodspf);

        require_once('Validate/SMTPHostList.php');
        $avoidhosts = new Zend_Form_Element_Textarea('avoidhosts', [
            'label'    =>  $t->_('Don\'t check these hosts') . " :",
            'title'   => $t->_("IPs/hosts added here will not be checked against the RBLs"),
            'required'   => false,
            'rows' => 5,
            'cols' => 30,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $avoidhosts->addValidator(new Validate_SMTPHostList());
        $hoststoavoid = preg_replace('/,\s\;/', "\n", $as->getParam('avoidhosts'));
        $hoststoavoid = preg_replace('/,/', "\n", $hoststoavoid);
        $avoidhosts->setValue($hoststoavoid);
        $this->addElement($avoidhosts);
    }

    public function setParams($request, $module)
    {
        parent::setParams($request, $module);

        $as = new Default_Model_Antispam_PreRBLs();
        $as->find(1);

        $rbllist = new Default_Model_DnsLists();
        $rbllist->load();
        $rblstr = '';
        foreach ($rbllist->getRBLs('IPRBL BSRBL DNSRBL') as $rbl) {
            $checkname = 'use_rbl_' . $rbl['name'];
            if ($request->getParam($checkname)) {
                $rblstr .= $rbl['name'] . " ";
            }
        }
        $rblstr = preg_replace('/^\s*/', '', $rblstr);
        $as->setparam('lists', $rblstr);
        $as->setparam('spamhits', $request->getParam('spamhits'));
        $as->setParam('avoidgoodspf', $request->getParam('avoidgoodspf'));
        $hoststoavoid = preg_replace('/,\s\;/', '\n', $request->getParam('avoidhosts'));
        $hoststoavoid = preg_replace('/\s+/', ',', $hoststoavoid);
        $as->setParam('avoidhosts', $hoststoavoid);
        $as->save();
    }
}
