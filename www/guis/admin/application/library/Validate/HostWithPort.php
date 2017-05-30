<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of hosts
 */

class Validate_HostWithPort extends Zend_Validate_Abstract
{
    const MSG_BADHOST = 'invalidHost';
    const MSG_BADPORT = 'invalidPort';

    protected $_messageTemplates = array(
        self::MSG_BADHOST => "'%host%' is not a valid host",
        self::MSG_BADPORT => "'%host%' is not a valid port number",
    );
    
    public $host;
    
    protected $_messageVariables = array(
        'host' => 'host'
    );

    public function isValid($value)
    {
        $this->_setValue($value);
        
        $validator = new Zend_Validate_Hostname( 
                                    Zend_Validate_Hostname::ALLOW_DNS | 
                                    Zend_Validate_Hostname::ALLOW_IP |
                                    Zend_Validate_Hostname::ALLOW_LOCAL);

        
        if (preg_match('/^([^:]+)\:(.+)$/', $value, $matches)) {
        	if ($validator->isValid($matches[1])) {
        		if (is_numeric($matches[2])) {
            	    return true;	
        		} else {
        			$this->host = $matches[2];
        			$this->_error(self::MSG_BADPORT);
        			return false;
        		}
        	}
        	$this->host = $matches[1];
        	$this->_error(self::MSG_BADHOST);
        }
        $this->host = $value;
        $this->_error(self::MSG_BADHOST);
        return false;
    }
}