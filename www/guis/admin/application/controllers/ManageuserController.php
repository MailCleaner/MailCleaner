<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for user managment
 */

class ManageuserController extends Zend_Controller_Action
{
	public function init()
	{
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$view->headLink()->appendStylesheet($view->css_path.'/main.css');
		$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');
		$view->headLink()->appendStylesheet($view->css_path.'/domain.css');
		$view->headScript()->appendFile($view->scripts_path.'/domain.js', 'text/javascript');
		$view->headLink()->appendStylesheet($view->css_path.'/management.css');
		$view->headScript()->appendFile($view->scripts_path.'/management.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');

		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Management')->class = 'menuselected';
		$view->selectedMenu = 'Management';
		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submanage_Users')->class = 'submenuelselected';
		$view->selectedSubMenu = 'Users';


		$searchForm = new Default_Form_Manage_UserSearch(ManageuserController::getSearchParams($this->getRequest(), $view));
		$view->searchForm = $searchForm;

		$view->searchdomainurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('search', 'user');
		$searchForm->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser'));

		ManageuserController::searchUserOrEmails($this->getRequest(), $view);

	}

	static protected function getSearchParams($request, $view) {
		$params = array();
		foreach (array('domain' => '', 'search' => '', 'page' => 1, 'type' => 'user') as $p => $v) {
			if ($request->getParam($p)) {
				$params[$p] = $request->getParam($p);
				$view->$p = $request->getParam($p);
			} else {
				$params[$p] = $v;
				$view->$p = $v;
			}
		}
		
		/*$t = Zend_Registry::get('translate');
		if ($params['search'] == $t->_('Search data')) {
			$params['search'] = '';
		}*/
		return $params;
	}

	static public function searchUserOrEmails($request, $view)
	{
		$t = Zend_Registry::get('translate');
        //$view->defaultsearchstring = $t->_('Search data');
		ManageuserController::getSearchParams($request, $view);
		if ($view->domain == '') {
			$view->domain = $request->getParam('domain');
		}
		if ($view->search == '') {
			$view->search = $request->getParam('search');
		}
		$elements = array();
		$view->elementdatafield = 'username';
		$add = $view->email;
		if ($view->type == 'email') {
			$view->elementdatafield = 'address';
			$email = new Default_Model_Email();
			$elements = $email->fetchAllName(array(
    	                                  'order' => 'address ASC',
    	                                  'address' => $view->search,
    	                                  'domain' => $view->domain));
		} else {
			$user = new Default_Model_User();
			$elements = $user->fetchAllName(array(
    	                                  'order' => 'username ASC',
    	                                  'username' => $view->search,
    	                                  'domain' => $view->domain));
		}
			
		require_once('Tools/SearchPagination.php');
		Tools_SearchPagination::paginateElements($elements, 12, $view->page, $view);
			
			
		$view->editurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser');
		#    	if ($view->type == 'email') {
		#         $view->editurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('editemail', 'manageuser');
		#   	}
		$view->editurl .= '/domain/'.$view->domain;
		$view->editurl .= '/search/'.$view->search;
		$view->editurl .= '/type/'.$view->type;
		$view->editurl .= '/page/'.$view->page;

	}

	public function indexAction() {
	}

	public function editAction() {
		$request = $this->getRequest();
		if ($request->getParam('type') == 'email') {
			$this->_forward('editemail', null, null, $request->getParams());
			return;
		}
		$this->_forward('edituser', null, null, $request->getParams());
	}

	public function edituserAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$request = $this->getRequest();
		$view->username = $request->getParam('userfield');

		$searchForm = new Default_Form_Manage_UserSearch(ManageuserController::getSearchParams($request, $view));
		$searchForm->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser'));
		$view->searchForm = $searchForm;

		ManageuserController::searchUserOrEmails($request, $view);

		$username = $request->getParam('user');
		if ( (!$username || $username == '') && $request->getParam('search') != '' && $request->getParam('removed') != '1') {
			$username = $request->getParam('search');
		}
		$user = new Default_Model_User();
		$user->find($username, $request->getParam('domain'));

		$view->username = $user->getParam('username');
		$username = $view->username;
		$view->userobject = $user;
		
		if ($request->isXmlHttpRequest()) {
			$layout->disableLayout();
			$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		}
			
		$panel = 'interfacesettings';
		$panelformclass = 'Default_Form_Manage_UserInterfacesettings';
		if ($request->getParam('panel') && array_key_exists($request->getParam('panel'), $user->getConfigPanels())) {
			$panel = $request->getParam('panel');
			$panelformclass = 'Default_Form_Manage_User'.ucfirst($panel);
		}
		$view->previouspanel = $user->getPreviousPanel($panel);
		$view->nextpanel = $user->getNextPanel($panel);
			
