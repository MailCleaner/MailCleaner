<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Logfile
 */

class Default_Model_Logfile
{	
	protected $_available_types = array(
	      'exim_stage1' => array('basefile' => 'exim_stage1/mainlog', 'cat' => 'Message handling', 'name' => 'Incoming MTA', 'pos' => 1, 'nextId_regex' => 'R=filter_forward.*OK id=([0-9a-zA-Z]{6}-[0-9a-zA-Z]{6,11}-[0-9a-zA-Z]{2,4})'),
	      'exim_stage2' => array('basefile' => 'exim_stage2/mainlog', 'cat' => 'Message handling', 'name' => 'Filtering MTA', 'pos' => 2, 'nextId_regex' => '\b([0-9a-zA-Z]{6}-[0-9a-zA-Z]{6,11}-[0-9a-zA-Z]{2,4})\b'),
	      'exim_stage4' => array('basefile' => 'exim_stage4/mainlog', 'cat' => 'Message handling', 'name' => 'Outgoing MTA', 'pos' => 3, 'nextId_regex' => '\b([0-9a-zA-Z]{6}-[0-9a-zA-Z]{6,11}-[0-9a-zA-Z]{2,4})\b.*T=spam_store'),
	
	      'mailscanner' => array('basefile' => 'mailscanner/infolog', 'cat' => 'Filter engine', 'name' => 'Filtering engine', 'pos' => 1, 'nextId_regex' => '\b([0-9a-zA-Z]{6}-[0-9a-zA-Z]{6,11}-[0-9a-zA-Z]{2,4})\b'),
	      'clamd' => array('basefile' => 'clamav/clamd.log', 'cat' => 'Filter engine', 'name' => 'Virus signatures engine', 'pos' => 4),
	      'spamd' => array('basefile' => 'mailscanner/spamd.log', 'cat' => 'Filter engine', 'name' => 'SpamAssassin engine', 'pos' => 2),
	      'clamspamd' => array('basefile' => 'clamav/clamspamd.log', 'cat' => 'Filter engine', 'name' => 'Spam signatures engine', 'pos' => 3),
	      'spamhandler' => array('basefile' => 'mailcleaner/SpamHandler.log', 'cat' => 'Filter engine', 'name' => 'Spam handling process', 'pos' => 5),
	
	      'freshclam' =>  array('basefile' => 'clamav/freshclam.log', 'cat' => 'Updates', 'name' => 'Antivirus engine', 'pos' => 1),
	
	      'apache' => array('basefile' => 'apache/access.log', 'cat' => 'Misc', 'name' => 'Web server', 'pos' => 1),
	      'mysql_slave' => array('basefile' => 'mysql_slave/mysql.log', 'cat' => 'Misc', 'name' => 'Local database', 'pos' => 2),
	);
	
	protected $_values = array(
 	      'file' => '',
	      'linkname' => '',
	      'date' => '',
	      'size' => '',
	      'slave_id' => '',
	      'type' => '',
	      'category' => '',
	      'name' => '',
	      'shortfile' => ''
	);
	
	protected $_delivery_log_order = array('exim_stage1', 'mailscanner','exim_stage4','spamhandler');
	
	public function getCategories() {
		$ret = array();
		foreach ($this->_available_types as $type => $t) {
			if (!in_array($t['cat'], $ret)) {
				array_push($ret, $t['cat']);
			}
		}
		return $ret;
	}

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

	public function find($type, $pdate, $slave = 1)
	{
		if (!$type || !isset($this->_available_types[$type]) || !$pdate || !preg_match('/^\d{8}$/', $pdate, $matches) || !$slave || !is_numeric($slave)) {
			return $this;
		}
		$date = new Zend_Date($pdate, 'yyyyMMdd');
		$today = new Zend_Date();
		
		$diff = $today->get(Zend_Date::TIMESTAMP) - $date->get(Zend_Date::TIMESTAMP);
		$days = floor(($diff / 86400));
		
		echo "file: ".$filename;
		
		return $this;
	}

	public function fetchAll($params = NULL) {
		$res = array();

		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
        $params['files'] = array();
        foreach ($this->_available_types as $c => $v) {
        	$params['files'][] = $v['basefile'];
        }
        foreach ($slaves as $s) {
            $sres = $s->sendSoapRequest('Logs_FindLogFiles', $params);
            foreach ($sres['files'] as $f ) {
                $l = new Default_Model_Logfile();
                $l->setParam('slave_id', $s->getId());
                $l->setParam('file', $f['file']);
                if (isset($f['size'])) {
                    $l->setParam('size', $f['size']);
                }
                $l->setParam('category', $this->getValueFromFile($f['basefile'], 'cat'));
                $l->setParam('name', $this->getValueFromFile($f['basefile'], 'name'));
                $l->setParam('shortfile', '');
                if (preg_match('/([^\/]+\/[^\/]+)$/', $l->getParam('file'), $matches)) {
                	$shortfile = $matches[1];
                	$l->setParam('shortfile', preg_replace('/\//', '-', $shortfile));
                }
                $l->setParam('link', preg_replace('/\//', '-', $l->getParam('file')));
                $res[] = $l;
            }           
        }
        return $res;
	}
	
	public function getValueFromFile($cat, $value) {
		foreach ($this->_available_types as $c) {
            if ($c['basefile'] == $cat) {
            	return $c[$value];
            }
		}
		return '';
	}
	public function getSize() {
		$size = $this->getParam('size');
		
		$t = Zend_Registry::get('translate');
		$str = $size." ".$t->_('bytes');
		if ($size > 1000*1000*1024) {
			return round($size / (1000*1000*1024), 2)." ".$t->_('Gb');
		}
	    if ($size > 1000*1024) {
			return round($size / (1000*1024), 2)." ".$t->_('Mb');
		}
	    if ($size > 1024) {
			return round($size / (1024), 2)." ".$t->_('Kb');
		}
		return $str;
	}
	
	public function loadByFileName($logfilename) {
		$filename = preg_replace('/-/', '/', $logfilename);
		$filename = preg_replace('/\.\d+(\.gz)?$/', '', $filename);
		
		foreach ($this->_available_types as $ck => $c) {
			if ($c['basefile'] == $filename) {
				$this->setParam('type', $ck);
                $this->setParam('name', $this->getValueFromFile($filename, 'name'));
                $this->setParam('category', $this->getValueFromFile($filename, 'cat'));
                $this->setParam('file', $logfilename);
                return;
			}
		}
	}
	
	public function getNextIdRegex() {
		if (isset($this->_available_types[$this->getParam('type')]['nextId_regex'])) {
    		return $this->_available_types[$this->getParam('type')]['nextId_regex'];
		}
		return '';
	}
	
	public function getNextLog() {
		$ext = '';
		if (preg_match('/(\.\d+(\.gz)?)$/', $this->getParam('file'), $matches)) {
			$ext = $matches[1];
		}
		$next = false;
		reset($this->_delivery_log_order);
		foreach ($this->_delivery_log_order as $type) {
			if ($type == $this->getParam('type')) {
				$next = current($this->_delivery_log_order);
			}
		}
		if ($next) {
    		return $this->_available_types[$next]['basefile'].$ext;
		}
		return '';
	}
}
