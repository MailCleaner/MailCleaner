<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Localization settings
 */

class Default_Model_Localization
{	
	protected $_dlglist = array(
	                     'Africa' => 'Africa', 
	                     'America' => 'America',
	                     'Asia' => 'Asia', 
	                     'Atlantic' => 'Atlantic Ocean',
	                     'Australia' => 'Australia',
	                     'Europe' => 'Europe',
	                     'Indian' => 'Indian Ocean',
	                     'Pacific' => 'Pacific Ocean', 
	                     'Etc' => 'None of the above');
	
	protected $_timezonefile = '/etc/timezone';
	protected $_zoneinfodir = '/usr/share/zoneinfo';
	protected $_currenttimezone = array('US', 'Central');
	protected $_currentsubzones = array();
	
	public function __construct() {
	}
    
	public function load() {
	   
	   if (file_exists($this->_timezonefile)) {
	   	   $content = file($this->_timezonefile);
	   	   foreach ($content as $line) {
	   	   	  $line = preg_replace('/\s+$/', '', $line);
              $zones = preg_split('/\//', $line);
              if (isset($zones[0]) && isset($this->_dlglist[$zones[0]]) && isset($zones[1])) {
              	  $this->_currenttimezone = array($zones[0], $zones[1]);
              	  $this->getSubZones($zones[0]);
              }
	   	   }
	   }
	}
	
	public function getMainZone() {
		return $this->_currenttimezone[0];
	}
	public function setMainZone($zone) {
		if (isset($this->_dlglist[$zone])) {
			$this->_currenttimezone[0] = $zone;
		}
	}
	
 	public function getSubZone() {
		return $this->_currenttimezone[1];
	}
	public function setSubZone($zone) {
		$this->_currenttimezone[1] = $zone;
	}
	
	public function getZones() {
		return $this->_dlglist;
	}
	
	public function getFullZone() {
		return $this->_currenttimezone[0]."/".$this->_currenttimezone[1];
	}
	public function getTextMainZone() {
		return $this->_dlglist[$this->_currenttimezone[0]];
	}
    public function getTextSubZone() {
    	return preg_replace('/_/', ' ', $this->_currenttimezone[1]);
	}
	
	public function getSubZones() {
		$zone = $this->getMainZone();
		if (isset($this->_dlglist[$zone])) {
			$files = scandir($this->_zoneinfodir."/".$zone);
			foreach ($files as $f) {
				if ( !preg_match('/^\./', $f)) {
					$rf = preg_replace('/_/', ' ', $f);
                    $this->_currentsubzones[$f] = $rf;
				}
			}
		}
		return $this->_currentsubzones;
	}
	
    public function save()
    {
    	return Default_Model_Localhost::sendSoapRequest('Config_saveTimeZone', $this->getMainZone()."/".$this->getSubZone());
    }
    	
}