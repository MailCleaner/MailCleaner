<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * LoginDialog needs the authentication connectors
 */
require_once("system/SystemConfig.php");
require_once("Auth.php");
require_once("view/Language.php");
require_once("user/User.php");
require_once("domain/Domain.php");
require_once("connector/AuthManager.php");

/**
 * constant definitions
 */
define("AUTHLOGFILE", "mc_auth.log");
/**
 * this class take care of setting the correct authentication connectors
 */
class LoginDialog
{

    /**
     * Authmanager object used
     * @var AuthManager
     */
    private $auth_;

    /**
     * username entered by the user, needed as it may be rewritten by connector
     * @var string
     */
    private $username_;

    /**
     * domain entered by the user or auto detected
     * @var string
     */
    private $domain_;

    /**
     * constructor, if defined, set the posted value and find out the other variables (f.e. domain)
     */
    public function __construct()
    {

        // first, unset any Auth session, this ensure that user is really logged out
        unset($_SESSION['_authsession']);
        // get global objects instances
        $sysconf = SystemConfig::getInstance();

        // detect if credential are passed through an HTTP request (POST or GET)
        $encrypted_password = "";
        if (isset($_REQUEST['id'])) {
            $decoded_id = base64_decode(urldecode($_REQUEST['id']));
            if (preg_match('/(\S+[^\=])\=(\S+)/', $decoded_id, $res)) {
                $_POST['username'] = $res[1];
                $_REQUEST['username'] = $res[1];
                $encrypted_password = $res[2];
            }
        }

        // check if a username has been posted
        if (isset($_REQUEST['username'])) {

            /** try to detect the domain for this username...
             * maybe given by POST or in username
             */

            // first get default domain
            $domain = $sysconf->getPref('default_domain');
            $this->username_ = $_REQUEST['username'];
            $this->username_ = str_replace('\'', '\\\'', $this->username_); // avoid problems with ' in usernames..

            // if we can find domain in login name given (such as login@domain)
            $ret = [];
            if (preg_match('/(.+)[@%](\S+)$/', $this->username_, $ret)) {
                $domain = $ret[2];
            }

            // if the domain is explicitly set in the POST
            if (isset($_REQUEST['domain']) && in_array($_REQUEST['domain'], $sysconf->getFilteredDomains())) {
                $domain = $_REQUEST['domain'];
            }

            // create domain object
            $this->domain_ = new Domain();
            $this->domain_->load($domain);

            // decrypt password if needed
            if ($encrypted_password != "") {
                if ($this->domain_->getPref('presharedkey') != "") {
                    // decrypt with mcrypt (3DES) using domain preshared key
                    $cipher = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
                    $iv = 'mailclea';
                    mcrypt_generic_init($cipher, $this->domain_->getPref('presharedkey'), $iv);
                    $password = mdecrypt_generic($cipher, $encrypted_password);
                } else {
                    $password = $encrypted_password;
                }
                $_POST['password'] = $password;
            }

            // then format username and create corresponding connector
            $this->username_ = $this->domain_->getFormatedLogin($this->username_);
            $_POST['username'] = $this->username_;
            $this->auth_ = AuthManager::getAuthenticator($this->domain_->getPref('auth_type'));
            $this->auth_->create($this->domain_);
        } else {
            // check if we want to authenticate using digest ID
            if (isset($_REQUEST['d']) && preg_match('/^[0-9a-f]{32}(?:[0-9a-f]{8})?$/i', $_REQUEST['d'])) {
                $this->auth_ = AuthManager::getAuthenticator('digest');
            } else {
                // create AuthManager instance with default domain connector
                $this->domain_ = new Domain();
                $this->domain_->load($sysconf->getPref('default_domain'));
                if (!$this->domain_) {
                    $this->auth_ = AuthManager::getAuthenticator(null);
                } else {
                    $this->auth_ = AuthManager::getAuthenticator($this->domain_->getPref('auth_type'));
                }
            }
            $this->auth_->create($this->domain_);
        }
    }

