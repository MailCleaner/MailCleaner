<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User
 */

class Default_Model_User
{
    protected $_id;

    protected $_values = [
        'username' => '',
        'pref' => 0,
        'domain' => '',
    ];
    protected $_mapper;
    protected $_domain;
    protected $_prefs;
    protected $_addresses = [];
    protected $_addressesObjects = [];
    protected $_localuser;

    protected $_configpanels = [
        0 => 'interfacesettings',
        1 => 'quarantinedisplay',
        2 => 'addressgroup',
        3 => 'actions',
        4 => 'authentification'
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

    public function getAvailableParams()
    {
        $ret = [];
        foreach ($this->_values as $key => $value) {
            $ret[] = $key;
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

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_UserMapper());
        }
        return $this->_mapper;
    }

    public function findById($id)
    {
        $this->getMapper()->findById($id, $this);
        $pref = new Default_Model_UserPref();
        $pref->find($this->getParam('pref'));
        $this->setPrefs($pref);

        $domainobject = new Default_Model_Domain();
        $domainobject->findByName($this->getParam('domain'));
        $this->setDomainObject($domainobject);

        require_once('connector/LoginFormatter.php');
        $formatter = LoginFormatter::getFormatter($domainobject->getPref('auth_modif'));
        if ($formatter) {
            $username = $formatter->format($this->getParam('username'), $this->getParam('domain'));
        }
        $this->setParam('username', $username);

        return $this;
    }

    public function find($username, $domain)
    {
        $domainobject = new Default_Model_Domain();
        $domainobject->findByName($domain);
        $this->setDomainObject($domainobject);

        require_once('connector/LoginFormatter.php');
        require_once('system/SystemConfig.php');
        $formatter = LoginFormatter::getFormatter($domainobject->getPref('auth_modif'));
        if ($formatter) {
            $username = $formatter->format($username, $domain);
        }

        $this->getMapper()->find($username, $domain, $this);
        $pref = new Default_Model_UserPref();
        $pref->find($this->getParam('pref'));
        $this->setPrefs($pref);

        if (!$pref->getId()) {
            $pref->setDefaultDomainPref($domainobject);
        }

        return $this;
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
        $this->setParam('domain', $this->getDomainObject()->getParam('name'));
        $this->_prefs->save();
        $this->setParam('pref', $this->_prefs->getId());
        $ret = $this->getMapper()->save($this);
        $this->getAddresses();
        foreach ($this->getAddressesObjects() as $add) {
            $add->setPref('language', $this->getPref('language'));
            $add->save();
        }
        /*if ($this->getMapper()->isNew()) {
            if ($this->getDomainObject()->getCalloutConnector() == 'local') {
                $this->getAddresses();
                foreach ($this->getAddressesObjects() as $add) {
                    $add->save();
                }
            }
        }*/
        return $ret;
    }

    public function delete()
    {
        $local = new Default_Model_LocalUser();
        if ($local->find($this->getParam('username'), $this->getParam('domain'))) {
            $local->delete();
        }

        $addresses = $this->getAddressesObjects();
        foreach ($addresses as $a) {
            $a->delete();
        }
        $this->_prefs->delete();
        return $this->getMapper()->delete($this);
    }

