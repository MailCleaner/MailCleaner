<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for services
 */

class ServicesController extends Zend_Controller_Action
{
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
        $view->selectedMenu = 'Configuration';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_Services')->class = 'submenuelselected';
        $view->selectedSubMenu = 'Services';

        $this->config_menu = new Zend_Navigation();

        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Web interfaces', 'id' => 'webinterfaces', 'action' => 'httpd', 'controller' => 'services']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'SNMP monitoring', 'id' => 'snmp', 'action' => 'snmpd', 'controller' => 'services']));
        # $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Console access', 'id' => 'console', 'action' => 'sshd', 'controller' => 'services']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'Database', 'id' => 'database', 'action' => 'database', 'controller' => 'services']));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(['label' => 'API', 'id' => 'api', 'action' => 'api', 'controller' => 'services']));
        $view->config_menu = $this->config_menu;

        $view->headScript()->appendFile($view->scripts_path . '/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path . '/servicesconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path . '/tooltip.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    }

    public function indexAction()
    {
    }

    public function httpdAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'webinterfaces')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'webinterfaces')->label;
        $request = $this->getRequest();
        $message = '';

        $httpdconfig = new Default_Model_HttpdConfig();
        $httpdconfig->find(1);

        $fwrule = new Default_Model_FirewallRule();
        $fwrule->findByService('web');

        if ($httpdconfig->getParam('use_ssl') == 'false') {
            $view->ssl_display_class = 'hidden';
        }

        $cert = new Default_Model_PKI();
        $cert->setCertificate($httpdconfig->getParam('tls_certificate_data'));
        $cert->setPrivateKey($httpdconfig->getParam('tls_certificate_key'));
        $certdata = $cert->getCertificateData();

        $form = new Default_Form_Httpd($httpdconfig, $fwrule);

        if ($request->isPost()) {
            $cert->setCertificate($request->getParam('tls_certificate_data'));
            $cert->setPrivateKey($request->getParam('tls_certificate_key'));
            $certdata = $cert->getCertificateData();

            if ($form->isValidPartial($request->getPost())) {
                try {
                    $form->setParams($request, $httpdconfig, $fwrule);
                    if ($httpdconfig->getParam('use_ssl') == 'true') {
                        $view->ssl_display_class = '';
                    } else {
                        $view->ssl_display_class = 'hidden';
                    }

                    $message = 'OK data saved';
                    $slaves = new Default_Model_Slave();
                    $slaves->sendSoapToAll('Service_setServiceToRestart', ['apache', 'firewall']);
                } catch (Exception $e) {
                    $view->ssl_display_class = '';
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            } else {
                if ($request->getParam('use_ssl')) {
                    $view->ssl_display_class = '';
                } else {
                    $view->ssl_display_class = 'hidden';
                }
                $message = "NOK bad settings";
            }
        }

        $view->form = $form;
        $view->message = $message;
        $view->certdata = $certdata;
    }

    public function snmpdAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'snmp')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'snmp')->label;
        $request = $this->getRequest();

        $message = '';

        $snmpdconfig = new Default_Model_SnmpdConfig();
        $snmpdconfig->find(1);

        $fwrule = new Default_Model_FirewallRule();
        $fwrule->findByService('snmp');
        $fwrule->setParam('service', 'snmp');
        $fwrule->setParam('port', 161);

        $form = new Default_Form_Snmpd($snmpdconfig, $fwrule);

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $snmpdconfig, $fwrule);
                    $message = 'OK data saved';
                    $slaves = new Default_Model_Slave();
                    $slaves->sendSoapToAll('Service_setServiceToRestart', ['snmpd', 'firewall']);
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            }
        }

        $view->form = $form;
        $view->message = $message;
    }

    public function sshdAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'console')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'console')->label;
        $request = $this->getRequest();
    }

    public function databaseAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'database')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'database')->label;
        $request = $this->getRequest();

        $message = '';

        $fwrule = new Default_Model_FirewallRule();
        $fwrule->findByService('database');
        $fwrule->setParam('service', 'database');
        $fwrule->setParam('port', 3306);

        $form = new Default_Form_Database($fwrule);

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $fwrule);
                    $message = 'OK data saved';
                    $slaves = new Default_Model_Slave();
                    $slaves->sendSoapToAll('Service_setServiceToRestart', ['firewall']);
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            }
        }

        $view->form = $form;
        $view->message = $message;
    }

    public function apiAction()
    {
        $t = Zend_Registry::get('translate');
        $this->config_menu->findOneBy('id', 'api')->class = 'generalconfigmenuselected';
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'api')->label;
        $request = $this->getRequest();

        $message = '';

        $defaults = new Default_Model_SystemConf();
        $defaults->load();

        $form = new Default_Form_Api($defaults);

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $form->setParams($request, $defaults);
                    $message = 'OK data saved';
                } catch (Exception $e) {
                    $message = 'NOK error saving data (' . $e->getMessage() . ')';
                }
            }
        }

        $view->form = $form;
        $view->message = $message;
    }
}
