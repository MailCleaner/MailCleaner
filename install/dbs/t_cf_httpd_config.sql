use mc_config;

DROP TABLE IF EXISTS httpd_config;

CREATE TABLE httpd_config (
  set_id			int(11) NOT NULL DEFAULT 1,
  serveradmin			varchar(150) NOT NULL DEFAULT 'postmaster@localhost',
  servername			varchar(150) NOT NULL DEFAULT 'localhost',
  use_ssl			enum('true','false') NOT NULL DEFAULT 'false',
  timeout			int(5) NOT NULL DEFAULT 300,
  keepalivetimeout		int(5) NOT NULL DEFAULT 100,
  min_servers			int(5) NOT NULL DEFAULT 3,
  max_servers			int(5) NOT NULL DEFAULT 10,
  start_servers			int(5) NOT NULL DEFAULT 5,
  http_port			int(3) NOT NULL DEFAULT 80,
  https_port			int(3) NOT NULL DEFAULT 443,
  certificate_file		varchar(50) NOT NULL DEFAULT 'default.pem',
  tls_certificate_data          blob,
  tls_certificate_key           blob,
  tls_certificate_chain         blob,
  PRIMARY KEY (set_id)
);

