USE mc_config;

DROP TABLE IF EXISTS fail2ban_conf;

CREATE TABLE fail2ban_conf (
        id                      int not NULL AUTO_INCREMENT,
        src_email               varchar(150) NOT NULL,
        src_name                varchar(150) NOT NULL,
        dest_email              varchar(150) NOT NULL,
        PRIMARY KEY (id)
);
