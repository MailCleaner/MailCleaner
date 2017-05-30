<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for smtp settings
 */

class PkiController extends Zend_Controller_Action
{
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    	
    }
    
    public function indexAction() {
  	
    }
    
    public function createkeyAction() {

    	$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        
        $request = $this->getRequest();
        $params = array('type' => $request->getParam('t'), 'length' =>  $request->getParam('l'));
        
    	$pki = new Default_Model_PKI();  	
    	$pki->createKey($params);
    	
    	$key = array('privateKey' => $pki->getPrivateKey(), 'privateKeyNoPEM' => $pki->getPrivateKeyNoPEM(),
    	             'publicKey' => $pki->getPublicKey(), 'publicKeyNoPem' => $pki->getPublicKeyNoPEM());
    	$this->_helper->json($key);	
    }

}