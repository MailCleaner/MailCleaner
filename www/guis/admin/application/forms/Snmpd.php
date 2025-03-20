<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SNMP service settings form
 */

class Default_Form_Snmpd extends ZendX_JQuery_Form
{
    protected $_snmpd;
    protected $_firewallrule;

    public function __construct($_snmpd, $fw)
    {
        $this->_snmpd = $_snmpd;
        $this->_firewallrule = $fw;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $restrictions = Zend_Registry::get('restrictions');

        $this->setAttrib('id', 'snmpd_form');

        $community = new  Zend_Form_Element_Text('community', [
            'label'    => $t->_('Read community') . " :",
            'title' => $t->_("SNMP community key for SNMP"),
            'required' => true
        ]);
        $community->setValue($this->_snmpd->getParam('community'));
        $this->addElement($community);
        if ($restrictions->isRestricted('ServicesSNMP', 'community')) {
            $community->setAttrib('disabled', 'disabled');
        }


        require_once('Validate/HostList.php');
        $allowed_ip = new Zend_Form_Element_Textarea('allowed_ip', [
            'label'    =>  $t->_('Allowed IP/ranges') . " :",
            'title' => $t->_("IP/ranges allowed to send SNMP requests to the MailCleaner server"),
            'required'   => false,
            'rows' => 5,
            'cols' => 30,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $allowed_ip->addValidator(new Validate_HostList());
        $allowed_ip->setValue($this->_snmpd->getParam('allowed_ip'));
        $this->addElement($allowed_ip);

        if ($restrictions->isRestricted('ServicesSNMP', 'allowed_ip')) {
            $allowed_ip->setAttrib('disabled', 'disabled');
        }

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
        if ($restrictions->isRestricted('ServicesSNMP', 'submit')) {
            $submit->setAttrib('disabled', 'disabled');
        }
    }

    public function setParams($request, $snmpd, $fwrule)
    {

        $restrictions = Zend_Registry::get('restrictions');

        if ($restrictions->isRestricted('ServicesSNMP', 'submit')) {
            throw new Exception('Access restricted');
        }
        $t = Zend_Registry::get('translate');

        $snmpd->setParam('community', $request->getParam('community'));
        $snmpd->setParam('allowed_ip', $request->getParam('allowed_ip'));
        $fwrule->setParam('allowed_ip', $request->getParam('allowed_ip'));
        $snmpd->save();
        $fwrule->save();
    }
}
