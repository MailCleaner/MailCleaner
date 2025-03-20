<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * This class is only a settings wrapper for the apache configuration
 */
class HTTPDConfig extends PrefHandler
{

    /**
     * httpd settings
     * @var array
     */
    private $pref_ = [
        'use_ssl' => 'true',
        'serveradmin' => 'postmaster@localhost',
        'servername' => 'localhost',
        'timeout' => 300,
        'keepalivetimeout' => 100,
        'min_servers' => 3,
        'max_servers' => 10,
        'start_servers' => 5,
        'http_port' => 80,
        'https_port' => 443,
        'certificate_file' => 'default.pem'
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('httpd_config', 'c', $this->pref_);
    }

    /**
     * load settings
     * @return  boolean true on success, false on failure
     */
    public function load()
    {
        return $this->loadPrefs('', '1=1', false);
    }
}
