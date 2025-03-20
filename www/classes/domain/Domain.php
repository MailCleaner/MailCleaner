<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * Domain contains a Connector (with AddressFormat and LoginFormat transformers).
 * It inherits from the PrefHandler storage possibilities
 */
require_once("helpers/PrefHandler.php");
require_once("connector/ConnectorSettings.php");
require_once("connector/LoginFormatter.php");

/**
 * Domain preferences and management
 * This class is mainly a wrapper to the domain object preferences
 * and the different object that it use, such as authentication connectors.
 *
 * @package mailcleaner
 */
class Domain extends PrefHandler
{

    /**
     * Domain destination SMTP port
     * This value is taken from the destination field below which is formatted like this 'server/port'
     * @var number
     */
    private $destination_port_ = 25;

    /**
     * Domain configuration set with default values (will be stored in domain table)
     * @var array
     */
    private $pref_domain_ = [
        'id'                    => 0,
        'name'                  => '',
        'destination'           => '',
        'callout'               => 'false',
        'altcallout'            => '',
        'adcheck'               => 'false',
        'forward_by_mx'         => 'false',
        'greylist'              => 'false',
    ];
    /**
     * Domain preferences set with default values (will be stored in domain_pref table)
     * it's id is references by the domain configuration (field prefs)
     * @var array
     */
    private $pref_domain_prefs_ = [
        'delivery_type'         => 1,
        'viruswall'             => 1,
        'virus_subject'         => '{Virus?}',
        'spamwall'              => 1,
        'spam_tag'              => '{Spam?}',
        'contentwall'           => 1,
        'content_subject'       => '{Content?}',
        'auth_type'             => 'local',
        'auth_server'           => 'localhost',
        'auth_modif'            => 'username_only',
        'auth_param'            => '',
        'address_fetcher'       => 'local',
        'allow_smtp_auth'       => 0,
        'daily_summary'         => 0,
        'weekly_summary'        => 1,
        'monthly_summary'       => 0,
        'language'              => 'en',
        'gui_displayed_spams'  => '20',
        'gui_displayed_days'   => '7',
        'gui_mask_forced'      => '0',
        'gui_graph_type'       => 'bar',
        'gui_default_address'  => '',
        'gui_group_quarantines' => 0,
        'web_template'          => 'default',
        'summary_template'      => 'default',
        'summary_type'          => 'html',
        'report_template'       => 'default',
        'support_email'         => '',
        'quarantine_bounces'    => 0,
        'presharedkey'          => '',
        'enable_whitelists'     => '0',
        'enable_warnlists'      => '0',
        'enable_blacklists'     => '0',
        'notice_wwlists_hit'    => '0',
        'warnhit_template'      => 'default',
        'falseneg_to'           => '',
        'falsepos_to'           => '',
        'supportemail'          => ''
    ];

    /**
     * Authentication connector object used by the domain
     * This is used to define how users will be authenticated (remote via imap/pop/ldap etc.. or local via mysql)
     * @var Connector
     */
    private $connector;

    /**
     * Login format used for the domain
     * This is how login entered in the mailcleaner interface will be transformed and formatted before being send
     * to the server used for the authentication
     * @var LoginFormatter
     */
    private $formatter_;

    /**
     * Domain object constructor
     */
    public function __construct()
    {
    }

    /**
     * Reload domain preferences
     * reload the domain preferences
     */
    public function reload()
    {
        $this->load($this->getPref('id'));
    }

    /**
     * Load the domain settings
     * Load the domain settings and preferences from the database. Also load used objects such
     * as authentication connectors ,etc...
     * @param $name     string the name of the domain, can also be the domain id !
     * @return          bool   false on failure, true on success
     */
    public function load($name)
    {
        global $sysconf_;

        $this->addPrefSet('domain', 'd', $this->pref_domain_);
        $this->addPrefSet('domain_pref', 'p', $this->pref_domain_prefs_);
        $this->setPrefSetRelation('p', 'd.prefs');

        $use_id = 0;
        if (is_numeric($name)) {
            $this->setPref('id', $name);
            $use_id = 1;
        }
        $matches = [];
        if (!preg_match('/^[A-Za-z0-9\_\-\.\*]+$/', $name, $matches)) {
            return false;
        } // bad domain name

        $where_clause = "p.id=d.prefs";
        if ($use_id) {
            $where_clause .= " AND d.id=" . $this->getPref('id');
        } else {
            $where_clause .= " AND d.name='" . $name . "'";
        }
        if (!$this->loadPrefs("d.id as id, d.name as name, p.id as pid,", $where_clause, true)) {
            $this->formatter_ = LoginFormatter::getFormatter('');
            return false;
        }

        if (preg_match('/(.+)\/(\d+)/', $this->getPref('destination'), $matches)) {
            $this->setPref('destination', $matches[1]);
            $this->destination_port_ = $matches[2];
        }

        $this->formatter_ = LoginFormatter::getFormatter($this->getPref('auth_modif'));
        $this->reloadConnector();
        return true;
    }

