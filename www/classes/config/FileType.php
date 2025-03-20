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
 * This class handle a file type rule
 */
class FileType extends PrefHandler
{

    /**
     * file name properties
     */
    private $pref_ = [
        'id' => 0,
        'status' => 'deny',
        'type' => '',
        'name' => '',
        'description' => ''
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('filetype', 'f', $this->pref_);
    }

    /**
     * load datas from database
     * @param    $id   numeric  id of file type record
     * @return         boolean  true on success, false on failure
     */
    public function load($id)
    {
        if (!is_numeric($id)) {
            return false;
        }
        $where = " id=$id";
        return $this->loadPrefs('', $where, true);
    }

    /**
     * save datas to database
     * @return    boolean  true on success, false on failure
     */
    public function save()
    {
        if ($this->getPref('type') == "") {
            return 'NOTYPEGIVEN';
        }
        if ($this->getPref('name') == "") {
            $this->setPref('name', '-');
        }
        if ($this->getPref('description') == "") {
            $this->setPref('description', '-');
        }

        return $this->savePrefs('', '', '');
    }

    /**
     * delete datas from database
     * @return    string  'OKDELETED' on success, error message on failure
     */
    public function delete()
    {
        return $this->deletePrefs(null);
    }
}
