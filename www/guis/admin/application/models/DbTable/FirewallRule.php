<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Firewall access table
 */

class Default_Model_DbTable_FirewallRule extends Zend_Db_Table_Abstract
{
    protected $_name    = 'external_access';

    public function __construct()
    {
        $this->_db = Zend_Registry::get('writedb');
    }
}
