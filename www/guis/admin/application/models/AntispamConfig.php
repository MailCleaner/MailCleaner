<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * Antispam configuration
 */

class Default_Model_AntispamConfig
{
    protected $_id;
    protected $_values = array(
       'trusted_ips' => '',
       'enable_whitelists' => '0',
       'enable_warnlists' => '0',
       'enable_blacklists' => '0',
       'tag_mode_bypass_whitelist' => '1',
       'whitelist_both_from' => '0',
       'use_bayes' => 1,
       'bayes_autolearn' => 0,
       'use_rbls' => 1,
       'rbls_timeout' => 20,
       'sa_rbls' => '',
       'use_dcc' => 1,
       'dcc_timeout' => 10,
       'use_razor' => 1,
       'razor_timeout' => 10,
       'use_pyzor' => 1,
       'pyzor_timeout' => 10,
       'use_ocr' => 1,
       'use_pdfinfo' => 1,
       'use_imageinfo' => 1,
       'use_botnet' => 1,
       'use_spf' => 1,
       'spf_timeout' => 5,
       'use_dkim' => 1,
       'dkim_timeout' => 5,
       'dmarc_follow_quarantine_policy' => 1,
     );

	protected $_mapper;

	public function setId($id) {
	   $this->_id = $id;
	}
	public function getId() {
		return $this->_id;
	}

	public function setParam($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}

	public function getParam($param) {
		if (array_key_exists($param, $this->_values)) {
			return $this->_values[$param];
		}
		return null;
	}

	public function getAvailableParams() {
		$ret = array();
		foreach ($this->_values as $key => $value) {
			$ret[]=$key;
		}
		return $ret;
	}

	public function getParamArray() {
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
            $this->setMapper(new Default_Model_AntispamConfigMapper());
        }
        return $this->_mapper;
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

    public function useRBL($rbl) {
    	return preg_match('/\b'.$rbl.'\b/', $this->getParam('sa_rbls'));
    }
}
