<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Pending alias request
 */

class Default_Model_PendingAlias
{
    protected $_id;

    protected $_values = [
        'date_in' => '',
        'alias' => '',
        'user' => 0,
    ];

    protected $_mapper;

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        $ret = null;
        if (array_key_exists($param, $this->_values)) {
            $ret = $this->_values[$param];
        }
        if ($ret == 'false') {
            return 0;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_PendingAliasMapper());
        }
        return $this->_mapper;
    }

    public function find($address)
    {
        $this->getMapper()->find($address, $this);
        return $this;
    }

    public function fetchAll($params = NULL)
    {
        return $this->getMapper()->fetchAll($params);
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        return $this->getMapper()->delete($this);
    }
}
