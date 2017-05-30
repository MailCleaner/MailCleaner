use mc_config;

DROP TABLE IF EXISTS http_auth;

CREATE TABLE  http_auth (
  username			 varchar(50) NOT NULL,
  password			 varchar(50) NOT NULL,
  PRIMARY KEY (username)
);

