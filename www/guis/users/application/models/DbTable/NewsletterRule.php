<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @copyright 2015 Fastnet SA
 */
class Default_Model_DbTable_NewsletterRule extends Zend_Db_Table_Abstract
{
    protected $_name    = 'wwlists';

    public function __construct() {
    	$this->_db = Zend_Registry::get('writedb');
    }
}
