use mc_config;

DROP TABLE IF EXISTS slave;

CREATE TABLE slave (
	id			int not NULL,
	hostname		varchar(150) NOT NULL DEFAULT 'localhost',
	port			int NOT NULL DEFAULT 3307,
	password		varchar(100),
	ssh_pub_key		blob,
	PRIMARY KEY (id)
);

