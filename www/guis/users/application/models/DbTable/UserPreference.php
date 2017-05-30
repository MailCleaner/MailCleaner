<?php
/**
 * MailCleaner
 * 
 * @license http://www.mailcleaner.net/open/licence_en.html MailCleaner Public License
 * @copyright 2015 Fastnet SA
 */

/**
 * @author jpgrossglauser
 * Class for user table
 */
class Default_Model_DbTable_UserPreference extends Zend_Db_Table_Abstract
{
    /**
     * @see end_Db_Table_Abstract
     */
    protected $_name    = 'user_pref';

    /**
     * @var array
     */
    protected $_dependentTables = array('user');
        
    /**
     * Constructor
     */
    public function __construct() {
    	$this->_db = Zend_Registry::get('writedb');
    }
}
