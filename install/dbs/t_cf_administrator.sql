use mc_config;

DROP TABLE IF EXISTS administrator;

CREATE TABLE  administrator (
  username			 varchar(120) NOT NULL,
  password			 varchar(120) NOT NULL,
  can_manage_users		 enum('1','0') NOT NULL DEFAULT '1',
  can_manage_domains		 enum('1','0') NOT NULL DEFAULT '0',
  can_configure			 enum('1','0') NOT NULL DEFAULT '0',
  can_view_stats		 enum('1','0') NOT NULL DEFAULT '0',
  can_manage_host		 enum('1','0') NOT NULL DEFAULT '0',
  domains 			 blob,
  allow_subdomains       enum('1','0') NOT NULL DEFAULT '0',
  web_template			varchar(50) NOT NULL DEFAULT 'default',
  id                            int(11) auto_increment not null,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_username (username)
);

