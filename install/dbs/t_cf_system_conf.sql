 use mc_config;

DROP TABLE IF EXISTS system_conf;

CREATE TABLE system_conf (
   id                  int(11) UNIQUE NOT NULL DEFAULT '1',
   organisation        varchar(200) NOT NULL DEFAULT '',
   company_name        varchar(200),
   contact             varchar(200), 
   contact_email       varchar(200),
   hostname            varchar(200) NOT NULL DEFAULT '',
   hostid	       int(11) NOT NULL DEFAULT 1,
   clientid	       int(20) NOT NULL,
   default_domain      varchar(200) NOT NULL DEFAULT '',
   default_language    varchar(50) NOT NULL DEFAULT 'en',
   sysadmin            varchar(200) NOT NULL DEFAULT '',
   days_to_keep_spams  int(10) NOT NULL DEFAULT 60,
   days_to_keep_virus  int(10) NOT NULL DEFAULT 60,
   cron_time	       time NOT NULL DEFAULT "000000",
   cron_weekday	       int(2) NOT NULL DEFAULT 1,
   cron_monthday       int(2) NOT NULL DEFAULT 1, 
   summary_subject     varchar(250) NOT NULL DEFAULT "Mailcleaner quarantine summary",
   summary_from	       varchar(200) NOT NULL DEFAULT "your_mail\@yourdomain",
   analyse_to	       varchar(200) NOT NULL DEFAULT "your_mail\@yourdomain",
   falseneg_to         varchar(200) NOT NULL DEFAULT "your_mail\@yourdomain",
   falsepos_to         varchar(200) NOT NULL DEFAULT "your_mail\@yourdomain",
   src_dir             varchar(255) NOT  NULL DEFAULT '/opt/mailcleaner',
   var_dir             varchar(255) NOT NULL DEFAULT '/var/mailcleaner',
   ad_server           varchar(80),
   ad_param            varchar(200),
   http_proxy          varchar(200),
   use_syslog          bool NOT NULL DEFAULT '0',
   syslog_host         varchar(200),
   smtp_proxy          varchar(200),
   use_archiver        bool NOT NULL DEFAULT '0',
   archiver_host       varchar(200),
   api_fulladmin_ips   blob,
   api_admin_ips       blob
);
