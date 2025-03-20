<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this mainly use the PEAR DB package
 */
require_once("Pear/DB.php");
require_once('system/SystemConfig.php');

/**
 * this class is a database manager.
 * It manages the connection to the database and the provide the different datas fetcher method.
 * It also provide the main system configuration set in the configuration file
 */
class DataManager
{

    /**
     * database connection handle
     * @var  DB
     */
    protected $db_handle;

    /**
     * default connection options
     * @param  array
     */
    private $db_config = [
        'HOST' => 'localhost',
        'PORT' => '3306',
        'USER' => 'mailcleaner',
        'MYMAILCLEANERPWD' => '',
        'PASSWORD' => '',
        'SOCKET' => '',
        'DATABASE' => 'mc_config',
    ];
    /**
     * default base system configuration options
     * @param  array
     */
    private $base_config = [
        'VARDIR' => '/var/mailcleaner',
        'SRCDIR' => '/usr/mailcleaner'
    ];

    /**
     * maintain the last error encountered
     * @var string
     */
    private $last_error_ = "";

    /**
     * constructor
     */
    public function __construct()
    {
        $baseconf = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);

        foreach ($baseconf as $option => $value) {
            $this->setOption($option, $value);
            $this->setConfig($option, $value);
        }
    }

    /**
     * set an database setting options
     * @param  $option  string  option name
     * @param  $value   mixed   option value
     * @return          boolean true on success, false on failure
     */
    protected function setOption($option, $value)
    {
        if (isset($this->db_config[$option])) {
            $this->db_config[$option] = $value;
            return true;
        }
        return false;
    }
    /**
     * set a global system configuration setting
     * @param  $option  string  setting name
     * @param  $value   mixed   setting value
     * @return          boolean true on success, false on failure
     */
    protected function setConfig($option, $value)
    {
        if (isset($this->base_config[$option])) {
            $this->base_config[$option] = $value;
            return true;
        }
        return false;
    }
    /**
     * return a database setting value
     * @param  $option  string  option name
     * @return          mixed   option value
     */
    protected function getOption($option)
    {
        if (isset($this->db_config[$option])) {
            return $this->db_config[$option];
        }
        return "";
    }
    /**
     * return a global system configuration setting value
     * @param  $option  string  setting name
     * @return          mixed   setting value
     */
    protected function getConfig($option)
    {
        if (isset($this->base_config[$option])) {
            return $this->base_config[$option];
        }
        return "";
    }

    /**
     * return the last error value encountered
     * @return  string  last error
     */
    public function getLastError()
    {
        return $this->last_error_;
    }

    /**
     * get the base configuration options set in the configuration file
     * @param  $file  string  configuration file path
     * @return        array   array of configuration options and values
     */
    static public function getFileConfig($file)
    {
        $val = [];
        $ret = [];

        $lines = file($file);
        if (!$lines) {
            return;
        }

        foreach ($lines as $line_num => $line) {
            if (preg_match('/^([A-Z0-9]+)\s+=\s+(\S+)/', $line, $val)) {
                $ret[$val[1]] = $val[2];
            }
        }
        return $ret;
    }

    /**
     * connect to the database chosen
     * @return  boolean  true on success, false on failure
     */
    private function connect()
    {
        if (!isset($this->db_handle) or DB::isError($this->db_handle)) {
            if ($this->getOption('SOCKET') != "") {
                $dsn = "mysqli://" . $this->getOption('USER') . ":" . $this->getOption('MYMAILCLEANERPWD') .
                    "@unix(" . $this->getOption('SOCKET') . ")/" . $this->getOption('DATABASE');
            } else {
                $dsn = "mysqli://" . $this->getOption('USER') . ":" . $this->getOption('PASSWORD') .
                    "@" . $this->getOption('HOST') . ":" . $this->getOption('PORT') . "/" . $this->getOption('DATABASE');
            }
            $options = [
                'persistent' => true,
                'debug' => 1
            ];


            $this->db_handle = DB::connect($dsn, $options);
            if (DB::isError($this->db_handle)) {
                // ERROR MSG
                $this->last_error_ = "error connecting to db, $dsn";
                return false;
            }
        }
        if (isset($this->db_handle) && !DB::isError($this->db_handle)) {
            return true;
        }
        // ERROR MSG
        $this->last_error_ = "no db handle";
        return false;
    }

    /**
     * execute a query to the database
     * @param  $query  string  query string to execute
     * @return         mixed   query result set
     */
    private function execute($query)
    {
        global $log_;
        if ($log_) {
            $log_->log("executing query: $query", PEAR_LOG_DEBUG);
        }
        if (!$this->connect()) {
            // ERROR MSG
            echo "lost connection...";
            return null;
        }
        $res = &$this->db_handle->query($query);

        if (DB::isError($res)) {
            if ($res->getCode() == -5) {
                return "RECORDALREADYEXISTS";
            }
            // ERROR MSG
            echo "missed query ($query)<br/>";
            echo $res->getMessage() . " (" . $res->getCode() . ")";
            return null;
        }

        return $res;
    }

    /**
     * execute a query and return the results in a hash (used for single row queries)
     * @param  $query  string  query to execute
     * @return         array   results as an hash table
     */
    public function getHash($query)
    {
        $res = $this->execute($query);
        if (!$res) {
            return null;
        }
        $row = &$res->fetchRow(DB_FETCHMODE_ASSOC);
        $ret = [];
        if (isset($row)) {
            $ret = $row;
        }
        $res->free();
        return $ret;
    }

    /**
     * execute a query and return the results as a simple list (used for id list queries for example)
     * @param  $query  string  query to execute
     * @return         [] list of values
     */
    public function getList($query)
    {
        $res = $this->execute($query);
        $res_a = [];
        while ($row = $res->fetchRow(DB_FETCHMODE_ORDERED)) {
            $res_a[$row[0]] = $row[0];
        }
        $res->free();
        return $res_a;
    }

    /**
     * execute a query and return the results as an list of hash tables
     * @param  $query  string  query to execute
     * @return         array   result set as array of hashes
     */
    public function getListOfHash($query)
    {
        if (!$this->connect()) {
            return null;
        }

        $res = &$this->db_handle->query($query);
        if (DB::isError($res)) {
            return null;
        }

        $res_a = [];
        while ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $res_a[$row['id']] = $row;
        }
        $res->free();
        return $res_a;
    }

    /**
     * execute a single query without result (such as insert, delete, etc..)
     * @param  $query  string  query to execute
     * @return         boolean true on success, false on failure
     */
    public function doExecute($query)
    {
        if (!$this->connect()) {
            return false;
        }

        $res = $this->execute($query);
        if ($res == "RECORDALREADYEXISTS") {
            $this->last_error_ = $res;
            return false;
        }
        if (DB::isError($res)) {
            return false;
        }
        return true;
    }

    /**
     * sanitize a value through the database functions
     * @param  $value  string  value to be sanitized
     * @return         string  clean value
     */
    public function sanitize($value)
    {
        if (!$this->connect()) {
            return false;
        }
        if (is_string($value)) {
            return $this->db_handle->escapeSimple($value);
        }
        if (is_array($value)) {
            $value[0] = $this->db_handle->escapeSimple($value[0]);
        }
        return $value;
    }
    /*
   public function getSimpleValue($query) {
     $ret = "";
     if (!$this->db_handle) { return $ret; }

     $res =& $this->db_handle->query($query);
     if (DB::isError($res)) { return $ret; }
     $ret = "";
     if ($res->numRows() > 0) {
       if ($row =& $res->fetchRow(DB_FETCHMODE_ORDERED)) {
         $ret = $row[0];
       }
     }
     $res->free();
     return $ret;
   }

  public function executeSimple($query) {
     $ret = "";
     if (!$this->db_handle) { return $ret; }

     $res =& $this->db_handle->query($query);
     if (DB::isError($res)) { return "FAILED"; }
     return $ret;
  }
*/
}
