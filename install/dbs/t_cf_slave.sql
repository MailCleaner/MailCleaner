use mc_config;

DROP TABLE IF EXISTS slave;

CREATE TABLE slave (
	id			int(11) not NULL,
	hostname		varchar(150) NOT NULL DEFAULT 'localhost',
	port			int(11) NOT NULL DEFAULT 3307,
	password		varchar(100),
	ssh_pub_key		blob,
	PRIMARY KEY (id)
);