		$panelform = new $panelformclass($user, $request->getParam('domain'));
		$params = $this->getRequest()->getParams();
			
		$params = array();
		foreach (array('page', 'search', 'type') as $p) {
			$params[$p] = $this->getRequest()->getParam($p);
		}
		$params['username'] = $username;
		$params['domain'] = $request->getParam('domain');
		$params['user'] = $username;
		$panelform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edituser', 'manageuser', NULL, $params));
		$panelform->setAttrib('id', 'useredit_form');
		$view->panel = $panel;
		$view->form = $panelform;
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edituser', 'manageuser', NULL, $params);
			
		$view->removeurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('removeuser', 'manageuser', NULL,
		array('domain' => $user->getDomainObject()->getParam('name'),
    	                                'user' => $username));
			
		$view->removeurl .= '/search/'.$view->search.'/page/'.$view->page;

		$message = '';

		if ($this->getRequest()->isPost() && $request->getParam('username')) {
			if ($panelform->isValid($request->getPost())) {
				try {
					$panelform->setParams($request, $user);
					$user->save();
					$message = 'OK data saved';

					ManageuserController::getSearchParams($request, $view);
					ManageuserController::searchUserOrEmails($request, $view);
					$view->username = $user->getParam('username');

					$user = new Default_Model_User();
					$user->find($view->username, $view->domain);
					$view->userobject = $user;
					$panelform = new $panelformclass($user, $request->getParam('domain'));
					$panelform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edituser', 'manageuser', NULL, $params));
					$panelform->setAttrib('id', 'useredit_form');
					$view->form = $panelform;

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
			
			
		$flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		if (isset($flashmessages[0])) {
			$message = $flashmessages[0];
		}
		$view->message = $message;
	}

	public function userpanellinkAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));


