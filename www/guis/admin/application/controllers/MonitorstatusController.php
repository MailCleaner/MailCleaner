<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * controller for status pages
 */

class MonitorstatusController extends Zend_Controller_Action
{
	protected $_columns = array('messages', 'load', 'disks', 'memory', 'spools', 'processes');
    protected $_statscachefile = '/tmp/host.stat.cache';
    
	public function init()
	{
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$view->headLink()->appendStylesheet($view->css_path.'/main.css');
		$view->headLink()->appendStylesheet($view->css_path.'/navigation.css');
		$view->headLink()->appendStylesheet($view->css_path.'/status.css');
        $view->headScript()->appendFile($view->scripts_path.'/status.js', 'text/javascript');

		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Monitoring')->class = 'menuselected';
		$view->selectedMenu = 'Monitoring';
		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submonitor_Status')->class = 'submenuelselected';
		$view->selectedSubMenu = 'Status';
	}

	public function indexAction() {
			
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();

		$slave = new Default_Model_Slave();
		$slaves = $slave->fetchAll();

		$view->slaves = $slaves;

		$view->pieLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('todaypie', 'monitorstatus', NULL, array());
		$view->columns = $this->_columns;
		$config = new MailCleaner_Config();
		$view->quarantinedir = $config->getOption('VARDIR')."/spam";
    	$view->initial_loading = 1;
	    $request = $this->getRequest();
		if ($request->getParam('r') == '') {
			$salt = uniqid();
		} else {
			$salt = $request->getParam('r');
		}
		$view->salt = $salt;
	}

	public function todaypieAction() {
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();

		$request = $this->getRequest();
		if ($request->getParam('s') == '') {
			return;
		}
                $stats_type = $request->getParam('t');
                if (!isset($stats_type) || !$stats_type || $stats_type == '') {
                    $stats_type = 'global';
                }


		$slave = new Default_Model_Slave();
		$slave->find($request->getparam('s'));
        
		$reporting = new Default_Model_ReportingStats();
		$what = array();
		$what['stats'] = $reporting->getTodayStatElements($stats_type);
                $usecache = 1;
                $graph_params = array();
                if ($request->getparam('gs') && is_numeric($request->getparam('gs'))) {
                   $graph_params['size'] = array($request->getparam('gs'), $request->getparam('gs'));
                }
                if ($request->getparam('gr') && is_numeric($request->getparam('gr'))) {
                   $graph_params['radius'] = $request->getparam('gr');
                }
		return $reporting->getTodayPie($what, $slave->getId(), $usecache, $stats_type, $graph_params);
	}

	public function restartserviceAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
			
