<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Web interface localized texts
 */

return [
    'login' => 'Log in',
    'Username' => 'Username',
    'Password' => 'Password',
    'badCredentials' => "Incorrect user name or password.",
    'badDataGiven' => "Bad input provided",
    'logout' => 'Logout',
    'loggedOut' => "You have been successfully logged out.",
    'welcome' => 'Welcome',
    'datanotvalid' => 'Invalid data provided',

    ## navigation
    'Configuration' => 'Configuration',
    'Management' => 'Management',
    'Monitoring' => 'Monitoring',
    'BaseSystem' => 'Base system',
    'GeneralSettings' => 'General settings',
    'Domains' => 'Domains',
    'SMTP' => 'SMTP',
    'AntiSpam' => 'Anti-Spam',
    'ContentProtection' => 'Content protection',
    'Accesses' => 'Accesses',
    'Services' => 'Services',
    'Cluster' => 'Cluster',

    'Users' => 'Users',
    'SpamQuarantine' => 'Spam quarantines',
    'ContentQuarantine' => 'Content quarantines',
    'Tracing' => 'Tracing',

    'Reporting' => 'Reporting',
    'Logs' => 'Logs',
    'Maintenance' => 'Maintenance',
    'Status' => 'Status',

    ## status
    'Hardware' => 'Hardware',
    'Spools' => 'Spools',
    'Load' => 'Load',
    'cannotgetdata' => 'no data',
    'healthy' => 'healthy',
    'critical' => 'critical',
    'spoolslow' => 'low',
    'spoolsmedium' => 'medium',
    'spoolshigh' => 'high',
    'loadlow' => 'low',
    'loadmedium' => 'medium',
    'loadhigh' => 'high',

    'stats' => 'stats',
    'load' => 'load',
    'disks' => 'disks',
    'memory' => 'memory',
    'spools' => 'spools',
    'processes' => 'processes',

    ## base system
    'Network settings' => 'Network settings',
    'Network settings Title' => 'network settings',
    'DNS settings' => 'DNS settings',
    'DNS settings Title' => 'DNS settings',
    'Localization' => 'Localization',
    'Localization Title' => 'localization',
    'Date and time' => 'Date and time',
    'Date and time Title' => 'date and time',
    'Proxies' => 'Proxies',
    'Proxies Title' => 'proxies',
    'Registration' => 'Registration',
    'Registration Title' => 'registration',

    ## network settings
    'Interface' => 'Interface',
    'IP address' => 'IP address',
    'Network mask' => 'Network mask',
    'Gateway' => 'Gateway',
    'notIpAddress' => 'invalid IP address',
    'isEmpty' => 'required field',
    'Submit' => 'Submit',
    'settingsaved' => 'data successfully saved',
    'reload network' => 'Apply all network settings...',
    'an error occurred' => 'An error occurred',
    'important' => 'Important !',
    'Back' => 'Back...',
    'applying network now will' => 'Applying network settings now will probably break your current connection to this interface.
  Make sure the new settings are correct before pressing the Reload button or you may definitively loose connectivity to your MailCleaner system.
  Once the settings are applied, this interface may not be responsive and you may not have any answer. You should however be able to connect back with the new settings you provided.',

    'networkingrestarted' => 'Networking successfully restarted',

    ## DNS settings
    'DomainSearch' => 'Domain search',
    'Primary DNS server' => 'Primary DNS server',
    'Secondary DNS server' => 'Secondary DNS server',
    'Tertiary DNS server' => 'Tertiary DNS server',
    'settingapplied' => 'Settings successfully applied',

    ## localization
    'Main zone' => 'Continent',
    'Sub zone' => 'Nearest city',

    ## date and time settings
    'Date' => 'Date',
    'Time' => 'Time',
    'notInt' => 'Not valid',
    'notLessThan' => 'Not valid',
    'invalidHostlist' => 'Not a valid host list',

    ## proxies settings
    'HTTP proxy' => 'HTTP proxy',
    'SMTP proxy' => 'SMTP proxy',

    ## registration settings
    'Registration number' => 'Registration number',
    'registered' => 'registered',

    ## General settings
    'Defaults' => 'Defaults',
    'Defaults Title' => 'defaults',
    'Company' => 'Company',
    'Company Title' => 'company',
    'Quarantines' => 'Quarantines',
    'Quarantines Title' => 'quarantines',
    'Periodic tasks' => 'Periodic tasks',
    'Periodic tasks Title' => 'periodic tasks',
    'Logging' => 'Logging',
    'Logging Title' => 'logging',

    ## Defaults
    'User GUI Language' => 'User GUI language',
    'Default domain' => 'Default domain',
    'Support address' => 'Support address',
    'System sender' => 'System mail sender address',
    'False negative address' => 'False negative reporting address',
    'False positive address' => 'False positive reporting address',
    'emailAddressInvalid' => 'Invalid e-mail address',

    ## Company
    'Company name' => 'Company name',
    'Contact name' => 'Contact name',
    'Contact email address' => 'Contact email address',
    'notDigits' => 'Not a valid number',

    ## Quarantines
    'Spam retention time' => 'Spam retention time',
    'Dangerous content retention time' => 'Dangerous content retention time',
    'days' => 'days',

    ## Periodic tasks
    'Daily tasks run at ' => 'Daily tasks run at ',
    'Weekly tasks run on' => 'Weekly tasks run on',
    'Monthly tasks run at day' => 'Monthly tasks run at day',
    'Sunday' => 'Sunday',
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',

    ## Logging

    ## email configuration menu
    'addresssettings' => 'Address settings',
    'warnlist' => 'Warn list',
    'whitelist' => 'White list',
    'blacklist' => 'Black list',
    'newslist' => 'Newsletter list',
    'actions' => 'Actions',

    ## domain configuration menu
    'general' => 'General',
    'delivery' => 'Delivery',
    'addressverification' => 'Address verification',
    'preferences' => 'Preferences',
    'authentication' => 'Users authentication',
    'filtering' => 'Filtering',
    'advanced' => 'Advanced features',
    'spamcovercharge' => 'SpamC rules adjustment',
    'outgoing' => 'Outgoing relay',
    'archiving' => 'Archiving',
    'templates' => 'Templates',

    ## domains callout
    'sendingtorandom' => 'Sending to random recipient',
    'sendingtopostmaster' => 'Sending to postmaster',
    'nodestinationset' => 'No valid destination server set',

    ## user authentication
    'userauthconn_none' => 'none',
    'userauthconn_imap' => 'imap',
    'userauthconn_pop3' => 'pop3',
    'userauthconn_ldap' => 'ldap/Active Directory',
    'userauthconn_smtp' => 'smtp',
    'userauthconn_local' => 'local',
    'userauthconn_radius' => 'radius',
    'userauthconn_sql' => 'SQL database',
    'userauthconn_tequila' => 'tequila',
    'usermod_username_only' => 'only use entered username (without domain)',
    'usermod_at_add' => 'add the domain using @ character',
    'usermod_percent_add' => 'add the domain using % character',
    'addlook_at_login' => 'build address by adding the domain to the username',
    'addlook_ldap' => 'fetch address(es) from ldap directory',
    'addlook_text_file' => 'fetch address(es) from text file',
    'addlook_param_add' => 'build  address by adding a custom string to the username',
    'addlook_mysql' => 'fetch address(es) from SQL database',
    'addlook_local' => 'fetch address(es) in local database',
    'Allow SMTP auth' => 'Allow users to use SMTP authentication',
    'Relay via Smarthost' => 'Use a smarthost to relay to',
    'Server Realy Smarthost' => 'Smarthost servers',

    ## domain filtering
    'Enable antispoof' => 'Reject unauthorized messages from this domain',
    'Add an address to the whitelist' => 'Add an address to the white list',
    'Add an address to the warnlist' => 'Add an address to the warn list',
    'Add an address to the blacklist' => 'Add an address to the black list',

    ## domain outgoing
    'Enable BATV' => 'Enable BATV (Bounce Address Tag Validation)',

    ## status table
    'process_exim_stage1' => 'Incoming MTA',
    'process_exim_stage2' => 'Filtering MTA',
    'process_exim_stage4' => 'Outgoing MTA',
    'process_apache'  => 'Web access',
    'process_mailscanner' => 'Filtering engine',
    'process_mysql_master' => 'Master database',
    'process_mysql_slave' => 'Slave database',
    'process_snmpd'  => 'SNMP daemon',
    'process_greylistd' => 'Greylist daemon',
    'process_cron' => 'Scheduler',
    'process_firewall' => 'Firewall',
    'process_preftdaemon' => 'Preferences daemon',
    'process_spamd' => 'SpamAssassin daemon',
    'process_clamd' => 'ClamAV daemon',
    'process_clamspamd' => 'ClamSpam daemon',
    'process_spamhandler' => 'SpamHandler daemon',
    'process_newsld'      => "Newsletters daemon",

    ## global status
    'loadlow' => 'low',
    'spoolslow' => 'low',

    ## global stats
    'global_title' => 'Overall statistics',
    'sessions_title' => 'SMTP sessions',
    'accepted_title' => 'Accepted messages',
    'refused_title' => 'Refused SMTP sessions',
    'delayed_title' => 'Delayed SMTP sessions',
    'relayed_title' => 'Relayed messages',

    'global_total' => 'Overall total',
    'accepted_total' => 'Messages accepted',
    'cleans_header' => 'Clean messages',
    'spams_header' => 'Spams detected',
    'viruses_header' => 'Viruses detected',
    'dangerous_header' => 'Dangerous contents',
    'outgoing_header' => 'Relayed messages',

    'sessions_total' => 'Total sessions',
    'accepted_header' => 'Messages accepted',
    'refused_header' => 'Sessions refused',
    'delayed_header' => 'Sessions delayed',
    'relayed_header' => 'Messages relayed',

    'refused_total' => 'Total refused sessions',
    'rbl_header' => 'DNS blacklists',
    'blacklists_header' => 'Host/sender blacklists',
    'relay_header' => 'Forbidden relay attempt',
    'policies_header' => 'Unsigned or unencrypted',
    'BATV_header' => 'Unsigned bounces',
    'callout_header' => 'Invalid destination address',
    'syntax_header' => 'Bad syntax',

    'delayed_total' => 'Total delayed sessions',
    'greylisted_header' => 'Greylisted sessions',
    'rate limited_header' => 'Ratelimited sessions',

    'relayed_total' => 'Total relayed messages',
    'by hosts_header' => 'By host',
    'authentified_header' => 'Authentified',

    ## host stats
    'global_hosttotal' => 'Total',
    'accepted_hosttotal' => 'Accepted',
    'cleans_hostheader' => 'Cleans',
    'spams_hostheader' => 'Spams',
    'viruses_hostheader' => 'Viruses',
    'dangerous_hostheader' => 'Dangerous',
    'outgoing_hostheader' => 'Relayed',

    'sessions_hosttotal' => 'Total',
    'accepted_hostheader' => 'Accepted',
    'refused_hostheader' => 'Refused',
    'delayed_hostheader' => 'Delayed',
    'relayed_hostheader' => 'Relayed',

    'refused_hosttotal' => 'Total',
    'rbl_hostheader' => 'DNSBLs',
    'blacklists_hostheader' => 'Blacklists',
    'relay_hostheader' => 'Forbidden relay',
    'policies_hostheader' => 'Policies',
    'BATV_hostheader' => 'Bad bounces',
    'callout_hostheader' => 'Callout',
    'syntax_hostheader' => 'Syntax',

    'delayed_hosttotal' => 'Total',
    'greylisted_hostheader' => 'Greylisted',
    'rate limited_hostheader' => 'Ratelimited',

    'relayed_hosttotal' => 'Total',
    'by hosts_hostheader' => 'By host',
    'authentified_hostheader' => 'Authentified',

    ## SMTP config
    'SMTP checks Title' => 'SMTP checks',
    'Connection control Title' => 'connection control',
    'Resources control Title' => 'resources control',
    'TLS/SSL Title' => 'TLS/SSL',
    'Greylisting Title' => 'greylisting',

    'ratelimit_enable' => "Enable per host rate limit",
    'trusted_ratelimit_enable' => "Enable per trusted host rate limit",

    ## content protection
    'Global settings Title' => 'global settings',
    'Anti-virus Title' => 'anti-virus',
    'HTML controls Title' => 'HTML controls',
    'Message format controls Title' => 'message format controls',
    'Attachment name Title' => 'attachment name',
    'Attachment type Title' => 'attachment type',

    ## services
    'Database Title' => 'database',
    'SNMP monitoring Title' => 'SNMP monitoring',
    'Web interfaces Title' => 'web interfaces',
    ## API config
    'API Title' => 'API access',

    ## TrustedSources
    'IPRWL' => 'IP addresses whitelist',
    'SPFLIST' => 'Domains with good SPF',

    ## Management, Email
    'Send summary to' => 'Send reports to this address',
    'interfacesettings' => 'Interface settings',
    'quarantinedisplay' => 'Quarantine display',
    'addressgroup' => 'Addresses group',
    'authentication' => 'Authentication',

    ## reporting
    'countglobal_rrdtitle' => 'Global messages/sessions counts',
    'countglobal_rrdlegend' => 'messages',
    'global_rrdtitle' => 'Messages/sessions throughput',
    'global_rrdlegend' => 'msgs/s',
    'countsessions_rrdtitle' => 'SMTP sessions',
    'countsessions_rrdlegend' => 'sessions',
    'sessions_rrdtitle' => 'SMTP sessions throughput',
    'sessions_rrdlegend' => 'sessions/s',
    'countaccepted_rrdtitle' => 'Accepted messages',
    'countaccepted_rrdlegend' => 'messages',
    'accepted_rrdtitle' => 'Accepted messages throughput',
    'accepted_rrdlegend' => 'messages/s',
    'countrefused_rrdtitle' => 'Refused SMTP sessions',
    'countrefused_rrdlegend' => 'sessions',
    'refused_rrdtitle' => 'Refused SMTP sessions throughput',
    'refused_rrdlegend' => 'sessions/s',
    'countdelayed_rrdtitle' => 'Delayed SMTP sessions',
    'countdelayed_rrdlegend' => 'sessions',
    'delayed_rrdtitle' => 'Delayed SMTP sessions throughput',
    'delayed_rrdlegend' => 'sessions/s',
    'countrelayed_rrdtitle' => 'Relayed messages',
    'countrelayed_rrdlegend' => 'messages',
    'relayed_rrdtitle' => 'Relayed messages throughput',
    'relayed_rrdlegend' => 'messages/s',
    ## load
    'load_rrdtitle' => 'System load average',
    'load_rrdlegend' => 'load average',
    'load1' => 'load average (last minute)',
    'load5' => 'load average (last 5 minutes)',
    'load15' => 'load average (last 15 minutes)',
    ## disks
    'countdisks_rrdtitle' => 'System partitions usage',
    'countdisks_rrdlegend' => 'disks usage [%]',
    #'system' => 'System partition',
    #'data' => 'Data partition',
    ## memory
    'countmemory_rrdtitle' => 'System Memory utilization',
    'countmemory_rrdlegend' => 'memory used',
    ## spools
    'spools_rrdtitle' => 'System spools status',
    'spools_rrdlegend' => 'messages waiting',
    'incoming_spool' => 'Incoming',
    'filtering_spool' => 'Filtering',
    'outgoing_spool' => 'Delivering',
    ## cpu
    'cpu_rrdtitle' => 'System CPU usage',
    'cpu_rrdlegend' => 'cpu usage [%]',
    ## io
    'io_rrdtitle' => 'Disk I/O bandwidth',
    'io_rrdlegend' => 'bytes per second',
    'write_os' => 'System write',
    'write_data' => 'Data write',
    'read_os' => 'System read',
    'read_data' => 'Data read',
    ## network
    'network_rrdtitle' => 'Network bandwidth',
    'network_rrdlegend' => 'bits per second',
    'if_in' => 'Incoming traffic',
    'if_out' => 'Outgoing traffic',

    ## tracing
    'log_stage1' => 'Incoming MTA stage',
    'log_stage2' => 'Filtering MTA stage',
    'log_engine' => 'Filtering Engine',
    'log_stage4' => 'Outgoing stage',
    'log_spamhandler' => 'Spam handling stage',
    'log_finalstage4' => 'Final outgoing stage',

    ## informational messages
    'unregistered system has low efficiency' => 'using an unregistered system will not provide optimal efficiency. It is strongly advised to register the system.',

    ### newsl
    'Show newsletters only' => 'Show newsletters only',
];
