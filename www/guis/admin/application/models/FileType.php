<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * File type
 */

class Default_Model_FileType
{
    protected $_id;
    protected $_values = [
        'status' => 'allow',
        'type' => '',
        'name' => '',
        'description' => ''
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
            $this->setMapper(new Default_Model_FileTypeMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function fetchAll()
    {
        return $this->getMapper()->fetchAll();
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        return $this->getMapper()->delete($this);
    }

    public function getStatus()
    {
        if ($this->getParam('status') != 'deny') {
            return 0;
        }
        return 1;
    }

    public function disable()
    {
        $this->setParam('status', 'allow');
        $this->save();
    }
    public function enable()
    {
        $this->setParam('status', 'deny');
        $this->save();
    }


    public function getComment()
    {
        if ($this->getParam('description') != '-') {
            return $this->getParam('description');
        }
        return '';
    }
}
