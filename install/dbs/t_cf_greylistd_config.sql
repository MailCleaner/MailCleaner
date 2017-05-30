use mc_config;

DROP TABLE IF EXISTS greylistd_config;

CREATE TABLE greylistd_config (
  set_id			int(11) NOT NULL DEFAULT 1,
  retry_min        int(20) NOT NULL DEFAULT 120,
  retry_max        int(20) NOT NULL DEFAULT 28800,
  expire           int(20) NOT NULL DEFAULT 5184000, 
  avoid_domains    blob,
  PRIMARY KEY (set_id)
);

INSERT INTO greylistd_config SET set_id=DEFAULT;
