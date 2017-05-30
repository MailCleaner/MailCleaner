use mc_config;

DROP TABLE IF EXISTS feature_restriction;


## currently supported restrictions:
## NetworkInterface - address
## NetworkInterface - netmask
## NetworkInterface - gateway
## NetworkInterface - submit
## NetworkInterface - relaodnetnow

CREATE TABLE  feature_restriction (
  id                 int(11) auto_increment not null,
  section            varchar(120) NOT NULL,
  feature            varchar(120) NOT NULL,
  target_level       enum('administrator', 'manager', 'hotline', 'user'),
  restricted         bool NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

