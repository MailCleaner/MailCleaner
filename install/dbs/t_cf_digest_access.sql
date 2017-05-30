use mc_config;

DROP TABLE IF EXISTS digest_access;

CREATE TABLE digest_access (
  id char(40) NOT NULL default '',
  date_in date NOT NULL,
  date_start date NOT NULL,
  date_expire date NOT NULL,
  address char(250) NOT NULL,
  PRIMARY KEY  (id)
); 
