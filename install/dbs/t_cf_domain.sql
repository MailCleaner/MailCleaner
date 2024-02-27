use mc_config;

DROP TABLE IF EXISTS domain;

CREATE TABLE domain (
  id				int(11) NOT NULL AUTO_INCREMENT ,
  name				char(200) NOT NULL DEFAULT '',
  active			char(5) NOT NULL DEFAULT 'true',
  destination		char(200) NOT NULL DEFAULT '',
  callout			enum('true', 'false') NOT NULL DEFAULT 'false',
  altcallout       char(200),
  adcheck			enum('true', 'false') NOT NULL DEFAULT 'false',
  addlistcallout    enum('true', 'false') NOT NULL DEFAULT 'false',
  extcallout        enum('true', 'false') NOT NULL DEFAULT 'false',
  forward_by_mx	    enum('true', 'false') NOT NULL DEFAULT 'false',
  greylist         enum('true', 'false') NOT NULL DEFAULT 'false',
  relay_smarthost                tinyint(1) NOT NULL DEFAULT '0',
  destination_smarthost          char(200) NOT NULL DEFAULT '',
  prefs				int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (id, name) 
);

-- create default preferences set
INSERT INTO domain SET name='__global__', prefs=1; 
