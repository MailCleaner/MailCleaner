<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for maintenance page
 */

class MonitormaintenanceController extends Zend_Controller_Action
{
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Monitoring')->class = 'menuselected';
    	$view->selectedMenu = 'Monitoring';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submonitor_Maintenance')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'Maintenance';
    }
    
    public function indexAction() {
  	
    }

}