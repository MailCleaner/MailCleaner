<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for message tracing page
 */

class ManagetracingController extends Zend_Controller_Action
{
    protected function getSearchParams()
    {
        $request = $this->getRequest();
        $params = [];
        foreach (['search', 'domain', 'sender', 'mpp', 'page', 'sort', 'fd', 'fm', 'td', 'tm', 'submit', 'cancel', 'hiderejected'] as $param) {
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
        #        $fromdateO->sub('1', Zend_Date::DAY, Zend_Registry::get('Zend_Locale')->getLanguage());

        $todate = Zend_Locale_Format::getDate($todateO, ['date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()]);
        $fromdate = Zend_Locale_Format::getDate($fromdateO, ['date_format' => Zend_Locale_Format::STANDARD, 'locale' => Zend_Registry::get('Zend_Locale')->getLanguage()]);


        foreach (['fd' => 'day', 'fm' => 'month'] as $tk => $tv) {
            if (!isset($params[$tk]) || !$params[$tk]) {
                $params[$tk] = $fromdate[$tv];
            }
        }
        $params['ty'] = $todate['year'];
        foreach (['td' => 'day', 'tm' => 'month'] as $tk => $tv) {
            if (!isset($params[$tk]) || !$params[$tk]) {
                $params[$tk] = $todate[$tv];
            }
        }
        $params['fy'] = $todate['year'];
        if ($params['tm'] < $params['fm']) {
            $params['fy']--;
        }

        $params['datefrom'] = sprintf("%04d%02d%02d", $params['fy'], $params['fm'], $params['fd']);
        $params['dateto'] = sprintf("%04d%02d%02d", $params['ty'], $params['tm'], $params['td']);
        if (isset($params['search']) && isset($params['domain'])) {
            $params['regexp'] = $params['search'] . '.*@' . $params['domain'];
        }
        if (isset($params['sender'])) {
            $params['filter'] = $params['sender'];
        }
        return $params;
    }


    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $view->headLink()->appendStylesheet($view->css_path . '/main.css');
        $view->headLink()->appendStylesheet($view->css_path . '/navigation.css');
        $view->headLink()->appendStylesheet($view->css_path . '/quarantine.css');
        $view->headScript()->appendFile($view->scripts_path . '/quarantine.js', 'text/javascript');
        $view->headScript()->appendFile($view->scripts_path . '/baseconfig.js', 'text/javascript');

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Management')->class = 'menuselected';
        $view->selectedMenu = 'Management';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submanage_Tracing')->class = 'submenuelselected';
        $view->selectedSubMenu = 'Tracing';
    }

    public function indexAction()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $request = $this->getRequest();
        $form    = new Default_Form_Tracing($this->getSearchParams());
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managetracing'));
        $view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managetracing', NULL, []);

        $view->form = $form;
    }

    public function searchAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
        $view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managecontentquarantine', NULL, []);

        $request = $this->getRequest();

        $loading = 1;
        if (!$request->getParam('load')) {
            sleep(1);
            $loading = 0;
        }
        $view->loading = $loading;
        $view->params = $this->getSearchParams();

        $orderfield = 'date';
        $orderorder = 'desc';

        $columns = [
            'taction' => ['label' => 'Action'],
            'in_date' => ['label' => 'Arrival date'],
            'in_status' => ['label' => 'Arrival status'],
            'from' => ['label' => 'Envelope sender'],
            'tos' => ['label' => 'Recipients'],
            'spam' => ['label' => 'Spam status'],
            'content' => ['label' => 'Content status'],
            'out_status' => ['label' => 'Deliver status'],
            'out_date' => ['label' => 'Delivery date']
        ];
        if ($request->getParam('sort') && preg_match('/(\S+)_(asc|desc)/', $request->getParam('sort'), $matches)) {
            $order = $request->getParam('sort');
            $orderfield = $matches[1];
            $orderorder = $matches[2];
            if (isset($columns[$orderfield])) {
                $columns[$orderfield]['order'] = $orderorder;
            }
        }

        $elements = [];
        $nbelements = 0;
        $nbpages = 0;
        $page = 0;

        $element = new Default_Model_MessageTrace();
        $params = $this->getSearchParams();

        $session = new Zend_Session_Namespace('MailCleaner');

        // escape params args
        array_walk($params, function (&$arg_value, $key) {
            if ($key == 'regexp') {
                $arg_value = escapeshellarg($arg_value);
            }
        });

        $view->canceled = 0;
        if (isset($params['cancel']) && $params['cancel']) {
            if (isset($session->trace_id) && $session->trace_id) {
                $params['trace_id'] = $session->trace_id;
            }
            $element->abortFetchAll($params);
            $session->trace_id = 0;
            $view->canceled = 1;
        }
        if (isset($params['submit']) && $params['submit'] && isset($session->trace_id) && $session->trace_id) {
            $params['trace_id'] = $session->trace_id;
            $element->abortFetchAll($params);
            $session->trace_id = 0;
        }
        if (isset($session->trace_id) && $session->trace_id) {
            $params['trace_id'] = $session->trace_id;
            $res = $element->getStatusFetchAll($params);
            if (isset($res['error'])) {
                $element->abortFetchAll($params);
                $session->trace_id = 0;
            }
            if (isset($res['count'])) {
                $view->nbrows = $res['count'];
            }
            ## search running or finished, check status
            if (isset($res['finished']) && $res['finished']) {
                $elements = $element->fetchAll($params);
                $view->loading = 0;
            } else {
                $view->loading = 1;
            }
        } else {
            ## no search running, launch search
            if ($request->getParam('domain') != "") {
                $trace_id = $element->startFetchAll($params);
                $session->trace_id = $trace_id;
            }
            $view->loading = 1;
        }

        $nbpages = $element->getNbPages();
        $page = $element->getEffectivePage();
        $nbelements = $element->fetchAllCount($params);

        $view->page = $page;
        $view->elements = $elements;

        $view->columns = $columns;
        $view->nbelements = $nbelements;
        $view->orderfield = $orderfield;
        $view->page = $page;
        $view->nbpages = $nbpages;

        $download_filename = '';
        if ($params['search']) {
            $download_filename = $params['search'] . "_at_";
        }
        $download_filename .= $params['domain'];
        $download_filename .= '_' . $params['datefrom'] . '_' . $params['dateto'];
        if ($nbpages > 1) {
            $download_filename .= '_' . $page;
        }
        $download_filename .= '.log';
        $view->downloadFilename = $download_filename;
    }

