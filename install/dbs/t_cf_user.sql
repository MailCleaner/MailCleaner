use mc_config;

DROP TABLE IF EXISTS user;

CREATE TABLE  user (
  id                             int NOT NULL auto_increment,
  username			 varchar(120) NOT NULL,
  domain			 varchar(200),
  pref				 int NOT NULL default 1,
  PRIMARY KEY (id)
);