		$user = new Default_Model_User();
		$view->previouspanel = $domain->getPreviousPanel($panel);
		$view->nextpanel = $domain->getNextPanel($panel);
		$panel = 'general';
		if ($request->getParam('panel') && array_key_exists($this->getRequest()->getParam('panel'), $user->getConfigPanels())) {
			$panel = $request->getParam('panel');
		}
		$view->panel = $panel;
	}

	public function editemailAction() {
	    $layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$request = $this->getRequest();

		$searchForm = new Default_Form_Manage_UserSearch(ManageuserController::getSearchParams($request, $view));
		$searchForm->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser'));
		$view->searchForm = $searchForm;

		ManageuserController::searchUserOrEmails($request, $view);

		$address = urldecode($request->getParam('email'));
		if ( (!$address || $address == '') && $request->getParam('search') != '' && $request->getParam('removed') != '1') {
			$address = $request->getParam('search');
		}
		if (!preg_match('/\@\S+/', $address) && $address != '') {
			$address .= '@'.$request->getParam('domain');
		}
		$email = new Default_Model_Email();
		$email->find($address);

		$view->address = $email->getParam('address');
		$address = $view->address;
		$view->emailobject = $email;

		$view->username = $view->address;
		$view->elementdatafield = 'address';
		$view->domain = $email->getDomain();
			
		if ($request->isXmlHttpRequest()) {
			$layout->disableLayout();
			$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		}

		$panel = 'addresssettings';
		$panelformclass = 'Default_Form_Manage_EmailAddresssettings';
		if ($request->getParam('panel') && array_key_exists($request->getParam('panel'), $email->getConfigPanels())) {
			$panel = $request->getParam('panel');
			$panelformclass = 'Default_Form_Manage_Email'.ucfirst($panel);
		}
		$view->previouspanel = $email->getPreviousPanel($panel);
		$view->nextpanel = $email->getNextPanel($panel);
			
		$panelform = new $panelformclass($email);
		$params = $this->getRequest()->getParams();
			
		$params = array();
		foreach (array('page', 'search', 'type') as $p) {
			$params[$p] = $this->getRequest()->getParam($p);
		}
		$params['email'] = $address;
		$params['domain'] = $email->getDomain();
		$panelform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('editemail', 'manageuser', NULL, $params));
		$panelform->setAttrib('id', 'emailedit_form');
		$view->panel = $panel;
		$view->form = $panelform;
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('editemail', 'manageuser', NULL, $params);
			
		$view->removeurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('removeemail', 'manageuser', NULL, $params);

		$view->email = $email;
        if (preg_match('/(\S+)\@/', $params['search'], $matches)) {
            $params['search'] = $matches[1];
        }
		
        $spamparams = $params;
        unset($spamparams['page']);
		unset($spamparams['type']);
		unset($spamparams['email']);
		if (preg_match('/(\S+)\@/', $address, $matches)) {
			$spamparams['search'] = $matches[1];
		}
		$urls['spamquarantine'] = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managespamquarantine', NULL, $spamparams);
		
		$userparams = $params;
		$userparams['type'] = 'email';
		if ($email->getLinkedUser()) {
           $userparams['search'] = $email->getLinkedUser()->getShortUsername();
		   $userparams['user'] = $email->getLinkedUser()->getParam('username');
		}
		unset($userparams['address']);
		$urls['usersettings'] = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser', NULL, $userparams);
		
		$guiparams = $params;
		unset($guiparams['search']);
		unset($guiparams['type']);
		$urls['usergui'] = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('autologuser', 'manageuser', NULL, $guiparams);
		
		$view->urls = $urls;
		$view->linkedusername = $email->getLinkedUser();
		$message = '';
	    if ($this->getRequest()->isPost() && $request->getParam('address')) {
			if ($panelform->isValid($request->getPost())) {
				try {
					$panelform->setParams($request, $email);
					$email->save();

					$message = 'OK data saved';
					
					ManageuserController::getSearchParams($request, $view);
					ManageuserController::searchUserOrEmails($request, $view);
					$view->address = $email->getParam('address');
					
					$email = new Default_Model_Email();
					$email->find($view->address);
					$view->emailobject = $email;
					$panelform = new $panelformclass($email);
					$panelform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('editemail', 'manageuser', NULL, $params));
					$panelform->setAttrib('id', 'emailedit_form');
					$view->form = $panelform;
				
					if ($panel == 'archiving' || $panel == 'addresssettings') {
						$slave = new Default_Model_Slave();
						$soapparams = array('what' => 'archiving');
						$res = $slave->sendSoapToAll('Service_silentDump', $soapparams);
					}

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
	    $flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		if (isset($flashmessages[0])) {
			$message = $flashmessages[0];
		}
		$view->message = $message;
	}

	public function removeuserAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
			
		$request = $this->getRequest();
		$user = new Default_Model_User();
		$user->find($request->getParam('user'), $request->getParam('domain'));

		$request = $this->getRequest();
		$message = '';
		$params = $request->getParams();
		try {
			$user->delete();
			unset($params['user']);
			unset($params['controller']);
			unset($params['action']);
			unset($params['module']);
			$params['removed'] = '1';
			$flashmessages = $this->_helper->getHelper('FlashMessenger')->addMessage('OK preferences removed');
			$url = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser', NULL, $params);
			$this->_helper->getHelper('Redirector')->gotoSimple('edituser', null, null, $params);
			return;
		} catch (Exception $e) {
			$message = 'NOK could not remove message ('.$e->getMessage().')';
		}
		$view->message = $message;
		return;
	}
	
	public function removeemailAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
			
		$request = $this->getRequest();
		$email = new Default_Model_Email();
		$email->find($request->getParam('email'));

		$request = $this->getRequest();
		$message = '';
		$params = $request->getParams();
		try {
			$email->delete();
			unset($params['email']);
			unset($params['controller']);
			unset($params['action']);
			unset($params['module']);
			$params['removed'] = '1';
            
			$flashmessages = $this->_helper->getHelper('FlashMessenger')->addMessage('OK preferences removed');
			$url = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'manageuser', NULL, $params);
			$this->_helper->getHelper('Redirector')->gotoSimple('editemail', null, null, $params);
			return;
		} catch (Exception $e) {
			$message = 'NOK could not remove preferences ('.$e->getMessage().')';
		}
		$view->message = $message;
		return;
	}

	public function autologuserAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$request = $this->getRequest();
			
		if ($request->getParam('user') && $request->getParam('domain')) {
				
			$admin = Zend_Registry::get('user');
			if ($admin->canManageDomain($request->getParam('domain'))) {
				// to do: check domain
				require_once('domain/Domain.php');
				$domain_ = new Domain();
				$domain_->load($request->getParam('domain'));
				require_once('user/User.php');
				$user = new User();
				$user->setDomain($domain_->getPref('name'));
				$user->load($request->getParam('user'));
				// and register it to the session
				$_SESSION['user'] = serialize($user);
				$_SESSION['username'] = $request->getParam('user');
				$_SESSION['domain'] = $domain_->getPref('name');

				header('Location: /');
				exit();
			}
		}
	}
}