    public function logextractAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));

        $traceid = 0;
        $msgid = '';
        $slaveid = 0;

        $log = '';

        $request = $this->getRequest();
        $session = new Zend_Session_Namespace('MailCleaner');
        if (isset($session->trace_id) && $session->trace_id) {
            $traceid = $session->trace_id;
        }
        $msgid = $request->getParam('m');

        if (is_numeric($request->getParam('s'))) {
            $slave = new Default_Model_Slave();
            $slave->find($request->getparam('s'));
        }
        $params = ['msgid' => $msgid, 'traceid' => $traceid];
        if ($slave) {
            $res = $slave->sendSoap('Logs_ExtractLog', $params);
        }
        if (is_array($res) && isset($res['full_log'])) {
            $log = $res['full_log'];
        }
        $view->logs = $res;
        $view->log = $log;
        $view->slave = $slave->getId();
        $view->msgid = $msgid;
        $view->t = Zend_Registry::get('translate');
    }

    public function downloadtraceAction()
    {
        function traceSort($a, $b)
        {
            if ($a['datetime'] == $b['datetime']) {
                return 0;
            }
            return ($a['datetime'] < $b['datetime']) ? -1 : 1;
        }

        $separator = "\r\n\r\n*************";
        $this->_helper->viewRenderer->setNoRender();
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $layout->disableLayout();

        $request = $this->getRequest();

        $messages = [];
        if ($request->getParam('m')) {
            $messages = preg_split('/,/', $request->getParam('m'));
        }
        $file = 'export_trace.log';
        if ($request->getParam('f') != '') {
            $file = $request->getParam('f');
        }

        $session = new Zend_Session_Namespace('MailCleaner');
        if (isset($session->trace_id) && $session->trace_id) {
            $traceid = $session->trace_id;
        }
        $traces = [];

        foreach ($messages as $msg_lid) {
            if (preg_match('/^(\d+)_([-a-zA-Z0-9]+)$/', $msg_lid, $matches)) {
                $slave = new Default_Model_Slave();
                $slave->find($matches[1]);
                $params = ['msgid' => $matches[2], 'traceid' => $traceid];
                if ($slave) {
                    $res = $slave->sendSoap('Logs_ExtractLog', $params);
                }
                if (is_array($res) && isset($res['full_log'])) {
                    array_push($traces, $res);
                }
            }
        }
        $mcconfig = MailCleaner_Config::getInstance();
        $tmpdir = $mcconfig->getOption('VARDIR') . '/run/mailcleaner/log_search/';
        $tmpfile = tempnam($tmpdir, 'download_');
        if (!$handle = fopen($tmpfile, 'w')) {
            return;
        }

        usort($traces, "traceSort");
        foreach ($traces as $t) {
            $tstr = '';
            fwrite($handle, $separator);
            foreach (['log_stage1', 'log_stage2', 'log_engine', 'log_stage4', 'log_spamhandler'] as $c) {
                $str = '';
                if (is_array($t[$c]) && count($t[$c]) > 0) {
                    $sstr = implode("\r\n", $t[$c]);
                    $str .= "\r\n" . $sstr;
                }
                if ($str != '') {
                    $tstr .= "\r\n" . $str;
                }
            }
            fwrite($handle, $tstr);
        }
        fclose($handle);
        header("Content-Type: application/octet-stream; ");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($tmpfile) . "; ");
        header("filename=\"" . $file . "\"; ");
        flush();

        $handle = fopen($tmpfile, "rb");

        while (!feof($handle)) {
            $data = fread($handle, 8192);

            echo $data;
            flush();
        }
        fclose($handle);

        flush();
        unlink($tmpfile);
    }
}
