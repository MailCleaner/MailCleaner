USE mc_config;
DROP TABLE IF EXISTS scanner;

CREATE TABLE scanner (
  id				 int(11) NOT NULL AUTO_INCREMENT,
  name				 varchar(40) NOT NULL,
  comm_name			 varchar(40) NOT NULL, 
  active			 bool NOT NULL DEFAULT '0',
  path				 varchar(200) NOT NULL DEFAULT '/usr/local',
  installed			 bool NOT NULL DEFAULT '0',
  version			 varchar(100),
  sig_version			 varchar(100),

  PRIMARY KEY (id)
);

-- create default preferences set
INSERT INTO scanner VALUES(1, 'clamd', 'ClamAV (daemon)', 1, '/opt/clamav', 1, '', '');
INSERT INTO scanner VALUES(2, 'clamavmodule', 'ClamAV (module)', 0, '/tmp', 1, '', '');
INSERT INTO scanner VALUES(3, 'clamav', 'ClamAV', 0, '/opt/clamav', 1, '', '');
INSERT INTO scanner VALUES(4, 'etrust', 'eTrust', 0, '/usr/etrust', 0, '', '');
INSERT INTO scanner VALUES(5, 'trend', 'TrendMicro', 0, '/pack/trend', 0, '', '');
INSERT INTO scanner VALUES(6, 'sophos', 'Sophos', 0, '/usr/local/sophos', 0, '', '');
INSERT INTO scanner VALUES(7, 'mcafee', 'McAfee', 0, '/usr/local/uvscan', 0, '', '');
