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
            'namespace' => 'Default_',
            'basePath'  => dirname(__FILE__),
        ));
        return $autoloader;
    }
    
    protected function _initRegistry()
    {
    	Zend_Registry::set('gui', 'admin');
    	Zend_Registry::set('default_language', 'en');
    	Zend_Registry::set('default_template', 'default');
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
        
        $spoolDb = new Zend_Db_Adapter_Pdo_Mysql(array(
    	                      'host'        => 'localhost',
                              'unix_socket' => $mcconfig->getOption('VARDIR')."/run/mysql_master/mysqld.sock",
                              'username'    => 'mailcleaner',
                              'password'    => $mcconfig->getOption('MYMAILCLEANERPWD'),
                              'dbname'      => 'mc_spool'
                             ));
                             
        Zend_Registry::set('spooldb', $spoolDb);
    }
    
    protected function _initAuth()
    {	
    	$controller = Zend_Controller_Front::getInstance();
    	require_once('Plugin/AdminAclManager.php');
    	$auth = Zend_Auth::getInstance();
    	$auth->setStorage(new Zend_Auth_Storage_Session('MailCleanerAdmin'));
    	$controller->registerPlugin(new Plugin_AdminAclManager($auth));
    	if ($auth->hasIdentity()) {
    		$identity = $auth->getIdentity();
    		Zend_Registry::set('identity', $identity);
    		$user = new Default_Model_Administrator;
    		$user->find($identity);
    		Zend_Registry::set('user', $user);
    		$restrictions = new Default_Model_FeatureRestriction();
    		$restrictions->load();
    		Zend_Registry::set('restrictions', $restrictions);
    		return;
    	}
    		Zend_Registry::set('identity', '');
    }

    protected function _initLayout()
    {
    	// set users template
    	$template = Zend_Registry::get('default_template');
    	
    	Zend_Layout::startMvc();
    	$layout = Zend_Layout::getMvcInstance();
    	$layout->setLayoutPath(APPLICATION_PATH . '/../public/templates/'.$template.'/scripts/layouts/');
    	$layout->setLayout('main');
    	
    	$view=$layout->getView();
    	$view->doctype('XHTML11');
    	#$view->doctype('HTML5');
    	$view->headTitle('MailCleaner');
    	$view->headTitle()->setSeparator(' - ');
        $view->setScriptPath(APPLICATION_PATH . '/../public/templates/'.$template.'/scripts/');
        $view->addHelperPath(APPLICATION_PATH . '/library/Helper','MailCleaner_View_Helper');
        Zend_Registry::set('basic_script_path', APPLICATION_PATH . '/../public/templates/'.$template.'/scripts/');
        Zend_Registry::set('ajax_script_path', APPLICATION_PATH . '/../public/templates/'.$template.'/scripts/ajax');
        
        $controller = Zend_Controller_Front::getInstance();
        require_once('Plugin/TemplatePath.php');
        $controller->registerPlugin(new Plugin_TemplatePath());
        
        $view->loggedusername = Zend_Registry::get('identity');

        $sysconf = MailCleaner_Config::getInstance();

        $view->is_slave = 1;
        if ($sysconf->getOption('ISMASTER') == 'Y') {
             $view->is_slave = 0;
        }
        
    	return $layout;
    }
    
    protected function _initView() {
    	$view = new Zend_View();
    	return $view;
    }
    
    protected function _initNavigation() {
    	$controller = Zend_Controller_Front::getInstance();
        require_once('Plugin/Navigation.php');
        $controller->registerPlugin(new Plugin_Navigation());
    }
    
    protected function _initLanguage()
    {
    	// set users language
    	$default_language = Zend_Registry::get('default_language');
    	
        $translate = new Zend_Translate('array', APPLICATION_PATH . '/../languages/' . $default_language . '/legends.php', $default_language );
        $translate->addTranslation(APPLICATION_PATH . '/../languages/' . $default_language . '/docs.php', 'en');
        Zend_Registry::set('translate', $translate);
        Zend_Registry::set('Zend_Translate', $translate);
        Zend_Validate_Abstract::setDefaultTranslator($translate);
        
        $this->bootstrap('layout');
        $layout=$this->getResource('layout');
        $view=$layout->getView();
        $view->t = $translate;
        
        // init locale
        $locale = new Zend_Locale();
        $locale->setLocale('en_US');
        Zend_Registry::set('locale', $locale);
        Zend_Registry::set('Zend_Locale', $locale);
        
        $mclocale = new Default_Model_Localization();
        $mclocale->load();
        putenv("TZ=".$mclocale->getFullZone());
        date_default_timezone_set($mclocale->getFullZone());
    }
  
}

