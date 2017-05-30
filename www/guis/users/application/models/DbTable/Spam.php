<?php

/**
 * MailCleaner
 *
 * @license http://www.mailcleaner.net/open/licence_en.html MailCleaner Public License
 * @copyright 2015 Fastnet SA
 */

/**
 * Spam controller
 */
class Default_Model_DbTable_Spam extends Zend_Db_Table_Abstract
{
    protected $_name    = 'spam';
    protected $_primary = 'exim_id';
    
    public function __construct() {
    	$this->_db = Zend_Registry::get('spooldb');
    }
}
