use mc_spool;

DROP TABLE IF EXISTS update_patch;

CREATE TABLE update_patch (
  id				int(11) NOT NULL,
  date				date NOT NULL,
  time				time NOT NULL,
  status			varchar(150) NOT NULL,
  description			varchar(250),
  PRIMARY KEY (id) 
);

