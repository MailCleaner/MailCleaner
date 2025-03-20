<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for cluster configuration
 */

class ClusterController extends Zend_Controller_Action
{
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
        $view->selectedMenu = 'Configuration';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_Cluster')->class = 'submenuelselected';
        $view->selectedSubMenu = 'Cluster';
    }

    public function indexAction()
    {
    }
}

