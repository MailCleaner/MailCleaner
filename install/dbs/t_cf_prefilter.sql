use mc_config;

DROP TABLE IF EXISTS prefilter;

CREATE TABLE prefilter (
  id		   int(11) NOT NULL AUTO_INCREMENT,
  set_id           int(11) NOT NULL DEFAULT 1,
  name             char(200) NOT NULL,
  active           int(1) NOT NULL DEFAULT 1,
  position         int(11) NOT NULL DEFAULT 1,
  neg_decisive     int(1) NOT NULL DEFAULT 1,
  pos_decisive     int(1) NOT NULL DEFAULT 1,
  decisive_field   varchar(100) NOT NULL DEFAULT 'none',
  timeOut          int(11) NOT NULL DEFAULT 10,
  maxSize          int(11) NOT NULL DEFAULT 500000,
  header           char(200),
  putHamHeader     int(1) NOT NULL DEFAULT 0,
  putSpamHeader    int(1) NOT NULL DEFAULT 1,
  visible          tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (id, name)
);

INSERT INTO prefilter VALUES(1, 1, 'TrustedSources', '1', '1', '1', '0', 'neg_decisive', 30, 0, 'X-TrustedSources', '1', '0', 1);
INSERT INTO prefilter VALUES(2, 1, 'NiceBayes', '1', '2', '0', '1', 'pos_decisive', 20, 500000, 'X-NiceBayes', '1', '1', 1);
INSERT INTO prefilter VALUES(3, 1,'ClamSpam', '1', '3', '0', '1', 'pos_decisive', 20, 500000, 'X-ClamSpam', '0', '1', 1);
INSERT INTO prefilter VALUES(4, 1, 'PreRBLs', '1', '4', '0', '1', 'pos_decisive', 30, 500000, 'X-PreRBLs', '0', '1', 1);
INSERT INTO prefilter VALUES(5, 1, 'UriRBLs', '1', '5', '0', '1', 'pos_decisive', 20, 500000, 'X-UriRBLs', '0', '1', 1);
INSERT INTO prefilter VALUES(6, 1, 'Spamc', '1', '6', '1', '1', 'both', 100, 500000, 'X-Spamc', '1', '1', 1);
INSERT INTO prefilter VALUES(7, 1, 'Spamc', '1', '0', '0', '0', 'both', 10, 2000000, 'X-Newsl', '1', '1', 0);
