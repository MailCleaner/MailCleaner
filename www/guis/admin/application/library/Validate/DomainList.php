<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate domain name list
 */

class Validate_DomainList extends Zend_Validate_Abstract
{
    const MSG_DOMAINLIST = 'invalidDomainllist';
    const MSG_BADDOMAIN = 'invalidDomain';

    protected $_messageTemplates = [
        self::MSG_DOMAINLIST => "'%value%' is not a valid domain list",
        self::MSG_BADDOMAIN => "'%dom%' is not a valid domain"
    ];

    public $domain = '';

    protected $_messageVariables = [
        'dom' => 'domain'
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        require_once('Validate/DomainName.php');
        $validator = new Validate_DomainName();

        $addresses = preg_split('/[,:\s]+/', $value);
        foreach ($addresses as $address) {
            if ($address == '*') {
                continue;
            }
            if (preg_match('/^\^/', $address)) {
                continue;
            }
            $address = preg_replace('/\.?\*/', '', $address);
            if (!$validator->isValid($address)) {
                $this->domain = $address;
                $this->_error(self::MSG_BADDOMAIN);
                return false;
            }
        }
        return true;
    }
}
