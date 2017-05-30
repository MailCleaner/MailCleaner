USE mc_config;
DROP TABLE IF EXISTS MessageSniffer;

CREATE TABLE MessageSniffer (
  set_id          int(11) NOT NULL DEFAULT 1,
  licenseid       varchar(255),
  authentication  varchar(255),
  PRIMARY KEY (set_id)
);

INSERT INTO MessageSniffer SET set_id=1;
