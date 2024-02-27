USE mc_config;

DROP TABLE IF EXISTS fail2ban_jail;

CREATE TABLE fail2ban_jail (
        id                      int(11) not NULL AUTO_INCREMENT,
	enabled			tinyint(1) DEFAULT TRUE,
        name                    varchar(150) NOT NULL UNIQUE,
        maxretry                int(11) not NULL,
        findtime                int(11) not NULL,
        bantime                 int(11) not NULL,
        port                    varchar(50) NOT NULL,
        filter                  varchar(50) NOT NULL,
        banaction               varchar(50) NOT NULL,
        logpath                 varchar(250) NOT NULL,
	max_count		int(11) not NULL,
        send_mail               tinyint(1) DEFAULT FALSE,
        send_mail_bl            tinyint(1) DEFAULT TRUE,
        PRIMARY KEY (id)
);
