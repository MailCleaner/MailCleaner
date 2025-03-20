<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

define(INTERFACE_FILE, "/etc/network/interfaces");

/**
 * This is the class is a configuration wrapper for a network interface
 */
class Iface
{
    /**
     * interface settings
     */
    private $props_ = [
        'if' => "",
        'ip' => "0.0.0.0",
        'netmask' => "255.255.255.0",
        'gateway' => "0.0.0.0",
        'netbase' => '0.0.0.0',
        'broadcast' => '0.0.0.0'
    ];

    /**
     * constructor
     * @param  $name   name of the interface
     */
    function __construct($name)
    {
        $this->setProperty('if', $name);
    }

    /**
     * set a preference
     * @param  $pref  string  preference name
     * @param  $value mixed   preference value
     * @return        boolean true on success, false on failure
     */
    public function setProperty($pref, $value)
    {
        if (isset($this->props_[$pref])) {
            $this->props_[$pref] = $value;
            return true;
        }
        return false;
    }

    /**
     * get a preference value
     * @param  $pref  string preference name
     * @return        mixed  preference value
     */
    public function getProperty($pref)
    {
        if (isset($this->props_[$pref])) {
            return $this->props_[$pref];
        }
        return "";
    }

    /**
     * load interface datas
     * @return  boolean  true on success, false on failure
     */
    public function load()
    {

        $file = file(INTERFACE_FILE);
        if (!$file) {
            return false;
        }

        $in_if = 0;
        $matches = [];
        foreach ($file as $line) {
            // find the correct block
            if (preg_match('/iface\s*(\S+\d+)/', $line, $matches)) {
                if ($matches[1] == $this->getProperty('if')) {
                    $in_if = 1;
                } else {
                    $in_if = 0;
                }
            }

            // if we are not in the correct block, then do not parse line;
            if (!$in_if) {
                continue;
            }

            // find ip address value
            if (preg_match('/^\s*address\s+(\S+)/', $line, $matches)) {
                $this->setProperty('ip', $matches[1]);
            }
            // find network mask value
            if (preg_match('/^\s*netmask\s+(\S+)/', $line, $matches)) {
                $this->setProperty('netmask', $matches[1]);
            }
            // find gateway ip value
            if (preg_match('/^\s*gateway\s+(\S+)/', $line, $matches)) {
                $this->setProperty('gateway', $matches[1]);
            }
        }

        $this->calcBroadcast();
    }

    /**
     * compute the broadcast and network base addresses once we have the netmask and the ip address
     * @return  boolean  true on success, false on failure
     */
    private function calcBroadcast()
    {
        $netmask = escapeshellarg($this->getProperty('netmask'));

        $cmd = "ipcalc -nb " . $this->ip_ . "/" . $netmask . " | grep Network: | cut -d' ' -f4 | cut -d'/' -f1";
        $this->setProperty('netbase', trim(`$cmd`));

        $cmd = "ipcalc -nb " . $this->ip_ . "/" . $this->netmask_ . " | grep Broadcast: | cut -d' ' -f2";
        $this->setProperty('broadcast', trim(`$cmd`));
        return true;
    }

    /**
     * return the interface full string to be used in interfaces file
     * @return  string  full interface string
     */
    public function getConfigString()
    {
        if ($this->ip_ == '0.0.0.0') {
            return "";
        }
        $this->calcBroadcast();
        $ret = "auto " . $this->getProperty('if') . "\n";
        $ret .= "iface " . $this->getProperty('if') . " inet static\n";
        $ret .= "  address " . $this->getProperty('ip') . "\n";
        $ret .= "  netmask " . $this->getProperty('netmask') . "\n";
        $ret .= "  broadcast " . $this->getProperty('broadcast') . "\n";
        $ret .= "  gateway " . $this->getProperty('gateway') . "\n";
        return $ret;
    }
}
