<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of email addresses
 */

class Validate_MessageID extends Zend_Validate_Abstract
{
    const MSG_MESSAGEID = 'invalidMessageID';

    protected $_messageTemplates = array(
        self::MSG_MESSAGEID => "'%value%' is not a valid message ID"
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        if (preg_match('/^[0-9A-Z]{6}-[0-9A-Z]{6}-[0-9A-Z]{2}$/i', $value)) {
        	return true;
        }
    }
}