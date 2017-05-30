use mc_config;

DROP TABLE IF EXISTS mysql_auth;

CREATE TABLE mysql_auth (
  username 			 varchar(200) NOT NULL,
  domain                         varchar(50),
  password			 varchar(200) NOT NULL,
  email				 varchar(200) NOT NULL,
  realname			 varchar(200),
  id                             int(11) auto_increment not null,
  PRIMARY KEY (id)
);

