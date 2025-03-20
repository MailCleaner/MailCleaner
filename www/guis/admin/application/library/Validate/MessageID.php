<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of email addresses
 */

class Validate_MessageID extends Zend_Validate_Abstract
{
    const MSG_MESSAGEID = 'invalidMessageID';

    protected $_messageTemplates = [
        self::MSG_MESSAGEID => "'%value%' is not a valid message ID"
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        if (preg_match('/^[0-9A-Z]{6}-[0-9A-Z]{6,11}-[0-9A-Z]{2,4}$/i', $value)) {
            return true;
        }
    }
}
