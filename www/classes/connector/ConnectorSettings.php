<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of storing Connection settings
 * @package mailcleaner
 */
class ConnectorSettings
{

    /**
     * Settings array with default values
     * @var array
     */
    private $settings_ = [
        'server' => 'localhost',
        'port'   => 0
    ];

    private $spec_settings_ = [];

    private $settings_type_ = [
        'server' => ['text', 20],
        'port' => ['text', 5]
    ];

    /**
     * List of available connector with corresponding classes
     * @var array
     */
    static private $connectors_ = [
        'none' => 'SQLSettings',
        'local' => 'SQLSettings',
        'sql' => 'SQLSettings',
        'mysql' => 'SQLSettings',
        'imap'  => 'SimpleServerSettings',
        'pop3'  => 'SimpleServerSettings',
        'ldap'  => 'LDAPSettings',
        'smtp'  => 'SimpleServerSettings',
        'radius' => 'RadiusSettings',
        'tequila' => 'TequilaSettings'
    ];

    /**
     * internal type of connector
     * @var string
     */
    private $type_ = 'local';

    /**
     * Constructor
     * Add the specialized settings to the parent settings
     */
    public function __construct($type)
    {
        foreach ($this->spec_settings_ as $setting => $value) {
            $this->addSetting($setting, $value);
        }
        if (isset(self::$connectors_[$type])) {
            $this->type_ = $type;
        }
        // set some defaults here are these use generic settings
        switch ($this->type_) {
            case 'imap':
                $this->setSetting('port', 143);
                break;
            case 'pop3':
                $this->setSetting('port', 110);
                break;
        }
    }

    /**
     * Settings factory
     */
    static public function getConnectorSettings($type)
    {
        if (!isset(self::$connectors_[$type])) {
            return null;
        }
        $filename = "connector/settings/" . self::$connectors_[$type] . ".php";
        include_once($filename);
        $class = self::$connectors_[$type];
        if (class_exists($class)) {
            return new $class($type);
        }
        return null;
    }

    /**
     * Set a setting value
     * @param $setting  string  setting name
     * @param $value    mixed   new setting value
     * @return          bool    true on success, false on failure
     */
    public function setSetting($setting, $value)
    {
        if (isset($this->settings_[$setting])) {
            $this->settings_[$setting] = $value;
            return true;
        }
        return false;
    }

    /**
     * Add a setting with a value
     * @param $setting  string setting name
     * @param $value    mixed  default value
     * @return          bool    true on success, false on failure
     */
    protected function addSetting($setting, $value)
    {
        $this->settings_[$setting] = $value;
        return true;
    }
    /**
     * Get a setting value
     * @param $setting  string  setting name
     * @return          mixed   setting value, or null if not found
     */
    public function getSetting($setting)
    {
        if (isset($this->settings_[$setting])) {
            return $this->settings_[$setting];
        }
        return false;
    }

    /**
     * Get the available settings list
     * @return   array  available setting list
     */
    public function getSettingsList()
    {
        $ret = [];
        foreach ($this->settings_ as $key => $value) {
            $ret[$key] = $key;
        }
        return $ret;
    }


    /**
     * Set the server settings from the auth_server field of the domain preferences
     * domain preferences are formatted like this: server:port
     * @param $settings  string  settings string from the domain preference
     * @return           bool    true on success, false on failure
     */
    public function setServerSettings($settings)
    {
        if ($this->getType() == 'local') {
            return;
        }

        if (preg_match('/:/', $settings)) {
            [$server, $port]  = preg_split("/:/", $settings);
        } else {
            $server = $settings;
        }
        if (!isset($server) or $server == "") {
            return false;
        }
        if (!isset($port) or $port == "") {
            $port = $this->getSetting('port');
        }
        $this->setSetting('server', $server);
        $this->setSetting('port', $port);
        return true;
    }

    /**
     * Set the parameters settings from the auth_param field of the domain
     * @param $settings  string  settings string from the domain preference
     * @return           bool    true on success, false on failure
     */
    public function setParamSettings($settings)
    {
        if (!isset($settings)) {
            return false;
        }
        $fields = preg_split('/\:/', $settings);
        if (count($fields) != count($this->spec_settings_)) {
            return false;
        }
        reset($this->spec_settings_);
        foreach ($fields as $field) {
            $key = key($this->spec_settings_);
            // watch for escaped collons
            $clean = str_replace('__C__', ':', $field);
            $this->setSetting($key, $clean);
            next($this->spec_settings_);
        }
        return true;
    }


    /**
     * return the servers settings as a flattened string as expected by the auth_server preference of domain
     * @return  string  flattened server settings
     */
    public function getFlatServerSettings()
    {
        return $this->getSetting('server') . ":" . $this->getSetting('port');
    }

    /**
     * return the parameters settings as a flattened string as expected by the auth_param preference of domain
     * @return  string  flattened param settings
     */
    public function getFlatParamSetting()
    {
        $ret = "";
        foreach ($this->settings_ as $key => $value) {
            if ($key == 'server' or $key == 'port') {
                continue;
            }
            // escape collons
            $clean = str_replace(':', '__C__', $this->getSetting($key));
            $ret .= $clean . ":";
        }
        $ret = rtrim($ret);
        $ret = rtrim($ret, '\:');
        return $ret;
    }

    /**
     * Get the internal connector type
     * @return   string  connector type
     */
    public function getType()
    {
        return $this->type_;
    }

    /**
     * Get the field type of a setting
     * @return array field type and values if needed
     */
    public function getFieldType($field_name)
    {
        if (isset($this->settings_type_[$field_name])) {
            return $this->settings_type_[$field_name];
        }
        if (isset($this->spec_settings_type_[$field_name])) {
            return $this->spec_settings_type_[$field_name];
        }
        return null;
    }

    /**
     * get the template tag for the condition
     * @return string  template condition
     */
    public function getTemplateCondition()
    {
        return $this->template_tag_;
    }
}
