<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * System registration
 */

class Default_Model_UnRegistrationManager
{
    private $_serial = ['abc1', 'def2', 'ghi3'];
    private $_data = ['rsp' => ''];

    public function load()
    {
        //TODO: implement
    }

    public function setSerialPart($part, $string)
    {
        if (!preg_match('/^[A-Za-z0-9]{4}$/', $string)) {
            return false;
        }
        if (!isset($this->_serial[$part])) {
            return false;
        }
        $this->_serial[$part] = $string;
    }
    public function getSerialPart($part)
    {
        if (isset($this->_serial[$part - 1])) {
            return $this->_serial[$part - 1];
        }
        return '';
    }
    public function getSerialString()
    {
        $str = '';
        foreach ($this->_serial as $s) {
            $str .= "-$s";
        }
        $str = preg_replace('/^-/', '', $str);
        return $str;
    }

    public function setData($what, $value)
    {
        $this->_data[$what] = $value;
    }
    public function getData($what)
    {
        if (isset($this->_data[$what])) {
            return $this->_data[$what];
        }
        return '';
    }


    public function save()
    {
        #return Default_Model_Localhost::sendSoapRequest('Config_saveRegistration', $this->getSerialString());
        $this->_data['timeout'] = 200;
        return Default_Model_Localhost::sendSoapRequest('Config_unregister', $this->_data);
    }
}
