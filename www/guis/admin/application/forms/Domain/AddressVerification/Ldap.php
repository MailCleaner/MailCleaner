<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * LDAP callout form
 */

class Default_Form_Domain_AddressVerification_Ldap
{
    protected $_domain;

    public function __construct($domain)
    {
        $this->_domain = $domain;
    }

    public function addForm($form)
    {
        $name = new Zend_Form_Element_Hidden('connector');
        $name->setValue('ldap');
        $form->addElement($name);

        $t = Zend_Registry::get('translate');

        require_once('Validate/SMTPHostList.php');
        $server = new  Zend_Form_Element_Text('ldapserver', [
            'label'    => $t->_('LDAP server') . " :",
            'required' => false,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $server->setValue($this->_domain->getPref('ldapcalloutserver'));
        $server->addValidator(new Validate_SMTPHostList());
        $form->addElement($server);

        $ldapparams = $this->getParams();

        $basedn = new  Zend_Form_Element_Text('basedn', [
            'label'    => $t->_('Base DN') . " :",
            'required' => false,
            'filters'    => ['StringTrim']
        ]);
        $basedn->setValue($ldapparams['basedn']);
        $form->addElement($basedn);

        $binddn = new  Zend_Form_Element_Text('binddn', [
            'label'    => $t->_('Bind user') . " :",
            'required' => false,
            'filters'    => ['StringTrim']
        ]);
        $binddn->setValue($ldapparams['binddn']);
        $form->addElement($binddn);

        $bindpass = new  Zend_Form_Element_Password('bindpass', [
            'label'    => $t->_('Bind password') . " :",
            'required' => false,
            'renderPassword' => true,
            'filters'    => ['StringTrim']
        ]);
        $bindpass->setValue($ldapparams['bindpass']);
        $form->addElement($bindpass);

        $group = new  Zend_Form_Element_Text('group', [
            'label'    => $t->_('Only addresses in group') . " :",
            'required' => false,
            'renderPassword' => true,
            'filters'    => ['StringTrim']
        ]);
        $group->setValue($ldapparams['group']);
        $form->addElement($group);

        $ldapusesslcheck = new Zend_Form_Element_Checkbox('usessl', [
            'label'   => $t->_('Use SSL') . " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);

        if ($ldapparams['usessl']) {
            $ldapusesslcheck->setChecked(true);
        }
        $form->addElement($ldapusesslcheck);
    }

    public function setParams($request, $domain)
    {
        $params = [
            'basedn' => $request->getParam('basedn'),
            'binddn' => $request->getParam('binddn'),
            'bindpass' => $request->getParam('bindpass'),
            'group' => $request->getParam('group'),
            'usessl' => $request->getParam('usessl'),
            'callout_server' =>  $request->getParam('ldapserver')
        ];
        $this->setParamsFromArray($params, $domain);
    }

    public function setParamsFromArray($array, $domain)
    {
        $domain->setCalloutConnector('ldap');
        $domain->setParam('adcheck', 'true');
        $domain->setParam('callout', 'false');
        $domain->setParam('addlistcallout', 'false');
        if (isset($array['callout_server'])) {
            $domain->setPref('ldapcalloutserver', $array['callout_server']);
        }
        $domain->setPref('ldapcalloutparam', $this->getParamsString($array));
    }

    public function getParams()
    {
        $ldapparams = preg_split('/:/', $this->_domain->getPref('ldapcalloutparam'));
        for ($i = 0; $i < 3; $i++) {
            if (!isset($ldapparams[$i])) {
                $ldapparams[$i] = '';
            }
            $ldapparams[$i] = preg_replace('/__C__/', ':', $ldapparams[$i]);
        }

        return [
            'basedn' => $ldapparams[0],
            'binddn' => $ldapparams[1],
            'bindpass' => $ldapparams[2],
            'group' => $ldapparams[3],
            'usessl' => $ldapparams[4],
            'callout_server' => $this->_domain->getPref('ldapcalloutserver')
        ];
    }

    public function getParamsString($params)
    {
        $bindpass = '';
        if (isset($params['bindpass'])) {
            $bindpass = preg_replace('/:/', '__C__', $params['bindpass']);
        }
        $basedn = '';
        if (isset($params['basedn'])) {
            $basedn = preg_replace('/:/', '__C__', $params['basedn']);
        }
        $binddn = '';
        if (isset($params['binddn'])) {
            $binddn = preg_replace('/:/', '__C__', $params['binddn']);
        }
        if (isset($params['group'])) {
            $group = preg_replace('/:/', '__C__', $params['group']);
        }
        $usessl = 0;
        if (isset($params['usessl']) && $params['usessl']) {
            $usessl = '1';
        }
        return implode(':', [$basedn, $binddn, $bindpass, $group, $usessl]);
    }
}
