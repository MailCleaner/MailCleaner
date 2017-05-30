<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Commtouch internal table
 */

class Default_Model_Antispam_DbTable_MessageSniffer extends Zend_Db_Table_Abstract
{
    protected $_name    = 'MessageSniffer';
    protected $_primary = 'set_id';
    
    public function __construct() {
    	$this->_db = Zend_Registry::get('writedb');
    }
}
