USE mc_config;
DROP TABLE IF EXISTS antispam;

CREATE TABLE antispam (
  set_id			 int(11) NOT NULL DEFAULT 1,
  use_spamassassin		 tinyint(1) NOT NULL DEFAULT '1',
  spamassassin_timeout		 int(11) NOT NULL DEFAULT  '20',
  use_bayes                      tinyint(1) NOT NULL DEFAULT '1',
  bayes_autolearn                tinyint(1) NOT NULL DEFAULT '0',
  ok_languages                   varchar(50) NOT NULL DEFAULT 'fr en de it es',
  ok_locales                     varchar(50) NOT NULL DEFAULT 'fr en de it es',
  use_rbls                       tinyint(1) NOT NULL DEFAULT '1',
  rbls_timeout                   int(11) NOT NULL DEFAULT  '20',
  sa_rbls                        varchar(250) NOT NULL DEFAULT 'SORBS RFCIGNORANT DSBL AHBL SPAMCOP BSP IADB HABEAS DNSWL URIBL',
  use_dcc                        tinyint(1) NOT NULL DEFAULT '1',
  dcc_timeout                    int(11) NOT NULL DEFAULT  '10',
  use_razor                      tinyint(1) NOT NULL DEFAULT '1',
  razor_timeout                  int(11) NOT NULL DEFAULT  '10',
  use_pyzor                      tinyint(1) NOT NULL DEFAULT '1',
  pyzor_timeout                  int(11) NOT NULL DEFAULT  '10',
  enable_whitelists              tinyint(1) NOT NULL DEFAULT '0',
  enable_warnlists               tinyint(1) NOT NULL DEFAULT '0',
  enable_blacklists              tinyint(1) NOT NULL DEFAULT '0',
  tag_mode_bypass_whitelist      tinyint(1) NOT NULL DEFAULT '1',
  whitelist_both_from            tinyint(1) NOT NULL DEFAULT '0',
  trusted_ips					  blob,
  html_wl_ips                                     blob,
  use_fuzzyocr                   tinyint(1) NOT NULL DEFAULT '1',
  use_pdfinfo                    tinyint(1) NOT NULL DEFAULT '1',
  use_imageinfo                  tinyint(1) NOT NULL DEFAULT '1',
  use_botnet                     tinyint(1) NOT NULL DEFAULT '1',
  use_domainkeys                 tinyint(1) NOT NULL DEFAULT '1',
  domainkeys_timeout             int(11) NOT NULL DEFAULT  '5',
  use_spf                        tinyint(1) NOT NULL DEFAULT '1',
  spf_timeout                    int(11) NOT NULL DEFAULT  '5',
  use_dkim                       tinyint(1) NOT NULL DEFAULT '1',
  dkim_timeout                   int(11) NOT NULL DEFAULT  '5',
  dmarc_follow_quarantine_policy tinyint(1) NOT NULL DEFAULT '0',
  spam_list_to_be_spam           int(11) NOT NULL DEFAULT '2',
  use_syslog                     tinyint(1) NOT NULL DEFAULT '0',
  do_stockme                     tinyint(1) NOT NULL DEFAULT '0',
  stockme_nbdays                 int(11) NOT NULL DEFAULT '3',
  dnsliststoreport               varchar(250) NOT NULL DEFAULT '',
  global_max_size		 int(11) NOT NULL DEFAULT '500',
  PRIMARY KEY (set_id)
);

-- create default preferences set
INSERT INTO antispam SET set_id=1; 

 
