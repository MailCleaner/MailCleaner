USE mc_config;
DROP TABLE IF EXISTS preRBLs;

CREATE TABLE preRBLs (
  set_id			int(11) NOT NULL DEFAULT 1,
  spamhits         int(5) NOT NULL DEFAULT 2,
  highspamhits     int(5) NOT NULL DEFAULT 3,
  lists            varchar(255) DEFAULT 'SPAMHAUS-ZEN spamcop.net NJABL SORBS-DNSBL',
  PRIMARY KEY (set_id)
)TYPE=MyISAM;

INSERT INTO preRBLs SET set_id=1;