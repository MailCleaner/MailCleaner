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
require_once("connector/settings/SQLSettings.php");

/**
 * This is the AdminAuthenticator class
 * This will take care of authenticate administrative users
 * @package mailcleaner
 */
class AdminAuthenticator extends AuthManager
{


    function create($domain)
    {
        // go with local default settings
        $settings = new SQLSettings('');
        $settings->setSetting('table', 'administrator');
        $settings->setSetting('crypt_type', 'crypt');
        $settings->setSetting('port', '3307');
        $dsn = $settings->getDSN();

        $funct = ["LoginDialog", "loginFunction"];
        $params = [
            "dsn"   => $dsn,
            "table" => $settings->getSetting('table'),
            "usernamecol" => $settings->getSetting('login_field'),
            "passwordcol" => $settings->getSetting('password_field'),
            "cryptType" => $settings->getSetting('crypt_type')
        ];
        $this->auth_ = new Auth('DB', $params, $funct);
        if ($this->auth_ instanceof Auth) {
            $this->setUpAuth();
            return true;
        }
        return false;
    }
}
