<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Main access management
 */

class Plugin_AdminAclManager extends Zend_Controller_Plugin_Abstract
{

    private $_defaultRole = 'guest';
    private $_authController = [
        'controller' => 'user',
        'action'     => 'login'
    ];

    public function __construct(Zend_Auth $auth)
    {
        $this->auth = $auth;
        $this->acl = new Zend_Acl();

        $this->acl->addRole(new Zend_Acl_Role($this->_defaultRole));
        $this->acl->addRole(new Zend_Acl_Role('hotline'), 'guest');
        $this->acl->addRole(new Zend_Acl_Role('manager'), 'hotline');
        $this->acl->addRole(new Zend_Acl_Role('administrator'), 'manager');

        $this->acl->add(new Zend_Acl_Resource('index'));
        $this->acl->add(new Zend_Acl_Resource('user'));
        $this->acl->add(new Zend_Acl_Resource('status'));
        $this->acl->add(new Zend_Acl_Resource('domain'));
        $this->acl->add(new Zend_Acl_Resource('baseconfiguration'));
        $this->acl->add(new Zend_Acl_Resource('generalsettings'));
        $this->acl->add(new Zend_Acl_Resource('smtp'));
        $this->acl->add(new Zend_Acl_Resource('antispam'));
        $this->acl->add(new Zend_Acl_Resource('contentprotection'));
        $this->acl->add(new Zend_Acl_Resource('accesses'));
        $this->acl->add(new Zend_Acl_Resource('services'));
        $this->acl->add(new Zend_Acl_Resource('cluster'));
        $this->acl->add(new Zend_Acl_Resource('pki'));

        $this->acl->add(new Zend_Acl_Resource('manageuser'));
        $this->acl->add(new Zend_Acl_Resource('managespamquarantine'));
        $this->acl->add(new Zend_Acl_Resource('managecontentquarantine'));
        $this->acl->add(new Zend_Acl_Resource('managetracing'));

        $this->acl->add(new Zend_Acl_Resource('monitorreporting'));
        $this->acl->add(new Zend_Acl_Resource('monitorlogs'));
        $this->acl->add(new Zend_Acl_Resource('monitormaintenance'));
        $this->acl->add(new Zend_Acl_Resource('monitorstatus'));

        /** main menus **/
        $this->acl->add(new Zend_Acl_Resource('Menu_Configuration'));
        $this->acl->add(new Zend_Acl_Resource('Menu_Management'));
        $this->acl->add(new Zend_Acl_Resource('Menu_Monitoring'));

        $sysconf = MailCleaner_Config::getInstance();

        $this->acl->deny();
        $this->acl->allow('guest', 'user', ['login', 'logout']);
        $this->acl->allow('administrator', 'baseconfiguration');
        $this->acl->allow('hotline', 'index');

        $this->acl->allow('manager', 'Menu_Configuration');

        if ($sysconf->getOption('ISMASTER') == 'Y') {

            $this->acl->allow('hotline', 'user');
            $this->acl->allow('hotline', 'index');
            $this->acl->allow('hotline', 'status');

            $this->acl->allow('manager', 'domain');
            $this->acl->allow('manager', 'pki');
            $this->acl->allow('administrator', 'generalsettings');
            $this->acl->allow('administrator', 'smtp');
            $this->acl->allow('administrator', 'antispam');
            $this->acl->allow('administrator', 'contentprotection');
            $this->acl->allow('administrator', 'accesses');
            $this->acl->allow('administrator', 'services');
            $this->acl->allow('administrator', 'cluster');
            $this->acl->allow('administrator', 'monitorlogs');
            $this->acl->allow('administrator', 'monitormaintenance');
            $this->acl->allow('administrator', 'monitorstatus');

            $this->acl->allow('hotline', 'manageuser');
            $this->acl->allow('hotline', 'managespamquarantine');
            $this->acl->allow('hotline', 'managecontentquarantine');
            $this->acl->allow('hotline', 'managetracing');

            $this->acl->allow('hotline', 'monitorreporting');
            #$this->acl->allow('hotline', 'monitorlogs');
            #$this->acl->allow('hotline', 'monitormaintenance');
            #$this->acl->allow('hotline', 'monitorstatus');

            $this->acl->allow('hotline', 'Menu_Monitoring');
            $this->acl->allow('hotline', 'Menu_Management');
        }

        Zend_Registry::set('acl', $this->acl);
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->auth->hasIdentity() && Zend_Registry::get('user')) {
            $user = Zend_Registry::get('user');
            $role = $user->getUserType();
        } else {
            $role = $this->_defaultRole;
        }

        if (!$this->acl->hasRole($role)) {
            $role = $this->_defaultRole;
        }

        $resource = $request->controller;
        $privilege = $request->action;

        if (!$this->acl->has($resource)) {
            $resource = null;
        }

        if (!$this->acl->isAllowed($role, $resource, $privilege)) {
            $request->setControllerName($this->_authController['controller']);
            $request->setActionName($this->_authController['action']);
        }
    }
}
