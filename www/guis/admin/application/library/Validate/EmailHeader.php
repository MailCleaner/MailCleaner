<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of email addresses
 */

class Validate_EmailHeader extends Zend_Validate_Abstract
{
    const MSG_EMAILHEADER = 'invalidEmailheader';
    const MSG_BADEMAIL = 'invalidEMail';

    public $email = '';

    protected $_messageTemplates = [
        self::MSG_EMAILHEADER => "'%value%' is not a valid email header",
        self::MSG_BADEMAIL => "'%email%' is not a valid email address"
    ];

    protected $_messageVariables = [
        'email' => 'email'
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        $emailvalidator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL);

        if (preg_match('/^\s*<?(\S+\@[^>]+)>?\s*$/', $value, $matches)) {
            if ($emailvalidator->isValid($matches[1])) {
                return true;
            }
            $this->email = $matches[1];
            $this->_error(self::MSG_BADEMAIL);
            return false;
        }

        if (preg_match('/^.* <(\S+\@[^>]+)>\s*$/', $value, $matches)) {
            if ($emailvalidator->isValid($matches[1])) {
                return true;
            }
            $this->email = $matches[1];
            $this->_error(self::MSG_BADEMAIL);
            return false;
        }
        $this->_error(self::MSG_EMAILHEADER);
        return false;
    }
}
