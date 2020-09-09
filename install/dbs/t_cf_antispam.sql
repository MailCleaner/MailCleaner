USE mc_config;
DROP TABLE IF EXISTS antispam;

CREATE TABLE antispam (
  set_id			 int(11) NOT NULL DEFAULT 1,
  use_spamassassin		 bool NOT NULL DEFAULT '1',
  spamassassin_timeout		 int NOT NULL DEFAULT  '20',
  use_bayes                      bool NOT NULL DEFAULT '1',
  bayes_autolearn                bool NOT NULL DEFAULT '0',
  ok_languages                   varchar(50) NOT NULL DEFAULT 'fr en de it es',
  ok_locales                     varchar(50) NOT NULL DEFAULT 'fr en de it es',
  use_rbls                       bool NOT NULL DEFAULT '1',
  rbls_timeout                   int NOT NULL DEFAULT  '20',
  sa_rbls                        varchar(250) NOT NULL DEFAULT 'SORBS RFCIGNORANT DSBL AHBL SPAMCOP BSP IADB HABEAS DNSWL URIBL',
  use_dcc                        bool NOT NULL DEFAULT '1',
  dcc_timeout                    int NOT NULL DEFAULT  '10',
  use_razor                      bool NOT NULL DEFAULT '1',
  razor_timeout                  int NOT NULL DEFAULT  '10',
  use_pyzor                      bool NOT NULL DEFAULT '1',
  pyzor_timeout                  int NOT NULL DEFAULT  '10',
  enable_whitelists              bool NOT NULL DEFAULT '0',
  enable_warnlists               bool NOT NULL DEFAULT '0',
  enable_blacklists               bool NOT NULL DEFAULT '0',
  tag_mode_bypass_whitelist      bool NOT NULL DEFAULT '1',
  whitelist_both_from            bool NOT NULL DEFAULT '0',
  trusted_ips					  blob,
  use_fuzzyocr                   bool NOT NULL DEFAULT '1',
  use_pdfinfo                    bool NOT NULL DEFAULT '1',
  use_imageinfo                  bool NOT NULL DEFAULT '1',
  use_botnet                     bool NOT NULL DEFAULT '1',
  use_domainkeys                 bool NOT NULL DEFAULT '1',
  domainkeys_timeout             int NOT NULL DEFAULT  '5',
  use_spf                        bool NOT NULL DEFAULT '1',
  spf_timeout                    int NOT NULL DEFAULT  '5',
  use_dkim                       bool NOT NULL DEFAULT '1',
  dkim_timeout                   int NOT NULL DEFAULT  '5',
  dmarc_follow_quarantine_policy bool NOT NULL DEFAULT '0',
  spam_list_to_be_spam           int NOT NULL DEFAULT '2',
  use_syslog                     bool NOT NULL DEFAULT '0',
  do_stockme                     bool NOT NULL DEFAULT '0',
  stockme_nbdays                 int NOT NULL DEFAULT '3',
  dnsliststoreport               varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (set_id)
);

-- create default preferences set
INSERT INTO antispam SET set_id=1; 

 
