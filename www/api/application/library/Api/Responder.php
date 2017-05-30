<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * index page controller
 */

class Api_Responder
{
	protected $_response = array(
	                     'status_code' => 0,
	                     'status_name' => 'undefined',
	                     'status_message' => ''
	);

	public function setResponse($code, $message, $data = NULL) {
		if ($this->_response['status_code'] > 0) {
			return true;
		}
 		$this->_response['status_code'] = $code;
		if ($code >= 200) {
			$this->_response['status_name'] = 'success';
		}
		if ($code >= 300) {
			$this->_response['status_name'] = 'unknown';
		}
		if ($code >= 400) {
			$this->_response['status_name'] = 'client-error';
		}
		if ($code >= 500) {
			$this->_response['status_name'] = 'server-error';
		}
		$this->_response['status_message'] = $message;
		if (isset($data) && is_array($data)) {
			$this->_response['data'] = $data;
		}
		return true;
	}
	
	public function hasResponse() {
		if ($this->_response['status_code'] > 0 ) {
			return true;
		}
		return false;
	}
	
	public function getResponse() {
		return $this->_response;
	}
	
    public function getXMLResponse() {
        $xml = new DOMDocument('1.0', 'utf-8');
        
        $response_el = $xml->appendChild(new domelement('response'));
        $response_el->setAttribute('code', $this->_response['status_code']);
        $response_el->setAttribute('name', $this->_response['status_name']);
        $response_el->appendChild($xml->createElement('message', $this->_response['status_message']));
        if (isset($this->_response['data']) && is_array($this->_response['data'])) {
        	$data_el = $xml->createElement('data');
        	while (1) {
           		if (is_numeric(key($this->_response['data']))) {
            		$data_el->appendChild($xml->createElement('element', current($this->_response['data'])));
        		} else if (is_string(current($this->_response['data'])) || is_numeric(current($this->_response['data']))) {
        			$data_el->appendChild($xml->createElement(key($this->_response['data']), current($this->_response['data'])));
        		} else if (is_array(current($this->_response['data']))) {
        			$arra_el = $xml->createElement(key($this->_response['data']));
        			foreach (current($this->_response['data']) as $element) {
        				if (key($element)) {
        					$arra_el->appendChild($xml->createElement(key($element), $element));
        				} else {
            				$arra_el->appendChild($xml->createElement('element', $element));
        				}
        			}
        			$data_el->appendChild($arra_el);
        		} else {
        			break;
        		}
        		next($this->_response['data']);
        	}
        	$response_el->appendChild($data_el);
        }
        return $xml->saveXML();
    }

}