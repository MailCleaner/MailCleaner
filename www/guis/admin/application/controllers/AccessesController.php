<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for access configuration
 */

class AccessesController extends Zend_Controller_Action
{
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');
        $view->headLink()->appendStylesheet($view->css_path . '/domain.css');
        $view->headScript()->appendFile($view->scripts_path . '/domain.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path . '/baseconfig.js', 'text/javascript');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
        $view->selectedMenu = 'Configuration';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_Accesses')->class = 'submenuelselected';
        $view->selectedSubMenu = 'Accesses';
        $t = Zend_Registry::get('translate');
        $view->defaultsearchstring = $t->_('Administrator search');

        $request = $this->getRequest();

        $admin = new Default_Model_Administrator();
        if ($request->getParam('username')) {
            $admin->find($request->getParam('username'));
        }
        $view->admin = $admin;

        $sname = $request->getParam('sname');
        if ($sname == $view->defaultsearchstring) {
            $sname = '';
        }
        $admins = $admin->fetchAllName([
            'order' => 'username ASC',
            'username' => $sname
        ]);
        $view->adminscount = count($admins);
        $nbelperpage = 12;
        $view->lastpage = ceil($view->adminscount / $nbelperpage);
        $view->username = $request->getParam('username');
        if ($view->username == '' &&  $request->getParam('name') != '') {
            $view->username = $request->getParam('name');
        }

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
        $view->admins = array_slice($admins, $offset, $nbelperpage);
        $view->page = $page;
        $view->sname = $request->getParam('sname');

        $view->searchurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'accesses');
        $view->searchurl .= '/sname/' . $view->sname . '/page/' . $view->page;
        $view->searchdomainurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('search', 'accesses');
        $view->addurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('add', 'accesses');
        $view->removeurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('remove', 'accesses') . "/username/" . $request->getparam('username');
        $view->removeurl .= '/sname/' . $view->sname . '/page/' . $view->page;
    }

    public function indexAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $request = $this->getRequest();

        $message = '';
        $flashmessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
        if (isset($flashmessages[0])) {
            $message = $flashmessages[0];
        }
        $view->message = $message;
    }

    public function addAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $request = $this->getRequest();

        $form    = new Default_Form_AdminEdit($view->admin);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('add', 'accesses'));
        $message = '';
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $view->admin);
                    $message = 'OK data saved';
                    ## find page of added element
                    $admin = new Default_Model_Administrator();
                    $all = $admin->fetchAllName([
                        'order' => 'username ASC',
                        'username' => $request->getParam('sname')
                    ]);
                    $pos = 1;
                    foreach ($all as $d) {
                        if ($d->getParam('username') == $view->admin->getParam('username')) {
                            break;
                        }
                        $pos++;
                    }
                    if ($pos % $view->nbelperpage != 0) {
                        $page = floor($pos / $view->nbelperpage) + 1;
                    } else {
                        $page = $pos / $view->nbelperpage;
                    }
                    if ($page < 1) {
                        $page = 1;
                    }
                    $redirector = $this->_helper->getHelper('Redirector');
                    $redirector->gotoSimple('edit', 'accesses', null, [
                        'username' => $view->admin->getParam('username'),
                        'page' => $page
                    ]);
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            }
        }
        $view->form = $form;
        $view->message = $message;
    }

    public function editAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $request = $this->getRequest();

        $form    = new Default_Form_AdminEdit($view->admin);
        #$form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'accesses'));
        $form->setAction($view->searchurl . "/name/" . $view->name);
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $view->admin);
                    $message = 'OK data saved';
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            } else {
                $message = "NOK bad settings";
            }
        }
        $view->form = $form;
        $view->message = $message;
    }

    public function searchAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
    }

    public function removeAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->backurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('edit', 'accesses') . "/username/" . $this->getRequest()->getparam('username');
        $view->backurl .= '/sname/' . $view->sname . '/page/' . $view->page;

        $urladding = '/sname/' . $view->sname . '/page/' . $view->page;
        $view->removeurldomain = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('remove', 'accesses') . "/username/" . $this->getRequest()->getparam('username');
        $view->removeurldomain .= $urladding . "/remove/only";

        $admin = $view->admin;

        $request = $this->getRequest();
        $message = '';
        if ($request->getParam('remove') == 'only') {
            try {
                $admin->delete();
                $this->_helper->getHelper('FlashMessenger')->addMessage('OK Administrator removed');
                $this->_redirect('/accesses/index' . $urladding);
            } catch (Exception $e) {
                $message = 'NOK could not remove message (' . $e->getMessage() . ')';
            }
        }
        $view->message = $message;
    }
}
