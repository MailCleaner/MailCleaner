<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Web server settings
 */

class Default_Model_HttpdConfig
{
    protected $_id;
    protected $_values = [
        'servername' => '',
        'use_ssl' => 'true',
        'http_port' => 80,
        'https_port' => 443,
        'tls_certificate_data' => '',
        'tls_certificate_key' => '',
        'tls_certificate_chain' => '',
    ];

    protected $_mapper;

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        if (array_key_exists($param, $this->_values)) {
            return $this->_values[$param];
        }
        return null;
    }

    public function getAvailableParams()
    {
        $ret = [];
        foreach ($this->_values as $key => $value) {
            $ret[] = $key;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_HttpdConfigMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function save()
    {
        if ($this->getParam('use_ssl')) {
            $this->setParam('use_ssl', 'true');
        } else {
            $this->setParam('use_ssl', 'false');
        }
        return $this->getMapper()->save($this);
    }

    public function checkSSLCertificate()
    {
        ## openssl x509 -noout -modulus -in certificate.crt | openssl md5
        ## openssl rsa -noout -modulus -in privateKey.key | openssl md5

        ## openssl verify certificate.crt
    }
}
