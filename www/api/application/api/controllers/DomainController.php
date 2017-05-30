<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * index page controller
 */

class DomainController extends Zend_Controller_Action
{

	public function init()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function addAction()
	{
		$request = $this->getRequest();
		$api = new Api_Model_DomainAPI();
		$api->add($request->getParam('name'), $request->getParams());
	}
	
    public function editAction()
    {
        $request = $this->getRequest();
        $api = new Api_Model_DomainAPI();
        $api->edit($request->getParam('name'), $request->getParams());
    }
    
    public function existsAction()
    {
        $request = $this->getRequest();
        $api = new Api_Model_DomainAPI();
        $api->exists($request->getParam('name'));
    }
    
    public function removeAction()
    {
        $request = $this->getRequest();
        $api = new Api_Model_DomainAPI();
        $api->remove($request->getParam('name'));
    }
    
    public function showAction()
    {
        $request = $this->getRequest();
        $api = new Api_Model_DomainAPI();
        $params = NULL;
        if ($request->getParam('params')) {
        	$params = preg_split('/[,:]/', $request->getParam('params'));
        }
        $api->show($request->getParam('name'), $params);
    }

    public function listAction()
    {
    	$request = $this->getRequest();
        $api = new Api_Model_DomainAPI();
    	$api->domainList();
    }
}