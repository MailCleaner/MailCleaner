<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Quarantined dangerous content mapper
 */

class Default_Model_QuarantinedContentMapper
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

	public function fetchAll($params)
	{	
		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
		
        $entriesflat = array();
        $sortarray = array();
        
        if (!isset($params['orderfield'])) {
        	$params['orderfield'] = 'to_address';
        } else {
        	if ($params['orderfield'] == 'to') {
        		$params['orderfield'] = 'to_address';
        	}
            if ($params['orderfield'] == 'from') {
        		$params['orderfield'] = 'from_address';
        	}
        }
        foreach ($slaves as $s) {
        	$res = $s->sendSoapRequest('Content_fetchAll', $params, 10000);
        	foreach ($res as $r) {
        		$r['store_id'] = $s->getId();
        		$entriesflat[$r['id']] = $r;
        		$sortarray[$r['id']] = $r[$params['orderfield']].''.$r['time'];
        	}
        }
        
        if ($params['orderfield'] == 'date') {
        	if ($params['orderorder'] == 'asc') {
        		$params['orderorder'] = 'desc';
        	} else {
        		$params['orderorder'] = 'asc';
        	}
        }
        if ($params['orderorder'] == 'desc') {
           asort($sortarray);
        } else {
           arsort($sortarray);
        }
        $entries = array();
        foreach ($sortarray as $se => $sa) {
        	$e = $entriesflat[$se];
        	$entry = new Default_Model_QuarantinedContent();
		foreach (array('store_id', 'id', 'size', 'from_address', 'to_address', 'to_domain', 'subject', 'virusinfected', 'nameinfected', 'otherinfected', 'report', 'date', 'time', 'content_forced') as $p) {
                $entry->setParam($p, $e[$p]);
        	}
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
