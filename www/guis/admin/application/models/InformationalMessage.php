<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Administrator
 */

class Default_Model_InformationalMessage
{
    protected $_title = 'Unknown message';
    protected $_description = '';
    protected $_slaves = [];
    protected $_toshow = false;
    protected $_link = [];

    public function getTitle()
    {
        $t = Zend_Registry::get('translate');
        return $t->_($this->_title);
    }

    public function getDescription()
    {
        $t = Zend_Registry::get('translate');
        return $t->_($this->_description);
    }

    public function fetchAll()
    {
        return [];
    }

    public function shouldShow()
    {
        return $this->_toshow;
    }

    public function getLink()
    {
        if (isset($this->_link['controller'])) {
            return $this->_link;
        }
        return null;
    }

    public function getSlavesList()
    {
        return implode(', ', $this->_slaves);
    }

    public function addSlave($slave)
    {
        if (!in_array($slave, $this->_slaves)) {
            array_push($this->_slaves, $slave);
        }
    }
}
