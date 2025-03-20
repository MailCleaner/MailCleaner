<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * IMAP user authentication settings form
 */

class Default_Form_Domain_UserAuthentication_Smtp
{
    protected $_domain;
    protected $_settings = [];

    public function __construct($domain)
    {
        $this->_domain = $domain;
    }

    public function addForm($form)
    {
        $name = new Zend_Form_Element_Hidden('connector');
        $name->setValue('none');
        $form->addElement($name);

        $t = Zend_Registry::get('translate');

        require_once('Validate/SMTPHostList.php');
        $server = new  Zend_Form_Element_Text('authserver', [
            'label'    => $t->_('Authentication server') . " :",
            'required' => false,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $server->setValue($this->_domain->getPref('auth_server'));
        $server->addValidator(new Validate_SMTPHostList());
        $form->addElement($server);

        foreach ($this->_settings as $key => $value) {
            $this->_settings[$key] = preg_replace('/__C__/', ':', $value);
        }
    }

    public function setParams($request, $domain)
    {
        $array = [
            'auth_server' => $request->getParam('authserver')
        ];
        $this->setParamsFromArray($array, $domain);
    }

    public function setParamsFromArray($array, $domain)
    {
        $domain->setPref('auth_type', 'smtp');
        $domain->setPref('auth_server', $array['auth_server']);
        $domain->setPref('auth_param', $this->getParamsString($array));
    }

    public function getParams()
    {
        $params = [];
        if ($this->_domain->getAuthConnector() != 'smtp') {
            return $params;
        }

        $authserver = $this->_domain->getPref('auth_server');
        if ($authserver !== '') {
            $params['auth_server'] = $authserver;
        }
        return $params;
    }

    public function getParamsString($params)
    {
        return '';
    }
}
