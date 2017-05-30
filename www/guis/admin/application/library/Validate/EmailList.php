<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of email addresses
 */

class Validate_EmailList extends Zend_Validate_Abstract
{
    const MSG_EMAILLIST = 'invalidEmaillist';
    const MSG_BADEMAIL = 'invalidEMail';

    public $email = '';
    
    protected $_messageTemplates = array(
        self::MSG_EMAILLIST => "'%value%' is not a valid email address list",
        self::MSG_BADEMAIL => "'%mail%' is not a valid email address"
    );
    
    protected $_messageVariables = array(
        'mail' => 'email'
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        $validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL);

        $addresses = preg_split('/[,:\s]+/', $value);
        foreach ($addresses as $address) {
          if (! $validator->isValid($address)) {
          	  $this->email = $address;
          	  $this->_error(self::MSG_BADEMAIL);
              return false;
          }
        }
        return true;
    }
}