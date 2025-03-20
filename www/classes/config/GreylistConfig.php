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
 * This class is only a settings wrapper for the Greylist Daemon configurations
 */
class GreylistConfig extends PrefHandler
{

    /**
     * antispam settings
     * @var array
     */
    private $pref_ = [
        'retry_min' => 120,
        'retry_max' => 28800,
        'expire' => 5184000,
        'avoid_domains' => 'df'
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('greylistd_config', 'g', $this->pref_);
    }

    /**
     * load datas from database
     * @return         boolean  true on success, false on failure
     */
    public function load()
    {
        return $this->loadPrefs('', '', false);
    }

    /**
     * save datas to database
     * @return    boolean  true on success, false on failure
     */
    public function save()
    {
        $sysconf_ = SystemConfig::getInstance();
        $sysconf_->setProcessToBeRestarted('GREYLISTD');
        return $this->savePrefs('', '', '');
    }
}
