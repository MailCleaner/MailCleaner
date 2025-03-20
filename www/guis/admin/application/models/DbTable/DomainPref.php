<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Domain preferences table
 */

class Default_Model_DbTable_DomainPref extends Zend_Db_Table_Abstract
{
    protected $_name    = 'domain_pref';
    protected $_primary = 'id';

    public function __construct()
    {
        $this->_db = Zend_Registry::get('writedb');
    }
}
