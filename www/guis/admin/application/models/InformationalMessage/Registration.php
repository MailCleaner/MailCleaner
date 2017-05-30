<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Administrator
 */

class Default_Model_InformationalMessage_Registration extends Default_Model_InformationalMessage
{
	protected $_title = 'System is unregistered';
	protected $_description = 'unregistered system has low efficiency';
	protected $_link = array('controller' => 'baseconfiguration', 'action' => 'registration');
	public function check() {
		require_once('MailCleaner/Config.php');
    		$config = new MailCleaner_Config();
    		$registered = $config->getOption('REGISTERED');
		if (!isset($registered) && $registered != "1" && $registered != "2")
    	    		$this->_toshow = true;
	}
}
