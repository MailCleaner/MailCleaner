<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * controller for log monitoring pages
 */

class MonitorlogsController extends Zend_Controller_Action
{
	protected function getSearchParams() {
		$request = $this->getRequest();
		$params = array();
		foreach (array('fd', 'fm') as $param) {
			$params[$param] = '';
			if ($request->getParam($param)) {
				$params[$param] = $request->getParam($param);
			}
		}

		$todateO = Zend_Date::now();
		$todate = Zend_Locale_Format::getDate($todateO, array('date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()));

		foreach ( array('fd' => 'day', 'fm' => 'month') as $tk => $tv) {
			if  (!isset($params[$tk]) || !$params[$tk]) {
				$params[$tk] = $todate[$tv];
			}
		}
		$params['fy'] = $todate['year'];
		
		$givendate = new Zend_Date(array('year' => $params['fy'], 'month' =>$params['fm'], 'day' => $params['fd']));
		if ($givendate->compare(Zend_Date::now()) > 0) {
			$params['fy'] = $todate['year'] - 1;
		}
		
		$params['date'] = sprintf("%04d%02d%02d",$params['fy'],$params['fm'],$params['fd']);

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
		$view->headScript()->appendFile($view->scripts_path.'/logs.js', 'text/javascript');
		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Monitoring')->class = 'menuselected';
		$view->selectedMenu = 'Monitoring';
		$main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submonitor_Logs')->class = 'submenuelselected';
		$view->selectedSubMenu = 'Logs';
	}

	public function indexAction() {

		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
			
		$form    = new Default_Form_Logs($this->getSearchParams());
		$form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorlogs'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorlogs', NULL, array());

		$view->form = $form;

	}

	public function searchAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->addScriptPath(Zend_Registry::get('ajax_script_path'));
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'monitorlogs', NULL, array());
			      
		$request = $this->getRequest();

		if ($request->getParam('load')) {
			$view->loading = 1;
			return;
		}
		$view->loading = 0;

		$log = new Default_Model_Logfile();
		$logs = $log->fetchAll($this->getSearchParams());

		$slave = new Default_Model_Slave();
		$view->slaves = $slave->fetchAll();

