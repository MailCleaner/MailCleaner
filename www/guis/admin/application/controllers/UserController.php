<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for interface users
 */

class UserController extends Zend_Controller_Action
{

    public function loginAction()
    {
    	$t = Zend_Registry::get('translate');
    	$this->view->headTitle($t->_('login'));
    	$this->view->layout()->setLayout('basic');
    	
    	$auth = Zend_Auth::getInstance();
    	if ($auth->hasIdentity()) {
            $this->_redirect('/index');
    	}

    	$request = $this->getRequest(); 
        // determine the page the user was originally trying to request 
        $redirect = $request->getPost('redirect'); 
        if (strlen($redirect) == 0) 
            $redirect = $request->getServer('REQUEST_URI'); 
        if (strlen($redirect) == 0) 
            $redirect = '/index';


        $request = $this->getRequest();
        $form    = new Default_Form_Login();
        if ($this->getRequest()->getParam('message')) {
        	$form->addErrorMessage($this->getRequest()->getParam('message'));
        }
        
        if ($this->getRequest()->isPost() && $form->isValid($request->getPost())) {
        	
    	    $authAdapter = new Zend_Auth_Adapter_DbTable(
                          Zend_Registry::get('writedb'),
                          'administrator',
                          'username',
                          'password',
                          'ENCRYPT(?, SUBSTRING(password,1,2))'
                       );   
            $authAdapter2 = new Zend_Auth_Adapter_DbTable(
                          Zend_Registry::get('writedb'),
                          'administrator',
                          'username',
                          'password',
                          'ENCRYPT(?, SUBSTR(password, 1,12))'
                       );   
            ## This one should work for most crypt sheme, principaly crypt-sha512, regarldess of the salt length
            $authAdapter4 = new Zend_Auth_Adapter_DbTable(
                          Zend_Registry::get('writedb'),
                          'administrator',
                          'username',
                          'password',
                          'ENCRYPT(?, SUBSTR(password, 1, LOCATE(\'$\', password, LOCATE(\'$\', password, 4)+1)))'
                       );   
            $authAdapter3 = new Zend_Auth_Adapter_DbTable(
                          Zend_Registry::get('writedb'),
                          'administrator',
                          'username',
                          'password',
                          'MD5(?)'
                       );                          

            $givenusername = $this->getRequest()->getParam('username');
            $givenpassword = $this->getRequest()->getParam('password');
            $authAdapter->setIdentity($givenusername)->setCredential($givenpassword);
            $authAdapter2->setIdentity($givenusername)->setCredential($givenpassword);
            $authAdapter3->setIdentity($givenusername)->setCredential($givenpassword);
            $authAdapter4->setIdentity($givenusername)->setCredential($givenpassword);

            $authpassed = false;
            $result = $auth->authenticate($authAdapter4);
            if ($result->isValid()) {
            	$authpassed = true;
            } else {
               $result = $auth->authenticate($authAdapter3);
               if ($result->isValid()) {
               	   $authpassed = true;
               } else {
                   $result = $auth->authenticate($authAdapter2);
                   if ($result->isValid()) {
                   	  $authpassed = true;
                   } else {
                       $result = $auth->authenticate($authAdapter);
                       if ($result->isValid()) {
                    	  $authpassed = true;
                       }
                   }
               }
            }
            if ($authpassed) {
        	    $user = new Default_Model_Administrator();
        	    $user->find($givenusername);
        	    Zend_Registry::set('user', $user);
                $user->checkPasswordEncryptionSheme($givenpassword);
                $this->_redirect($redirect);
            } else {
            	$form->addError('badCredentials');
            }
         } else {
         	if ($this->getRequest()->isPost()) {
            	$form->addError('badDataGiven');
         	}
         }
         $this->view->error = '';
         if (count($form->getErrorMessages() > 0)) {
         	$this->view->error = $t->_(array_pop($form->getErrorMessages()));
         }
         $this->view->form = $form;
         $this->view->headLink()->appendStylesheet($this->view->css_path.'/login.css');
         
    }
    
    public function logoutAction()
    {
    	$auth = Zend_Auth::getInstance();
    	$auth->clearIdentity();
    	$t = Zend_Registry::get('translate');
    	$this->_helper->getHelper('Redirector')->gotoSimple('login', 'user', null, array('message' => 'loggedOut'));
    }

    protected function setupSearchFields($request, $view) {
        $view->email = $request->getParam('email');
        $view->domain = $request->getParam('domain');
        $view->username = $request->getParam('username');
        $view->page = $request->getParam('page');
    }
    
    public function searchAction()
    {
        $layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$layout->disableLayout();
    	$view->addScriptPath(Zend_Registry::get('ajax_script_path')); 
        
    	$request = $this->getRequest();
    	
    	require_once('ManageuserController.php');
    	ManageuserController::searchUserOrEmails($this->getRequest(), $view);
    	
    }
    
    
}
