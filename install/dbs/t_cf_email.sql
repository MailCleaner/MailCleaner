use mc_config;

DROP TABLE IF EXISTS email;

CREATE TABLE  email (
  id                             int NOT NULL auto_increment,
  address			 varchar(120) NOT NULL,
  user				 int NOT NULL,
  is_main			 enum('1','0') NOT NULL DEFAULT '1',
  pref				 int NOT NULL default 1,
  PRIMARY KEY (id)
);

