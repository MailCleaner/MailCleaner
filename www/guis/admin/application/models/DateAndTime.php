<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Date and time settings
 */

class Default_Model_DateAndTime
{	
	protected $_datearray = array('hour' => 0, 'minute' => 0, 'second' => 0);
	
	public function __construct() {
	}
    
	public function load() {
		$currentdate = new Zend_Date();
        $this->_datearray = $currentdate->toArray();
	}

	public function getYear() {
		return $this->_datearray['year'];
	}
	public function setYear($year) {
		if ($year >= 2009) {
			$this->_datearray['year'] = $year;
		}
	}
	
	public function getMonth() {
		return $this->_datearray['month'];
	}
	public function setMonth($month) {
		if ($month > 0 && $month < 13) {
			$this->_datearray['month'] = $month;
		}
	}
	
	public function getDay() {
		return $this->_datearray['day'];
	}
	public function setDay($day) {
		if ($day > 0 && $day < 32) {
			$this->_datearray['day'] = $day;
		}
	}
	
	public function getHour() {
		return $this->_datearray['hour'];
	}
	public function setHour($hour) {
		if ($hour >= 0 && $hour < 24) {
			$this->_datearray['hour'] = $hour;
		}
	}
	
    public function getMinute() {
		return $this->_datearray['minute'];
	}
	public function setMinute($minute) {
		if ($minute >= 0 && $minute < 60) {
			$this->_datearray['minute'] = $minute;
		}
	}
	
    public function getSecond() {
		return $this->_datearray['second'];
	}
	public function setSecond($second) {
		if ($second >= 0 && $second < 60) {
			$this->_datearray['second'] = $second;
		}
	}
	
	public function setDate($string) {
		if (preg_match('/(\d+)\/(\d+)\/(\d+)/', $string, $matches)) {
			$this->setMonth($matches[1]);
			$this->setDay($matches[2]);
			$this->setYear($matches[3]);
		}
	}
	
	public function getDate() {
		return $this->getMonth()."/".$this->getDay()."/".$this->getYear();
	}
	
	public function getFullSystemString() {
		$str = sprintf('%02d%02d%02d%02d%04d.%02d',
		               $this->getMonth(), $this->getDay(), $this->getHour(), 
		               $this->getMinute(), $this->getYear(), $this->getSecond());
		return $str;
	}
	
    public function save()
    {
    	return Default_Model_Localhost::sendSoapRequest('Config_saveDateTime', $this->getFullSystemString());
    }
    	
}