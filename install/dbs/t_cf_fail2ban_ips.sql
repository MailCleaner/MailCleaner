USE mc_config;

DROP TABLE IF EXISTS fail2ban_ips;

CREATE TABLE fail2ban_ips (
        id                      int(11) not NULL AUTO_INCREMENT,
        ip                      varchar(150) NOT NULL,
        count                   int(11) not NULL,
        active                  tinyint(1),
        blacklisted             tinyint(1) DEFAULT FALSE,
        whitelisted             tinyint(1) DEFAULT FALSE,
        jail			varchar(20) NOT NULL,
	last_hit		TIMESTAMP DEFAULT NOW(),
	host			varchar(150) NOT NULL,
        PRIMARY KEY (id)
);