    /**
     * Reload the connector settings
     * @return     bool  true on success, false on failure
     */
    public function reloadConnector()
    {
        $this->connector = ConnectorSettings::getConnectorSettings($this->getPref('auth_type'));
        $this->connector->setServerSettings($this->getPref('auth_server'));
        $this->connector->setParamSettings($this->getPref('auth_param'));
    }

    /**
     * Set a preference value
     * Set the value of a preference
     * @param $pref    string the name of the preference
     * @param $value   mixes  the value of the preference
     * @return         bool   false on failure, true on success
     */
    public function setPref($pref, $value)
    {
        if ($pref == 'auth_type') {
            $this->connector = ConnectorSettings::getConnectorSettings($value);
            $this->connector->setServerSettings($this->getPref('auth_server'));
            $this->connector->setParamSettings($this->getPref('auth_param'));
        }
        $matches = [];
        if (preg_match('/^conn_(\S+)/', $pref, $matches)) {
            return $this->connector->setSetting($matches[1], $value);
        }
        if (preg_match('/destinationport/', $pref, $matches)) {
            $this->destination_port_ = $value;
        }
        if ($pref == "viruswall") {
            $this->setPref('contentwall', $value);
        }
        if ($pref == 'summary_type' && !preg_match('/^(html|text|digest)$/', $value)) {
            $value = 'html';
        }
        return parent::setPref($pref, $value);
    }

    /**
     * Set the destination port
     * Set the value of the destination server SMTP port
     * @param port     number the port number
     * @return         bool   false on failure, true on success
     */
    public function setPort($port)
    {
        $this->destination_port_ = $port;
        return true;
    }

    /**
     * Get the destination port
     * Get the value of the destination server SMTP port
     * @return         number   the port number
     */
    public function getPort()
    {
        return $this->destination_port_;
    }

    /**
     * Save the domain preferences
     * save the domain settings and preferences to the database
     * @return         string  string 'OKSAVED' if successfully updated, 'OKADDED' id successfully added, error message if neither
     */
    public function save()
    {
        global $sysconf_;
        global $admin_;
        $retok = "";

        if (!$admin_->canManageDomain($this->getPref('name'))) {
            return "NOTALLOWED";
        }
        $this->flatten_prefs();

        if ($this->getPref('id') == 0 && in_array("$this->getPref('name')", $sysconf_->getFilteredDomains())) {
            return 'ERR_INSERTDOM_DOMAINEXISTS';
        }
        # save value
        $ret = $this->savePrefs(null, null, '');
        if (!$ret) {
            return $ret;
        }
        ## set as default domain if needed
        if (
            $sysconf_->getPref('default_domain') == "your_domain" ||
            $sysconf_->getPref('default_domain') == "" ||
            $sysconf_->getPref('default_domain') == 'localdomain'
        ) {
            $sysconf_->setPref('default_domain', $this->getPref('name'));
            $sysconf_->save();
        }
        ## dump the configuration through all hosts
        $res = $sysconf_->dumpConfiguration('domains', $this->getPref('name'));
        return $ret;
    }

    /**
     * Reformat the preferences before saving
     * Reformat some preferences fields so that they can be correctly saved
     * @return         string  string 'OKSAVED' if successfully updated, 'OKADDED' id successfully added, error message if neither
     */
    private function flatten_prefs()
    {
        $this->setPref('destination', $this->getPref('destination') . "/" . $this->destination_port_);
        $this->setPref('auth_param', $this->connector->getFlatParamSetting());
        $this->setPref('auth_server', $this->connector->getFlatServerSettings());
        return true;
    }

    /**
     * Delete the domain
     * Delete the domain instance in the database and the preferences associated
     * @return         string 'OK' if successful, error otherwise
     */
    public function delete()
    {
        global $sysconf_;
        $ret = $this->deletePrefs(null);
        if (!$ret) {
            return $ret;
        }
        ## dump the configuration through all hosts
        $res = $sysconf_->dumpConfiguration('domains', $this->getPref('name'));
        return $ret;
    }

    /**
     * Connector accessor¬
     * @return  ConnectorSettings  ConnectorSettings object
     */
    public function getConnectorSettings()
    {
        return $this->connector;
    }

    /**
     * get the reformatted username
     * @param  $login_given  string  the username given by the user
     * @return               string  the reformatted username
     */
    public function getFormatedLogin($login_given)
    {
        if (!$this->formatter_) {
            return ' ';
        }
        return $this->formatter_->format($login_given, $this->getPref('name'));
    }
}
