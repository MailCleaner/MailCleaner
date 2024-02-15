<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for content quarantine page
 */

class ManagecontentquarantineController extends Zend_Controller_Action
{
	protected function getSearchParams() {
		$request = $this->getRequest();
		$params = array();
		foreach (array('search', 'domain', 'sender', 'subject', 'mpp', 'page', 'sort', 'fd', 'fm', 'td', 'tm', 'reference') as $param) {
			$params[$param] = '';
			if ($request->getParam($param)) {
				$params[$param] = $request->getParam($param);
			}
		}
		if (isset($params['sort']) && preg_match('/(\S+)_(asc|desc)/', $params['sort'], $matches)) {
			$order = $params['sort'];
			$params['orderfield'] = $matches[1];
			$params['orderorder'] = $matches[2];
		}
		
		$todateO = Zend_Date::now();
	    $fromdateO = Zend_Date::now();
        $fromdateO->sub('1', Zend_Date::DAY, Zend_Registry::get('Zend_Locale')->getLanguage());
        
        $todate = Zend_Locale_Format::getDate($todateO, array('date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()));      
        $fromdate = Zend_Locale_Format::getDate($fromdateO, array('date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()));
       
        
        foreach ( array('fd' => 'day', 'fm' => 'month') as $tk => $tv) {
        	if  (!isset($params[$tk]) || !$params[$tk]) {
        	    $params[$tk] = $fromdate[$tv];
            }
        }
        $params['ty'] = $todate['year'];
	    foreach ( array('td' => 'day', 'tm' => 'month') as $tk => $tv) {
	    	if  (!isset($params[$tk]) || !$params[$tk]) {
        	    $params[$tk] = $todate[$tv];
            }
        }
        $params['fy'] = $todate['year'];
        if ($params['tm'] < $params['fm']) {
        	$params['fy']--;
        }
        
		return $params;
	}
	
    public function init()
    {
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	$view->headLink()->appendStylesheet($view->css_path.'/main.css');
    	$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');
		$view->headLink()->appendStylesheet($view->css_path.'/quarantine.css');
		$view->headScript()->appendFile($view->scripts_path.'/quarantine.js', 'text/javascript');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Management')->class = 'menuselected';
    	$view->selectedMenu = 'Management';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submanage_ContentQuarantine')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'ContentQuarantine';
    }
    
    public function indexAction() {
  	    $t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		 
		$request = $this->getRequest();
		$form    = new Default_Form_ContentQuarantine($this->getSearchParams());
		$form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine', NULL, array());

		$view->form = $form;
    }
    
    public function searchAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine', NULL, array());
		 
		$request = $this->getRequest();
		 
		$loading = 1;
		if (! $request->getParam('load')) {
			sleep(1);
			$loading = 0;
		}
		$view->loading = $loading;
		$view->params = $this->getSearchParams();
		 
		$nbelements = 0;
		$orderfield = 'date';
		$orderorder = 'desc';
		$nbpages = 0;
		$page = 0;
		$elements = array();
		 
		$columns = array(
    	  'caction' => array('label' => 'Action'),
    	  'date' => array('label' => 'Date', 'label2' => 'date', 'order' => 'desc'),
    	  'to' => array('label' => 'Recipient', 'label2' => 'recipient', 'order' => 'desc'),
    	  'from' => array('label' => 'Sender', 'label2' => 'sender', 'order' => 'desc'),
    	  'subject' => array('label' => 'Subject', 'label2' => 'subject', 'order' => 'desc'),
    	  'content' => array('label' => 'Content')
		);
		if ($request->getParam('sort') && preg_match('/(\S+)_(asc|desc)/', $request->getParam('sort'), $matches)) {
			$order = $request->getParam('sort');
			$orderfield = $matches[1];
			$orderorder = $matches[2];
			if (isset($columns[$orderfield])) {
				$columns[$orderfield]['order'] = $orderorder;
			}
		}
		
		if ($request->getParam('domain') != "" || $request->getParam('reference') != "") {
			$elements = array();
			$nbelements = 0;
			$element = new Default_Model_QuarantinedContent();
			$params = $this->getSearchParams();
			#$nbelements = $element->fetchAllCount($params);
			 
			#if ($nbelements > 0) {
				$elements = $element->fetchAll($params);
				$nbelements = $element->fetchAllCount();
				$nbpages = $element->getNbPages();
				$page = $element->getEffectivePage();
			#}
		}
		$view->page = $page;
		$view->elements = $elements;
		 
		$view->columns = $columns;
		$view->nbelements = $nbelements;
		$view->orderfield = $orderfield;
		$view->page = $page;
		$view->nbpages = $nbpages;
	}

	public function forceAction() {
  	    $t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine', NULL, array());
		 
		$view->headLink()->appendStylesheet($view->css_path.'/popup.css');

		$view->release_status = '';
		$request = $this->getRequest();
		
		$id = '';
		if ($request->getParam('id') && preg_match('/^\d{8}-[a-zA-Z0-9]{6}-[a-zA-Z0-9]{6,11}-[a-zA-Z0-9]{2,4}$/', $request->getParam('id'))) {
			$id = $request->getParam('id');
			$id = preg_replace('/-/', '/', $id, 1);
		}
		$s = '';
		if ($request->getParam('s') && is_numeric($request->getParam('s'))) {
			$s = $request->getParam('s');
		}
		
		if ($id == '') {
			$view->release_status = $t->_('Bad message id');
		}
		if ($s == '') {
			$view->release_status = $t->_('Bad parameters');
		}
		
		$slave = new Default_Model_Slave();
		$slave->find($s);
		$res = $slave->sendSoapRequest('Content_release', array('id' => $id, 'soap_timeout' => 40));
		if ($res['status'] == 1) {
            	        $view->release_status = $t->_('Message released');
		} else {
			$view->release_status = $t->_('Message could not be released')." (".$t->_($res['message']).")";
		}
	}
	
	public function viewAction() {
  	    $t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine', NULL, array());
		$view->headLink()->appendStylesheet($view->css_path.'/messageview.css');
		$view->headScript()->appendFile($view->scripts_path.'/messageview.js', 'text/javascript');
				
		$request = $this->getRequest();
		
	        $id = '';
		if ($request->getParam('id') && preg_match('/^\d{8}-[a-zA-Z0-9]{6}-[a-zA-Z0-9]{6,11}-[a-zA-Z0-9]{2,4}$/', $request->getParam('id'))) {
			$id = $request->getParam('id');
			$id = preg_replace('/-/', '/', $id, 1);
		}
		$s = '';
		if ($request->getParam('s') && is_numeric($request->getParam('s'))) {
			$s = $request->getParam('s');
		}
		
		if ($id == '') {
			$view->release_status = $t->_('Bad message id');
		}
		if ($s == '') {
			$view->release_status = $t->_('Bad parameters');
		}
		
		$slave = new Default_Model_Slave();
		$slave->find($s);
		$res = $slave->sendSoapRequest('Content_find', array('id' => $id));
		if (! isset($res['error'])) {
			## fill fields
			$msg = new Default_Model_QuarantinedContent();
			foreach ($res as $key => $value) {
				$msg->setParam($key, $value);
			}
			$view->msg = $msg;
			
			$msg->setParam('store_id', $s);
			$view->infos = $res;
			$view->msgid = $id;
			$view->umsgid = preg_replace('/\//', '-', $id, 1);
			$view->headers = Default_Model_QuarantinedContent::parseHeaders($res['headers']);
		} else {
			$view->release_status = $t->_('Message could not be loaded')." (".$t->_($res['error']).")";
		}
	}
}
