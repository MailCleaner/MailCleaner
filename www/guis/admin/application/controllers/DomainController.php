<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for domains configuration
 */

class DomainController extends Zend_Controller_Action
{
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/domain.css');
        $view->headScript()->appendFile($view->scripts_path.'/domain.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/pki.js', 'text/javascript');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
    	$view->selectedMenu = 'Configuration';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_Domains')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'Domains';
    	
        $t = Zend_Registry::get('translate');
    	$view->defaultsearchstring = $t->_('Domain search');
    	
    	$request = $this->getRequest();
    	$view->request = $request;
    	
  	    $domain = new Default_Model_Domain();
  	    $sname = $request->getParam('sname');
  	    if ($sname == $view->defaultsearchstring) {
  	    	$sname = '';
  	    }
    	$domains = $domain->fetchAllName(array(
    	                                  'order' => 'name ASC',
    	                                  'name' => $sname));
    	$view->domainscount = count($domains);
    	$nbelperpage = 12;
    	$view->lastpage = ceil($view->domainscount / $nbelperpage);
    	
    	$page = 1;
    	if ($request->getParam('page') && is_numeric($request->getParam('page')) && $request->getParam('page') > 0) {
    		if ($request->getParam('page') > $view->lastpage) {
    			$page = $view->lastpage;
    		} else {
    		    $page = $request->getParam('page');
    		}
    	}
    	$offset = $nbelperpage * ($page - 1);
    	$view->nbelperpage = $nbelperpage;
  	    $view->domains = array_slice($domains, $offset, $nbelperpage);
  	    $view->page = $page;
  	    $view->sname = $request->getParam('sname');
  	    
  	    $view->addurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('add', 'domain');
  	    $view->globalurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('global', 'domain');
  	    
