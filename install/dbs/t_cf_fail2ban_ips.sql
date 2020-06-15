USE mc_config;

DROP TABLE IF EXISTS fail2ban_ips;

CREATE TABLE fail2ban_ips (
        id                      int not NULL AUTO_INCREMENT,
        ip                      varchar(150) NOT NULL,
        count                   int not NULL,
        active                  BOOLEAN,
        blacklisted             BOOLEAN DEFAULT FALSE,
        whitelisted             BOOLEAN DEFAULT FALSE,
        jail			varchar(20) NOT NULL,
	last_hit		TIMESTAMP DEFAULT NOW(),
	host			varchar(150) NOT NULL,
        PRIMARY KEY (id)
);
