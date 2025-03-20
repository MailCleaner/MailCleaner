<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User/Email preferences
 */

class Default_Model_UserPref
{
    protected $_id;
    protected $_values = [
        'language' => 'en',
        'gui_default_address' => '',
        'gui_displayed_spams' => 20,
        'gui_displayed_days' => 7,
        'gui_mask_forced' => 0,
        'gui_group_quarantines' => 0,
        'spam_tag' => '',
        'delivery_type' => 0,
        'quarantine_bounces' => 0,
        'summary_type' => 'text',
        'daily_summary' => 0,
        'weekly_summary' => 0,
        'monthly_summary' => 0,
        'summary_to' => '',
        'archive_mail' => 0,
        'copyto_mail' => '',
        'bypass_filtering' => 0,
        'allow_newsletters' => '0',
    ];

    protected $_mapper;

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function copy($prefs)
    {
        foreach ($this->_values as $key => $value) {
            $this->setParam($key, $prefs->getParam($key));
        }
    }

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        if (array_key_exists($param, $this->_values)) {
            return $this->_values[$param];
        }
        return null;
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

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_UserPrefMapper());
        }
        return $this->_mapper;
    }

    public function load()
    {
        return $this->find(1);
    }
    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        return $this->getmapper()->delete($this);
    }

    public function setDefaultDomainPref($domain)
    {
        foreach ($this->_values as $pref => $value) {
            if ($domain->getPref($pref)) {
                $this->setParam($pref, $domain->getPref($pref));
            }
        }
    }
}
