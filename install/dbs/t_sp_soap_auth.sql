use mc_spool;

DROP TABLE IF EXISTS soap_auth;

CREATE TABLE soap_auth (
  id char(32) NOT NULL default '',
  time timestamp NOT NULL,
  user_type enum('admin', 'user') NOT NULL default 'user',
  user char(150) default NULL,
  host char(50),
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
);
