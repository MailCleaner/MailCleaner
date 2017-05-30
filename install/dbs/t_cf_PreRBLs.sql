USE mc_config;
DROP TABLE IF EXISTS PreRBLs;

CREATE TABLE PreRBLs (
  set_id			int(11) NOT NULL DEFAULT 1,
  spamhits         int(5) NOT NULL DEFAULT 1,
  highspamhits     int(5) NOT NULL DEFAULT 3,
  lists            varchar(255) DEFAULT 'SPAMCOP SORBS BACKSCATTERER',
  avoidgoodspf     int(1) NOT NULL DEFAULT 0,
  avoidhosts       blob,
  PRIMARY KEY (set_id)
);

INSERT INTO PreRBLs SET set_id=1;
