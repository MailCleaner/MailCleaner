<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * This is the POPIMAPAuthenticator class
 * @package mailcleaner
 */
class POPIMAPAuthenticator extends AuthManager
{


    /**
     * values to be fetched from authentication
     * @var array
     */
    private $values_ = ['stub_user' => 1];

    protected $exhaustive_ = false;

    private $server_;
    private $status_ = 0;
    private $params_ = ['type' => 'imap', 'host' => '', 'port' => '', 'usessl' => ''];

    /**
     * create the authenticator
     */
    function create($domain)
    {
        $settings = $domain->getConnectorSettings();
        if (!$settings instanceof SimpleServerSettings) {
            return false;
        }

        $use_ssl = '/novalidate-cert';
        if ($settings->getSetting('usessl') == "true" || $settings->getSetting('usessl')) {
            $use_ssl = '/ssl/novalidate-cert';
        }
        $type = '/imap';
        if ($domain->getPref('auth_type') == 'pop' || $domain->getPref('auth_type') == 'pop3') {
            $type = '/pop3';
        }

        $this->params_ = [
            "type" => $type,
            "host" => $settings->getSetting('server'),
            "port" => $settings->getSetting('port'),
            "usessl" => $use_ssl
        ];
        return true;
    }

    /**
     * overridden from AuthManager
     */
    public function start()
    {
    }

    /**
     * overridden from AuthManager
     */
    public function getStatus()
    {
        return $this->status_;
    }

    /**
     * overridden from Authmanager
     */
    public function doAuth($username)
    {

        if (!$username || $username == '') {
            $this->status_ = -1;
            return false;
        }
        $dn = "{" . $this->params_['host'] . ":" . $this->params_['port'] . $this->params_['type'] . $this->params_['usessl'] . "}INBOX";
        $res = imap_open($dn, $username, $_POST['password']);

        if ($res) {
            imap_close($res);
            $this->status_ = 0;
            return true;
        }

        // wrong login
        $this->status_ = -3;
        return false;
    }
}
