<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Proxies settings
 */

class Default_Model_ProxyManager
{
    protected $_httpproxy = '';
    protected $_smtpproxy = '';

    public function load()
    {
        $config = MailCleaner_Config::getInstance();
        $this->setHttpProxy($config->getOption('HTTPPROXY'));
        $this->setSmtpProxy($config->getOption('SMTPPROXY'));
    }

    public function getHttpProxy()
    {
        return $this->_httpproxy;
    }
    public function setHttpProxy($string)
    {
        $string = preg_replace('/http:\/\//', '', $string);
        $this->_httpproxy = $string;
    }
    public function getHttpProxyString()
    {
        if ($this->_httpproxy != '') {
            return 'http://' . $this->_httpproxy;
        }
        return '';
    }

    public function getSmtpProxy()
    {
        return $this->_smtpproxy;
    }
    public function setSmtpProxy($string)
    {
        $this->_smtpproxy = $string;
    }

    public function save()
    {
        return Default_Model_Localhost::sendSoapRequest('Config_saveMCConfigOption', ['HTTPPROXY' => $this->getHttpProxyString(), 'SMTPPROXY' => $this->getSMTPProxy()]);
    }
}
