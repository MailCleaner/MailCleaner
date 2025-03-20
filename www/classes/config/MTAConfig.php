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
 * This class is only a settings wrapper for the exim configurations
 */
class MTAConfig extends PrefHandler
{
    /**
     * the exim instance to be configured
     * @var  numeric
     */
    private  $stage_ = 1;

    /**
     * exim instance settings
     * @var array
     */
    private $pref_ = [
        'header_txt' => '',
        'accept_8bitmime' => 'true',
        'print_topbitchars' => 'true',
        'return_path_remove' => 'true',
        'ignore_bounce_after' => '2d',
        'timeout_frozen_after' => '7d',
        'smtp_relay' => 'true',
        'relay_from_hosts' => '',
        'smtp_receive_timeout' => '30s',
        'smtp_accept_max_per_host' => 10,
        'smtp_accept_max' => 50,
        'smtp_accept_queue_per_connection' => 10,
        'smtp_conn_access' => '*',
        'host_reject' => '',
        'sender_reject' => '',
        'verify_sender' => 0,
        'global_msg_max_size' => '50M',
        'max_rcpt' => 1000,
        'received_headers_max' => 30,
        'use_incoming_tls' => 0,
        'tls_certificate' => 'default',
        'use_syslog' => 0,
        'smtp_banner' => '$smtp_active_hostname ESMTP Exim $version_number $tod_full'
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('mta_config', 'c', $this->pref_);
    }

    /**
     * set the actual exim instance
     * @param  $stage  numeric   exim stage instance
     * @return         boolean   true on success, false on failure
     */
    private function setStage($stage)
    {
        if (is_numeric($stage)) {
            $this->stage_ = $stage;
            return true;
        }
        return false;
    }

    /**
     * get the actual exim instance
     * @return  numeric  exim stage instance
     */
    public function getStage()
    {
        return $this->stage_;
    }

    /**
     * load datas from database
     * @param  $stage  numeric  exim stage instance
     * @return         boolean  true on success, false on failure
     */
    public function load($stage)
    {

        if (!$this->setStage($stage)) {
            return false;
        }

        return $this->loadPrefs('', 'stage=' . $this->getStage(), false);
    }

    /**
     * save datas to database
     * @return    boolean  true on success, false on failure
     */
    public function save()
    {

        $relay = $this->getPref('relay_from_hosts');
        $relay = preg_replace('/[\s\n]+/', ':', $relay);
        $relay = preg_replace('/:$/', '', $relay);
        $this->setPref('relay_from_hosts', $relay);
        $sysconf_ = SystemConfig::getInstance();

        $mta = 'MTA' . $this->getStage();
        $sysconf_->setProcessToBeRestarted($mta);
        return $this->savePrefs('', 'stage=' . $this->getStage(), '');
    }

    /**
     * get the available certificate
     * @return  hashref   list of certificates
     */
    public function getAvailableCertificates()
    {
        global $sysconf_;

        $dir = $sysconf_->SRCDIR_ . "/etc/exim/certs";
        $certs = [];

        if (file_exists($dir) && is_dir($dir)) {
            $files = @scandir($dir);
        }
        if (empty($files)) {
            return $certs;
        }
        foreach ($files as $file) {
            if (preg_match('/^(\S+)\.crt$/', $file, $matches)) {
                $certs[$matches[1]] = $matches[1];
            }
        }
        return $certs;
    }
}
