<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a domain name
 */

class Validate_DomainName extends Zend_Validate_Abstract
{
    const MSG_DOMAINNAME = 'invalidDomainName';

    protected $_messageTemplates = [
        self::MSG_DOMAINNAME => "'%value%' is not a valid domain name"
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        if ($value == '') {
            return false;
        }
        if (preg_match('/^[a-z0-9\-_.]+$/', $value)) {
            return true;
        }
        $this->_error(self::MSG_DOMAINNAME);
        return false;
    }
}
