<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of email addresses
 */

class Validate_AdminName extends Zend_Validate_Abstract
{
    const MSG_ADMINNAME = 'invalidAdminName';
    
    protected $_messageTemplates = array(
        self::MSG_ADMINNAME => "'%value%' is not a valid user name"
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        if (preg_match('/[^-_@%&.+a-zA-Z0-9]/', $value)) {
        	$this->_error(self::MSG_ADMINNAME);
        	return false;
        }
        return true;
    }
}