<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * NTP settings
 */

class Default_Model_NTPSettings
{
    protected $_configfile = '/etc/ntp.conf';
    protected $_servers = [];

    public function __construct()
    {
    }

    public function load()
    {
        if (file_exists($this->_configfile)) {
            $content = file($this->_configfile);
            foreach ($content as $line) {
                if (preg_match('/^\s*server\s+(\S+)/', $line, $matches)) {
                    $this->_servers[] = $matches[1];
                }
            }
        }
    }

    public function getServersString()
    {
        $ret = '';
        foreach ($this->_servers as $s) {
            $ret .= ', ' . $s;
        }
        return preg_replace('/^,\s*/', '', $ret);
    }

    public function setServers($string)
    {
        $this->_servers = [];
        if ($string == "") {
            return;
        }
        $servers = preg_split('/[,:\s]+/', $string);
        foreach ($servers as $s) {
            $s = preg_replace('/^\s*/', '', $s);
            $s = preg_replace('/\s*$/', '', $s);
            $this->_servers[] = $s;
        }
    }

    public function useNTP()
    {
        if (count($this->_servers) > 0) {
            return true;
        }
        return false;
    }


    public function save($sync = false)
    {
        $tmpfile = '/tmp/mc_ntp.tmp';

        if (file_exists($this->_configfile)) {
            $txt = '';
            $content = file($this->_configfile);
            foreach ($content as $line) {
                if (!preg_match('/^\s*server\s+(\S+)/', $line)) {
                    $txt .= $line;
                }
            }
            if ($this->useNTP() && $sync) {
                foreach ($this->_servers as $s) {
                    if (preg_match('/\S+/', $s)) {
                        $txt .= 'server ' . $s . ' iburst' . "\n";
                    }
                }
            }
            $written = file_put_contents($tmpfile, $txt);
            if ($written) {
                $soapres = Default_Model_Localhost::sendSoapRequest('Config_saveNTPConfig', ['sync' => $sync, 'timeout' => 20]);
                return $soapres;
            } else {
                'NOK could not write to temporary file';
            }
        }
        return 'NOK config file not found';
    }
}
