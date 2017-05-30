<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for smtp settings
 */

class SmtpController extends Zend_Controller_Action
{
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');
        $view->headScript()->appendFile($view->scripts_path.'/pki.js', 'text/javascript');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
    	$view->selectedMenu = 'Configuration';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_SMTP')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'SMTP';
    	
    	$this->config_menu = new Zend_Navigation();
    	
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'SMTP checks', 'id' => 'smtpchecks', 'action' => 'smtpchecks', 'controller' => 'smtp')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Connection control', 'id' => 'connectioncontrol', 'action' => 'connectioncontrol', 'controller' => 'smtp')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Resources control', 'id' => 'resourcescontrol', 'action' => 'resourcescontrol', 'controller' => 'smtp')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'TLS/SSL', 'id' => 'tls', 'action' => 'tls', 'controller' => 'smtp')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Greylisting', 'id' => 'greylisting', 'action' => 'greylisting', 'controller' => 'smtp')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'DKIM', 'id' => 'dkim', 'action' => 'dkim', 'controller' => 'smtp')));
        $view->config_menu = $this->config_menu;
        
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/tooltip.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/smtpconfig.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    	
    }
    
    public function indexAction() {
  	
    }
    
    public function smtpchecksAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'smtpchecks')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'smtpchecks')->label;
    	$request = $this->getRequest();
    	
    	$mta = new Default_Model_MtaConfig();
    	$mta->find(1);
    	
    	$form    = new Default_Form_SmtpChecks($mta);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('smtpchecks', 'smtp'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	$form->setParams($this->getRequest(), $mta);
            	try {
            	  $mta->save();
            	  $message = 'OK data saved';
            	  $slaves = new Default_Model_Slave();
            	  $slaves->sendSoapToAll('Service_setServiceToRestart', array('exim_stage1'));
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;    	
    }
    
    public function connectioncontrolAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'connectioncontrol')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'connectioncontrol')->label;
    	$request = $this->getRequest();
    	
    	$mta = new Default_Model_MtaConfig();
    	$mta->find(1);
    	
    	$form    = new Default_Form_SmtpConnectionControl($mta);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('connectioncontrol', 'smtp'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	$form->setParams($this->getRequest(), $mta);
            	
            	try {
            	  $mta->save();
            	  $message = 'OK data saved';
            	  $slaves = new Default_Model_Slave();
            	  $slaves->sendSoapToAll('Service_setServiceToRestart', array('exim_stage1'));
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function tlsAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'tls')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'tls')->label;
    	$request = $this->getRequest();
    	
     	$mta = new Default_Model_MtaConfig();
    	$mta->find(1);
    	$mta2 = new Default_Model_MtaConfig();
    	$mta2->find(2);
    	$mta4 = new Default_Model_MtaConfig();
    	$mta4->find(4);
    	
        if ($mta->getParam('use_incoming_tls') < 1) {
    		$view->ssl_display_class = 'hidden';
    	}
    	
    	$form    = new Default_Form_SmtpTls($mta);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('tls', 'smtp'));
        
        $cert = new Default_Model_PKI();
        $cert->setCertificate($mta->getParam('tls_certificate_data'));
        $certdata = $cert->getCertificateData();
        $message = '';
        
        if ($request->isPost()) {
        	$cert->setCertificate($request->getParam('tls_certificate_data'));
        	$certdata = $cert->getCertificateData();
            if ($form->isValidPartial($request->getPost())) {
            	$form->setParams($this->getRequest(), $mta);
            	$form->setParams($this->getRequest(), $mta2);
            	$form->setParams($this->getRequest(), $mta4);
            	try {
            	  $mta->save();
            	  $mta2->save();
            	  $mta4->save();
            	  if ($mta->getParam('use_incoming_tls') > 0) {
    		          $view->ssl_display_class = '';
    	          } else {
    	          	  $view->ssl_display_class = 'hidden';
    	          }
            	  $message = 'OK data saved';
            	  $slaves = new Default_Model_Slave();
            	  $slaves->sendSoapToAll('Service_setServiceToRestart', array('exim_stage1','exim_stage4'));
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
                if ($request->getParam('use_incoming_tls')) {
                      $view->ssl_display_class = '';
                } else {
                      $view->ssl_display_class = 'hidden';
                }
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
        $view->certdata = $certdata;
    }
    
    public function resourcescontrolAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'resourcescontrol')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'resourcescontrol')->label;
    	$request = $this->getRequest();
    	
     	$mta = new Default_Model_MtaConfig();
    	$mta->find(1);
    	$mta2 = new Default_Model_MtaConfig();
    	$mta2->find(2);
    	$mta4 = new Default_Model_MtaConfig();
    	$mta4->find(4);
    	
    	$view->rate_display_class = array();
    	foreach (array('rate', 'trusted_rate') as $rtype ) {
    		$view->rate_display_class[$rtype] = 'none';
        	if ($mta->getParam($rtype.'limit_enable') < 1) {
        		$view->rate_display_class[$rtype] = 'hidden';
    	    }
    	}
 
    	$form    = new Default_Form_SmtpResourcesControl($mta);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('resourcescontrol', 'smtp'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	$form->setParams($this->getRequest(), $mta);
            	$form->setParams($this->getRequest(), $mta2);
            	$form->setParams($this->getRequest(), $mta4);
            	try {
            	  $mta->save();
            	  $mta2->save();
            	  $mta4->save();
            	  foreach (array('rate', 'trusted_rate') as $rtype ) {
                	  if ($mta->getParam($rtype.'limit_enable') > 0) {
        		          $view->rate_display_class[$rtype] = 'none';
            	          $slaves = new Default_Model_Slave();
            	          $slaves->sendSoapToAll('Service_setServiceToRestart', array('exim_stage1','exim_stage4'));
    	              } else {
    	             	  $view->rate_display_class[$rtype] = 'hidden';
    	              }
            	  }
            	  $message = 'OK data saved';
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
                foreach (array('rate', 'trusted_rate') as $rtype ) {
                    if ($request->getParam($rtype.'limit_enable')) {
                          $view->rate_display_class[$rtype] = 'none';
                    } else {
                          $view->rate_display_class[$rtype] = 'hidden';
                    }
                }
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function greylistingAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'greylisting')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'greylisting')->label;
    	$request = $this->getRequest();
    	
    	$greylist = new Default_Model_GreylistConfig();
    	$greylist->find(1);
    	
    	$form    = new Default_Form_SmtpGreylisting($greylist);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('greylisting', 'smtp'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	$form->setParams($request, $greylist);
            	try {
            	  $greylist->save();
            	  $message = 'OK data saved';
            	  $slaves = new Default_Model_Slave();
            	  $slaves->sendSoapToAll('Service_setServiceToRestart', array('greylistd'));
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    
    public function dkimAction() {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'dkim')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'dkim')->label;
        $request = $this->getRequest();
        
        $mta = new Default_Model_MtaConfig();
        $mta->find(1);
       
        $form    = new Default_Form_SmtpDkim($mta);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('dkim', 'smtp'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                  $form->setParams($request, $mta);
                  $mta->save();
                  $message = 'OK data saved';
            	  $slaves = new Default_Model_Slave();
            	  $slaves->sendSoapToAll('Service_setServiceToRestart', array('exim_stage1','exim_stage4'));
                } catch (Exception $e) {
                  $message = 'NOK error saving data ('.$e->getMessage().')';
                }
            } else {
                $message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
        $view->message = $message;
    }
    
    public function clearcalloutAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
        $slave = new Default_Model_Slave();
        $slave->sendSoapToAll('Service_clearCalloutCache');
        $view->message = 'OK';
    }

}
