USE mc_config;

DROP TABLE IF EXISTS fail2ban_jail;

CREATE TABLE fail2ban_jail (
        id                      int not NULL AUTO_INCREMENT,
	enabled			BOOLEAN DEFAULT TRUE,
        name                    varchar(150) NOT NULL UNIQUE,
        maxretry                int not NULL,
        findtime                int not NULL,
        bantime                 int not NULL,
        port                    varchar(50) NOT NULL,
        filter                  varchar(50) NOT NULL,
        banaction               varchar(50) NOT NULL,
        logpath                 varchar(250) NOT NULL,
	max_count		int not NULL,
        send_mail               BOOLEAN DEFAULT FALSE,
        send_mail_bl            BOOLEAN DEFAULT TRUE,
        PRIMARY KEY (id)
);
