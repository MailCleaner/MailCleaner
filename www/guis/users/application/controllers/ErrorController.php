<?php
/**
 * MailCleaner
 *
 * @license http://www.mailcleaner.net/open/licence_en.html MailCleaner Public License
 * @copyright 2015 Fastnet SA
 */

/**
 * Error handling controller
 * @author jpgrossglauser
 */
class ErrorController extends Zend_Controller_Action
{
    public function errorAction()
    {
        $this->view->errors = $this->_getParam('error_handler');
        die(var_dump($this->view->errors));
    }
}

