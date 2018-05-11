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
    	Zend_Registry::set('gui', 'users');
    	# Zend_Registry::set('default_language', 'en');
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
        require_once 'user/User.php';
       
        if (!isset($_SESSION['user'])) {
            /*
            $location = 'login.php';
            if (isset($_REQUEST['d']) && preg_match('/^[0-9a-f]{32}(?:[0-9a-f]{8})?$/i', $_REQUEST['d'])) {
                $location .= "?d=".$_REQUEST['d'];
            }
            if (isset($_REQUEST['p'])) {
                $location .= '&p='.$_REQUEST['p'];
            }
            header("Location: ".$location);
            exit;
            */
            // die('Authentication required');
        } else {
            /*
            $session = unserialize($_SESSION['user']);
            Zend_Registry::set('user', $session);
    		Zend_Registry::set('identity', $session->getID());
    		*/
        }
    }

    protected function _initLayout()
    {        
    	Zend_Layout::startMvc();
    	$layout = Zend_Layout::getMvcInstance();
    	$layout->setLayoutPath(APPLICATION_PATH . '/layouts/scripts/');
    	$layout->setLayout('layout');
        
    	$view = $layout->getView();
    	
        $view->doctype('XHTML11');
    	
        # $view->doctype('HTML5');

        $view->headTitle('MailCleaner');

        return $layout;
    }
    
    protected function _initView() 
    {
    	$view = new Zend_View();
    	return $view;
    }
   
    
    protected function _initLanguage()
    {
        $lang = 'en';
       
    	// set users language
    	if (!empty($_GET['lang'])) {
            if (in_array($_GET['lang'], array('en', 'fr', 'de', 'es', 'it'))) {
                $lang = $_GET['lang'];
            } else {
                $lang = 'en';
            }
        }
                
        $translate = new Zend_Translate('array', APPLICATION_PATH . '/languages/' . $lang. '/i18n.php', $lang);
        Zend_Registry::set('translate', $translate);
        Zend_Registry::set('Zend_Translate', $translate);
        Zend_Validate_Abstract::setDefaultTranslator($translate);
        
        $this->bootstrap('layout');
        $layout=$this->getResource('layout');
        $view=$layout->getView();
        $view->tr = $translate;
        
        // init locale
        $locale = new Zend_Locale();
        $locale->setLocale('en_US');
        Zend_Registry::set('locale', $locale);
        Zend_Registry::set('Zend_Locale', $locale);
    }
  
}

