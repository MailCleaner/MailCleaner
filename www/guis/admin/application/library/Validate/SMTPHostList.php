<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of SMTP hosts
 */

class Validate_SMTPHostList extends Zend_Validate_Abstract
{
    const MSG_SMTPHOSTLIST = 'invalidHostlist';
    const MSG_SMTPBADHOST = 'invalidHost';

    public $host;

    protected $_messageVariables = [
        'host' => 'host'
    ];

    protected $_messageTemplates = [
        self::MSG_SMTPHOSTLIST => "'%value%' is not a valid host list",
        self::MSG_SMTPBADHOST => "'%host%' is not a valid host"
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        $validator = new Zend_Validate_Hostname(
            Zend_Validate_Hostname::ALLOW_DNS |
                Zend_Validate_Hostname::ALLOW_IP |
                Zend_Validate_Hostname::ALLOW_LOCAL
        );

        $hosts = preg_split('/[,\s]+/', $value);
        foreach ($hosts as $host) {
            if ($host == '+') {
                continue;
            }
            if (preg_match('/^\s*\*\s*$/', $host)) {
                continue;
            }
            if (preg_match('/^#/', $host)) {
                continue;
            }

            if (!preg_match('/^[a-f0-9:]+$/', $host)) { # avoid breaking ipv6
                $host = preg_replace('/::?\d+/', '', $host);
            }
            $host = preg_replace('/^\!?/', '', $host);
            $host = preg_replace('/\/([0-9]?[0-9]|1([01][0-9]|2[0-8]))$/', '', $host);
            $host = preg_replace('/\/(a|A|aaaa|AAAA|mx|MX)$/', '', $host);
            $host = preg_replace('/(_)*(.*)\/(spf|SPF)$/', '\2', $host);
            $host = preg_replace('/^\./', '', $host);
            if (!$validator->isValid($host)) {
                $this->host = $host;
                $this->_error(self::MSG_SMTPBADHOST);
                return false;
            }
        }
        return true;
    }
}
