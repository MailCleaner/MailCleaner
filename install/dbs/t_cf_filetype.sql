USE mc_config;
DROP TABLE IF EXISTS filetype;

CREATE TABLE filetype (
  id				 int(11) NOT NULL AUTO_INCREMENT,
  status			 enum('allow', 'deny') DEFAULT 'allow',
  type				 varchar(50) NOT NULL,
  name				 varchar(150),
  description			 varchar(150),

  PRIMARY KEY (id)
);

-- create default preferences set
INSERT INTO filetype VALUES(NULL, 'allow', 'text', '-', '-');
INSERT INTO filetype VALUES(NULL, 'allow', 'script', '-', '-');
INSERT INTO filetype VALUES(NULL, 'allow', 'archive', '-', '-');
INSERT INTO filetype VALUES(NULL, 'deny', 'self-extract', 'No self-extracting archives', 'No self-extracting archives allowed');
INSERT INTO filetype VALUES(NULL, 'deny', 'ELF', 'No executables', 'No programs allowed');
INSERT INTO filetype VALUES(NULL, 'allow', 'executable', 'No executables', 'No programs allowed');
INSERT INTO filetype VALUES(NULL, 'allow', 'MPEG', 'No MPEG movies', 'No MPEG movies allowed');
INSERT INTO filetype VALUES(NULL, 'allow', 'AVI', 'No AVI movies', 'No AVI movies allowed');
INSERT INTO filetype VALUES(NULL, 'allow', 'MNG', 'No MNG/PNG movies', 'No MNG movies allowed');
INSERT INTO filetype VALUES(NULL, 'allow', 'QuickTime', 'No QuickTime movies', 'No QuickTime movies allowed');
INSERT INTO filetype VALUES(NULL, 'deny', 'Registry', 'No Windows Registry entries', 'No Windows Registry files allowed');

