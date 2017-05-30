use mc_config;

DROP TABLE IF EXISTS rrd_stats;

CREATE TABLE rrd_stats (
  id			int(11) NOT NULL DEFAULT 1,
  name          varchar(255),
  type          enum('count', 'frequency'),
  family        varchar(255) DEFAULT 'default',
  base          int(11) DEFAULT 1,
  min_yvalue    int(11) DEFAULT 0,
  PRIMARY KEY (id)
);

INSERT INTO rrd_stats VALUES(1, 'global', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(2, 'global', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(3, 'sessions', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(4, 'sessions', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(5, 'accepted', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(6, 'accepted', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(7, 'refused', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(8, 'refused', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(9, 'delayed', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(10, 'delayed', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(11, 'relayed', 'count', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(12, 'relayed', 'frequency', 'messages', 1, 0);
INSERT INTO rrd_stats VALUES(13, 'load', 'frequency', 'load', 1, 0);
INSERT INTO rrd_stats VALUES(14, 'disks', 'count', 'disk', 1, 100);
INSERT INTO rrd_stats VALUES(15, 'memory', 'count', 'memory', 1024, 0);
INSERT INTO rrd_stats VALUES(16, 'spools', 'frequency', 'spools', 1, 0);
INSERT INTO rrd_stats VALUES(17, 'cpu', 'frequency', 'load', 1, 0);
INSERT INTO rrd_stats VALUES(18, 'io', 'frequency', 'disk', 1024, 0);
INSERT INTO rrd_stats VALUES(19, 'network', 'frequency', 'load', 1000, 0);