<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * TrustedSources form
 */

class Default_Form_AntiSpam_TrustedSources extends Default_Form_AntiSpam_Default
{
    protected $_viewscript = 'forms/antispam/TrustedSourcesForm.phtml';
    public $_rwl_checks = [];

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

        $trustedsources = new Default_Model_Antispam_TrustedSources();
        $trustedsources->find(1);

        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $use_alltrusted = new Zend_Form_Element_Checkbox('use_alltrusted', [
            'label'   => $t->_('Enable all trusted path detection') . " :",
            'title' => $t->_("The all trusted path detection is the way MailCleaner detects that a message is all internal and was not issued by an external host"),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        $use_alltrusted->setValue($trustedsources->getParam('use_alltrusted'));
        $this->addElement($use_alltrusted);

        require_once('Validate/DomainList.php');
        $domainstospf = new Zend_Form_Element_Textarea('domainsToSPF', [
            'label'    =>  $t->_('Trust SPF validation on these domains') . " :",
            'required'   => false,
            'rows' => 5,
            'cols' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $domainstospf->addValidator(new Validate_DomainList());
        $domainstospf->setValue($trustedsources->getParam('domainsToSPF'));
        $this->addElement($domainstospf);

        require_once('Validate/SMTPHostList.php');
        $smtpservers = new Zend_Form_Element_Textarea('authservers', [
            'label'    =>  $t->_('Known good authenticated SMTP servers') . " :",
            'required'   => false,
            'rows' => 5,
            'cols' => 40,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $smtpservers->addValidator(new Validate_SMTPHostList());
        $smtpservers->setValue(preg_replace('/\s+/', "\n", $trustedsources->getParam('authservers')));
        $this->addElement($smtpservers);

        $authstring = new  Zend_Form_Element_Text('authstring', [
            'label'    => $t->_('Authenticated SMTP servers search string') . " :",
            'title' => $t->_("The Authenticated SMTP servers search string is any string that is present in the header added by the authentication server and which is pretty unique to it. This will help to enforce the check, but is not strictly required."),
            'required' => false
        ]);
        $authstring->setValue($trustedsources->getParam('authstring'));
        $this->addElement($authstring);

        $rwllist = new Default_Model_DnsLists();
        $rwllist->load();

        foreach ($rwllist->getRBLs('IPRWL SPFLIST') as $rwl) {
            $userwl = new Zend_Form_Element_Checkbox('use_rwl_' . $rwl['name'], [
                'label' => $t->_($rwl['dnsname']) . " (" . $t->_($rwl['type']) . ")",
                'uncheckedValue' => "0",
                'checkedValue' => "1"
            ]);
            if ($trustedsources->useRWL($rwl['name'])) {
                $userwl->setChecked(true);
            }
            $this->addElement($userwl);
            $this->_rwl_checks[] = $userwl;
        }
    }

    public function setParams($request, $module)
    {
        parent::setParams($request, $module);

        $trustedsources = new Default_Model_Antispam_TrustedSources();
        $trustedsources->find(1);
        $trustedsources->setParam('use_alltrusted', $request->getParam('use_alltrusted'));
        $trustedsources->setParam('domainsToSPF', $request->getParam('domainsToSPF'));
        $trustedsources->setParam('authservers', preg_replace('/\r?\n/', ' ', $request->getParam('authservers')));
        $trustedsources->setParam('authstring', $request->getParam('authstring'));
        $trustedsources->setParam('use_authservers', 0);
        if (preg_match('/\S/', $trustedsources->getParam('authservers'))) {
            $trustedsources->setParam('use_authservers', 1);
        }

        $rwllist = new Default_Model_DnsLists();
        $rwllist->load();
        $rwlstr = '';
        foreach ($rwllist->getRBLs('IPRWL SPFLIST') as $rwl) {
            $checkname = 'use_rwl_' . $rwl['name'];
            if ($request->getParam($checkname)) {
                $rwlstr .= $rwl['name'] . " ";
            }
        }
        $rwlstr = preg_replace('/^\s*/', '', $rwlstr);
        $trustedsources->setparam('whiterbls', $rwlstr);

        $trustedsources->save();
    }
}
