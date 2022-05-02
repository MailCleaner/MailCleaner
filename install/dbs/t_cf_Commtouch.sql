USE mc_config;
DROP TABLE IF EXISTS Commtouch;

CREATE TABLE Commtouch (
  set_id           int(11) NOT NULL DEFAULT 1,
  ctasdLicense    varchar(255),
  ctipdLicense    varchar(255),
  PRIMARY KEY (set_id)
);

INSERT INTO Commtouch SET set_id=1;
