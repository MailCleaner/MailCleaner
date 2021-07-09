<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Validate a list of hosts
 */

class Validate_HostList extends Zend_Validate_Abstract
{
    const MSG_HOSTLIST = 'invalidHostlist';
    const MSG_BADHOST = 'invalidHost';

    protected $_messageTemplates = array(
        self::MSG_HOSTLIST => "'%value%' is not a valid host list",
        self::MSG_BADHOST => "'%host%' is not a valid host"
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
    
        if ($value == '*') {
          	return true;
        }
        $hosts = preg_split('/[,\s]+/', $value);
        foreach ($hosts as $host) {
          if (preg_match('/^#/', $host)) {
              continue;
          }
          $host = preg_replace('/^\!?/', '', $host);
          $host = preg_replace('/\/([0-9]?[0-9]|1([01][0-9]|2[0-8]))$/', '', $host);
          $host = preg_replace('/(.*)\/(a|A|aaaa|AAAA|mx|MX)$/', '\1', $host);
          $host = preg_replace('/(_)*(.*)\/(spf|spf)$/', '\2', $host);
          if (! $validator->isValid($host)) {
          	  $this->host = $host;
          	  $this->_error(self::MSG_BADHOST);
              return false;
          }
        }
        return true;
    }
}
