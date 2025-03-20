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
 * This is the RadiusAuthenticator class
 * This will take care of authenticate user against a Radius server
 * @package mailcleaner
 */
class RadiusAuthenticator extends AuthManager
{

    protected $exhaustive_ = true;

    function create($domain)
    {
        $settings = $domain->getConnectorSettings();
        if (!$settings instanceof RadiusSettings) {
            return false;
        }

        $ser = [[$settings->getSetting('server'), $settings->getSetting('port'), $settings->getSetting('secret')]];

        $funct = ["LoginDialog", "loginFunction"];
        $params = [
            "servers" => $ser,
            "authtype" => $settings->getSetting('authtype')
        ];
        $this->auth_ = new Auth('RADIUS', $params, $funct);
        if ($this->auth_ instanceof Auth) {
            $this->setUpAuth();
            return true;
        }
        return false;
    }
}
