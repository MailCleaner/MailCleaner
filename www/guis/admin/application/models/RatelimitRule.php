<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP rate limiting rule
 */

class Default_Model_RatelimitRule
{
   protected $_ratelimit_count = '0';
   protected $_ratelimit_interval = '0s';
   
   protected $_time_units = array('s' => 'seconds', 'm' => 'minutes', 'h' => 'hours', 'd' => 'days');
   
   public function __construct($str) {
   	  if (preg_match('/(\d+)\s*\/\s*(\d+[smhd])/', $str, $matches)) {
   	  	$this->_ratelimit_interval = $matches[2];
   	  	$this->_ratelimit_count = $matches[1];
   	  }
   }
   
   public function getCountValue() {
   	  return $this->_ratelimit_count;
   }
   
   public function getIntervalUnit() {
   	  if (preg_match('/^(\d)([smhd])/', $this->_ratelimit_interval, $matches)) {
   	  	return $matches[2];
   	  }
   	  return 's';
   }
   
   public function getIntervalValue() {
   	  if (preg_match('/^(\d)([smhd])/', $this->_ratelimit_interval, $matches)) {
   	  	return $matches[1];
   	  }
   	  return $this->_ratelimit_interval;
   }
   
   public function getUnits() {
   	  return $this->_time_units;
   }
   
   public function setCount($value) {
   	  if (!is_numeric($value)) {
   	  	 $value = 0;
   	  }
   	  $this->_ratelimit_count = $value;
   }
   public function setInterval($value, $unit) {
   	  if (!array_key_exists($unit, $this->_time_units)) {
   	  	$unit = 's';
   	  }
   	  if (!is_numeric($value)) {
   	  	 $value = 0;
   	  }
   	  $this->_ratelimit_interval = $value.$unit;
   }
  
   public function getRatelimitRule() {
   	 return $this->_ratelimit_count." / ".$this->_ratelimit_interval." / strict";
   }
   
}