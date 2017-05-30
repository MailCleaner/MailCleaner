<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * index page controller
 */

class QuarantineController extends Zend_Controller_Action
{

  public function init()
  {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);
  }

  public function getspamAction()
  {
     $request = $this->getRequest();
     $api = new Api_Model_QuarantineAPI();
     $api->getSpam($request->getParams());
  }
    
}
