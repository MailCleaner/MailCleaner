<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of fetching addresses in a SQL database
 * They may be multiple addresses, separated by ','
 * @package mailcleaner
 */
class SQLlookup extends AddressFetcher
{


    public function fetch($username, $domain)
    {
        $sysconf_ = SystemConfig::getInstance();
        $settings = $domain->getConnectorSettings();

        if (!$settings instanceof SQLSettings) {
            return false;
        }
        $dsn = $settings->getDSN();

        $query = "SELECT " . $settings->getSetting('email_field') . " from " . $settings->getSetting('table');
        $query .= " WHERE " . $settings->getSetting('login_field') . "='$username' AND " . $settings->getSetting('domain_field') . "='" . $domain->getPref('name') . "'";

        $db = &DB::connect($dsn);
        if (DB::isError($db)) {
            return $this->getAddresses();
        }
        $res = &$db->query($query);
        if ($res->numRows() < 1) {
            return $this->getAddresses();
        }
        if (!$row = &$res->fetchRow(DB_FETCHMODE_ASSOC)) {
            return $this->getAddresses();
        }
        $adds = preg_split('/,/', $row['email']);
        foreach ($adds as $add) {
            $this->addAddress($add, $add);
        }
        $res->free();
        $db->disconnect();

        return $this->getAddresses();
    }

    public function searchUsers($u, $d)
    {
        require_once('helpers/DataManager.php');

        $db_slaveconf = DM_SlaveConfig::getInstance();
        $query = "SELECT username FROM mysql_auth WHERE username LIKE '" . $db_slaveconf->sanitize($u) . "%' AND domain='" . $db_slaveconf->sanitize($d->getPref('name')) . "'";
        $res = $db_slaveconf->getListOfHash($query);
        if (!is_array($res)) {
            return [];
        }
        $ret = [];
        foreach ($res as $user) {
            $ret[$user['username']] = $user['username'];
        }
        return $ret;
    }

    public function searchEmails($l, $d)
    {
        require_once('helpers/DataManager.php');

        $db_slaveconf = DM_SlaveConfig::getInstance();
        if ($d->getPref('name') != '*') {
            $query = "SELECT email FROM mysql_auth WHERE email LIKE '%" . $db_slaveconf->sanitize($l) . "%@" . $db_slaveconf->sanitize($d->getPref('name')) . "'";
        } else {
            $query = "SELECT email FROM mysql_auth WHERE email LIKE '%" . $db_slaveconf->sanitize($l) . "%'";
        }
        $res = $db_slaveconf->getListOfHash($query);
        if (!is_array($res)) {
            return [];
        }
        $ret = [];
        foreach ($res as $email) {
            $emails = preg_split('/,/', $email['email']);
            foreach ($emails as $add) {
                $ret[$add] = $add;
            }
        }
        return $ret;
    }

    public function canModifyList()
    {
        return false;
    }
}
