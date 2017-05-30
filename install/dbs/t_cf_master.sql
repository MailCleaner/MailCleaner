use mc_config;

DROP TABLE IF EXISTS master;

CREATE TABLE master (
	hostname		varchar(150) NOT NULL DEFAULT 'localhost',
	port			int NOT NULL DEFAULT 3306,
	password		varchar(100),
	ssh_pub_key		blob,
	PRIMARY KEY (hostname)
);

