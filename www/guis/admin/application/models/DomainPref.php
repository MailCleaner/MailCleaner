<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Domain preferences
 */

class Default_Model_DomainPref
{
    protected $_id;
    protected $_values = [
        'auth_param' => '',
        'systemsender' => '',
        'falseneg_to' => '',
        'falsepos_to' => '',
        'supportname' => '',
        'supportemail' => '',
        'ldapcalloutserver' => '',
        'ldapcalloutparam' => '',
        'language' => 'en',
        'gui_group_quarantines' => 0,
        'delivery_type' => 2,
        'spam_tag' => '',
        'virus_subject' => '',
        'file_subject' => '',
        'content_subject' => '',
        'daily_summary' => 1,
        'weekly_summary' => 0,
        'monthly_summary' => 0,
        'summary_type' => 'html',
        'summary_to' => '',
        'auth_type' => 'none',
        'auth_server' => '',
        'auth_param' => '',
        'auth_modif' => '',
        'address_fetcher' => '',
        'allow_smtp_auth' => 0,
        'smtp_auth_cachetime' => 0,
        'spamwall' => 1,
        'contentwall' => 1,
        'viruswall' => 1,
        'enable_whitelists' => 0,
        'enable_warnlists' => 0,
        'enable_blacklists' => 0,
        'notice_wwlists_hit' => 0,
        'web_template' => 'default',
        'summary_template' => 'default',
        'report_template' => 'default',
        'batv_check' => 0,
        'batv_secret' => '',
        'prevent_spoof' => 0,
        'reject_capital_domain' => 0,
        'dkim_domain' => '',
        'dkim_selector' => '',
        'dkim_pkey' => '',
        'require_incoming_tls' => 0,
        'require_outgoing_tls' => 0,
        'archive_mail' => 0,
        'copyto_mail' => '',

        ### newsl
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
            $this->setMapper(new Default_Model_DomainPrefMapper());
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

    public function save($global = false)
    {
        return $this->getMapper()->save($this, $global);
    }

    public function delete()
    {
        return $this->getmapper()->delete($this);
    }
}