  	    $view->searchurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'domain');
  	    $view->searchurl .= '/sname/'.$view->sname.'/page/'.$view->page;
  	    
  	    #$view->searchdomainurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('search', 'domain');
  	    $view->searchdomainurl = "/admin/domain/search";
  	    $view->panellinkurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('panellink', 'domain');
  	    $view->removeurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('remove', 'domain')."/name/".$request->getparam('name');
  	    $view->removeurl .= '/sname/'.$view->sname.'/page/'.$view->page;
  	    if ($request->getParam('name')) {
  	    	$domain->findByName($request->getParam('name'));
  	    }
  	    $view->name = $request->getParam('name');
  	    $view->editurl = $view->searchurl."/name/".$view->name;
  	    if ($request->getParam('panel')) {
  	    	$view->editurl .= '/panel/'.$request->getParam('panel');
  	    }
  	    #$addurl = '/sname/'.$view->sname.'/page/'.$view->page;
  	    #$addurl .= '/panel/'.$request->getParam('panel');
  	    #$addurl .= "/name/".$view->name;
  	    #$view->addurl = $addurl;
  	    $view->domain = $domain;
  	    
  	    $view->testdestinationsmtpurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('testdestinationsmtp', 'domain');
        $view->calloutconnectorurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('calloutconnector', 'domain');
        $view->authconnectorurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('authconnector', 'domain');
        $view->testcalloutURL = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('testcallout', 'domain');
        $view->testuserauthURL = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('testuserauth', 'domain');
   }
    
   public function indexAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();

    	$request = $this->getRequest();
    	    	
    	$message = '';
    	$flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
        if (isset($flashmessages[0])) {
        	$message = $flashmessages[0];
        }
        $view->message = $message;
    	
    }
    
    public function panellinkAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
        
        $request = $this->getRequest();
        $panel = 'general';
        if ($request->getParam('panel') && array_key_exists($request->getParam('panel'), $view->domain->getConfigPanels())) {
    		$panel = $request->getParam('panel');
        }
        $view->panel = $panel;
        $domain = new Default_Model_Domain();
        $view->previouspanel = $domain->getPreviousPanel($panel);
        $view->nextpanel = $domain->getNextPanel($panel);
    }
    
    public function editAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
    	$request = $this->getRequest();
        
        if ($request->isXmlHttpRequest()) {
        	$layout->disableLayout();
        	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	}
    	
    	$panel = 'general';
    	$panelformclass = 'Default_Form_DomainGeneral';
    	if ($request->getParam('panel') && array_key_exists($request->getParam('panel'), $view->domain->getConfigPanels())) {
    		$panel = $request->getParam('panel');
    		$panelformclass = 'Default_Form_Domain'.ucfirst($panel);
    	}
    	$view->name = $request->getParam('name');	
    	$view->previouspanel = $view->domain->getPreviousPanel($panel);
    	$view->nextpanel = $view->domain->getNextPanel($panel);
    
        $whitelistelement = new Default_Model_WWElement();
        $whitelistform = $whitelistelement->fetchAll('@'.$view->domain->getParam('name'),'white');
        $warnlistelement = new Default_Model_WWElement();
        $warnlistform = $warnlistelement->fetchAll('@'.$view->domain->getParam('name'),'warn');
	$blacklistelement = new Default_Model_WWElement();
        $blacklistform = $blacklistelement->fetchAll('@'.$view->domain->getParam('name'),'black');
        $newslistelement = new Default_Model_WWElement();
        $newslistform = $newslistelement->fetchAll('@'.$view->domain->getParam('name'),'wnews');

    	$panelform = new $panelformclass($view->domain, $whitelistform, $warnlistform, $blacklistform, $newslistform, $this->getRequest());
    	$panelform->setAction($view->searchurl."/name/".$view->name);
    	$view->panel = $panel;
    	$view->form = $panelform;

       #$view->searchdomainurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->url(array('controller' => 'domain', 'action' => 'search', 'sname' => $view->sname));

    	$message = '';
        if ($this->getRequest()->isPost()) {
            if ($panelform->isValid($request->getPost())) {
                try {
                  $is_domain_active = $view->domain->getParam('active');
                  $panelform->setParams($request, $view->domain);
                  if ($request->get('enabledomain')) {
		      $view->domain->setParam('active', $request->get('enabledomain') == 1 ? "true" : "false");
                  } else {
                      $view->domain->setParam('active', $is_domain_active);
                  }

                  if ($panel == 'filtering') {
                      $panelform->_whitelist = $whitelistelement->fetchAll('@'.$view->domain->getParam('name'),'white');
                      $panelform->_warnlist = $warnlistelement->fetchAll('@'.$view->domain->getParam('name'),'warn');
                      $panelform->_blacklist = $blacklistelement->fetchAll('@'.$view->domain->getParam('name'),'black');
                      $panelform->_newslist = $newslistelement->fetchAll('@'.$view->domain->getParam('name'),'wnews');
                  }
            	  $view->domain->save();
            	  $view->domain->saveAliases();
            	  $message = 'OK data saved';
            	  
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
               if (count($panelform->getMessages() > 0)) {
        	     $this->view->errors = $panelform->getMessages();
         	     $this->view->error = array_pop($panelform->getMessages());
         	     $message = 'NOK datanotvalid';
                }
            }
         }
         $view->message = $message;
         $view->whitelistform = $whitelistform;
         $view->warnlistform = $warnlistform;
         $view->blacklistform = $blacklistform;
         $view->newslistform = $newslistform;

    }
    
    public function globalAction() {
    	$redirector = $this->_helper->getHelper('Redirector');
            	  $redirector->gotoSimple('edit', 'domain', null, array(
            	                                   'name' => '__global__' ));
    }
    public function addAction() {
        $layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
    	$request = $this->getRequest();
    	
        if ($request->isXmlHttpRequest()) {
        	$layout->disableLayout();
        	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	}
    	
    	$panel = 'general';
    	$panelformclass = 'Default_Form_DomainAdd';
           	
    	$view->name = '';	
    	$view->previouspanel = $view->domain->getPreviousPanel($panel);
    	$view->nextpanel = $view->domain->getNextPanel($panel);
    	
    	$panelform = new $panelformclass($view->domain);
    	$panelform->setAction($view->addurl);
    	$view->panel = $panel;
    	$view->form = $panelform;
    	
    	$domain = new Default_Model_Domain();
    	$view->domain = $domain;
    	
    	$message = '';
        if ($this->getRequest()->isPost()) {
    	    $domainnames = '';
    	    if ($this->getRequest()->getParam('domainname') != '') {
    		   $domainnames = $this->getRequest()->getParam('domainname');
    	    }
    	    if ($this->getRequest()->getParam('mdomainname') != '') {
    	        $domainnames = $this->getRequest()->getParam('mdomainname');
    	    }
    	    $topdomain = '';
    	    $user = Zend_Registry::get('user');
    	    if ($this->getRequest()->getParam('topdomains') != '') {
    	    	 if ($user->canManageDomain($this->getRequest()->getParam('topdomains'))) {
    	        	$topdomain = '.'.$this->getRequest()->getParam('topdomains');
    	    	 } else {
    	    	 	throw new Exception('NOT authorized');
    	    	 }
    	    }
    	    $defdom = new Default_Model_Domain();
    	    if ($this->getRequest()->getParam('defaultvalues') != '') {
    	    	$defdom->findByName($this->getRequest()->getParam('defaultvalues'));
            }
            if ($panelform->isValid($request->getPost()) && ($domainnames != '')) {
                try {
                  require_once('Validate/DomainName.php');
    	          $validator = new Validate_DomainName();
                  foreach (preg_split('/\s+/', $domainnames) as $do) {
                  	$d = $do.$topdomain;
                    if (!$validator->isValid($d)) {
                    	throw new Exception('NOT Domain not valid');
                    }
                    $domain = new Default_Model_Domain();
                    $panelform->setParams($request, $domain);
		    $domain->setParam('name', $d);
                    $is_domain_active = $view->domain->getParam('active');
                    if ($request->get('enabledomain')) {
                        $domain->setParam('active', $request->get('enabledomain'));
                    } else {
                        $domain->setParam('active', $is_domain_active);
                    }
                    $domain->copyPrefs($defdom);
                    $domain->save();
            	    $domain->saveAliases();
            	    if ($view->domain->getParam('name') == '') {
            	    	$view->domain->setParam('name', $d);
            	    }
                  }
            	  $message = 'OK data saved';
            	  ## find page of first domain added
            	  $all = $domain->fetchAllName($this->getRequest()->getParam('sname'));
            	  $pos = 1;
            	  foreach ($all as $d) {
            	    if ($d->getParam('name') == $view->domain->getParam('name')) {
            	  	   break;
            	    }
            	  	$pos++;
            	  }
            	  $page = floor($pos / $view->nbelperpage) + 1; 
            	  $redirector = $this->_helper->getHelper('Redirector');
            	  $redirector->gotoSimple('edit', 'domain', null, array(
            	                                   'name' => $view->domain->getParam('name'),
            	                                   'page' => $page ));
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
               if ($this->getRequest()->getParam('domainname') == '') {
               	  $message = 'NOK enterdomainname';
               	  $panelform->addErrorMessage(array('domainname' => 'toto'));
               }
               if (count($panelform->getMessages()) > 0) {
        	     $this->view->errors = $panelform->getMessages();
         	     $this->view->error = array_pop($panelform->getMessages());
         	     $message = 'NOK datanotvalid';
                }
            }
         }
         $view->message = $message;
    }
    
    
    public function calloutconnectorAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    	$connector = $this->getRequest()->getParam('connector');
	    $connectorform  = new Default_Form_DomainAddressverification($view->domain);
	    $view->connector = $connector;
	    $view->form = $connectorform;
    }
    
    public function authconnectorAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    	$connector = $this->getRequest()->getParam('connector');
	    $connectorform  = new Default_Form_DomainAuthentication($view->domain);
	    $view->connector = $connector;
	    $view->form = $connectorform;
    }
    
    public function searchAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));   
    	unset($this->domain);	
    	#unset($view->name);
    	unset($view->domain);
    }
    
    public function testdestinationsmtpAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    	$view->status = $view->domain->getDestinationTestStatus($this->getRequest()->getParam('reset'));
    	$view->finished = $view->domain->destinationTestFinished();
    }
    
    public function testcalloutAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    	$view->status = $view->domain->getCalloutTestStatus($this->getRequest()->getParam('reset'));
    	$view->finished = $view->domain->calloutTestFinished();
    }
    
    public function testuserauthAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    	$view->data = $view->domain->testUserAuth(
    	                          $this->getRequest()->getParam('username'), 
    	                          $this->getRequest()->getParam('password'));
    }
    
    public function removeAction() {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->backurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'domain')."/name/".$this->getRequest()->getparam('name');
  	    $view->backurl .= '/sname/'.$view->sname.'/page/'.$view->page;
  	    
  	    $urladding = '/sname/'.$view->sname.'/page/'.$view->page;
  	    $view->removeurldomain = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('remove', 'domain')."/name/".$this->getRequest()->getparam('name');
  	    $view->removeurldomain .= $urladding."/remove/only";
  	    $view->removeurlaliases = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('remove', 'domain')."/name/".$this->getRequest()->getparam('name');
        $view->removeurlaliases .= $urladding."/remove/all";
        
        $domain = $view->domain;
        
        $request = $this->getRequest();
        $message = '';
        if ($request->getParam('remove') == 'only') {
        	try {
               $domain->delete();
        	   $this->_helper->getHelper('FlashMessenger')->addMessage('OK Domain removed');
               $this->_redirect('/domain/index'.$urladding);
            } catch (Exception $e) {
               $message = 'NOK could not remove message ('.$e->getMessage().')';
            }
        }
        if ($request->getParam('remove') == 'all') {
        	try {
        		$view->domain->setAliases(array());
        		$view->domain->saveAliases();
                $view->domain->delete();
        	    $this->_helper->getHelper('FlashMessenger')->addMessage('OK Domain and aliases removed');
                $this->_redirect('/domain/index'.$urladding);
        	} catch (Exception $e) {
        		$message = 'NOK could not remove message ('.$e->getMessage().')';
        	}
        }
        $view->message = $message;
    }
    
    public function isauthexhaustiveAction() {
        $layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
        $domain = $view->domain;
    	$view->response = 'NO';
        if (!$domain->getId() || $domain->isAuthExhaustive()) {
    	  $view->response = 'YES';  
        }
    }

    public function clearsmtpauthcacheAction() {
        $layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));

        $request = $this->getRequest();
        $domain_name = $request->getParam('name');
    
        $slave = new Default_Model_Slave();
        $slave->sendSoapToAll('Service_clearSMTPAutCache', array('domain' => $domain_name));
        $view->message = 'OK';
    }
}
