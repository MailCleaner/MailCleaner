<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Email
 */

class Default_Model_Email
{
    protected $_id;

    protected $_values = [
        'address' => '',
        'user' => 0,
        'pref' => 0,
        'is_main' => 0
    ];
    protected $_prefs;
    protected $_mapper;
    protected $_domain;
    protected $_domainobject;

    protected $_configpanels = [
        0 => 'addresssettings',
        1 => 'warnlist',
        2 => 'whitelist',
        3 => 'archiving',
        4 => 'actions',
        5 => 'blacklist',
        6 => 'newslist'
    ];

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        $ret = null;
        if (array_key_exists($param, $this->_values)) {
            $ret = $this->_values[$param];
        }
        if ($ret == 'false') {
            return 0;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setPrefs($prefs)
    {
        $this->_prefs = $prefs;
    }
    public function getPrefs()
    {
        return $this->_prefs;
    }

    public function getPref($prefname)
    {
        $ret = null;
        if (!$this->_prefs) {
        } else {
            $ret = $this->_prefs->getParam($prefname);
        }
        if ($ret == 'false') {
            return 0;
        }
        return $ret;
    }

    public function setPref($prefname, $prefvalue)
    {
        return $this->_prefs->setParam($prefname, $prefvalue);
    }

    public function copyPrefs($domain)
    {
        foreach ($this->_values as $key => $value) {
            if ($key != 'name') {
                $this->setParam($key, $domain->getParam($key));
            }
        }
        if (!$this->_prefs) {
            $this->_prefs = new Default_Model_UserPref();
        }
    }

    public function setDomainObject($domain)
    {
        $this->_domain = $domain;
    }
    public function getDomainObject()
    {
        return $this->_domain;
    }

    public function getDomain()
    {
        return $this->getDomainObject()->getParam('name');
    }

    public function getLocalPart()
    {
        if (preg_match('/^([^\@]+)\@/', $this->getParam('address'), $matches)) {
            return $matches[1];
        }
        return $this->getParam('address');
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_EmailMapper());
        }
        return $this->_mapper;
    }

    public function getConfigPanels()
    {
        $panels = [];
        $t = Zend_Registry::get('translate');

        foreach ($this->_configpanels as $panel) {
            $panels[$panel] = $t->_($panel);
        }

        $antispam = new Default_Model_AntispamConfig();
        $antispam->find(1);

        if (!$antispam->getParam('enable_whitelists') || !$this->getDomainObject()->getPref('enable_whitelists')) {
            unset($panels['whitelist']);
        }
        if (!$antispam->getParam('enable_warnlists') || !$this->getDomainObject()->getPref('enable_warnlists')) {
            unset($panels['warnlist']);
        }
        if (!$antispam->getParam('enable_blacklists') || !$this->getDomainObject()->getPref('enable_blacklists')) {
            unset($panels['blacklist']);
        }
        return $panels;
    }

    public function getPreviousPanel($panel)
    {
        for ($i = 0; $i < count($this->_configpanels); $i++) {
            if ($i > 0 && $this->_configpanels[$i] == $panel) {
                return $this->_configpanels[$i - 1];
            }
        }
        return '';
    }

    public function getNextPanel($panel)
    {
        for ($i = 0; $i < count($this->_configpanels); $i++) {
            if ($i < count($this->_configpanels) - 1 && $this->_configpanels[$i] == $panel) {
                return $this->_configpanels[$i + 1];
            }
        }
        return '';
    }

    public function find($address)
    {
        $this->getMapper()->find($address, $this);

        $pref = new Default_Model_UserPref();
        $pref->find($this->getParam('pref'));
        $this->setPrefs($pref);

        $domain = '';
        if (preg_match('/^[^@]+@(\S+)/', $address, $matches)) {
            $domain = $matches[1];
        }
        $domainobject = new Default_Model_Domain();
        $domainobject->findByName($domain);
        $this->setDomainObject($domainobject);

        if (!$pref->getId()) {
            $pref->setDefaultDomainPref($domainobject);
        }

        return $this;
    }

    public function fetchAllRegistered($params = NULL)
    {
        return $this->getMapper()->fetchAllRegistered($params);
    }

    public function fetchAllName($params = NULL)
    {
        return $this->getMapper()->fetchAllName($params);
    }

    public function save()
    {
        if (!$this->_prefs) {
            $this->_prefs = new Default_Model_UserPref();
        }
        $this->_prefs->save();
        $this->setParam('pref', $this->_prefs->getId());
        $this->getMapper()->save($this);

        $alias = new Default_Model_PendingAlias();
        $alias->find($this->getParam('address'));
        if ($alias->getId()) {
            $alias->delete();
        }
        if ($this->isNew()) {
            if ($this->getDomainObject()->getCalloutConnector() == 'local') {
                $slave = new Default_Model_Slave();
                $soapparams = [
                    'what' => 'domains',
                    'domain' => $this->getDomain()
                ];
                $res = $slave->sendSoapToAll('Service_silentDump', $soapparams);
            }
        }
        return true;
    }

    public function delete()
    {
        $wwlists = new Default_Model_WWElement();
        $types = ["white", "black", "warn", "wnews"];
        foreach ($types as $type) {
            $elements = $wwlists->fetchAll($this->getParam('address'), $type);
            foreach ($elements as $element) {
                $element->delete();
            }
        }
        $this->_prefs->delete();
        $ret = $this->getMapper()->delete($this);
        if ($this->getDomainObject()->getCalloutConnector() == 'local') {
            $slave = new Default_Model_Slave();
            $soapparams = [
                'what' => 'domains',
                'domain' => $this->getDomain()
            ];
            $res = $slave->sendSoapToAll('Service_silentDump', $soapparams);
        }
        return $ret;
    }

    public function isPendingAlias()
    {
        if ($this->getId() && $this->getParam('user')) {
            return false;
        }

        $alias = new Default_Model_PendingAlias();
        $alias->find($this->getParam('address'));
        if ($alias->getId()) {
            return true;
        }
        return false;
    }

    public function getStatus()
    {
        if ($this->isPendingAlias()) {
            return 0;
        }
        return 1;
    }
    public function setStatus()
    {
        $a = new Default_Model_PendingAlias();
        $a->find($this->getParam('address'));
        if ($a->getId()) {
            $a->delete();
        }
    }

    public function getComment()
    {
        return '';
    }
    public function setComment()
    {
    }

    public function getSummaryFrequency()
    {
        if ($this->getPref('daily_summary')) {
            return 'daily';
        }
        if ($this->getPref('weekly_summary')) {
            return 'weekly';
        }
        if ($this->getPref('monthly_summary')) {
            return 'monthly';
        }
        return 'none';
    }

    public function setSummaryFrequency($frequency)
    {
        $options = ['daily' => 'daily_summary', 'weekly' => 'weekly_summary', 'monthly' => 'monthly_summary'];
        foreach ($options as $key => $value) {
            $this->setPref($value, '0');
        }
        if (isset($options[$frequency])) {
            $this->setPref($options[$frequency], '1');
        }
        return;
    }

    public function getLinkedUser()
    {
        if ($this->getParam('user')) {
            $user = new Default_Model_User();
            $user->findById($this->getParam('user'));
            return $user;
        }
        return NULL;
    }

    public function isNew()
    {
        return $this->getMapper()->isNew();
    }
}
