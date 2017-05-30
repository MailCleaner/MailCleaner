<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Pending alias request mapper
 */

class Default_Model_RRDGraphicMapper
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
            $this->setDbTable('Default_Model_DbTable_RRDGraphic');
        }
        return $this->_dbTable;
    }

    public function find($id, Default_Model_RRDGraphic $graphic)
    {
        $query = $this->getDbTable()->select();
        if (is_numeric($id)) {
            $query->where('id = ?', $id);
        $result = $this->getDbTable()->fetchAll($query);
        } else {
            if (preg_match('/^([a-z0-9]+)_([a-z0-9]+)$/', $id, $matches)) {
            	$query->where('name = ?', $matches[1]);
                $result = $this->getDbTable()->fetchAll($query);
                if (count($result) > 1) {
            	    $query->where('type = ?', $matches[2]);
                    $result = $this->getDbTable()->fetchAll($query);
                }
            }
        }
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $graphic->setId($row->id);
        $graphic->setName($row->name);
        $graphic->setType($row->type);
        $graphic->setFamily($row->family);
        $graphic->setBase($row->base);
        $graphic->setYValue($row->min_yvalue);
        $elements = new Default_Model_RRDGraphicElement();
        $graphic->addElements($elements->fetchAll(array('graphicid' => $graphic->getID())));
    }
    
    public function fetchAll($params)
    {
        $elements = array();
        
        $query = $this->getDbTable()->select();
        foreach ($params as $key => $value) {
        	if ($value) {
            	$query->where($key.' = ?', $value);
        	}
        }
        $resultSet = $this->getDbTable()->fetchAll($query);
        foreach ($resultSet as $row) {
        	$element = new Default_Model_RRDGraphic();
            $element->find($row->id);
            $elements[] = $element;
        }
        return $elements;
    }
}