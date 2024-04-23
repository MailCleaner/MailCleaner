<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for reporting page
 */

class MonitorreportingController extends Zend_Controller_Action
{
    protected function getSearchParams() {
		$request = $this->getRequest();
		$params = array();
		foreach (array('search', 'domain', 'fd', 'fm', 'td', 'tm', 'submit', 'sort', 'top') as $param) {
			$params[$param] = '';
			if ($request->getParam($param)) {
				$params[$param] = $request->getParam($param);
			}
		}
		
		if ($params['top'] == '') {
			$params['top'] = 10;
		}
		$what = '';
		if (isset($params['search'])) {
			if ($params['search'] == '') {
				$what = '*';
			} else {
				$what = escapeshellcmd($params['search']);
			}
			if (isset($params['domain']) && $params['domain'] != '') {
				$what .= "@".escapeshellcmd($params['domain']);
			}
		}
		$params['what'] = $what;
		
		$todateO = Zend_Date::now();
	    $fromdateO = Zend_Date::now();
        $fromdateO->sub('4', Zend_Date::DAY, Zend_Registry::get('Zend_Locale')->getLanguage());
        
        $todate = Zend_Locale_Format::getDate($todateO, array('date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()));      
        $fromdate = Zend_Locale_Format::getDate($fromdateO, array('date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()));
       
        foreach ( array('fd' => 'day', 'fm' => 'month', 'fy' => 'year') as $tk => $tv) {
        	if  (!isset($params[$tk]) || !$params[$tk]) {
        	    $params[$tk] = $fromdate[$tv];
            }
        }
        $params['ty'] = $todate['year'];
	    foreach ( array('td' => 'day', 'tm' => 'month', 'ty' => 'year') as $tk => $tv) {
	    	if  (!isset($params[$tk]) || !$params[$tk]) {
        	    $params[$tk] = $todate[$tv];
            }
        }
        $params['fy'] = $todate['year'];
        #if ($params['tm'] < $params['fm']) {
        #	$params['fy']--;
        #}

        if (intval($params['fm']) > $todateO->toValue(Zend_Date::MONTH) || 
             (intval($params['fm']) == $todateO->toValue(Zend_Date::MONTH) && intval($params['fd']) > $todateO->toValue(Zend_Date::DAY)) && 
             intval($params['fy']) >= $todateO->toValue(Zend_Date::YEAR)) {
            $params['fy']--;
        }
        if (intval($params['tm']) > $todateO->toValue(Zend_Date::MONTH) ||
             (intval($params['tm']) == $todateO->toValue(Zend_Date::MONTH) && intval($params['td']) > $todateO->toValue(Zend_Date::DAY)) && 
             intval($params['ty']) >= $todateO->toValue(Zend_Date::YEAR)) {
            $params['ty']--;
        }
        if ( intval($params['fy']) > intval($params['ty']) ||
             (intval($params['fy']) == intval($params['ty']) && intval($params['fm']) > intval($params['tm'])) ||
             (intval($params['fy']) == intval($params['ty']) && intval($params['fm']) == intval($params['tm']) && intval($params['fd']) > intval($params['td']))) {
               foreach (array('fy', 'fm', 'fd') as $key) {
                  $tmp[$key] = $params[$key];
               }
               foreach (array('y', 'm', 'd') as $key) {
                  $params['f'.$key] = $params['t'.$key];
                  $params['t'.$key] = $tmp['f'.$key];
               }
        }
        
        $params['datefrom'] = sprintf("%04d%02d%02d",$params['fy'],$params['fm'],$params['fd']);
        $params['dateto'] = sprintf("%04d%02d%02d",$params['ty'],$params['tm'],$params['td']);
        if ($params['datefrom'] > $params['dateto']) {
            $tmp = $params['dateto'];
            $params['dateto'] = $params['datefrom'];
            $params['datefrom'] = $tmp;
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
		$view->headLink()->appendStylesheet($view->css_path.'/stats.css');
        $view->headScript()->appendFile($view->scripts_path.'/baseconfig.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/reporting.js', 'text/javascript');

    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Monitoring')->class = 'menuselected';
    	$view->selectedMenu = 'Monitoring';
    	$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submonitor_Reporting')->class = 'submenuelselected';
    	$view->selectedSubMenu = 'Reporting';
    }
    
    public function indexAction() {
  	    $t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		 
		$request = $this->getRequest();
		$form    = new Default_Form_Reporting($this->getSearchParams());
		$form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorreporting'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorreporting', NULL, array());

		$view->form = $form;
    }

    public function searchAction() {
    	$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorreporting', NULL, array());
		 
		$request = $this->getRequest();
		 
		$loading = 1;
		if (! $request->getParam('load')) {
			sleep(1);
			$loading = 0;
		}
		$view->loading = $loading;
		$params = $this->getSearchParams();
		$view->params = $params;
		
        $session = new Zend_Session_Namespace('MailCleaner');
        
        $element = new Default_Model_ReportingStats();
        $elements = array();

		if (isset($params['submit']) && $params['submit'] && isset($session->search_id) && $session->search_id) {
			$params['search_id'] = $session->search_id;
			$elements = $element->abortFetchAll($params);
			$session->search_id = 0;
		}
		
        if (isset($session->search_id) && $session->search_id) {
			$params['search_id'] = $session->search_id;
			$res = $element->getStatusFetchAll($params);
			if (isset($res['error'])) {
				$element->abortFetchAll($params);
				$session->search_id = 0;
			}
            ## search running or finished, check status
            if (isset($res['finished']) && $res['finished']) {
                $elements = $element->fetchAll($params);
		        $view->loading = 0;
		        $element->setFromDate(sprintf('%04d%02d%02d', $params['fy'],$params['fm'],$params['fd']));
		        $element->setToDate(sprintf('%04d%02d%02d',$params['ty'],$params['tm'],$params['td']));
		        $view->fromdate = $element->getDate('from');
		        $view->todate = $element->getDate('to');
            } else {
		        $view->loading = 1;
            }
		} else {
			## no search running, launch search
	    	$search_id = $element->startFetchAll($params);
	    	if (! (is_array($search_id) && isset($search_id['error'])) ) {
     	    	$session->search_id = $search_id ;
	     	    $view->loading = 1;
	    	}
		}
		$view->elements = $elements;
            $view->global_users = $global_users;
    }
    
    public function graphAction() {
		$this->_helper->viewRenderer->setNoRender();
		
    	$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		
		$request = $this->getRequest();
		$graph = new Default_Model_RRDGraphic($request->getParam('h'));
		if ($request->getParam('t') != '') {
			if ($request->getParam('m')) {
    			$graph->find($request->getParam('t').'_'.$request->getParam('m'));
			} else {
				$graph->find($request->getParam('t').'_default');
			}
		} else {
    		$graph->find($request->getParam('g'));
		}
		$graph->setPeriod($request->getParam('p'));
		$graph->setHost($request->getParam('h'));
		$graph->setTitle();
		$graph->setLegend();
		$graph->stroke();
    }
}
