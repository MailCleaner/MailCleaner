<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Quarantined spam mapper
 */

class Default_Model_QuarantinedSpamMapper
{

    protected $_dbTable;
    protected $_nbspams = 0;
    protected $_pages = 0;
    protected $_page = 0;
    protected $_table = 'spam';

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        $table = MailCleaner_Config::getInstance()->getOption('SPAMTABLE');
        if ($table != "") {
            $this->_table = 'spam';
        }
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Default_Model_DbTable_QuarantinedSpam');
        }
        $table = MailCleaner_Config::getInstance()->getOption('SPAMTABLE');
        if ($table != "") {
            $this->_table = $table;
        }
        return $this->_dbTable;
    }

    public function find($todomain, $touser, $eximid, $spam)
    {
        if (preg_match('/^([a-zA-Z0-9])/', $touser, $matches)) {
            $init = $matches[1];
            if (is_numeric($init)) {
                $init = 'num';
            } else {
                $init = strtolower($init);
            }
            $this->getDbTable()->setTableName('spam_' . $init);
        }

        $query = $this->getDbTable()->select();
        if ($this->_table != '' && $this->_table != 'spam') {
            $domain = new Default_Model_Domain();
            $domain->findByName($todomain);
            $query->where('to_domain = ?', $domain->getId());
        } else {
            $query->where('to_domain = ?', $todomain);
        }
        $query->where('to_user = ?', $touser);
        $query->where('exim_id = ?', $eximid);
        $result = $this->getDbTable()->fetchAll($query);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        foreach ($spam->getAvailableParams() as $key) {
            $spam->setParam($key, $row->$key);
        }
    }

    protected function buildBaseFetchAll($query, $params)
    {
        if (isset($params['search']) && preg_match('/^[a-zA-Z0-9.\-_+:=]+$/', $params['search'])) {
            $query->where('to_user LIKE ?', $params['search'] . '%');
            if ($this->_table != '' && $this->_table != 'spam') {
                $first = ord(strtolower(substr($params['search'], 0, 1)));
                $partition = 27;
                if ($first >= 97 && $first <= 122) {
                    $partition = $first - 97;
                } else if ($first >= 48 && $first <= 57) {
                    $partition = 26;
                }
                if ($this->_table != '' && $this->_table != 'spam') {
                    $query->where('partition = ?', $partition);
                }
            }
        }
        if (isset($params['domain'])) {
            if ($this->_table != '' && $this->_table != 'spam') {
                $domain = new Default_Model_Domain();
                $domain->findByName($params['domain']);
                $query->where('domain = ?', $domain->getId());
            } else {
                $query->where('to_domain = ?', $params['domain']);
            }
        }
        if (isset($params['sender']) && $params['sender'] != "") {
            $query->where('sender LIKE ?', '%' . $params['sender'] . '%');
        }
        if (isset($params['subject']) && $params['subject'] != "") {
            $query->where('M_subject LIKE ?', '%' . $params['subject'] . '%');
        }
        if (isset($params['forced']) && $params['forced'] != "") {
            $query->where('forced != 1');
        }
        if (
            isset($params['td']) && isset($params['td']) && isset($params['tm']) && isset($params['tm'])
            && isset($params['fd']) && isset($params['fd']) && isset($params['fm']) && isset($params['fm'])
        ) {
            $fromdate = new Zend_Date(['year' => $params['fy'], 'month' => $params['fm'], 'day' => $params['fd']]);
            $todate = new Zend_Date(['year' => $params['ty'], 'month' => $params['tm'], 'day' => $params['td']]);
            $today = new Zend_Date();
            if ($todate < $fromdate) {
                $fromdate = new Zend_Date(['year' => $params['fy'] - 1, 'month' => $params['fm'], 'day' => $params['fd']]);
            }
            if ($todate > $today) {
                $todate = $today;
                // bug: $todate = new Zend_Date(['year' => $params['ty']-1, 'month' => $params['tm'], 'day' => $params['td']]);
            }
            if ($fromdate > $today) {
                $fromdate = new Zend_Date(['year' => $params['fy'] - 1, 'month' => $params['fm'], 'day' => $params['fd']]);
            }
            $sysconf = new Default_Model_SystemConf;
            $sysconf->load();
            $maxdays = $sysconf->getParam('days_to_keep_spams');
            $conds = [];
            $currentday = 0;
            while ($fromdate <= $todate) {
                if ($currentday++ > $maxdays) {
                    break;
                }
                $conds[] = "date_in = '" . $todate->get(Zend_Date::YEAR) . "-" . $todate->get(Zend_Date::MONTH) . "-" . $todate->get(Zend_Date::DAY) . "'";
                $todate->sub('1', Zend_Date::DAY, Zend_Registry::get('Zend_Locale')->getLanguage());
            }
            $str = implode(' OR ', $conds);
            $query->where($str);
        }
    }


    public function fetchAllCount($params)
    {
        $this->getDbTable();
        if ($this->_table != '' && $this->_table != 'spam') {
            $this->getDbTable()->setTableName($this->_table);
        } else {
            if (preg_match('/^([a-zA-Z0-9])/', $params['search'], $matches)) {
                $init = $matches[1];
                if (is_numeric($init)) {
                    $init = 'num';
                } else {
                    $init = strtolower($init);
                }
                $this->getDbTable()->setTableName('spam_' . $init);
            }
        }

        $query = $this->getDbTable()->select();
        if (isset($params['hidedup']) && $params['hidedup'] != "") {
            $query->from($this->getDbTable()->getTableName(), 'COUNT(DISTINCT exim_id) as count');
        } else {
            $query->from($this->getDbTable()->getTableName(), 'COUNT(*) as count');
        }
        ### newsl
        if (!empty($params['showSpamOnly'])) {
            $query->where('is_newsletter != ?', 1);
        } else if (!empty($params['showNewslettersOnly'])) {
            $query->where('is_newsletter = ?', 1);
        }

        $this->buildBaseFetchAll($query, $params);

        $nbspams = 0;
        #echo $query."<br />";
        $row = $this->getDbTable()->fetchRow($query);
        if ($row) {
            $nbspams = $row->count;
        }
        $this->_nbspams = $nbspams;
        return $nbspams;
    }

    public function fetchAll($params)
    {
        if (isset($params['search']) && preg_match('/^[a-zA-Z0-9.\-_+:=]+$/', $params['search'])) {
            $this->_localpart = $params['search'];
        }

        if ($this->_table != '' && $this->_table != 'spam') {
            $this->getDbTable()->setTableName($this->_table);
        }
        $query = $this->getDbTable()->select();
        $this->buildBaseFetchAll($query, $params);

        $mpp = 20;
        if (isset($params['mpp']) && is_numeric($params['mpp'])) {
            $mpp = $params['mpp'];
        }
        if ($this->_nbspams && $mpp) {
            $this->_pages = ceil($this->_nbspams / $mpp);
        }
        $this->_page = 1;
        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0 && $params['page'] <= $this->_pages) {
            $this->_page = $params['page'];
        }

        ## possible orders
        $orders = [
            'date' => 'date_in, time_in',
            'to' => 'to_user, to_domain',
            'from' => 'sender',
            'subject' => 'M_Subject',
            'globalscore' => 'M_globalscore'
        ];
        if ($this->_table != '' && $this->_table != 'spam') {
            $orders['date'] = 'date_in';
            $orders['to'] = 'to_user';
        }

        ## set order
        if (isset($params['orderfield']) && array_key_exists($params['orderfield'], $orders) && $params['orderorder']) {
            $sorders = preg_split('/,/', $orders[$params['orderfield']]);
            $sorders2 = [];
            foreach ($sorders as $o) {
                $sorders2[] = $o . " " . $params['orderorder'];
            }
            $query->order($sorders2);
        }


        $query->limit($mpp, ($this->_page - 1) * $mpp);
        if (isset($params['hidedup']) && $params['hidedup'] != "") {
            $query->group('exim_id');
        }

        $entries = [];
        #echo $query."<br />";
        $resultSet = $this->getDbTable()->fetchAll($query);
        foreach ($resultSet as $row) {
            $entry = new Default_Model_QuarantinedSpam();
            foreach ($entry->getAvailableParams() as $key) {
                $entry->setParam($key, $row->$key);
            }
            if ($entry->getParam('to_domain') == '') {
                $entry->setDestination($entry->getParam('to_user'), $params['domain']);
            } else {
                $entry->setDestination($entry->getParam('to_user'), $entry->getParam('to_domain'));
            }
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getNbPages()
    {
        return $this->_pages;
    }
    public function getEffectivePage()
    {
        return $this->_page;
    }
}
