<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Greylist daemon configuration
 */

class Default_Model_GreylistConfig
{
    protected $_id;
    protected $_values = [
        'retry_min' => 0,
        'retry_max' => 0,
        'expire' => 0,
        'avoid_domains' => ''
    ];

    protected $_mapper;

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        if (array_key_exists($param, $this->_values)) {
            return $this->_values[$param];
        }
        return null;
    }

    public function getAvailableParams()
    {
        $ret = [];
        foreach ($this->_values as $key => $value) {
            $ret[] = $key;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_GreylistConfigMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }
}
