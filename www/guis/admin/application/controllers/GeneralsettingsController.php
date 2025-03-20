<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for general settings
 */

class GeneralsettingsController extends Zend_Controller_Action
{
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
        $view->selectedMenu = 'Configuration';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_GeneralSettings')->class = 'submenuelselected';
        $view->selectedSubMenu = 'GeneralSettings';

        $this->config_menu = new Zend_Navigation();

        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Defaults', 'id' => 'defaults', 'action' => 'defaults', 'controller' => 'generalsettings']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Company', 'id' => 'company', 'action' => 'company', 'controller' => 'generalsettings']));

        // Autoconfiguration is available only for EE edition
        $sysconf = MailCleaner_Config::getInstance();
        if ($sysconf->getOption('REGISTERED') == 1) {
            $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Auto-configuration', 'id' => 'autoconfiguration', 'action' => 'autoconfiguration', 'controller' => 'generalsettings']));
        }

        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Quarantines', 'id' => 'quarantines', 'action' => 'quarantines', 'controller' => 'generalsettings']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Periodic tasks', 'id' => 'tasks', 'action' => 'tasks', 'controller' => 'generalsettings']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Logging', 'id' => 'logging', 'action' => 'logging', 'controller' => 'generalsettings']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Archiving', 'id' => 'archiving', 'action' => 'archiving', 'controller' => 'generalsettings']));
        $view->config_menu = $this->config_menu;

        $view->headScript()->appendFile($view->scripts_path . '/baseconfig.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    }

    public function indexAction()
    {
    }

    public function defaultsAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'defaults')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'defaults')->label;
        $request = $this->getRequest();

        $defaults = new Default_Model_SystemConf();
        $defaults->load();
        $usergui = new Default_Model_UserGUI();
        $usergui->load();
        $domain = new Default_Model_Domain();
        $domains = $domain->fetchAllName();

        $form    = new Default_Form_Defaults($defaults, $usergui, $domains);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('defaults', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $defaults->setParam('default_language', $request->getParam('language'));
                $defaults->setParam('default_domain', $request->getParam('domain'));
                $defaults->setParam('sysadmin', $request->getParam('sysadmin'));
                $defaults->setParam('summary_from', $request->getParam('systemsender'));
                $defaults->setParam('falseneg_to', $request->getParam('falsenegaddress'));
                $defaults->setParam('falsepos_to', $request->getParam('falseposaddress'));
                $usergui->setParam('want_domainchooser', $request->getParam('showdomainselector'));
                try {
                    $defaults->save();
                    $usergui->save();
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

    public function companyAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'company')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'company')->label;
        $request = $this->getRequest();

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form    = new Default_Form_Company($defaults);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('company', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $defaults->setParam('organisation', $request->getParam('companyname'));
                $defaults->setParam('contact', $request->getParam('contactname'));
                $defaults->setParam('contact_email', $request->getParam('contactemail'));
                try {
                    $defaults->save();
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

    public function autoconfigurationAction()
    {
        $this->config_menu->findOneBy('id', 'autoconfiguration')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'autoconfiguration')->label;

        $message = '';
        $autoconfmgr = new Default_Model_AutoconfigurationManager();
        $autoconfmgr->load();
        $form = new Default_Form_Autoconfiguration($autoconfmgr);

        $request = $this->getRequest();

        if ($request->isPost()) {
            // Download and set once the reference configuration
            $isDownload = $request->getParam('download');
            if (isset($isDownload) && $isDownload) {
                $message = $autoconfmgr->download();
            }

            // Enable daily auto-configuration
            if ($form->isValid($request->getPost())) {
                $autoconfmgr->setAutoconfenabled($request->getParam('autoconfiguration'));
                $message = $autoconfmgr->save();
            } else {
                $message = 'NOK an error occurred';
            }
        }

        $view->form = $form;
        $view->message = $message;
    }

    public function quarantinesAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'quarantines')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'quarantines')->label;
        $request = $this->getRequest();

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form    = new Default_Form_Quarantines($defaults);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('quarantines', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $defaults->setParam('days_to_keep_spams', $request->getParam('spamretention'));
                $defaults->setParam('days_to_keep_virus', $request->getParam('contentretention'));
                try {
                    $defaults->save();
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

    public function tasksAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'tasks')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'tasks')->label;
        $request = $this->getRequest();

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form    = new Default_Form_Tasks($defaults);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('tasks', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $defaults->setParam('cron_time', $request->getParam('cron_time'));
                $defaults->setParam('cron_weekday', $request->getParam('cron_weekday'));
                $defaults->setParam('cron_monthday', $request->getParam('cron_monthday'));
                try {
                    $defaults->save();
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

    public function loggingAction()
    {

        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'logging')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'logging')->label;
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $layout->disableLayout();
            $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
            $view->message = Default_Model_Localhost::sendSoapRequest('Services_getStarterLog', 'syslog');
            return;
        }

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form    = new Default_Form_Logging($defaults);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('logging', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $defaults->setParam('use_syslog', $request->getParam('use_syslog'));
                $defaults->setParam('syslog_host', $request->getParam('syslog_host'));
                if ($request->getParam('syslog_host') == '' && $request->getParam('use_syslog')) {
                    $form->getElement('syslog_host')->addError('Cannot be empty');
                    $message = "NOK Syslog server cannot be empty";
                } else {
                    try {
                        $defaults->save();
                        $slaves = new Default_Model_Slave();
                        $slaves->sendSoapToAll('Service_setServiceToRestart', ['exim_stage1', 'exim_stage4', 'mailscanner']);
                        $message = $slaves->sendSoapToAll('Services_restartSyslog', []);
                        if (preg_match('/^OK/', $message)) {
                            $message = "OK data saved, services may need to be restarted";
                        }
                    } catch (Exception $e) {
                        $message = 'NOK error saving data (' . $e->getMessage() . ')';
                    }
                }
            } else {
                $message = "NOK bad settings";
            }
        }

        $view->waiturl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('logging', 'generalsettings');
        $view->finishedwait = 'Logging service restarted';
        $view->form = $form;
        $view->message = $message;
    }

    public function archivingAction()
    {

        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'archiving')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'archiving')->label;
        $request = $this->getRequest();

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form    = new Default_Form_Archiving($defaults);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('archiving', 'generalsettings'));
        $message = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $defaults);
                    $defaults->save();
                    $slaves = new Default_Model_Slave();
                    $message = $slaves->sendSoapToAll('Service_setServiceToRestart', ['exim_stage1', 'exim_stage4']);
                    if (preg_match('/^OK/', $message)) {
                        $message = "OK data saved";
                    }
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
}
