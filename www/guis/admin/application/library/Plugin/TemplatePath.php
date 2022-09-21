<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Setup base view variables
 */

class Plugin_TemplatePath extends Zend_Controller_Plugin_Abstract 
  {
  	public function preDispatch(Zend_Controller_Request_Abstract $request) 
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();

    	$baseuri = Zend_Controller_Front::getInstance()->getBaseUrl();
        $baseuri = preg_replace('/index\.php/', '', $baseuri);
    	
    	$template = Zend_Registry::get('default_template');
    	$view->template_dir = $baseuri.'/templates/'.$template;
        $view->scripts_path = $baseuri.'/js';
        $view->css_path = $view->template_dir."/css";
        $view->images_path = $view->template_dir.'/images';
    	$view->headLink()->appendStylesheet($view->css_path.'/global.css');
    	
    	$view->statusUrl = $view->url(array('action'=> 'quickstatus', 'controller' => 'status'));
        
        $view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
        $view->jQuery()->enable();
        $view->jQuery()->setLocalPath($view->scripts_path.'/jquery.min.js');
        $view->jQuery()->setUiLocalPath($view->scripts_path.'/jquery-ui.custom.min.js');
        $view->jQuery()->addStylesheet($view->css_path."/smoothness/jquery-ui.css");     
        
        $view->headScript()->appendFile($view->scripts_path.'/navigation.js', 'text/javascript'); 
        $view->headScript()->appendFile($view->scripts_path.'/quickstatus.js', 'text/javascript');
        
        $view->thisurl = $view->url(array('action' => $request->getActionName(), 'controller' => $request->getControllerName()));
        $view->indexurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('', 'index');
        
        $view->baseurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('', '');
        $view->baseurl = preg_replace('/index\.php/', '', $view->baseurl);
        $view->baseurl = preg_replace('/\/+$/', '', $view->baseurl);
    }
    
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
    	$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();  
        
        $view->headLink()->appendStylesheet($view->css_path.'/ie7.css', 'screen', 'lt IE 8'); 
        $view->headLink()->appendStylesheet($view->css_path.'/ie8.css', 'screen', 'gt IE 7'); 
    }
  	
  }
?>
