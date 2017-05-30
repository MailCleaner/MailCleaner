<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * DNS lists
 */

class Default_Model_DnsLists
{

	protected $_config_path = 'etc/rbls';
	protected $_all_lists = array();
	
	public function __construct() {
		$conf = MailCleaner_Config::getInstance();
		$this->_config_path = $conf->getOption('SRCDIR')."/etc/rbls";
	}
	
	public function load() {
		if (is_dir($this->_config_path)) {
			if ($dh = opendir($this->_config_path)) {
                while (($file = readdir($dh)) !== false) {
                	if (preg_match('/^[A-Z]+\.cf$/', $file)) {
                	    $this->addRBLConfig($file);
                	}
                }
            }
		}
	}
	
	protected function addRBLConfig($file) {
		$path = $this->_config_path."/".$file;
		if (! file_exists($path)) {
			return;
		}

		$contents = file($path);
		$fields = array();
		foreach ($contents as $line) {
			if (preg_match('/^([^=]+)\s*=\s*(.*)/', $line, $matches)) {
				$fields[$matches[1]] = $matches[2];
			}
		}
		if (!$fields['name']) {
			return;
		}
		$name = $fields['name'];
	    $this->_all_lists[$name] = $fields;
	}
	
	public function getRBLs($type) {
		$ret = array();
		$types = preg_split("/\s/", $type);
		foreach ($this->_all_lists as $listname => $list) {
			if (in_array($list['type'], $types)) {
				$ret[$listname] = $list;
			}
		}
		return $ret;
	}
	
}