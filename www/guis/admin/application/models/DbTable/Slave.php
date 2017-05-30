<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Slave servers table
 */

class Default_Model_DbTable_Slave extends Zend_Db_Table_Abstract
{
    protected $_name    = 'slave';
    
    public function __construct() {
    	$this->_db = Zend_Registry::get('writedb');
    }
}
