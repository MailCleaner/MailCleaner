<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Message trace
 */

class Default_Model_MessageTrace
{
	protected $_values = array(
	    'date_in' => '',
	    'accepted' => '',
            'relayed' => '',
	    'inreport' => '',
	    'inid' => '',
	    'from' => '',
	    'tos' => '',
	    'outid' => '',
	    'spam' => '',
	    'spamreport' => '',
	    'content' => '',
	    'contentreport' => '',
	    'date_out' => '',
	    'delivered' => '',
	    'outreport' => '',
            'outmessage' => '',
	    'slave_id' => 0,
            'senderhostname' => '',
            'senderhostip' => '',
            'outhost' => ''
	);

	protected $_mapper;

	public function setParam($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}

	public function getParam($param) {
		$ret = null;
		if (array_key_exists($param, $this->_values)) {
			$ret = $this->_values[$param];
		}
		if ($ret == 'false') {
			return 0;
		}
		return $ret;
	}

	public function getCleanParam($param) {
		$t = Zend_Registry::get('translate');
		$split_fields = array(
        'subject' => 80,
		);
		$data = $this->getParam($param);
		
		if ($param == 'from' || $param == 'tos') {
			$data = $this->getCleanAddress($data);
		}

	    if (preg_match('/(\=\?[^?]{3,15}\?.\?[^?]+\?\=)/', $data, $matches)) {
				$ddata = @iconv_mime_decode($matches[1], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
				$data = preg_replace('/\=\?[^?]{3,15}\?.\?[^?]+\?\=/', $ddata, $data);
		}

		$ret = htmlentities($data, ENT_COMPAT, "UTF-8");	
		if ($param == 'report') {
			$ret = preg_replace('/,/', '<br />', $ret);
		}
		
		if ($param == 'size' && is_numeric($ret)) {
			if ($ret > 1000000000) {
				$ret = ($ret / 1000000000)." ".$t->_('Gb');
			} else if ($ret > 1000000) {
				$ret = ($ret / 1000000)." ".$t->_('Mb');
			} else if ($ret > 1000) {
				$ret = ($ret / 1000)." ".$t->_('Kb');
			} else {
				$ret .= " ".$t->_('bytes');
			}
		} 
		if (isset($split_fields[$param]) && (strlen($ret) > $split_fields[$param])) {
			$ret = substr($ret, 0, $split_fields[$param]);
			$ret .= '...';
		}

		if ($ret == '') {
			$ret = '-';
		}
		return htmlentities($ret);
	}

	public function getParamArray() {
		return $this->_values;
	}

	public function getAvailableParams() {
		$ret = array();
		foreach ($this->_values as $key => $value) {
			$ret[]=$key;
		}
		return $ret;
	}

	public function setId($id) {
		$this->_id = $id;
	}
	public function getId() {
		return $this->_id;
	}

	public function setMapper($mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}

	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Default_Model_MessageTraceMapper());
		}
		return $this->_mapper;
	}

	public function getCleanAddress($address, $nocut = 0) {
		$locallen = 25;
		$domainlen = 25;
		$res = $address;
		$ca = array();
		foreach (split(',', $address) as $a) {
		if (preg_match('/(\S+)\@(\S+)/', $a, $matches)) {
			$str = "";
			if (strlen($matches[1]) > $locallen && !$nocut) {
				$str .= substr($matches[1], 0, $locallen)."...";
			} else {
				$str .= $matches[1];
			}

			$str.="@";
			if (strlen($matches[2]) > $domainlen && !$nocut) {
				$str .= substr($matches[2], 0, $domainlen)."...";
			} else {
				$str .= $matches[2];
			}
			$ca[] = $str;
		}
		}
		$str = implode(', ', $ca);
		$str = htmlspecialchars($str);
                if ($nocut) {
                   return $str;
                }
		return substr($str, 0, 50);
	}

	public function find($id)
	{
		$this->getMapper()->find($id, $this);
		return $this;
	}
	
	public function loadFromLine($line) {
		if (preg_match('/^([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)/', $line, $matches)) {
			$this->setParam('date_in', $matches[1]);
                        $this->setParam('slave_id', $matches[2]);
                        $this->setParam('senderhostname', $matches[3]);
                        $this->setParam('senderhostip', $matches[4]);
			$this->setParam('accepted', $matches[5]);
                        $this->setParam('relayed', $matches[6]);
			$this->setParam('inreport', $matches[7]);
			$this->setParam('inid', $matches[8]);
			$this->setParam('from', $matches[9]);
			$this->setParam('tos', $matches[10]);
			$this->setParam('outid', $matches[11]);
			$this->setParam('spam', $matches[12]);
			$this->setParam('spamreport', $matches[13]);
			$this->setParam('content', $matches[14]);
			$this->setParam('contentreport', $matches[15]);
			$this->setParam('date_out', $matches[16]);
			$this->setParam('delivered', $matches[17]);
			$this->setParam('outreport', $matches[18]);
                        $this->setParam('outmessage', $matches[19]);
                        $this->setParam('outhost', rtrim($matches[20]));
		}
	}

	public function startFetchAll($params) {
		return $this->getMapper()->startFetchAll($params);
	}
	public function getStatusFetchAll($params) {
		return $this->getMapper()->getStatusFetchAll($params);
	}
	public function abortFetchAll($params) {
		return $this->getMapper()->abortFetchAll($params);
	}
	public function fetchAllCount($params = NULL) {
		return $this->getMapper()->fetchAllCount($params);
	}
	public function fetchAll($params = NULL) {
		return $this->getMapper()->fetchAll($params);
	}
	

	public function getNbPages() {
		return $this->getMapper()->getNbPages();
	}
	public function getEffectivePage() {
		return $this->getMapper()->getEffectivePage();
	}
}
