<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * MailCleaner configuration fetcher
 */

class MailCleaner_Config
{
	private static $instance;
	private $_configFile = '/etc/mailcleaner.conf';
	
	private $_options = array();

	public static function getInstance() {
		if (empty (self :: $instance)) {
			self :: $instance = new MailCleaner_Config();
		}
		return self :: $instance;
	}

	public function __construct() {
	    $this->getFileConfig();
	}

	private function getFileConfig() {
		$val = array ();
		$ret = array ();

		$lines = file($this->_configFile);
		if (!$lines) { return; }

		foreach ($lines as $line_num => $line) {
			if (preg_match('/^([A-Z0-9]+)\s*=\s*(.*)/', $line, $val)) {
				$this->_options[$val[1]] = $val[2];
			}
		}
	}
	
	public function getOption($option) {
	    if (isset($this->_options[$option])) {
	        return  $this->_options[$option];
	    }
	    return null;
	}
	 
	public function getUserGUIAvailableLanguages() {
		return array('en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish');
	}
}
