<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

define('SOAPTIMEOUT', 20);
require_once('system/SoapTypes.php');

/**
 * this is the soap wrapper
 */
class Soaper
{

    /**
     * soap client object
     * @var  SoapClient
     */
    private $client_;

    /**
     * load and connect soap client
     * @param  $host  string  slave host
     * @return        string  'OK' on success, error message on failure
     */
    public function load($host, $timeout = 20)
    {
        global $SoapClassMap;

        $time = SOAPTIMEOUT;
        if (is_numeric($timeout)) {
            $time = $timeout;
        }
        // don't want any cache
        ini_set("soap.wsdl_cache_enabled", "0");
        // and connect
        try {
            $this->client_ = @new SoapClient(
                "http://$host:5132/mailcleaner.wsdl",
                ["connection_timeout" => $time, "trace" => 0, "exceptions" => 1, "classmap" => $SoapClassMap]
            );
        } catch (Exception $e) {
            //@todo  catch more exceptions
            return "SOAPERRORCANNOTCONNECTSLAVE: $e";
        }
        return "OK";
    }

    /**
     * do the soap session authentication stuff
     * return   string  session id of the soap session
     */
    public function authenticateAdmin()
    {
        global $admin_;
        if (!isset($admin_) || (!$admin_ instanceof Administrator)) {
            return 'NOADMINAVAILABLEFORSOAP';
        }

        if (!$this->client_ instanceof SoapClient) {
            return 'LOSTSOAPCLIENT';
        }

        try {
            return $this->client_->setAuthenticated($admin_->getPref('username'), 'admin', $_SERVER['REMOTE_ADDR']);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * return the user name of the session
     * @param  $sid  string session ID
     * @return       string user/admin name or ""
     */
    public function getSessionUser($sid)
    {
        if ($this->checkSession($sid)) {
            try {
                return $this->client_->getAdminName($sid);
            } catch (Exception $e) {
            }
        }
        return "";
    }

    /**
     * execute a soap query
     * @param  $function  string  soap function name
     * @param  $params    array   function parameters
     * @return            mixed   function return values
     */
    public function query($query, $params)
    {
        $sid = $this->authenticateAdmin();
        if (preg_match('/^[A-Z]+$/', $sid)) {
            return $sid;
        }

        if (!$this->client_ instanceof SoapClient) {
            return 'LOSTSOAPCLIENT';
        }

        // do the call
        try {
            return $this->client_->$query($sid);
        } catch (Exception $e) {
        }
        return null;
    }

    /**
     * execute a soap query with parameters but without session id
     * @param  $function  string soap function name
     * @param  $params    array  function parameters
     * @return            mixed  function return values
     */
    public function queryParam($query, $params)
    {
        if (!$this->client_ instanceof SoapClient) {
            return 'LOSTSOAPCLIENT';
        }
        try {

            switch (count($params)) {
                case 1:
                    return $this->client_->$query($params[0]);
                case 2:
                    return $this->client_->$query($params[0], $params[1]);
                case 3:
                    return $this->client_->$query($params[0], $params[1], $params[2]);
                case 4:
                    return $this->client_->$query($params[0], $params[1], $params[2], $params[3]);
            }
        } catch (Exception $e) {
        }
        return null;
    }

    /**
     * check the session
     * @param $sid  string  session ID
     * @return      boolean true id session is valid, false if not
     */
    public function checkSession($sid)
    {
        if (!$this->client_ instanceof SoapClient) {
            return false;
        }
        try {
            return $this->client_->checkAuthenticated($sid, 'admin');
        } catch (Exception $e) {
        }
    }
}
