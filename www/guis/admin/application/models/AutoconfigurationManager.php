<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka, John Mertz
 * @copyright 2017, Mentor Reka; 2023, John Mertz
 *
 * Auto-configuration Manager
 */

class Default_Model_AutoconfigurationManager
{
    private $MC_AUTOCONF_TAG_FILE = "/spool/mailcleaner/mc-autoconf";
    protected $_config;
    protected $_autoconfenabled = false;

    public function load()
    {
        $this->_config = MailCleaner_Config::getInstance();
        $this->setAutoconfenabled(file_exists($this->_config->getOption('VARDIR') . $this->MC_AUTOCONF_TAG_FILE));
    }

    public function getAutoconfenabled()
    {
        return $this->_autoconfenabled;
    }

    public function setAutoconfenabled($autoconfenabled)
    {
        $this->_autoconfenabled = $autoconfenabled;
    }

    public function save()
    {
        return Default_Model_Localhost::sendSoapRequest('Config_autoconfiguration', [
            'autoconfenabled' => $this->getAutoconfenabled()
        ]);
    }

    public function download()
    {
        return Default_Model_Localhost::sendSoapRequest('Config_autoconfigurationDownload', ['download' => true]);
    }
}
