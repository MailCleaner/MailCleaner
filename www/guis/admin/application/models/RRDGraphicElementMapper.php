<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Pending alias request mapper
 */

class Default_Model_RRDGraphicElementMapper
{

    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Default_Model_DbTable_RRDGraphicElement');
        }
        return $this->_dbTable;
    }

    public function find($elementid, Default_Model_RRDGraphicElement $element)
    {
        $query = $this->getDbTable()->select();
        $query->where('id = ?', $elementid);

        $result = $this->getDbTable()->fetchAll($query);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $element->setId($row->id);
        $element->setParam('name', $row->name);
        $element->setParam('type', $row->type);
        $element->setParam('function', $row->function);
        $element->setParam('oid', $row->oid);
        $element->setParam('draw_name', $row->draw_name);
        $element->setParam('draw_order', $row->draw_order);
        $element->setParam('draw_style', $row->draw_style);
        $element->setParam('min', $row->min);
        $element->setParam('max', $row->max);
        $element->setParam('draw_factor', $row->draw_factor);
        $element->setParam('draw_format', $row->draw_format);
        $element->setParam('draw_unit', $row->draw_unit);
    }

    public function fetchAll($params)
    {
        $query = $this->getDbTable()->select();
        if (isset($params['graphicid'])) {
            $query->where('stats_id = ?', $params['graphicid']);
        }
        $query->order('draw_order');

        $elements = [];
        $resultSet = $this->getDbTable()->fetchAll($query);
        foreach ($resultSet as $row) {
            $e = new Default_Model_RRDGraphicElement();
            $e->find($row->id);
            $elements[] = $e;
        }
        return $elements;
    }
}

