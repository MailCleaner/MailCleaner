<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of fetching LDAP addresses
 * @package mailcleaner
 */
class LDAPlookup extends AddressFetcher
{

    /**
     * ldap connection handler
     * @var resource
     */
    protected $connection_;

    /**
     * Definition of possible mail attributes
     * @var array
     */
    protected $mail_attributes_ = ['mail', 'maildrop', 'mailAlternateAddress', 'mailalternateaddress', 'proxyaddresses', 'proxyAddresses', 'oldinternetaddress', 'oldInternetAddress', 'cn', 'userPrincipalName', 'mailPrimaryAddress', 'mailAlternativeAddress'];

    public function fetch($username, $domain)
    {
        $settings = $domain->getConnectorSettings();
        if (!$this->connect($settings)) {
            return $this->getAddresses();
        }

        $userfilter = '';
        $filter = sprintf(
            '(&(%s=%s)%s)',
            $settings->getSetting('useratt'),
            $username,
            $userfilter
        );

        $r = ldap_search($this->connection_, $settings->getSetting('basedn'), $filter);
        $ret = [];
        if ($r) {
            $result = ldap_first_entry($this->connection_, $r);
            while ($result) {
                $attrs = ldap_get_attributes($this->connection_, $result);

                foreach ($this->mail_attributes_ as $att) {
                    if (isset($attrs[$att])) {
                        $address = ldap_get_values($this->connection_, $result, $att);
                        foreach ($address as $i => $add) {
                            if (!is_int($i)) {
                                continue;
                            }
                            if (preg_match('/\s?(\S+:)?(\S+\@\S+)/i', $add, $ret)) {
                                $this->addAddress($ret[2], $ret[2]);
                            } elseif ($att == 'maildrop') {
                                $this->addAddress($add . '@' . $domain->getPref('name'), false);
                            }
                        }
                    }
                }
                $result = ldap_next_entry($this->connection_, $result);
            }
            ldap_free_result($r);
        }

        return $this->getAddresses();
    }

    /**
     * Connect to the ldap server and bind if needed
     * @param  $settings  LDAPSetting  LDAP settings
     * @return            bool         true on success, false on failure
     */
    protected function connect($settings)
    {
        if (!$settings instanceof LDAPSettings) {
            return false;
        }
        $url = $settings->getURL();
        $this->connection_ = ldap_connect($url);
        ldap_set_option($this->connection_, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection_, LDAP_OPT_PROTOCOL_VERSION, $settings->getSetting('version'));
        ldap_set_option($this->connection_, LDAP_OPT_TIMELIMIT, 30);
        if ($settings->getSetting('usessl')) {
            ldap_start_tls($this->connection_);
        }
        if (!@ldap_bind($this->connection_, $settings->getSetting('binduser'), $settings->getSetting('bindpassword'))) {
            // cannot bind
            echo "Error in bind";
            return false;
        }
        return true;
    }


    public function canModifyList()
    {
        return false;
    }

    public function searchUsers($u, $d)
    {
        $ignore_username = ['/SM_[a-z0-9]{17}/'];
        $settings = $d->getConnectorSettings();
        if (!$this->connect($settings)) {
            return $this->getAddresses();
        }

        $filter = "(&(objectClass=person)(" . $settings->getSetting('useratt') . "=" . $u . "*)(|";
        // only get the accounts that have an email
        foreach ($this->mail_attributes_ as $att) {
            #$filter .= "($att=*@*)";
            $filter .= "($att=*)";
        }
        $filter .= "))";
        $ret = [];
        $r = @ldap_search($this->connection_, $settings->getSetting('basedn'), $filter, [$settings->getSetting('useratt')], 0, 1000);
        if ($r) {
            $entry = ldap_first_entry($this->connection_, $r);
            while ($entry) {
                $user = ldap_get_values($this->connection_, $entry, $settings->getSetting('useratt'));
                $do = 1;
                foreach ($ignore_username as $iu) {
                    if (preg_match($iu, $user[0])) {
                        $do = 0;
                    }
                }
                if (preg_match("/\@(\S+)/", $user[0], $matches)) {
                    if ($matches[1] != $d->getPref('name')) {
                        $do = 0;
                    }
                }
                if ($do) {
                    $ret[$user[0]] = $user[0];
                }
                $entry = ldap_next_entry($this->connection_, $entry);
            }
        }
        @ldap_free_result($r);
        return $ret;
    }

    public function searchEmails($l, $d)
    {
        $ignore_email = ['/FederatedEmail\.[-a-z0-9]{20,}/'];
        $settings = $d->getConnectorSettings();
        if (!$this->connect($settings)) {
            return $this->getAddresses();
        }
        #$filter = "(&(objectClass=person)(|";
        $filter = "(|";
        $add_expr = "*" . $l . "*";
        if ($l == '') {
            $add_expr = '*';
        }
        foreach ($this->mail_attributes_ as $att) {
            #$filter .= "($att=".$add_expr."@".$d->getPref('name').")";
            if ($att == 'maildrop') {
                $filter .= "($att=" . $add_expr . ")";
            } else {
                $filter .= "($att=" . $add_expr . "@" . $d->getPref('name') . ")";
            }
        }
        #$filter .= "))";
        $filter .= ")";
        $ret = [];
        $matches = [];
        $r = @ldap_search($this->connection_, $settings->getSetting('basedn'), $filter, $this->mail_attributes_, 0, 1000);
        $add_expr = preg_replace('/\*/', '.*', $add_expr);
        if ($r) {
            $entry = ldap_first_entry($this->connection_, $r);
            while ($entry) {
                $attrs = ldap_get_attributes($this->connection_, $entry);
                foreach ($this->mail_attributes_ as $att) {
                    if (isset($attrs[$att])) {
                        $email = ldap_get_values($this->connection_, $entry, $att);
                        $i = 0;
                        while (1) {
                            if (!isset($email[$i])) {
                                break;
                            }
                            $address = $email[$i++];
                            $do = 1;
                            foreach ($ignore_email as $ie) {
                                if (preg_match($ie, $address)) {
                                    $do = 0;
                                }
                            }
                            if ($att != 'maildrop' && !preg_match("/" . $add_expr . "@" . $d->getPref('name') . "/i", $address)) {
                                $do = 0;
                            }
                            if ($do) {
                                if ($att == 'maildrop') {
                                    $ret[strtolower($address . '@' . $d->getPref('name'))] = strtolower($address . '@' . $d->getPref('name'));
                                }
                                // matches STMP: xx@xx  fields
                                if (preg_match('/\s?\S+:([a-zA-Z0-9\_\-\.]+\@[a-zA-Z0-9\_\-\.]+)/i', $address, $matches)) {
                                    $ret[strtolower($matches[1])] = strtolower($matches[1]);
                                }
                                // matches other fields
                                if (preg_match('/^[a-zA-Z0-9\_\-\.]+\@[a-zA-Z0-9\_\-\.]+$/i', $address)) {
                                    $ret[strtolower($address)] = strtolower($address);
                                }
                            }
                        }
                    }
                }
                $entry = ldap_next_entry($this->connection_, $entry);
            }
            ldap_free_result($r);
        }
        return $ret;
    }
}
