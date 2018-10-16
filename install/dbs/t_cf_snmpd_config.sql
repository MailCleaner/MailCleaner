use mc_config;

DROP TABLE IF EXISTS snmpd_config;

CREATE TABLE snmpd_config (
  set_id			int(11) NOT NULL DEFAULT 1,
  allowed_ip			varchar(200) DEFAULT '127.0.0.1',
  community			varchar(200) DEFAULT 'mailcleaner',
  disks				varchar(200) DEFAULT '/:/var',
  PRIMARY KEY (set_id)
);

insert into snmpd_config set set_id=1;
