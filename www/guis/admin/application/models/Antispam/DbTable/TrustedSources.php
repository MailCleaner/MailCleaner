<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * TrustedSources internal table
 */

class Default_Model_Antispam_DbTable_TrustedSources extends Zend_Db_Table_Abstract
{
    protected $_name    = 'trustedSources';
    protected $_primary = 'set_id';

    public function __construct()
    {
        $this->_db = Zend_Registry::get('writedb');
    }
}
