<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SQL user authentication settings form
 */

class Default_Form_Domain_UserAuthentication_Sql
{
    protected $_domain;
    protected $_settings = [
        "sqlusername" => '',
        "sqlpassword" => ''
    ];

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

        $this->_settings = $this->getParams();

        $username = new  Zend_Form_Element_Text('sqlusername', [
            'label'    => $t->_('Username') . " :",
            'required' => false,
            'filters'    => ['StringTrim']
        ]);
        $username->setValue($this->_settings['sqlusername']);
        $form->addElement($username);

        $password = new  Zend_Form_Element_Password('sqlpassword', [
            'label'    => $t->_('Password') . " :",
            'required' => false,
            'renderPassword' => true,
            'filters'    => ['StringTrim']
        ]);
        $password->setValue($this->_settings['sqlpassword']);
        $form->addElement($password);
    }

    public function setParams($request, $domain)
    {
        $array = [
            'auth_server' => $request->getParam('authserver'),
            'sqlusername' => $request->getParam('sqlusername'),
            'sqlpassword' => $request->getParam('sqlpassword')
        ];
        $this->setParamsFromArray($array, $domain);
    }

    public function setParamsFromArray($array, $domain)
    {
        $domain->setPref('auth_type', 'sql');
        $domain->setPref('auth_server', $array['auth_server']);
        $domain->setPref('auth_param', $this->getParamsString($array));
    }

    public function getParams()
    {
        $params = $this->_settings;
        if ($this->_domain->getAuthConnector() != 'sql') {
            return $params;
        }
        if (preg_match('/([^:]*)/', $this->_domain->getPref('auth_param'), $matches)) {
            $params['sqlusername'] = $matches[1];
            $params['sqlpassword'] = $matches[1];
        }
        foreach ($params as $key => $value) {
            $params[$key] = preg_replace('/__C__/', ':', $value);
        }
        $params['auth_server'] = $this->_domain->getPref('auth_server');
        return $params;
    }

    public function getParamsString($params)
    {
        $fields = ['sqlusername', 'sqlpassword'];
        $str = '';
        foreach ($fields as $key) {
            if (isset($params[$key])) {
                $params[$key] = preg_replace('/:/', '__C__', $params[$key]);
            } else {
                $params[$key] = $this->_settings[$key];
            }
            $str .= ':' . $params[$key];
        }
        $str = preg_replace('/^:/', '', $str);
        return $str;
    }
}
