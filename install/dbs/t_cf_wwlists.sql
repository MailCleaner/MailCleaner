USE mc_config;
DROP TABLE IF EXISTS wwlists;

CREATE TABLE wwlists (
  id				 int(11) NOT NULL AUTO_INCREMENT,
  sender			 varchar(150) NOT NULL,
  recipient         varchar(150) NOT NULL,
  type              varchar(15) NOT NULL DEFAULT 'warn',
  expiracy          date NOT NULL,
  status            int(2) DEFAULT 1,
  comments          blob,
  PRIMARY KEY (id),
  UNIQUE KEY (sender, recipient)
);