    /**
     * start the authentication
     * This check authentication and if, success, register the session and redirect to the index page
     * @return   boolean  don't return on success, false on failure
     */
    public function start()
    {
        // check connector
        if (!isset($this->auth_) || !$this->auth_ instanceof AuthManager) {
            return false;
        }

        // and start authentication objects
        $this->auth_->start();

        // ok, now check if user has given a good login/password pair !
        $_POST['username'] = $this->username_;
        if (isset($_POST['password'])) {
            $_POST['password'] = $_POST['password'];
        } else {
            $_POST['password'] = '';
        }
        if ($this->auth_->doAuth($this->username_)) {
            if ($this->auth_->getValue('username') != "") {
                $this->username_ = $this->auth_->getValue('username');
            }
            // just in case...
            if ($this->username_ == '') {
                return false;
            }
            // ok, create the user !
            $user = new User();
            if ($this->auth_->getValue('domain') != "") {
                unset($this->domain_);
                $this->domain_ = new Domain();
                $this->domain_->load($this->auth_->getValue('domain'));
            }
            $user->setDomain($this->domain_->getPref('name'));
            // setup some default values
            foreach (['gui_displayed_days'] as $p) {
                if ($this->auth_->getValue($p) != "") {
                    $user->setTmpPref($p, $this->auth_->getValue($p));
                }
            }

            // if we have a stub user (i.e. digest mode) so we need to feed it with address preferences
            if ($this->auth_->getValue('stub_user') != "" && $this->auth_->getValue('stub_user') > 0) {
                $user->setStub(true);
                $user->addAddress($this->auth_->getValue('mainaddress'));
                $user->setName($user->getMainAddress());
                $email = new Email();
                $email->load($user->getMainAddress());
                foreach (['gui_displayed_spams', 'gui_displayed_days', 'gui_mask_forced', 'gui_graph_type', 'gui_group_quarantines'] as $p) {
                    $user->setPref($p, $email->getPref($p));
                }
                // otherwise load user
            } else {
                $user->load($this->username_);
            }
            if ($this->auth_->getValue('realname') != "") {
                $user->setName($this->auth_->getValue('realname'));
            }
            $lang_ = Language::getInstance('user');
            if (isset($_GET['lang']) && $lang_->is_available($_GET['lang'])) {
                $user->setPref('language', $_GET['lang']);
            }
            // and register it to the session
            $_SESSION['user'] = serialize($user);
            $_SESSION['username'] = $this->username_;
            $_SESSION['domain'] = $this->domain_->getPref('name');
            // now log the login
            $sysconf = SystemConfig::getInstance();
            if (is_writable($sysconf->VARDIR_ . "/log/apache/" . AUTHLOGFILE)) {
                if ($logfile = fopen($sysconf->VARDIR_ . "/log/apache/" . AUTHLOGFILE, "a")) {
                    fwrite($logfile, "[" . date("d/M/Y:H:i:s O") . "] login SUCCESSFUL for user: " . $this->username_ . " - " . $_SERVER['REMOTE_ADDR'] . "\n");
                    fclose($logfile);
                }
            }
            // and finally redirect to the login page
            header("Location: index.php");
            exit();
        } else {
            // log authentication failure (with IP)
            if (isset($_REQUEST['username'])) {
                $sysconf = SystemConfig::getInstance();
                if (is_writable($sysconf->VARDIR_ . "/log/apache/" . AUTHLOGFILE)) {
                    if ($logfile = fopen($sysconf->VARDIR_ . "/log/apache/" . AUTHLOGFILE, "a")) {
                        fwrite($logfile, "[" . date("d/M/Y:H:i:s O") . "] login FAILED for user: " . $_REQUEST['username'] . " - " . $_SERVER['REMOTE_ADDR'] . "\n");
                        fclose($logfile);
                    }
                }
            }
        }
        return false;
    }

    /**
     * get the html string of the domain chooser select field if needed
     * @return    string  html select string
     */
    public function printDomainChooser()
    {
        $ret = "";
        $sysconf = SystemConfig::getInstance();
        if ($sysconf->getPref('want_domainchooser') == 1) {
            $ret .= "<select name=\"domain\" id=\"domainchooser\">\n";
            foreach ($sysconf->getFilteredDomains() as $domainname) {
                if ($domainname == $sysconf->getPref('default_domain')) {
                    $ret .= "<option value=\"$domainname\" selected>@$domainname</option>\n";
                } else {
                    $ret .= "<option value=\"$domainname\">@$domainname</option>\n";
                }
            }
            $ret .= "</select>\n";
        }
        return $ret;
    }

    public function hasDomainChooser()
    {
        $sysconf = SystemConfig::getInstance();
        if ($sysconf->getPref('want_domainchooser') == 1) {
            return true;
        }
        return false;
    }

    /**
     * get the html string of the language chooser select field if needed
     * @param $curr which is the key of the current selected lang
     * @return    string  html select string
     */
    public function printLanguageChooser($curr = null)
    {
        $ret = "";
        $sysconf = SystemConfig::getInstance();
        $lang_ = Language::getInstance('user');
        $availablesLangs = $lang_->getLanguages();
        $ret .= "<select name=\"language\" id=\"language\">\n";
        foreach ($availablesLangs as $key => $value) {
            if ($curr != null && $curr == $key)
                $ret .= "<option value=\"$key\" selected=\"selected\">$value</option>\n";
            else
                $ret .= "<option value=\"$key\">$value</option>\n";
        }
        $ret .= "</select>\n";
        return $ret;
    }

    /**
     * get the html string dispalying the status of the login
     * @return  string  status html string
     */
    public function printStatus()
    {
        $ret = "";
        if (!isset($this->auth_)) {
            return "";
        }
        $lang = Language::getInstance('user');
        if ($this->auth_->getStatus() == -3) {
            return 'BADLOGIN';
        } else if ($this->auth_->getStatus() == -2) {
            return 'SESSIONEXPIRED';
        }
        return "";
    }

    public function isLocal()
    {
        if (isset($this->domain_) && $this->domain_->getPref('auth_type') == 'local') {
            return true;
        }
        return false;
    }
}
