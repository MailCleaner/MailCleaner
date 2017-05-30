<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Local host system
 */

class Default_Model_Localhost
{

    static public function sendSoapRequest($service, $params = NULL) {
    	$url = 'http://';
        $url .= "localhost:5132/soap/index.php?wsdl";
        
    	$client = new Zend_Soap_Client($url);
        try {
            ini_set('default_socket_timeout', 5);
            if (is_array($params) && isset($params['timeout'])) {
                  ini_set('default_socket_timeout', $params['timeout']);
                  unset($params['timeout']);
            }
            if (preg_match('/^Services_(restart|stop)/', $service)) {
            	ini_set('default_socket_timeout', 30);
            }
            if ($params && count($params)) {
              $result = $client->$service($params);
            } else {
              $result = $client->$service();
            }
            return $result;
        } catch (Exception $e) {
            return "NOK cannot fetch web service, maybe service timeout<br />(".$e->getMessage().")";
        }

    }
}
