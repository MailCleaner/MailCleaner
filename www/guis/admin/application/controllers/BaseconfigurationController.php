<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2017 Mentor Reka <reka.mentor@gmail.com>
 *
 * controller for base configuration
 */

class BaseconfigurationController extends Zend_Controller_Action
{
	var $config_menu;
	
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
    	$view->selectedMenu = 'Configuration';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_BaseSystem')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'BaseSystem';
    	$view->selectedSubMenuLabel = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_BaseSystem')->label;
    	
        $this->config_menu = new Zend_Navigation();

//	var_dump($this->view->serverUrl().$this->view->baseUrl()); die();

	$conf = MailCleaner_Config::getInstance();
        if (file_exists($conf->getOption('VARDIR').'/run/configurator/dis_conf_interface.enable')) {
	    $wizard = Zend_Navigation_Page::factory(array(
    		'label' => 'Wizard',
	        'uri'   => $this->view->serverUrl().':4242/',
	    ));
    	    $this->config_menu->addPage($wizard);
        }
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Network settings', 'id' => 'networksettings', 'action' => 'networksettings', 'controller' => 'baseconfiguration')));
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'DNS settings', 'id' => 'dnssettings', 'action' => 'dnssettings', 'controller' => 'baseconfiguration')));
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Localization', 'id' => 'localization', 'action' => 'localization', 'controller' => 'baseconfiguration')));
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Date and time', 'id' => 'dateandtime', 'action' => 'dateandtime', 'controller' => 'baseconfiguration')));
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Proxies', 'id' => 'proxies', 'action' => 'proxies', 'controller' => 'baseconfiguration')));
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Registration', 'id' => 'registration', 'action' => 'registration', 'controller' => 'baseconfiguration')));
        $view->config_menu = $this->config_menu;
        
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
            
    }
    
    public function indexAction() {
  	
    }
    
    public function networksettingsAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'networksettings')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'networksettings')->label;
    	$request = $this->getRequest();
    	
        if ($request->isXmlHttpRequest()) {
        	$layout->disableLayout();
        	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	}
    	    	
    	$interface = new Default_Model_NetworkInterface();
    	if ($request->getParam('interface')) {
    	    $interface->find($request->getParam('interface'));
    	}
    	$interfaces = $interface->fetchAll();
      
        /* Interface form */
        $form    = new Default_Form_NetworkInterface($interfaces, $interface);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('networksettings', 'baseconfiguration'));
        $message = '';
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($request->getPost())) {
            	try {
			// Enable/disable the configurator
       	                require_once('MailCleaner/Config.php');
                        $config = new MailCleaner_Config();
			$enable_configurator=$this->getRequest()->getParam('enable_configurator');
			$disable_configurator=$enable_configurator == "true" ? "false" : "true";
			$tmp_file=$config->getOption('VARDIR')."/run/configurator/dis_conf_interface.enable";
			if ($enable_configurator == "true" && !file_exists($tmp_file)) { // configurator interface enabled
				touch($tmp_file);
			} else if ($enable_configurator == "false" && file_exists($tmp_file)) {
				unlink($tmp_file);
			}
			$cmd="sudo ".$config->getOption('SRCDIR')."/bin/dis_config_interface.sh ".$disable_configurator;
                        $res=`$cmd`;
                	$form->setParams($this->getRequest(), $interface);
             	    	$message = $interface->save();
             	    	$interfaces = $interface->fetchAll();
         	        $interface->find($interface->getName());
            	} catch (Exception $e) {
            		$message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
               if (count($form->getMessages() > 0)) {
        	     $this->view->errors = $form->getMessages();
         	     $this->view->error = array_pop($form->getMessages());
         	     $message = 'NOK datanotvalid';
                }
            }
         	$form->getElement('selectinterface')->setValue($interface->getName());
        }
        $view->form = $form;
        
        $flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
        if (isset($flashmessages[0])) {
        	$message = $flashmessages[0];
        }
        $view->message = $message;
        
        $view->interfaces = $interfaces;
        $view->interface = $interface;
    	$this->view->error = '';
        $this->view->errors = array();
        
    }
    
    public function reloadnetworkAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'networksettings')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'networksettings')->label;
    	$request = $this->getRequest();
	
    	$form    = new Default_Form_ReloadNetwork();
    	$form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('reloadnetwork', 'baseconfiguration'));
    	if ($this->getRequest()->isPost()) {
                $restrictions = Zend_Registry::get('restrictions');
                if ($restrictions->isRestricted('NetworkInterface', 'reloadnetnow')) {
    			$this->_helper->getHelper('FlashMessenger')->addMessage('NOK could not save due to restricted feature');
    		} else {
                if ($form->isValid($request->getPost())) {
                  $soapres = Default_Model_Localhost::sendSoapRequest('Config_applyNetworkSettings', array('timeout' => 20));
                  $this->_helper->getHelper('FlashMessenger')->addMessage($soapres);
                  $this->_redirect('/baseconfiguration/networksettings');
                }
            }
    	}
    	$view->form = $form;
    }

    public function dnssettingsAction() {
    	$this->config_menu->findOneBy('id', 'dnssettings')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'dnssettings')->label;
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$message = '';
    	/* Dns form */
    	$dnsconfig = new Default_Model_DnsSettings();
    	$dnsconfig->load();
        $dnsform = new Default_Form_NetworkDns($dnsconfig);
        $dnsform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('dnssettings', 'baseconfiguration'));
    	if ($this->getRequest()->isPost()) {
            $restrictions = Zend_Registry::get('restrictions');
            if ($restrictions->isRestricted('NetworkDns', 'dnssubmit')) {
                        $this->_helper->getHelper('FlashMessenger')->addMessage('NOK could not save due to restricted feature');
            } else {
                if ($dnsform->isValid($this->getRequest()->getPost())) {
                    $dnsconfig->setDomainSearch($this->getRequest()->getParam('domainsearch'));
                    $dnsconfig->clearNameServers();
         	    $dnsconfig->addNameServer($this->getRequest()->getParam('primarydns'));
         	    $dnsconfig->addNameServer($this->getRequest()->getParam('secondarydns'));
         	    $dnsconfig->addNameServer($this->getRequest()->getParam('tertiarydns'));
         	    $oldhelo = $dnsconfig->getHeloName();
         	    $dnsconfig->setHeloName($this->getRequest()->getParam('heloname'));
         	    if ($dnsconfig->getHeloName() != $oldhelo) {
         	    	Default_Model_Localhost::sendSoapRequest('Service_setServiceToRestart', array('exim_stage1', 'exim_stage4'));
         	    }
         	    $message = $dnsconfig->save();
         	    } else {
                    $message = "NOK bad DNS settings";
                }
            }
    	}
        $view->form = $dnsform;
        $view->message = $message;
    }
    
    public function localizationAction() {
    	$this->config_menu->findOneBy('id', 'localization')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'localization')->label;
    	
        if ($this->getRequest()->isXmlHttpRequest()) {
        	$layout->disableLayout();
        	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	}
    	
    	$locale = new Default_Model_Localization();
    	if ($this->getRequest()->getParam('zone') != '') {
    		$locale->setMainZone($this->getRequest()->getParam('zone'));
    	} else {
            $locale->load();
    	}
    	$message = '';
    	$localeform = new Default_Form_Localization($locale);
        $localeform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('localization', 'baseconfiguration'));
    	$localesubmited = $this->getRequest()->getParam('localesubmit');
        if ($this->getRequest()->isPost() && $localesubmited ) {
            if ($localeform->isValid($this->getRequest()->getPost())) {
            	$locale->setMainZone($this->getRequest()->getParam('zone'));
            	$locale->setSubZone($this->getRequest()->getParam('selectsubzone'));
            	$message = $locale->save();
            	if (preg_match('/^OK/', $message)) {
            		putenv("TZ=".$locale->getFullZone());
            	}
            } else {
            	$message = "NOK bad settings";
            }
            
    	}
    	$view->form = $localeform;
    	
    	$this->_helper->getHelper('FlashMessenger')->addMessage($message);
    	if ($message == ''	) {
    		$message = array_pop($flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages());
    		$message = preg_replace('/\n/', '', $message);
    	}
    
    	$view->message = $message;
    	
    	$currenttime = new Zend_Date();
    	$view->currentlocaltime = $currenttime;
    }
    
    public function dateandtimeAction() {
    	$this->config_menu->findOneBy('id', 'dateandtime')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'dateandtime')->label;
    	$view->headScript()->appendFile($view->scripts_path.'/dateandtime.js', 'text/javascript');
    	
    	$message = '';
    	
    	$ntp = new Default_Model_NTPSettings();
        $ntp->load();
        
        $locale = new Default_Model_Localization();
        $locale->load();
        $view->locale = $locale;
        
        $form = new Default_Form_DateAndTime($ntp);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('dateandtime', 'baseconfiguration'));
        $datetimesubmited = $this->getRequest()->getParam('datetimesubmit');
        if ($this->getRequest()->isPost() && $datetimesubmited) {
        	if ($form->isValid($this->getRequest()->getPost())) {
        	    // date and time setting
        	    $date = new Zend_Date($this->getRequest()->getParam('date'));
        	    $date->set($this->getRequest()->getParam('hour'), Zend_Date::HOUR);
                $date->set($this->getRequest()->getParam('minute'), Zend_Date::MINUTE);
                $date->set($this->getRequest()->getParam('second'), Zend_Date::SECOND);
                
                $message = Default_Model_Localhost::sendSoapRequest('Config_saveDateTime', $date->toString('MMddHHmmyyyy.ss'));
        	} else {
        		$message = "NOK bad settings";
        	}
        }
        
        if ($this->getRequest()->isPost() && ($this->getRequest()->getParam('saveandsync') || $this->getRequest()->getParam('hsaveandsync') == "1")) {
            if ($form->isValid($this->getRequest()->getPost())) {
              // NTP settings
                if ($this->getRequest()->getParam('usentp')) {
                    if ($this->getRequest()->getParam('ntpserver') == '') {
                        $form->getElement('ntpserver')->addError('Some NTP server must be provided');
                        $message = "NOK bad settings";
                    } else {
                        $ntp->setServers($this->getRequest()->getParam('ntpserver'));
                        $form->updateDateTime();
                        $message = $ntp->save(true);
                    }
                } else {
                	$message = $ntp->save(false);
                }
            } else {
            	$message = "NOK bad settings";
            }
        }
	   	
    	$view->ntp = $ntp;
    	$view->form = $form;
        $view->message = $message;
        
    }
    
    public function proxiesAction() {
    	$this->config_menu->findOneBy('id', 'proxies')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'proxies')->label;
    	
    	$message = '';
    	$proxymgr = new Default_Model_ProxyManager();
    	$proxymgr->load();
    	$form = new Default_Form_Proxies($proxymgr);
    	
    	if ($this->getRequest()->isPost()) {
    		if ($form->isValid($this->getRequest()->getPost())) {
    			$proxymgr->setHttpProxy($this->getRequest()->getParam('httpproxy'));
    			$proxymgr->setSmtpProxy($this->getRequest()->getParam('smtpproxy'));
    			$message = $proxymgr->save();
    		} else {
    			$message = 'NOK bad settings';
    		}
    	}
    	
    	
    	$view->form = $form;
    	$view->message = $message;
    }

    public function registrationAction() {
    	$this->config_menu->findOneBy('id', 'registration')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'registration')->label;
    	$message = '';

    	$mgr = new Default_Model_RegistrationManager();
    	$mgr->load();

	$mgrce = new Default_Model_RegistrationCEManager();
	$mgrce->load();

	$unmgr = new Default_Model_UnRegistrationManager();
	$unmgr->load();

	$mgrhostid = new Default_Model_HostIdManager();
        $mgrhostid->load();

        $sysconf = MailCleaner_Config::getInstance();
	$registered = 0;
        if ($sysconf->getOption('REGISTERED') > 0 ) {
          $registered = $sysconf->getOption('REGISTERED');
        }
        $view->registered = $registered;

    	$form   = new Default_Form_Registration($mgr);
	$formce = new Default_Form_RegistrationCE($mgrce);
	$formun = new Default_Form_UnRegistration($unmgr, $registered);
	$formhostid = new Default_Form_ChangeHostId($mgrhostid, $registered);

	if ($this->getRequest()->isPost()) {
    		if ($this->getRequest()->getParam('changehostid') ||  $this->getRequest()->getParam('register_ce') || $this->getRequest()->getParam('register') || $this->getRequest()->getParam('unregister')) {
			if ($this->getRequest()->getParam('register_ce') && $formce->isValid($this->getRequest()->getPost())) { // We use the CE form
				$keys = array('first_name', 'last_name', 'email', 'company_name', 'address', 'postal_code', 'city', 'country', 'accept_newsletters', 'accept_releases', 'accept_send_statistics');
				foreach ($keys as $d) {
                                        $mgrce->setData($d, $this->getRequest()->getParam($d));
                                }
                                $message = $mgrce->save();
                                if (preg_match('/^OK/', $message)) {
                                        $message = 'OK System has been registered';
                                        $view->registered = 2;
                                } else {
					if (preg_match('/UNREGISTER/', $message))
	                                        $message = 'NOK System could not be registered, please UNREGISTER IT first';
					else
						$message = 'NOK System could not be registered, please check parameters and connectivity';
                                }
			} else if ($this->getRequest()->getParam('register') && $form->isValid($this->getRequest()->getPost())) {
				foreach (array('clientid', 'resellerid', 'resellerpwd') as $d) {
                           		$mgr->setData($d, $this->getRequest()->getParam($d));
                        	}
                        	$message = $mgr->save();
                        	if (preg_match('/^OK/', $message)) {
                           		$message = 'OK System has been registered';
		                        $view->registered = 1;
                	        } else {
                        		$message = 'NOK System could not be registered, please check parameters and connectivity';
	                        }
			} else if ($this->getRequest()->getParam('unregister') && $formun->isValid($this->getRequest()->getPost())) {
				$unmgr->setData('rsp', $this->getRequest()->getParam('rsp'));
				$message = $unmgr->save();
				if (preg_match('/^OK/', $message)) {
                                        $message = 'OK System has been unregistered';
                                        $view->registered = 0;
                                } else {
                                        $message = 'NOK System could not be unregistered, please check parameters and connectivity';
                                }
			} else if ($this->getRequest()->getParam('changehostid') && $formhostid->isValid($this->getRequest()->getPost())) {
				$mgrhostid->setData('host_id', $this->getRequest()->getParam('host_id'));
                                $message = $mgrhostid->save();
                                if (preg_match('/^OK/', $message)) {
                                        $message = 'OK Host Id has been changed';
                                } else {
                                        $message = "NOK Host Id can't be changed, please check parameters and system.";
                                }
			} else {
			}
    		} else {
    			$message = 'NOK bad settings';
    		}
    	}
    	
    	$view->form = $form;
	$view->formCE = $formce;
	$view->formun = $formun;
	$view->formhostid = $formhostid;

    	$view->message = $message;
    }

    public function getdateandtimeAction() {
        $layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
       
        $now = Zend_Date::now();
        
        $view->date = $now->get(Zend_Date::DATES);
        $view->hour = $now->get(Zend_Date::HOUR);
        $view->minute = $now->get(Zend_Date::MINUTE);
        $view->second = $now->get(Zend_Date::SECOND);
    }
}
