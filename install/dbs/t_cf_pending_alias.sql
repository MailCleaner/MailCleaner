use mc_config;

DROP TABLE IF EXISTS pending_alias;

CREATE TABLE pending_alias (
  id char(32) NOT NULL default '',
  date_in date NOT NULL default '0000-00-00',
  alias char(150) default NULL,
  user char(150) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
);
