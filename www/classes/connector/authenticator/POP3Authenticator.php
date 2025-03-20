<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * requires PEAR's Auth class
 */
require_once("Auth.php");

/**
 * This is the POP3Authenticator class
 * This will take care of authenticate user against an POP3 server
 * @package mailcleaner
 */
class POP3Authenticator extends AuthManager
{

    protected $exhaustive_ = false;

    function create($domain)
    {
        $settings = $domain->getConnectorSettings();
        if (!$settings instanceof SimpleServerSettings) {
            return false;
        }

        $funct = ["LoginDialog", "loginFunction"];
        $params = [
            "host" => $settings->getSetting('server'),
            "port" => $settings->getSetting('port')
        ];
        $this->auth_ = new Auth('POP3', $params, $funct);
        if ($this->auth_ instanceof Auth) {
            $this->setUpAuth();
            return true;
        }
        return false;
    }
}
