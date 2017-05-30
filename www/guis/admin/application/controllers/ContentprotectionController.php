<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for content protection settings
 */

class ContentprotectionController extends Zend_Controller_Action
{
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Configuration')->class = 'menuselected';
    	$view->selectedMenu = 'Configuration';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_ContentProtection')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'ContentProtection';
    	$view->selectedSubMenuLabel = Zend_Registry::get('main_menu')->findOneBy('id', 'subconfig_ContentProtection')->label;
    	
    	$this->config_menu = new Zend_Navigation();
    	
    	$this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Global settings', 'id' => 'globalsettings', 'action' => 'globalsettings', 'controller' => 'contentprotection')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Anti-virus', 'id' => 'antivirus', 'action' => 'antivirus', 'controller' => 'contentprotection')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'HTML controls', 'id' => 'htmlcontrols', 'action' => 'htmlcontrols', 'controller' => 'contentprotection')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Message format controls', 'id' => 'messageformat', 'action' => 'messageformat', 'controller' => 'contentprotection')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Attachment name', 'id' => 'filename', 'action' => 'filename', 'controller' => 'contentprotection')));
        $this->config_menu->addPage(new Zend_Navigation_Page_Mvc(array('label' => 'Attachment type', 'id' => 'filetype', 'action' => 'filetype', 'controller' => 'contentprotection')));
        $view->config_menu = $this->config_menu;
        
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/antivirusconfig.js', 'text/javascript');
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        
        $module = new Default_Model_AntispamModule();
        $modules = $module->fetchAll();
        $view->modules = $modules;
    }
    
    public function indexAction() {
  	
    }
    
    public function globalsettingsAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'globalsettings')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'globalsettings')->label;
    	$request = $this->getRequest();
    	
    	$av = new Default_Model_AntivirusConfig();
    	$av->find(1);
    	
    	$form    = new Default_Form_AntivirusGlobalSettings($av);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('globalsettings', 'contentprotection'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	
            	try {
                  $form->setParams($this->getRequest(), $av);
            	  $av->save();
            	  $message = 'OK data saved';
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function antivirusAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'antivirus')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'antivirus')->label;
    	$request = $this->getRequest();
    	
    	$av = new Default_Model_AntivirusConfig();
    	$av->find(1);
    	
    	$form    = new Default_Form_ContentAntivirus($av);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('antivirus', 'contentprotection'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	
            	try {
                  $form->setParams($this->getRequest(), $av);
            	  $av->save();
            	  $message = 'OK data saved';
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function htmlcontrolsAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'htmlcontrols')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'htmlcontrols')->label;
    	$request = $this->getRequest();
    	
    	$dc = new Default_Model_DangerousContent();
    	$dc->find(1);
    	
    	$form    = new Default_Form_ContentHTMLControls($dc);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('htmlcontrols', 'contentprotection'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	
            	try {
                  $form->setParams($this->getRequest(), $dc);
            	  $message = 'OK data saved';
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function messageformatAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'messageformat')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'messageformat')->label;
    	$request = $this->getRequest();
    	
    	$dc = new Default_Model_DangerousContent();
    	$dc->find(1);
    	
    	$form    = new Default_Form_ContentMessageFormat($dc);
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('messageformat', 'contentprotection'));
        $message = '';
        
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
            	
            	try {
                  $form->setParams($this->getRequest(), $dc);
            	  $message = 'OK data saved';
            	} catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
            	}
            } else {
            	$message = "NOK bad settings";
            }
        }
        
        $view->form = $form;
    	$view->message = $message;
    }
    
    public function filenameAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'filename')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'filename')->label;
    	$request = $this->getRequest();
    	
    	$filename = new Default_Model_FileName();
    	$filenames = $filename->fetchAll();
    	
    	$view->filenames = $filenames;
    	
    	$listform    = new Default_Form_ElementList($filenames, 'Default_Model_FileName');
    	$listform->setAttrib('id', 'contentfilename_form');
        $listform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('filename', 'contentprotection'));
        $message = '';
    	
        if ($request->isPost()) {
        	if ($listform->isValid($request->getPost())) {
              try {
              	$listform->manageRequest($request);
        		$message = 'OK data saved';
        		$filenames = $filename->fetchAll();
    	        $view->filenames = $filenames;
              } catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
              }
               	       
        	}
        }
        $view->listform = $listform;
        $view->message = $message;
    }
    
    public function filetypeAction() {
    	$t = Zend_Registry::get('translate');
    	$this->config_menu->findOneBy('id', 'filetype')->class = 'generalconfigmenuselected';
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->selectedConfigMenuLabel = $this->config_menu->findOneBy('id', 'filetype')->label;
    	$request = $this->getRequest();
    	
    	$filetype = new Default_Model_FileType();
    	$filetypes = $filetype->fetchAll();
    	
    	$view->filetypes = $filetypes;
    	
    	$listform    = new Default_Form_ElementList($filetypes, 'Default_Model_FileType');
        $listform->setAttrib('id', 'contentfiletype_form');
        $listform->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('filetype', 'contentprotection'));
        $message = '';
    	
        if ($request->isPost()) {
        	if ($listform->isValid($request->getPost())) {
              try {
              	$listform->manageRequest($request);
        		$message = 'OK data saved';
        		$filetypes = $filetype->fetchAll();
    	        $view->filetype = $filetypes;
              } catch (Exception $e) {
            	  $message = 'NOK error saving data ('.$e->getMessage().')';
              }
               	       
        	}
        }
        $view->listform = $listform;
        $view->message = $message;
    }

}
