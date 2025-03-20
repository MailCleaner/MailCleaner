<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Local user
 */

class Default_Model_LocalUser
{
    protected $_id;

    protected $_values = [
        'username' => '',
        'domain' => '',
        'password' => '',
        'email' => '',
        'realname' => ''
    ];
    protected $_mapper;
    protected $_domain;

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

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setDomainObject($domain)
    {
        $this->_domain = $domain;
    }
    public function getDomainObject()
    {
        return $this->_domain;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_LocalUserMapper());
        }
        return $this->_mapper;
    }

    public function find($username, $domain)
    {
        $this->getMapper()->find($username, $domain, $this);
        return $this;
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        return $this->getMapper()->delete($this);
    }

    public function setPassword($password)
    {
        if ($password == '') {
            return;
        }
        $salt = '$6$rounds=1000$' . dechex(rand(0, 15)) . dechex(rand(0, 15)) . '$';
        $crypted = crypt($password, $salt);
        $this->setParam('password', $crypted);
    }
}
