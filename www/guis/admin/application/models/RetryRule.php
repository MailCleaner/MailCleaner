<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SMTP retry rule
 */

class Default_Model_RetryRule
{
    protected $_retry_cutoff = '0s';
    protected $_retry_delay = '0s';

    protected $_time_units = ['s' => 'seconds', 'm' => 'minutes', 'h' => 'hours', 'd' => 'days'];

    public function __construct($str)
    {
        if (preg_match('/F,(\d+[smhd]),(\d+[smhd])/', $str, $matches)) {
            $this->_retry_delay = $matches[2];
            $this->_retry_cutoff = $matches[1];
        }
    }

    public function getDelayValue()
    {
        if (preg_match('/^(\d+)([smhd])/', $this->_retry_delay, $matches)) {
            return $matches[1];
        }
        return $this->_retry_delay;
    }

    public function getDelayUnit()
    {
        if (preg_match('/^(\d+)([smhd])/', $this->_retry_delay, $matches)) {
            return $matches[2];
        }
        return 's';
    }

    public function getCutoffValue()
    {
        if (preg_match('/^(\d+)([smhd])/', $this->_retry_cutoff, $matches)) {
            return $matches[1];
        }
        return $this->_retry_cutoff;
    }

    public function getCutoffUnit()
    {
        if (preg_match('/^(\d+)([smhd])/', $this->_retry_cutoff, $matches)) {
            return $matches[2];
        }
        return 's';
    }

    public function getUnits()
    {
        return $this->_time_units;
    }

    public function setDelay($value, $unit)
    {
        if (!array_key_exists($unit, $this->_time_units)) {
            $unit = 's';
        }
        if (!is_numeric($value)) {
            $value = 0;
        }
        $this->_retry_delay = $value . $unit;
    }
    public function setCutoff($value, $unit)
    {
        if (!array_key_exists($unit, $this->_time_units)) {
            $unit = 's';
        }
        if (!is_numeric($value)) {
            $value = 0;
        }
        $this->_retry_cutoff = $value . $unit;
    }

    public function getRetryRule()
    {
        return 'F,' . $this->_retry_cutoff . ',' . $this->_retry_delay;
    }
}
