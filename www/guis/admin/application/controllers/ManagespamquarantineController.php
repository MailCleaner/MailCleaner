<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * controller for spam quarantine page
 */

class ManagespamquarantineController extends Zend_Controller_Action
{
    protected function getSearchParams()
    {
        $request = $this->getRequest();
        $params = [];
        foreach (['search', 'domain', 'sender', 'subject', 'mpp', 'page', 'sort', 'forced', 'fd', 'fm', 'td', 'tm', 'hidedup', 'showSpamOnly', 'showNewslettersOnly'] as $param) {
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

        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'Management')->class = 'menuselected';
        $view->selectedMenu = 'Management';
        $main_menus = Zend_Registry::get('main_menu')->findOneBy('id', 'submanage_SpamQuarantine')->class = 'submenuelselected';
        $view->selectedSubMenu = 'SpamQuarantine';
    }

    public function indexAction()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $request = $this->getRequest();
        $form    = new Default_Form_SpamQuarantine($this->getSearchParams());
        $form->setAction(Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managespamquarantine'));
        $view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managespamquarantine', NULL, []);

        $view->form = $form;
    }

    public function searchAction()
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();
        $layout->disableLayout();
        $view->addScriptPath(Zend_Registry::get('ajax_script_path'));
        $view->thisurl = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->simple('index', 'managespamquarantine', NULL, []);

        $request = $this->getRequest();

        $loading = 1;
        if (!$request->getParam('load')) {
            sleep(1);
            $loading = 0;
        }
        $view->loading = $loading;
        $view->params = $this->getSearchParams();

        $nbspams = 0;
        $orderfield = 'date';
        $orderorder = 'desc';
        $nbpages = 0;
        $page = 0;
        $spams = [];

        $columns = [
            'action' => ['label' => 'Action'],
            'date' => ['label' => 'Date', 'label2' => 'date', 'order' => 'desc'],
            'to' => ['label' => 'Recipient', 'label2' => 'recipient', 'order' => 'desc'],
            'from' => ['label' => 'Sender', 'label2' => 'sender', 'order' => 'desc'],
            'subject' => ['label' => 'Subject', 'label2' => 'subject', 'order' => 'desc'],
            'globalscore' => ['label' => 'Score', 'label2' => 'score', 'order' => 'asc']
        ];
        if ($request->getParam('sort') && preg_match('/(\S+)_(asc|desc)/', $request->getParam('sort'), $matches)) {
            $order = $request->getParam('sort');
            $orderfield = $matches[1];
            $orderorder = $matches[2];
            if (isset($columns[$orderfield])) {
                $columns[$orderfield]['order'] = $orderorder;
            }
        }

        if ($request->getParam('domain') != "") {
            $spam = new Default_Model_QuarantinedSpam();
            $params = $this->getSearchParams();
            $nbspams = $spam->fetchAllCount($params);

            if ($nbspams > 0) {
                $spams = $spam->fetchAll($params);
                $nbpages = $spam->getNbPages();
                $page = $spam->getEffectivePage();
            }
        }
        $view->page = $page;
        $view->spams = $spams;

        $view->columns = $columns;
        $view->nbspams = $nbspams;
        $view->orderfield = $orderfield;
        $view->page = $page;
        $view->nbpages = $nbpages;
    }
}