		$view->downloadLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('download', 'monitorlogs', NULL, array());
		$view->viewLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('view', 'monitorlogs', NULL, array());
		$view->log = $log;
		$view->logs = $logs;
	}

	public function downloadAction() {
		$this->_helper->viewRenderer->setNoRender();
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
			
		$request = $this->getRequest();
		$file = '';
		if ($request->getParam('f') != '') {
			$file = $request->getParam('f');
		}
		if (!preg_match('/^(\d+)-([A-Za-z0-9\._]+-[A-Za-z0-9\._]+)$/', $file, $matches)) {
			header("HTTP/1.0 404 Not Found");
			echo "Bad parameters";
			return;
		}

		$file = $matches[2];
		$slave_id = $matches[1];

		$slave = new Default_Model_Slave();
		$slave->find($slave_id);
		if ($slave->getHostname() == '') {
			header("HTTP/1.0 404 Not Found");
			echo "Host not found";
			return;
		}

		$url = 'http://'.$slave->getHostname().':5132/soap/DownloadLog.php?file='.$file;

		$headers = get_headers($url);
		$size = 0;
		foreach ($headers as $header) {
			if (preg_match('/Content-Length: (\d+)/', $header, $matches)) {
				$size = $matches[1];
			}
		}

		$handle = fopen($url, "rb");
		if (!is_resource($handle)) {
			return;
		}
		stream_set_timeout($handle, 60);

		header("Content-Type: application/octet-stream; ");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$size."; ");
		header("filename=\"".$file."\"; ");
		flush();

		while(!feof($handle)) {
			$data = fread($handle, 8192);

			echo $data;
			flush();
		}
		fclose($handle);
	}

	public function viewAction() {
		$layout = Zend_Layout::getMvcInstance();
		$view=$layout->getView();
		$layout->disableLayout();
		$view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('view', 'monitorlogs', NULL, array());
		$view->headLink()->appendStylesheet($view->css_path.'/viewlog.css');
        $view->headScript()->appendFile($view->scripts_path.'/logview.js', 'text/javascript');
		$view->headLink()->appendStylesheet($view->css_path.'/ie7.css', 'screen', 'lt IE 8');
		$view->headLink()->appendStylesheet($view->css_path.'/ie8.css', 'screen', 'gt IE 7');
        
        $request = $this->getRequest();
        $file = '';
        if ($request->getParam('f')) {
            $file = $request->getParam('f');
        }
        $view->lastline = 3000;
        $view->baseurl = $view->thisurl."f/".$file;
		
	if (!preg_match('/^(\d+)-([^\-]+-[^\-]+)$/', $file, $matches)) {
            header("HTTP/1.0 404 Not Found");
            echo "Bad parameters";
            return;
        }
        $file = $matches[2];
        $view->thisfile = $file;
        $slave_id = $matches[1];
        $view->slaveid = $slave_id;           

        $log = new Default_Model_Logfile();
        $log->loadByFileName($file);
            
		if ($request->isXmlHttpRequest()) {
			$view->addScriptPath(Zend_Registry::get('ajax_script_path'));

			$fromline = 0;
			$toline = 0;
			if ($request->getParam('fl')) {
				$fromline = $request->getParam('fl');
			}
			if ($request->getParam('tl')) {
				$toline = $request->getParam('tl');
			}

			$slave = new Default_Model_Slave();
                        $hosts = $slave->fetchAll();
                        $view->otherHosts = array();
                        foreach ($hosts as $h) {
                            if ($h->getID() != $slave_id) {
                               $view->otherHosts[] = $h;
                            }
                        }
			$slave->find($slave_id);
			if ($slave->getHostname() == '') {
				header("HTTP/1.0 404 Not Found");
				echo "Host not found";
				return;
			}

			$params['file'] = $file;
			$params['fromline'] = $fromline;
			$params['toline'] = $toline;
			$params['search'] = $request->getParam('s');
			$params['position'] = $request->getParam('sp');
			$params['percent'] = $request->getParam('percent');
			$params['last_element'] = $request->getParam('le');
            $params['maxlines'] = $request->getParam('maxlines');
            $params['maxchars'] = $request->getParam('maxchars');
			$view->params = $params;

			$res = $slave->sendSoapRequest('Logs_GetLogLines', $params);
			if (isset($res['error'])) {
				$view->logtext = $res['error'];
			}
			$lines = array();
			if (isset($res['lines'])) {
				foreach ($res['lines'] as $line) {
					$html_line = htmlentities($line);
					$html_line = str_replace('___SB___', '<strong>', $html_line);
					$html_line = str_replace('___EB___', '</strong>', $html_line);
                                        $html_line = str_replace('___SFB___', '<strong class="firstmatch">', $html_line);
                                        $html_line = str_replace('___EFB___', '</strong>', $html_line);
                                        $html_line = str_replace('___SML___', '<span class="matchedline">', $html_line);
                                        $html_line = str_replace('___EML___', '</span>', $html_line);
					$lines[] = $html_line;
				}
				$view->logtext = implode("<br \/>", $lines);
				$view->lastline = $res['nblines'];
				$view->searched = 0;
				if (isset($res['search_results']) && isset($res['position'])) {
					$view->searched = 1;
					$view->search_results = $res['search_results'];
					$view->position = $res['position'];
				}
			}
		    $nextlog_link = '';
		    $view->viewLink = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('view', 'monitorlogs', NULL, array());
            if ($res['msgid']) {
            	$nextlog = $log->getNextLog();
            	if ($nextlog) {
                    $nextlog_link = $slave->getId()."-".preg_replace('/\//', '-', $nextlog)."/s/".urlencode($res['msgid']);

            	}
            }
            $res['nextlog_link'] = $nextlog_link;
            #var_dump($res['nextlog_link']);
            
			$view->res = $res;
			
		} else {
			$slave = new Default_Model_Slave();
            $slave->find($slave_id);
            
            if ($request->getParam('s')) {
               $view->initial_search = $request->getParam('s');
            }
            
            $t = Zend_Registry::get('translate');
            $view->headTitle($t->_('Log view')." - ".$slave_id." (".$slave->getHostname().") - ".$t->_($log->getParam('name')));
		}
	}
}
