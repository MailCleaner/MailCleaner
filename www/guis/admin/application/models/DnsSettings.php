<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * System DNS settings
 */

class Default_Model_DnsSettings
{
    protected $_config_file = "/etc/resolv.conf";

    protected $_domainsearch = "";
    protected $_nameservers = [];
    protected $_heloname = '';

    public function __construct()
    {
    }

    public function getDomainSearch()
    {
        return $this->_domainsearch;
    }
    public function setDomainSearch($domainsearch)
    {
        $this->_domainsearch = $domainsearch;
    }

    public function getNameServer($position)
    {
        $position = $position - 1;
        if (isset($this->_nameservers[$position])) {
            return $this->_nameservers[$position];
        }
        return '';
    }

    public function getNameServers()
    {
        return $this->_nameservers;
    }

    public function addNameServer($nameserver)
    {
        $this->_nameservers[] = $nameserver;
    }

    public function clearNameServers()
    {
        $this->_nameservers = [];
    }

    public function setHeloName($helo)
    {
        $this->_heloname = $helo;
    }

    public function getHeloName()
    {
        return $this->_heloname;
    }

    public function load()
    {
        if (file_exists($this->_config_file)) {
            $content = file($this->_config_file);
            foreach ($content as $line) {
                if (preg_match('/\s*(nameserver|search)\s+(\S+)/', $line, $matches)) {
                    switch ($matches[1]) {
                        case 'nameserver':
                            $this->_nameservers[] = $matches[2];
                            break;
                        case 'search':
                            $this->_domainsearch = $matches[2];
                            break;
                    }
                }
            }
        }
        $config = MailCleaner_Config::getInstance();
        $this->setHeloName($config->getOption('HELONAME'));
    }

    public function save()
    {
        $tmpfile = '/tmp/mc_resolv.tmp';
        $txt = '';
        if ($this->_domainsearch != '') {
            $txt = 'search ' . $this->_domainsearch . "\n";
        }

        foreach ($this->_nameservers as $ns) {
            if ($ns != '') {
                $txt .= 'nameserver ' . $ns . "\n";
            }
        }

        $written = file_put_contents($tmpfile, $txt);
        if ($written || $txt == '') {
            $soapres = Default_Model_Localhost::sendSoapRequest('Config_saveDnsConfig', null);
            $soapres = Default_Model_Localhost::sendSoapRequest('Config_saveMCConfigOption', [
                'HELONAME' => $this->getHeloName()
            ]);
            return $soapres;
        }
        return 'NOK could not write config file';
    }
}
