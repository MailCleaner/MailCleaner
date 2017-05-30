<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate an e-mail address as it may appears in mail headers
 */

class Validate_EmailAddressField extends Zend_Validate_Abstract
{
    const MSG_BADTEXTPART = 'invalidTextpart';
    const MSG_BADADDRESS = 'invalidAddress';
    
    public $host;

    protected $_messageVariables = array(
        'text' => 'text',
        'address' => 'address'
    );
    
    protected $_messageTemplates = array(
        self::MSG_BADTEXTPART => "'%text%' is not a valid informational text",
        self::MSG_BADADDRESS => "'%address%' is not a valid email address"
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        $validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL);

        if (preg_match('/^\s*\"?([a-zA-Z0-9\_\-\+\.\,\ ]+)\"?\s+\<(\S+)\>/', $value, $matches)) {
            $text = $matches[1];
            $address = $matches[2];
        } else {
            $address = $value;
        }

        if (!$validator->isValid($address)) {
            $this->address = htmlentities($address);
            $this->_error(self::MSG_BADADDRESS);
            return false;
        }
        return true;
    }
}
