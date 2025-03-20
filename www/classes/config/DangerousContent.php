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
 * This class is only a settings wrapper for the dangerous content scanner configuration
 */
class DangerousContent extends PrefHandler
{

    /**
     * scanner properties
     * @var array
     */
    private $pref_ = [
        'block_encrypt' => 1,
        'block_unencrypt' => 'no',
        'allow_passwd_archives' => 'no',
        'allow_partial' => 'no',
        'allow_external_bodies' => 'no',
        'allow_iframe' => 'no',
        'silent_iframe' => 'yes',
        'allow_form' => 'yes',
        'silent_form' => 'no',
        'allow_script' => 'yes',
        'silent_script' => 'no',
        'allow_webbugs' => 'yes',
        'silent_webbugs' => 'no',
        'allow_codebase' => 'no',
        'silent_codebase' => 'no',
        'notify_sender' => 'no'
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('dangerouscontent', 'd', $this->pref_);
    }

    /**
     * load datas from database
     * @return                boolean  true on success, false on failure
     */
    public function load()
    {
        return $this->loadPrefs('', '', false);
    }

    /**
     * save datas to database
     * @return    string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {
        $sysconf_ = SystemConfig::getInstance();
        $sysconf_->setProcessToBeRestarted('ENGINE');
        return $this->savePrefs('', '', '');
    }
}
