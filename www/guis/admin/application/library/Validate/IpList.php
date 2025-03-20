<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of IP addresses
 */

class Validate_IpList extends Zend_Validate_Abstract
{
    const MSG_IPLIST = 'invalidIplist';
    const MSG_BADIP = 'invalidIp';

    protected $_messageTemplates = [
        self::MSG_IPLIST => "'%value%' is not a valid IP address list",
        self::MSG_BADIP => "'%ip%' is not a valid IP address"
    ];

    public $ip = '';

    protected $_messageVariables = [
        'ip' => 'ip'
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        $validator = new Zend_Validate_Ip();

        if ($value == '*') {
            return true;
        }
        $lines = preg_split('/\n+/', $value);
        $value = '';
        foreach ($lines as $line) {
            if (preg_match('/^#/', $line)) {
                continue;
            } else {
                $value .= $line . ',';
            }
        }
        $addresses = preg_split('/[,\s]+/', $value);
        foreach ($addresses as $address) {
            if (preg_match('/^\s*$/', $address)) {
                continue;
            }
            $address = preg_replace('/^\!?/', '', $address);
            $address = preg_replace('/\/([0-9]?[0-9]|1([01][0-9]|2[0-8]))$/', '', $address);
            if (preg_match('/\/(a|A|aaaa|AAAA|mx|MX|spf|SPF)$/', $address)) {
                continue;
            } elseif (!$validator->isValid($address)) {
                $this->ip = $address;
                $this->_error(self::MSG_BADIP);
                return false;
            }
        }
        return true;
    }
}
