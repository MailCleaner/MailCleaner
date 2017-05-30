<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain table
 */

class Default_Model_DbTable_Domain extends Zend_Db_Table_Abstract
{
    protected $_name    = 'domain';
    protected $_primary = 'id';
    
    public function __construct() {
    	$this->_db = Zend_Registry::get('writedb');
    }
}