    public function getConfigPanels()
    {
        $panels = [];
        $t = Zend_Registry::get('translate');
        if (!$this->getDomainObject()->isAuthLocal()) {
            unset($this->_configpanels[4]);
        }

        foreach ($this->_configpanels as $panel) {
            $panels[$panel] = $t->_($panel);
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

    public function getAddresses($addpending = false)
    {
        if (!empty($this->_addresses)) {
            return $this->_addresses;
        }

        require_once('connector/AddressFetcher.php');
        $address_fetcher = AddressFetcher::getFetcher($this->getDomainObject()->getPref('address_fetcher'));
        $domain = $this->getDomainObject()->loadOldDomain();
        $this->_addresses = $address_fetcher->fetch($this->getParam('username'), $domain);
        ## add registered addresses
        if ($this->getId()) {
            $email = new Default_Model_Email();
            $emails = $email->fetchAllRegistered([
                'domain' => $this->getDomainObject()->getParam('name'),
                'user' => $this->getId()
            ]);

            foreach ($emails as $e) {
                if (!in_array($e, $this->_addresses)) {
                    $this->_addresses[$e] = $e;
                }
            }

            if ($addpending) {
                ## add pending aliases
                $alias = new Default_Model_PendingAlias();
                $aliases = $alias->fetchAll([
                    'user' => $this->getId()
                ]);

                foreach ($aliases as $a) {
                    if (!in_array($a->getParam('alias'), $this->_addresses)) {
                        $this->_addresses[$a->getParam('alias')] = $a->getParam('alias');
                    }
                }
            }
        }

        ksort($this->_addresses);
        return $this->_addresses;
    }

    public function getAddressesObjects($addpending = false)
    {
        if (!empty($this->_addressesObjects)) {
            return $this->_addressesObjects;
        }

        $ret = [];
        foreach ($this->getAddresses($addpending) as $add => $ismain) {
            $a = new Default_Model_Email();
            $a->find($add);
            if (!$a->getId()) {
                $a->setParam('address', $add);
                $a->setParam('user', $this->getId());
                $a->setPref('language', $this->getPref('language'));
            }
            $ret[$a->getParam('address')] = $a;
        }
        return $ret;
    }

    public function isAddressEligible($address, $wrongIfAlreadyLinked = false, $wrongIfAlreadyLinkedToAnother = true)
    {
        ## check address format validity
        $validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS |
            Zend_Validate_Hostname::ALLOW_LOCAL);
        if (!$validator->isValid($address)) {
            throw new Exception('address is not valid');
        }
        ## check format and extract domain part
        if (!preg_match('/^[^@]+\@(\S+)$/', $address, $matches)) {
            throw new Exception('this address is not valid');
        }
        $domain = $matches[1];
        ## check domains is filtered
        $d = new Default_Model_Domain();
        $d->findByName($domain);
        if (!$d->getId()) {
            throw new Exception('address ' . $address . ' cannot be filtered');
        }

        ## ok, create address
        $email = new Default_Model_Email();
        $email->find($address);

        ## check if already exists
        if ($email->getId()) {
            ## check it doesn't belong to someone else
            if ($email->getParam('user') != 0 && $email->getParam('user') != $this->getId() && $wrongIfAlreadyLinkedToAnother) {
                throw new Exception('address ' . $address . ' is already linked to another account');
                ## check if not already linked to this account
            } elseif ($email->getParam('user') == $this->getId() && $wrongIfAlreadyLinked) {
                throw new Exception('address ' . $address . ' already linked to this account');
            }
        }
        return true;
    }

    public function addAddress($address, $wrongIfAlreadyLinked = false, $wrongIfAlreadyLinkedToAnother = true)
    {

        ## cannot add address if user does not exists...
        if (!$this->getId()) {
            $this->save();
        }
        ## ok, create address
        $email = new Default_Model_Email();
        $email->find($address);

        if (!$this->isAddressEligible($address, $wrongIfAlreadyLinked, $wrongIfAlreadyLinkedToAnother)) {
            throw new Exception('address is not eligible');
        }
        ## ok, link and save
        $email->setParam('address', $address);
        $email->setParam('user', $this->getId());
        $email->save();
        $this->reloadAddressesList();
    }

    public function reloadAddressesList()
    {
        $this->_addresses = [];
        $this->_addressesObjects = [];
    }

    public function getLocalUserObject()
    {
        if ($this->getDomainObject()->isAuthLocal()) {
            $this->_localuser = new Default_Model_LocalUser();
            $this->_localuser->find($this->getParam('username'), $this->getParam('domain'));
            return $this->_localuser;
        }
        return null;
    }

    public function getShortUsername()
    {
        if (preg_match('/([^\@]+)\@\S+/', $this->getParam('username'), $matches)) {
            return $matches[1];
        }
        return $this->getParam('username');
    }
}
