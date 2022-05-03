<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * admin application bootstrap
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initAutoload()
	{
		$autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Api_',
            'basePath'  => dirname(__FILE__),
		));

		$defaultLoader = new Zend_Application_Module_Autoloader(array(
              'basePath'  => APPLICATION_PATH . '/../../guis/admin/application/',
              'namespace' => 'Default_',
		));
		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->pushAutoloader($defaultLoader);

		return $autoloader;
	}


	protected function _initRegistry()
	{
		$controller = Zend_Controller_Front::getInstance();
		$controller->addModuleDirectory(APPLICATION_PATH . '/../application/');

		require_once('Api/Responder.php');
		$responder = new Api_Responder();
		Zend_Registry::set('response', $responder);
		Zend_Registry::set('soap', false);
	}

	protected function _initDatabases()
	{
		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();
			
		$writeConfigDb = new Zend_Db_Adapter_Pdo_Mysql(array(
    	                      'host'        => 'localhost',
                              'unix_socket' => $mcconfig->getOption('VARDIR')."/run/mysql_master/mysqld.sock",
                              'username'    => 'mailcleaner',
                              'password'    => $mcconfig->getOption('MYMAILCLEANERPWD'),
                              'dbname'      => 'mc_config'
                              ));
                               
                              Zend_Registry::set('writedb', $writeConfigDb);
	}

	protected function _initAuth()
	{
                $config = MailCleaner_Config::getInstance();
                if ($config->getOption('ISMASTER') != 'Y' ) {
                   Zend_Registry::get('response')->setResponse(404, 'API is only available on master host');
                }
                
		function netMatch ($CIDR,$IP) {
			if (!preg_match('/\//', $CIDR)) {
				return $CIDR == $IP;
			} else {
				list ($net, $mask) = explode ('/', $CIDR);
				return ( ip2long ($IP) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($net);
			}
		}

		$sysconf = new Default_Model_SystemConf();
		$sysconf->load();

		foreach (preg_split('/[\s,:]/', $sysconf->getParam('api_fulladmin_ips')) as $allowed) {
			if (netMatch($allowed, $_SERVER['REMOTE_ADDR']) || netMatch('127.0.0.1', $_SERVER['REMOTE_ADDR'])) {
				$user = new Default_Model_Administrator();
				$user->find('admin');
				Zend_Registry::set('user', $user);
				break;
			}
		}
		foreach (preg_split('/[\s,:]/', $sysconf->getParam('api_admin_ips')) as $allowed) {
			if (netMatch($allowed, $_SERVER['REMOTE_ADDR'])) {
				$authentified = false;
				if ( (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) ||
                     (isset($_REQUEST['username']) && isset($_REQUEST['password']))
                    ) {
                    $user = new Default_Model_Administrator();
                    $username = '';
                    $password = '';
                    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                        $username = $_SERVER['PHP_AUTH_USER'];
                        $password = $_SERVER['PHP_AUTH_PW'];
                    } elseif (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
                        $username = $_REQUEST['username'];
                        $password = $_REQUEST['password'];
                    }
                    if ($user->checkAPIAuthentication($username, $password)) {
                    	$user->find($username);
                        Zend_Registry::set('user', $user);
                        $authentified = true;
                    } else {
                    	Zend_Registry::get('response')->setResponse(401, 'authentication failed');
                    }
                }
				### if we need to have interractive authentication (which is probably not the case for an API)
				#if (!$authentified) {
				#	header('WWW-Authenticate: Basic realm="MailCleaner API"');
				#	header('HTTP/1.0 401 Unauthorized');
				#	die();
				#}
				break;
			}
		}
	}

	protected function _initView() {
		$view = new Zend_View();

		$controller = Zend_Controller_Front::getInstance();
		require_once('Plugin/XMLResponder.php');
		$controller->registerPlugin(new Plugin_XMLResponder());

		return $view;
	}

	protected function _initLayout()
	{
		Zend_Layout::startMvc();
		$layout = Zend_Layout::getMvcInstance();

		$view=$layout->getView();
		$view->doctype('XHTML11');
		$view->headTitle('MailCleaner API');
		$view->headTitle()->setSeparator(' - ');
			
		return $layout;
	}

}

