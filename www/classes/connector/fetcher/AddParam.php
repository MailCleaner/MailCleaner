<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of guessing addresses by adding some parameter to the username
 * @package mailcleaner
 */
class AddParam extends AddressFetcher
{


    public function fetch($username, $domain)
    {
        $matches = [];
        if (preg_match('/^(\S+)[\@\%](\S+)$/', $username, $matches)) {
            $username = $matches[1];
        }
        // check for NT domains
        if (preg_match('/^\S+\\\\(\S+)$/', $username, $matches)) {
            $username = $matches[1];
        }
        switch ($this->getType()) {
            case 'at_login':
                $add = $username . "@" . $domain->getPref('name');
                break;
            case 'param_add':
                //@todo this should be taken from a ConnectorSettings object
                list($t1, $t2, $t3, $t4, $t5, $suffix) = preg_split('/:/', $domain->getPref('auth_param'));
                $add = $username . '@' . $suffix;
                break;
        }
        $this->addAddress($add, $add);
        return $this->getAddresses();
    }

    public function searchUsers($u, $d)
    {
        return [];
    }

    public function searchEmails($l, $d)
    {
        return [];
    }

    public function canModifyList()
    {
        return true;
    }
}
