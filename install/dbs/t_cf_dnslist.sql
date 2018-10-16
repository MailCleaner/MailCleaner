USE mc_config;
DROP TABLE IF EXISTS dnslist;

CREATE TABLE dnslist (
  name				 varchar(40) NOT NULL UNIQUE,
  url				 varchar(250) NOT NULL,
  type              varchar(20) NOT NULL DEFAULT 'blacklist',
  active            bool NOT NULL DEFAULT '1',
  comment           blob,
  PRIMARY KEY (name)
);

-- blacklists
INSERT INTO dnslist SET name='SPAMHAUS-ZEN', url='zen.spamhaus.org.', active=1, comment='<a target="_blank" href="http://www.spamhaus.org/zen/">http://www.spamhaus.org/zen/</a>';
INSERT INTO dnslist SET name='spamcop.net', url='bl.spamcop.net.', active=1, comment='<a target="_blank" href="http://www.spamcop.net">http://www.spamcop.net</a>';
INSERT INTO dnslist SET name='SORBS-DNSBL', url='dnsbl.sorbs.net.', active=1, comment='<a target="_blank" href="http://www.sorbs.net">http://www.sorbs.net</a>';
INSERT INTO dnslist SET name='RFC-Ignorant', url='fulldom.rfc-ignorant.org.', active=1, comment='<a target="_blank" href="http://www.rfc-ignorant.org">http://www.rfc-ignorant.org</a>';
INSERT INTO dnslist SET name='CompleteWhois', url='combined-HIB.dnsiplists.completewhois.com.', active=1, comment='<a target="_blank" href="http://www.completewhois.com">http://www.completewhois.com</a>';
INSERT INTO dnslist SET name='DSBL', url='list.dsbl.org.', active=1, comment='<a target="_blank" href="http://www.dsbl.org">http://www.dsbl.org</a>';
INSERT INTO dnslist SET name='AHBL', url='rhsbl.ahbl.org.', active=1, comment='<a target="_blank" href="http://www.ahbl.org">http://www.ahbl.org</a>';
INSERT INTO dnslist SET name='SECURITYUSAGE', url='blackhole.securitysage.com.', active=1, comment='<a target="_blank" href="http://www.securitysage.com">http://www.securitysage.com</a>';

-- whitelists
INSERT INTO dnslist SET name='BSP', url='sa-trusted.bondedsender.org.', type='whitelist', active=1;
INSERT INTO dnslist SET name='IADB', url='iadb.isipp.com.', type='whitelist', active=1;
INSERT INTO dnslist SET name='HABEAS', url='sa-accredit.habeas.com.', type='whitelist', active=1;
INSERT INTO dnslist SET name='DNSWL', url='list.dnswl.org.', type='whitelist', active=1;
