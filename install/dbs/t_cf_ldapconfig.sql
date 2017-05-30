use mc_config;

DROP TABLE IF EXISTS ldapconfig;

CREATE TABLE ldapconfig (
  id				int(11) NOT NULL AUTO_INCREMENT ,
  name				char(200) NOT NULL DEFAULT 'ldapconfig',
  user              char(120),
  
  servers           blob,
  basedb            varchar(255),
  binddn            varchar(255),
  bindpass          varchar(255),
  user_fields       varchar(255),
  
  PRIMARY KEY (id) 
);

