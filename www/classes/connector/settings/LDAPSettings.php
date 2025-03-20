<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of storing LDAP settings
 * @package mailcleaner
 */
class LDAPSettings extends ConnectorSettings
{

    /**
     * template tag
     * @var string
     */
    protected $template_tag_ = 'LDAPAUTH';

    /**
     * Specialized settings array with default values
     * @var array
     */
    protected $spec_settings_ = [
        'basedn' => '',
        'useratt'   => '',
        'binduser' => '',
        'bindpassword' => '',
        'usessl' => false,
        'version' => 2
    ];

    /**
     * fields type
     * @var array
     */
    protected $spec_settings_type_ = [
        'basedn' => ['text', 30],
        'useratt'   => ['text', 20],
        'binduser' => ['text', 20],
        'bindpassword' => ['password', 20],
        'usessl' => ['checkbox', 'true'],
        'version' => ['select', ['2' => '2', '3' => '3']]
    ];

    public function __construct($type)
    {
        parent::__construct($type);
        $this->setSetting('server', 'localhost');
        $this->setSetting('port', '389');
    }

    /**
     * Get the LDAP connection URL
     * @return   string  connection url
     */
    public function getURL()
    {
        $url = "ldap://";
        if ($this->getSetting('usessl') && ($this->getSetting('usessl') == "true" || $this->getSetting('usessl') == "1")) {
            $url = "ldaps://";
            if ($this->getSetting('port') == '389') {
                $this->setSetting('port', '636');
            }
        }
        $url .= $this->getSetting('server') . ":" . $this->getSetting('port') . "/";
        return $url;
    }

    /**
     * add ou check to the normal parameters found in the db
     */
    public function setParamSettings($settings)
    {
        parent::setParamSettings($settings);

        // add OU if passed by login form
        if (isset($_POST['ou']) && preg_match('/^[a-zA-Z0-9]+$/', $_POST['ou'])) {
            $this->setSetting('basedn', 'OU=' . $_POST['ou'] . "," . $this->getSetting('basedn'));
        }
        return true;
    }
}
