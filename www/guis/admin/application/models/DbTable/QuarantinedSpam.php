<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Quarantined spam table
 */

class Default_Model_DbTable_QuarantinedSpam extends Zend_Db_Table_Abstract
{
    protected $_name    = 'spam';
    protected $_primary = 'exim_id';
    
    public function __construct() {
    	$this->_db = Zend_Registry::get('spooldb');
    }

    public function setTableName($name) {
    	$this->_name = $name;
    }
    
    public function getTableName() {
    	return $this->_name;
    }
    
}
