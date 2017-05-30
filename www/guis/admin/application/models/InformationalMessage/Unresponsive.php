<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Administrator
 */

class Default_Model_InformationalMessage_Unresponsive extends Default_Model_InformationalMessage
{
	protected $_title = 'Host is unresponsive or in error';
	protected $_description = "Host %s does not respond or provided an unexpected response.";
        protected $_link = array();

        protected $_hostname = '';

        public function __construct($hostname) {
           $this->_hostname = $hostname; 
        }
	
	public function check() {
	}

        public function getDescription() {
                $t = Zend_Registry::get('translate');
                return sprintf($t->_($this->_description), "<span class=\"mark\">".$this->_hostname."</span>");
        }
}
