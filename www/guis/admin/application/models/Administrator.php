<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Administrator
 */

class Default_Model_Administrator
{
    protected $_id;
    protected $_username;
    protected $_values = [
        'username' => '',
        'domains' => '',
        'password' => '',
    ];
    protected $_domains = [];
    protected $_rights = [
        'can_configure' => 0,
        'can_manage_domains' => 0,
        'can_manage_users' => 0,
        'can_view_stats' => 0,
        'allow_subdomains' => 0
    ];

    protected $_roles = [
        ['name' => 'administrator', 'rights' => ['all_domains', 'can_configure', 'can_manage_domains', 'can_manage_users']],
        ['name' => 'manager', 'rights' => ['can_manage_domains', 'can_manage_users']],
        ['name' => 'hotline', 'rights' => ['can_manage_users']]
    ];

    protected $_mapper;

    public function setUsername($username)
    {
        $this->_username = $username;
        $this->setParam('username', $username);
    }

    public function getUsername()
    {
        return $this->_username;
    }

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        $ret = null;
        if (array_key_exists($param, $this->_values)) {
            $ret = $this->_values[$param];
        }
        if ($ret == 'false') {
            return 0;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function getRights()
    {
        $ret = [];
        foreach ($this->_rights as $r => $v) {
            if ($r == 'all_domains') {
                continue;
            }
            $ret[] = $r;
        }
        return $ret;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setDomains($domains)
    {
        $this->_domains = preg_split('/[,\s]+/', $domains);
        sort($this->_domains);
        $this->setParam('domains', implode(' ', $this->_domains));
    }

    public function setRight($right, $value)
    {
        if ($right == 'all_domains' && $value) {
            $this->setDomains('*');
        }
        if ($value > 0) {
            $this->_rights[$right] = 1;
            return;
        }
        $this->_rights[$right] = 0;
    }

    public function getRight($right)
    {
        if ($right == 'all_domains' && $this->canManageDomain('*')) {
            return 1;
        }
        if (isset($this->_rights[$right])) {
            return $this->_rights[$right];
        }
        return 0;
    }

    public function getDomainsArray()
    {
        return $this->_domains;
    }

    public function canManageDomain($domain)
    {
        if (in_array('*', $this->_domains)) {
            return 1;
        }
        foreach ($this->_domains as $d) {
            if ($d == $domain) {
                return 1;
            }
            if ($this->getRight('allow_subdomains') && preg_match('/\S+\.' . $d . '$/', $domain)) {
                return 1;
            }
        }
        return 0;
    }

    public function getRoles()
    {
        return $this->_roles;
    }

    public function getUserType()
    {
        foreach ($this->_roles as $role) {
            $notrole = 0;
            foreach ($role['rights'] as $right) {
                if (!$this->getRight($right)) {
                    $notrole = 1;
                    break;
                }
            }
            if (!$notrole) {
                return $role['name'];
            }
        }
        return 'none';
    }

    public function setUserType($r)
    {
        if ($r == 'administrator') {
            $this->setParam('domains', '*');
        }
        foreach ($this->_roles as $role) {
            if ($role['name'] == $r) {
                foreach ($this->getRights() as $right) {
                    if (in_array($right, $role['rights'])) {
                        $this->setRight($right, 1);
                    } else {
                        $this->setRight($right, 0);
                    }
                }
            }
        }
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_AdministratorMapper());
        }
        return $this->_mapper;
    }

    public function find($username)
    {
        $this->getMapper()->find($username, $this);
        return $this;
    }

    public function fetchAllName($params = NULL)
    {
        return $this->getMapper()->fetchAllName($params);
    }

    public function setPassword($password)
    {
        if ($password == '') {
            return;
        }
        #$this->setParam('password', crypt($password, dechex(rand(0,15)).dechex(rand(0,15))));
        #$this->setParam('password', md5($password));
        $crypted = crypt($password, '$6$rounds=1000$' . dechex(rand(0, 15)) . dechex(rand(0, 15)) . '$');
        $this->setParam('password', $crypted);
    }

    public function save()
    {
        if ($this->_values['password'] == '') {
            unset($this->_values['password']);
        }
        foreach ($this->_rights as $r => $v) {
            if ($r == 'all_domain') {
                continue;
            }
            $this->_values[$r] = $v;
        }
        if ($this->getParam('username') == 'admin') {
            $this->setDomains('*');
        }
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        $t = Zend_Registry::get('translate');
        if ($this->getParam('username') == 'admin') {
            throw new Exception($t->_('Cannot remove admin user'));
        }
        if ($this->getParam('username') == Zend_Registry::get('user')->getParam('username')) {
            throw new Exception($t->_('Cannot commit suicide'));
        }
        return $this->getMapper()->delete($this);
    }

    public function checkAuthentication($givenusername, $givenpassword)
    {
        // deprecated call, gui interface authentication through controller
        return false;
    }
    public function checkAPIAuthentication($givenusername, $givenpassword)
    {
        $authAdapter = new Zend_Auth_Adapter_DbTable(
            Zend_Registry::get('writedb'),
            'administrator',
            'username',
            'password',
            'ENCRYPT(?, SUBSTRING(password, 1, LOCATE(\'$\', password, LOCATE(\'$\', password, 4)+1)))'
        );
        $authAdapter2 = new Zend_Auth_Adapter_DbTable(
            Zend_Registry::get('writedb'),
            'administrator',
            'username',
            'password',
            'MD5(?)'
        );
        $authAdapter->setIdentity($givenusername)->setCredential($givenpassword);
        $authAdapter2->setIdentity($givenusername)->setCredential($givenpassword);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($authAdapter);
        $result2 = $auth->authenticate($authAdapter2);
        if ($result->isValid() || $result2->isValid()) {
            return true;
        }
        return false;
    }

    public function checkPasswordEncryptionSheme($clearpassword)
    {
        $acceptedScheme = '^\$6\$'; # we want crypt-sha512, so check for encrypted pass starting with $6$

        if (!$clearpassword || $clearpassword == '') {
            return false;
        }
        if (preg_match('/' . $acceptedScheme . '/', $this->getParam('password'))) {
            return true;
        }
        ## old/weak password encryption, save it with new scheme
        $this->setPassword($clearpassword);
        $this->save();
    }
}
