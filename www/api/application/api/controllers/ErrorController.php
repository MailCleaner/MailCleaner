<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * generic error controller
 */

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $code = 500;
        
        switch ($errors->type) { 
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
        
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $code = 404;
                $this->view->message = 'Function not found';
                break;
            default:
                // application error 
                $this->getResponse()->setHttpResponseCode(500);
                $code = 500;
                $this->view->message = 'Application error';
                break;
        }
        
        Zend_Registry::get('response')->setResponse($code, $this->view->message);
        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
    }

}

