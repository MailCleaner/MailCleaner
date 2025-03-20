<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * this class takes care of the system integrator information
 */
class Integrator extends PrefHandler
{
    /**
     * integrator information
     * @var array
     */
    private $infos_ = [
        'Name_T' => "",
        'Surname_T' => "",
        'Phone_T' => "",
        'Mail_T' => "",
        'Company' => "",
        'SiteInternet' => "",
        'Phone' => ""
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        require_once('system/SystemConfig.php');
        $sysconf = SystemConfig::getInstance();

        $values = @file($sysconf->VARDIR_ . "/spool/mailcleaner/integrator.txt");
        if (!$values || !is_array($values)) {
            return;
        }
        $matches = [];
        foreach ($values as $line) {
            if (preg_match('/^(\S+)\s*\=\s*(.*)/', $line, $matches)) {
                $this->setInfo($matches[1], $matches[2]);
            }
        }
    }

    /**
     * get information
     * @param $name string  information to be retrieved
     * @return      mixed   information value
     */
    public function getInfo($name)
    {
        if (isset($this->infos_[$name])) {
            return $this->infos_[$name];
        }
        return "";
    }

    /**
     * set an information
     * @param $name  string  information name
     * @param $value mixed   information value
     * @return       boolean true on success, false on failure
     */
    private function setInfo($name, $value)
    {
        if (isset($this->infos_[$name])) {
            $this->infos_[$name] = $value;
            return true;
        }
        return false;
    }
}
