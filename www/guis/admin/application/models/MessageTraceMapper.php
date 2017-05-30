<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Message trace mapper
 */

class Default_Model_MessageTraceMapper
{

	protected $_elements = 0;
	protected $_nbelements = 0;
	protected $_pages = 1;
	protected $_page = 0;


	public function find($id, $spam)
	{
	}

	public function fetchAllCount($params) {
		return $this->_nbelements;
	}

	public function startFetchAll($params) {
		$trace_id = 0;
		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
        foreach ($slaves as $s) {
        	$res = $s->sendSoapRequest('Logs_StartTrace', $params);
        	if (isset($res['trace_id'])) {
        		$trace_id = $res['trace_id'];
                $params['trace_id'] = $trace_id;
        	} else {
                        continue;
        	}
        }
        return $trace_id;
	}
	
	public function getStatusFetchAll($params) {
		$res = array('finished' => 0, 'count' => 0, 'data' => array());
	    $slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
        $params['noresults'] = 1;
        $stillrunning = count($slaves);
        $globalrows = 0;
        $params['soap_timeout'] = 20;
        foreach ($slaves as $s) {
        	$sres = $s->sendSoapRequest('Logs_GetTraceResult', $params);
        	if (isset($sres['error']) && $sres['error'] != "") {
                        $stillrunning--;
                        continue;
        	}
        	if (isset($sres['message']) && $sres['message'] == 'finished') {
        		$stillrunning--;
        	}
        	if (isset($sres['nbrows'])) {
        		$globalrows += $sres['nbrows'];
        	}
        }
        $res['count'] = $globalrows;
        if (!$stillrunning) {
        	$res['finished'] = 1;
        }
        return $res;
	}
	
    public function abortFetchAll($params) {
		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
        foreach ($slaves as $s) {
        	$res = $s->sendSoapRequest('Logs_AbortTrace', $params);
        }
        return $res;
	}
	
	public function fetchAll($params)
	{	
		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
		
        $entriesflat = array();
        $sortarray = array();
        $slaveentries = array();
        
        $params['noresults'] = 0;
        $params['soap_timeout'] = 20;
        $stillrunning = count($slaves);
        $globalrows = 0;
        foreach ($slaves as $s) {
            $sres = $s->sendSoapRequest('Logs_GetTraceResult', $params);
        	if (isset($sres['error']) && $sres['error'] != "") {
                        $stillrunning--;
                        continue;
        	}
        	if (isset($sres['message']) && $sres['message'] == 'finished') {
        		$stillrunning--;
        	}
        	if (isset($sres['nbrows'])) {
        		$globalrows += $sres['nbrows'];
        	}

        	foreach ($sres['data'] as $line) {
        		if (preg_match('/^(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)/', $line, $matches)) {
                    $id = md5(uniqid(mt_rand(), true)); 
        			$sortarray[$id] = $matches[1];
        			$entriesflat[$id] = $line;
        			$slaveentries[$id] = $s->getId();
        		}
        	}
        }
        
        if (isset($params['orderfield']) && $params['orderfield'] == 'date') {
        	if ($params['orderorder'] == 'asc') {
        		$params['orderorder'] = 'desc';
        	} else {
        		$params['orderorder'] = 'asc';
        	}
        }
        if (isset($params['orderorder']) && $params['orderorder'] == 'desc') {
           asort($sortarray);
        } else {
           arsort($sortarray);
        }
        $entries = array();
        foreach ($sortarray as $se => $sa) {
        	$e = $entriesflat[$se];
        	$entry = new Default_Model_MessageTrace();
        	$entry->loadFromLine($e);
        	$entry->setParam('slave_id', $slaveentries[$se]);
        	$entries[] = $entry;
        }
        
        $this->_nbelements = count($entries);
        
	    $mpp = 20;
		if (isset($params['mpp']) && is_numeric($params['mpp'])) {
			$mpp = $params['mpp'];
		}
		if ($this->_nbelements && $mpp) {
			$this->_pages = ceil($this->_nbelements / $mpp);
		}
		$this->_page = 1;
		if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0 && $params['page'] <= $this->_pages ) {
			$this->_page = $params['page'];
		}
		
		$entriespage = array_slice($entries, ($this->_page - 1) * $mpp, $mpp);
		
        return $entriespage;
	}

	public function getNbPages() {
		return $this->_pages;
	}
	public function getEffectivePage() {
		return $this->_page;
	}
}
