<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka, John Mertz
 * @copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>; 2023, John Mertz
 * Registration CE form
 */

class Default_Model_RegistrationCEManager
{
    private $_serial = ['abc1', 'def2', 'ghi3'];
    private $_data = [
        'first_name'  => '',
        'last_name' => '',
        'email' => '',
        'company_name' => '',
        'address' => '',
        'postal_code' => '',
        'city' => '',
        'country' => '',
        'accept_newsletters' => 0,
        'accept_releases' => 0,
        'accept_send_statistics' => 0
    ];


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
        //return Default_Model_Localhost::sendSoapRequest('Config_saveRegistration', $this->getSerialString());
        $this->_data['timeout'] = 200;
        return Default_Model_Localhost::sendSoapRequest('Config_register_ce', $this->_data);
    }
}
