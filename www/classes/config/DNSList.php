<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this is a preference handler
 */
require_once('helpers/PrefHandler.php');

/**
 * This class contains DNS lists information
 */
class DNSList extends PrefHandler
{

    /**
     * scanner properties
     * @var array
     */
    private $pref_ = [
        'name' => '',
        'url' => '',
        'type' => '',
        'active' => 1,
        'comment' => '',
    ];


    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('dnslist', 'l', $this->pref_);
    }

    /**
     * load datas from database
     * @param  $listname      string  list name
     * @return                boolean  true on success, false on failure
     */
    public function load($list_name)
    {
        $where = "name='$list_name'";
        return $this->loadPrefs('', $where, false);
    }

    /**
     * save datas to database
     * @return    string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {
        $where = "name='" . $this->getPref('name') . "'";
        return $this->savePrefs('', $where, '');
    }

    public function isEnabled($givenlist)
    {
        return preg_match("/\b" . $this->getPref('name') . "\b/", $givenlist);
    }
}
