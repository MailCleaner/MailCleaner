USE mc_community;
DROP TABLE IF EXISTS registration;

CREATE TABLE registration (
  id					int(11) NOT NULL AUTO_INCREMENT,
  first_name 				varchar(255) NOT NULL,
  last_name 				varchar(255) NOT NULL,
  company 				varchar(255) DEFAULT NULL,
  email 				varchar(255) NOT NULL,
  address 				varchar(255) DEFAULT NULL,
  postal_code 				varchar(255) DEFAULT NULL,
  city 					varchar(255) DEFAULT NULL,
  country 				varchar(255) DEFAULT NULL,
  accept_newsletters 			tinyint(1) DEFAULT '1',
  accept_releases 			tinyint(1) DEFAULT '1',
  accept_send_statistics    		tinyint(1) DEFAULT '1',
  created_at 				timestamp NULL DEFAULT NULL,
  updated_at 				timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
);
