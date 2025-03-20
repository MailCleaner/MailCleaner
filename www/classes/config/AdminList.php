<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this is as list
 */
require_once('helpers/ListManager.php');

/**
 * This will takes care of fetching list of administrators
 */
class AdminList extends ListManager
{

    /**
     * load adminsitrator from database
     * @return  boolean  true on success, false on failure
     */
    public function Load()
    {
        require_once('helpers/DM_SlaveConfig.php');
        $db_slaveconf = DM_SlaveConfig::getInstance();

        $query = "SELECT username FROM administrator";
        $row = $db_slaveconf->getList($query);
        foreach ($row as $admin) {
            $this->setElement($admin, $admin);
        }
        return true;
    }
}
