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
 * This will takes care of fetching list of filtered domains
 */
class DomainList extends ListManager
{

    /**
     * load domains from database
     * @return  boolean  true on success, false on failure
     */
    public function Load()
    {
        require_once('helpers/DM_SlaveConfig.php');
        $db_slaveconf = DM_SlaveConfig::getInstance();

        global $admin_;

        $query = "SELECT name FROM domain WHERE name != '__global__'";
        $row = $db_slaveconf->getList($query);
        foreach ($row as $domain) {
            if ($admin_->canManageDomain($domain)) {
                $d = new Domain();
                $d->load($domain);
                $this->setElement($domain, $d);
            }
        }
        return true;
    }

    /**
     * check if a domain is filtered or not
     * @param  $d  string  domain name
     * @return     boolean true if domain is filtered, false if not
     */
    public function is_filtered($d)
    {
        return $this->hasElement($d);
    }
}
