<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this is a DataManager instance
 */
require_once('helpers/DataManager.php');

/**
 * connect to the main master configuration database
 */
class DM_MasterConfig extends DataManager
{

    private static $instance;

    public function __construct()
    {
        parent::__construct();

        $socket = $this->getConfig('VARDIR') . "/run/mysql_master/mysqld.sock";
        $this->setOption('SOCKET', $socket);
        $this->setOption('DATABASE', 'mc_config');
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new DM_MasterConfig();
        }
        return self::$instance;
    }
}

