USE mc_config;
DROP TABLE IF EXISTS UriRBLs;

CREATE TABLE UriRBLs (
  set_id			int(11) NOT NULL DEFAULT 1,
  rbls                          varchar(250) DEFAULT 'MCURIBL',
  listeduristobespam            int(5) NOT NULL DEFAULT 1,
  listedemailtobespam           int(5) NOT NULL DEFAULT 1,
  resolve_shorteners            int(1) NOT NULL DEFAULT 1,
  avoidhosts                    blob,
  PRIMARY KEY (set_id)
);

INSERT INTO UriRBLs SET set_id=1;
