USE mc_config;
DROP TABLE IF EXISTS trustedSources;

CREATE TABLE trustedSources (
  set_id			int(11) NOT NULL DEFAULT 1,
  use_alltrusted   int(1) NOT NULL DEFAULT 1,
  use_authservers  int(1) NOT NULL DEFAULT 1,
  useSPFOnLocal    int(1) NOT NULL DEFAULT 1,
  useSPFOnGlobal   int(1) NOT NULL DEFAULT 0,
  authstring       varchar(250),
  authservers      blob,
  domainsToSPF     blob,
  whiterbls        varchar(255) DEFAULT 'DNSWL MCTRUSTEDSPF',
  PRIMARY KEY (set_id)
);

INSERT INTO trustedSources SET set_id=1;
