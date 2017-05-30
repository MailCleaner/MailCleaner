<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of IP addresses
 */

class Validate_IpList extends Zend_Validate_Abstract
{
    const MSG_IPLIST = 'invalidIplist';
    const MSG_BADIP = 'invalidIp';

    protected $_messageTemplates = array(
        self::MSG_IPLIST => "'%value%' is not a valid IP address list",
        self::MSG_BADIP => "'%ip%' is not a valid IP address"
    );
    
    public $ip = '';
    
    protected $_messageVariables = array(
        'ip' => 'ip'
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        $validator = new Zend_Validate_Ip();

        if ($value == '*') {
        	return true;
        }
        $addresses = preg_split('/[,\s]+/', $value);
        foreach ($addresses as $address) {
          $address = preg_replace('/\/\d+$/', '', $address);
          if (! $validator->isValid($address)) {
          	  $this->ip = $address;
          	  $this->_error(self::MSG_BADIP);
              return false;
          }
        }
        return true;
    }
}
