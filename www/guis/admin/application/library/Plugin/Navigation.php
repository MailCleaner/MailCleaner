<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Interface menu managment
 */

class Plugin_Navigation extends Zend_Controller_Plugin_Abstract
{
    protected $_acl;
    protected $_role;

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $t = Zend_Registry::get('translate');
        $main_menus_defs = [
            'Configuration' => ['controller' => 'baseconfiguration'],
            'Management' => ['controller' => 'manageuser'],
            'Monitoring' => ['controller' => 'monitorreporting']
        ];

        $main_menu = new Zend_Navigation();
        $main_menus = [];

        $role = 'guest';
        try {
            $this->_role = Zend_Registry::get('user')->getUserType();
            $this->_acl = Zend_Registry::get('acl');
        } catch (Exception $e) {
            return;
        }

        foreach ($main_menus_defs as $mk => $m) {
            if (!$this->_acl->isAllowed($this->_role, 'Menu_' . $mk)) {
                continue;
            }
            $page = new Zend_Navigation_Page_Mvc([
                'label' => $t->_($mk),
                'id' => "$mk",
                'action' => '',
                'controller' => $m['controller'],
                'class' => 'menubutton'
            ]);
            $main_menu->addPage($page);
        }

        if ($main_menu->findOneBy('id', 'Configuration')) {
            $this->setupConfigurationMenu($main_menu->findOneBy('id', 'Configuration'));
        }
        if ($main_menu->findOneBy('id', 'Management')) {
            $this->setupManagementMenu($main_menu->findOneBy('id', 'Management'));
        }
        if ($main_menu->findOneBy('id', 'Monitoring')) {
            $this->setupMonitoringMenu($main_menu->findOneBy('id', 'Monitoring'));
        }

        Zend_Registry::set('main_menu', $main_menu);

        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $view->main_menu = $main_menu;

        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('url');
        $view->logoutLink = $helper->simple('logout', 'user');
    }

    protected function setupConfigurationMenu($nav)
    {
        $t = Zend_Registry::get('translate');
        $config_menus_defs = [
            'BaseSystem' => ['controller' => 'baseconfiguration', 'action' => 'networksettings'],
            'GeneralSettings' => ['controller' => 'generalsettings', 'action' => 'defaults'],
            'Domains' => ['controller' => 'domain', 'action' => ''],
            'SMTP' => ['controller' => 'smtp', 'action' => 'smtpchecks'],
            'AntiSpam' => ['controller' => 'antispam', 'action' => 'globalsettings'],
            'ContentProtection' => ['controller' => 'contentprotection', 'action' => 'globalsettings'],
            'Accesses' => ['controller' => 'accesses', 'action' => ''],
            'Services' => ['controller' => 'services', 'action' => 'httpd'],
        ];

        foreach ($config_menus_defs as $mk => $m) {
            if (!$this->_acl->isAllowed($this->_role, $m['controller'])) {
                continue;
            }
            $page = new Zend_Navigation_Page_Mvc([
                'label' => $t->_($mk),
                'id' => "subconfig_$mk",
                'action' => $m['action'],
                'controller' => $m['controller'],
                'class' => 'submenubutton'
            ]);
            $nav->addPage($page);
        }
    }

    protected function setupManagementMenu($nav)
    {
        $t = Zend_Registry::get('translate');
        $manage_menus_defs = [
            'Users' => ['controller' => 'manageuser', 'action' => ''],
            'SpamQuarantine' => ['controller' => 'managespamquarantine', 'action' => ''],
            'ContentQuarantine' => ['controller' => 'managecontentquarantine', 'action' => ''],
            'Tracing' => ['controller' => 'managetracing', 'action' => '']
        ];
        foreach ($manage_menus_defs as $mk => $m) {
            $page = new Zend_Navigation_Page_Mvc([
                'label' => $t->_($mk),
                'id' => "submanage_$mk",
                'action' => '',
                'controller' => $m['controller'],
                'class' => 'submenubutton'
            ]);
            $nav->addPage($page);
        }
    }

    protected function setupMonitoringMenu($nav)
    {
        $t = Zend_Registry::get('translate');
        $monitor_menus_defs = [
            'Reporting' => ['controller' => 'monitorreporting', 'action' => ''],
            'Logs' => ['controller' => 'monitorlogs', 'action' => ''],
            #'Maintenance' => ['controller' => 'monitormaintenance', 'action' => ''],
            'Status' => ['controller' => 'monitorstatus', 'action' => '']
        ];
        foreach ($monitor_menus_defs as $mk => $m) {
            if (!$this->_acl->isAllowed($this->_role, $m['controller'])) {
                continue;
            }
            $page = new Zend_Navigation_Page_Mvc([
                'label' => $t->_($mk),
                'id' => "submonitor_$mk",
                'action' => '',
                'controller' => $m['controller'],
                'class' => 'submenubutton'
            ]);
            $nav->addPage($page);
        }
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $view->message = preg_replace("/'/", '\\\'', $view->message);
        $view->message = preg_replace("/\n/", '<br />', $view->message);
    }
}