		$status = 0;
		$slaveid = 0;
		$process = '';
		$request = $this->getRequest();
		if (is_numeric($request->getParam('s')) && $request->getParam('p') != '' && $request->getParam('a') != '') {

			$process = $request->getParam('p');
			$slaveid = $request->getParam('s');

			$slave = new Default_Model_Slave();
			$slave->find($slaveid);

			$res = $slave->sendSoapRequest('Service_silentStopStart', array('service' => $process, 'action' => $request->getParam('a'), 'soap_timeout' => 100));
			#var_dump($res);
			#sleep(3);

			$processes = $slave->sendSoap('Status_getProcessesStatus');
			if (isset($processes[$process])) {
				$status = $processes[$process];
			}
		}
		$view->slaveid = $slaveid;
		$view->process = $process;
		$view->status = $status;
	}

	public function showprocessAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
			
		$status = 0;
		$slaveid = 0;
		$process = '';
		$request = $this->getRequest();
		if (is_numeric($request->getParam('s')) && $request->getParam('p') != '') {
			$process = $request->getParam('p');
			$slaveid = $request->getParam('s');

			$slave = new Default_Model_Slave();
			$slave->find($slaveid);

			$processes = $slave->sendSoap('Status_getProcessesStatus');
			if (isset($processes[$process])) {
				$status = $processes[$process];
			}
		}
		$view->slaveid = $slaveid;
		$view->process = $process;
		$view->status = $status;
	}

	public function viewspoolAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->headLink()->appendStylesheet($view->css_path.'/quarantine.css');
		$view->headScript()->setFile($view->scripts_path.'/spools.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path.'/tooltip.js', 'text/javascript');

		$request = $this->getRequest();
		if ($request->isXmlHttpRequest()) {
			$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		}

		$slave = new Default_Model_Slave();
		$spool=1;
		$msgs = array();
		$nbmsgs = 0;
		$page = 1;
		$pages = 1;
		$limit = 25;
		$offset = 0;

		if (is_numeric($request->getParam('offset'))) {
			$offset = $request->getParam('offset');
		}
		if (is_numeric($request->getParam('limit'))) {
			$limit = $request->getParam('limit');
		}
		if (is_numeric($request->getParam('slave')) && is_numeric($request->getParam('spool')) ) {
			$slaveid = $request->getParam('slave');
			$spool = $request->getParam('spool');

			$slave->find($slaveid);
			
			$params = array('limit' => $limit, 'offset' => $offset, 'spool' => $spool);
			if ($request->isXmlHttpRequest()) {
				$call_res = $slave->getSpool($spool, $params);
				if (isset($call_res['msgs'])) {
					$msgs = $call_res['msgs'];
					$nbmsgs = $call_res['nbmsgs'];
					$page = $call_res['page'];
					$pages = $call_res['pages'];
				}
			}
		}

		$view->slave = $slave;
		$view->spool = $spool;
		$view->msgs = $msgs;
		$view->nbmsgs = $nbmsgs;
		$view->pages = $pages;
		$view->page = $page;
		$view->nextoffset = -1;
		if ($page*$limit < $nbmsgs) {
			$view->nextoffset = ($page)*$limit;
		}
		$view->prevoffset = -1;
		if ($page > 1) {
			$view->prevoffset = ($page - 2)*$limit;
		}
		
		$spools = array(1 => 'incoming', 2 => 'filtering', 4 => 'outgoing');
		$t = Zend_Registry::get('translate');
        $view->headTitle($t->_('Spool view')." - ".$slave->getId()." (".$slave->getHostname().") - ".$t->_($spools[$spool]));
	}
	
	public function spooldeleteAction() {
		$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $layout->disableLayout();
		
        $slave = new Default_Model_Slave();
        $spool=1;
        require_once('Validate/MessageID.php');
        $msgvalidator = new Validate_MessageID();
        
        $request = $this->getRequest();
        if (is_numeric($request->getParam('slave')) && is_numeric($request->getParam('spool')) && $msgvalidator->isValid($request->getParam('msg'))) {
        	$slaveid = $request->getParam('slave');
            $spool = $request->getParam('spool');

            $slave->find($slaveid);
            $res = $slave->deleteSpoolMessage($spool, $request->getParam('msg'));
        }
	}
	
	public function spooltryAction() {
		$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $layout->disableLayout();
        
        $slave = new Default_Model_Slave();
        $spool=1;
        require_once('Validate/MessageID.php');
        $msgvalidator = new Validate_MessageID();
        
        $request = $this->getRequest();
        if (is_numeric($request->getParam('slave')) && is_numeric($request->getParam('spool')) && $msgvalidator->isValid($request->getParam('msg')) ) {
        	$slaveid = $request->getParam('slave');
            $spool = $request->getParam('spool');

            $slave->find($slaveid);
            $res = $slave->trySpoolMessage($spool, $request->getParam('msg'));
        }
	}
	
	public function hoststatusAction() {
		$layout = Zend_Layout::getMvcInstance();
        $view=$layout->getView();
        $layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
        
    	$request = $this->getRequest();
    	
    	$slaveid = 1;
    	if (is_numeric($request->getParam('slave'))) {
    		$slaveid = $request->getParam('slave');
    	}
    	$slave = new Default_Model_Slave();
    	$slave->find($slaveid);
    	$view->pieLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('todaypie', 'monitorstatus', NULL, array());
		$view->graphBaseLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('graph', 'monitorreporting', NULL, array());
    	$view->slave = $slave;
		$view->columns = $this->_columns;
		$config = new MailCleaner_Config();
		$view->quarantinedir = $config->getOption('VARDIR')."/spam";
    	$view->initial_loading = 0;

    	$reporting = new Default_Model_ReportingStats();
    	$salt = uniqid();
		$view->salt = $salt;
		
		$morecontent = array(
                 'messages' => array('type' => array('global', 'sessions', 'accepted', 'refused', 'relayed'), 'selected_type' => 'global',
                                     'mode' => array('count', 'frequency'), 'selected_mode' => 'count'),
                 'load'     => array('type' => array('load', 'cpu'), 'selected_type' => 'load'),
                 'disk'     => array('type' => array('disks', 'io', 'network'), 'selected_type' => 'disks'),
                 'memory'   => array('type' => array('memory'), 'selected_type' => 'memory'),
                 'spools'   => array('type' => array('spools'), 'selected_type' => 'spools')
        );
        $available_periods = array('hour', 'day', 'week', 'month', 'year');
        $view->periods = $available_periods;
        foreach ($morecontent as $cname => $c) {
        	if (!isset($morecontent[$cname]['selected_period'])) {
            	$morecontent[$cname]['selected_period'] = 'day';
        	}
        	if (!isset($morecontent[$cname]['selected_mode'])) {
        		$morecontent[$cname]['selected_mode'] = 'count';
        	}
        }
        $types = preg_split('/,/', $request->getParam('t'));
        foreach ($types as $type) {
        	if (preg_match('/^([a-z0-9]+)_([a-z0-9]+)/', $type, $matches)) {
               $s_col = $matches[1];
               $s_type = $matches[2];
               if (isset($morecontent[$s_col]) && in_array($s_type, $morecontent[$s_col]['type']))  {
               	  $morecontent[$s_col]['selected_type'] = $s_type;
               }     		
        	}
        }
		$stats_type = $morecontent['messages']['selected_type'];
		
		$modes = preg_split('/,/', $request->getParam('m'));
        foreach ($modes as $mode) {
        	if (preg_match('/^([a-z0-9]+)_([a-z0-9]+)/', $mode, $matches)) {
               $s_col = $matches[1];
               $s_mode = $matches[2];
               if (isset($morecontent[$s_col]) && in_array($s_mode, $morecontent[$s_col]['mode']))  {
               	  $morecontent[$s_col]['selected_mode'] = $s_mode;
               }     		
        	}
        }
		$stats_mode = $morecontent['messages']['selected_mode'];

		$periods = preg_split('/,/', $request->getParam('p'));
        foreach ($periods as $period) {
        	if (preg_match('/^([a-z0-9]+)_([a-z0-9]+)/', $period, $matches)) {
               $s_col = $matches[1];
               $s_period = $matches[2];
               if (isset($morecontent[$s_col]) && in_array($s_period, $available_periods))  {
               	  $morecontent[$s_col]['selected_period'] = $s_period;
               }     		
        	}
        }
		$stats_period = $morecontent['messages']['selected_period'];
        $what = array();
	    $what['stats'] = $reporting->getTodayStatElements($stats_type);
        $data = $reporting->getTodayValues($what, $slaveid, $stats_type);
        
        $view->pielink = $view->baseurl.'/monitorstatus/todaypie/c/1/s/'.$slave->getId();
        $view->pielink .= '/t/'.$stats_type;
        $view->pielink .= '/r/'.uniqid();
        
        $view->stats_type = $stats_type;
        $total = 0;
        foreach ($data as $d) {
        	$total += $d;
        }
        $view->stats_total = $total;
    	$view->stats = $data;
    	
    	$template = Zend_Registry::get('default_template');
    	include_once(APPLICATION_PATH . '/../public/templates/'.$template.'/css/pieColors.php');
    	$view->colors = $data_colors;
    	
    	$graphfinder = new Default_Model_RRDGraphic();
    	$graphs = array();
    	foreach ($this->_columns as $gc) {
    		$graphs[$gc] = $graphfinder->fetchAll(array('family' => $gc));
    	}
    	$view->graphs = $graphs;
    	
    	$view->more_content = $morecontent;
    	$view->more_to_show = preg_split('/,/', $request->getParam('mts'));
	}
}
