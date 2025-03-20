<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for anti spam settings
 */

class AntispamController extends Zend_Controller_Action
{
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
        $view->selectedMenu = 'Configuration';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_AntiSpam')->class = 'submenuelselected';
        $view->selectedSubMenu = 'AntiSpam';

        $this->config_menu = new Zend_Navigation();

        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Global settings', 'id' => 'globalsettings', 'action' => 'globalsettings', 'controller' => 'antispam']));
        $view->config_menu = $this->config_menu;

        $view->headScript()->appendFile($view->scripts_path . '/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path . '/antispamconfig.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');

        $module = new Default_Model_AntispamModule();
        $modules = $module->fetchAll();
        $view->modules = $modules;
    }

    public function indexAction()
    {
    }

    public function globalsettingsAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'globalsettings')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'globalsettings')->label;
        $request = $this->getRequest();

        $antispam = new Default_Model_AntispamConfig();
        $antispam->find(1);

        // blacklistmr
        $blacklistelement = new Default_Model_WWElement();
        $blacklist = $blacklistelement->fetchAll('', 'black');

        $whitelistelement = new Default_Model_WWElement();
        $whitelist = $whitelistelement->fetchAll('', 'white');

        $warnlistelement = new Default_Model_WWElement();
        $warnlist = $warnlistelement->fetchAll('', 'warn');

        $newslistelement = new Default_Model_WWElement();
        $newslist = $newslistelement->fetchAll('', 'wnews');

        $form    = new Default_Form_AntispamGlobalSettings($antispam, $whitelist, $warnlist, $blacklist, $newslist);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('globalsettings', 'antispam'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {

                try {
                    $form->setParams($this->getRequest(), $antispam);
                    $form->_blacklist = $blacklistelement->fetchAll('', 'black');
                    $form->_whitelist = $whitelistelement->fetchAll('', 'white');
                    $form->_warnlist = $warnlistelement->fetchAll('', 'warn');
                    $form->_newslist = $newslistelement->fetchAll('', 'wnews');
                    $antispam->save();

                    $message = 'OK data saved';
                    $slaves = new Default_Model_Slave();
                    $slaves->sendSoapToAll('Service_setServiceToRestart', ['exim_stage1', 'mailscanner']);
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                    if (preg_match('/Duplicate entry/', $message)) {
                        $message = "NOK this entry already exists";
                    }
                }
            } else {
                $message = "NOK bad settings";
            }
        }

        $view->form = $form;
        $view->message = $message;
    }

    public function editmoduleAction()
    {
        $t = Zend_Registry::get('translate');
        $request = $this->getRequest();
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $module = new Default_Model_AntispamModule();
        if ($request->getParam('prefilter')) {
            $module->findByName($request->getParam('prefilter'));
        }

        $formclass = 'Default_Form_AntiSpam_' . $module->getParam('name');
        if (!@class_exists($formclass)) {
            $formclass = 'Default_Form_AntiSpam_Default';
        }

        $form = new $formclass($module);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('editmodule', 'antispam', null, ['prefilter' => $module->getParam('name')]));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $form->setParams($this->getRequest(), $module);

                try {
                    $module->save();
                    $modules = $module->fetchAll();
                    $view->modules = $modules;
                    $message = 'OK data saved';
                    $slaves = new Default_Model_Slave();
                    $slaves->sendSoapToAll('Service_setServiceToRestart', ['mailscanner']);
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            } else {
                $message = "NOK bad settings";
            }
        }
        $view->message = $message;
        $view->form = $form;
        $view->module = $module;
    }
}
