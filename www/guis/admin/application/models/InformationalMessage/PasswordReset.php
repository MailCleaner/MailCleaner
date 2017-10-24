<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka
 * @copyright 2017, Mentor Reka
 *
 * Inform when the default password isn't changed
 */

class Default_Model_InformationalMessage_PasswordReset extends Default_Model_InformationalMessage
{
	protected $_title = 'System is not safe';
	protected $_description = null;
	protected $_link = array(); // not used, because custom link
	public function check() {
		require_once('MailCleaner/Config.php');
    		$config = new MailCleaner_Config();
    		$mcPwd = $config->getOption('MYMAILCLEANERPWD');
		if (isset($mcPwd) && md5($mcPwd) == "cbf0466a9c823ad153ce349411e32407") {
			// We're building custom link when configurator is enabled
			// Check in DB if use_ssl is true and configurator enabled
			$url=".";
			require_once('system/SystemConfig.php');
			$sysconf_ = SystemConfig :: getInstance();
			require_once ('helpers/DataManager.php');
			$db_masterconf = DM_MasterConfig :: getInstance();
			$configurator_enabled=$db_masterconf->getHash("select * from external_access where service='configurator' AND protocol='TCP' AND port='4242'");
			if ( isset($configurator_enabled['id']) && !empty($configurator_enabled['id'])) {
				$res=$db_masterconf->getHash("select use_ssl from httpd_config;");
				$protocol=$res['use_ssl']=="true" ? 'https://' : 'http://';
				$url=" (<a href='".$protocol.$_SERVER['SERVER_NAME'].":4242'>Click here to access the wizard</a>).";
			}
			$this->_description="you are using the default MailCleaner password".$url;
    	    		$this->_toshow = true;
		}
	}
}
