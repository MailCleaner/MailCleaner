use mc_config;

DROP TABLE IF EXISTS mta_config;

CREATE TABLE mta_config (
  set_id			int(11) NOT NULL DEFAULT 1,
  stage				int(2) NOT NULL DEFAULT 1,
  header_txt			blob NOT NULL DEFAULT '',
  accept_8bitmime		enum('true','false') NOT NULL DEFAULT 'true',
  print_topbitchars		enum('true','false') NOT NULL DEFAULT 'true',
  return_path_remove		enum('true','false') NOT NULL DEFAULT 'true',
  ignore_bounce_after		char(10) NOT NULL DEFAULT '2d',
  timeout_frozen_after		char(10) NOT NULL DEFAULT '7d',
-- retry
  smtp_relay			enum('true','false') NOT NULL DEFAULT 'false',
  relay_from_hosts		blob,
  allow_relay_for_unknown_domains   tinyint(1) NOT NULL DEFAULT '0',
  no_ratelimit_hosts            blob,
  smtp_enforce_sync             enum('true','false') NOT NULL DEFAULT 'true',
  allow_mx_to_ip        enum('true','false') NOT NULL DEFAULT 'false',
  smtp_receive_timeout		char(10) NOT NULL DEFAULT '30s',
  smtp_accept_max_per_host	int(10) NOT NULL DEFAULT 10,
  smtp_accept_max_per_trusted_host  int(10) NOT NULL DEFAULT 20,
  smtp_accept_max		int(10) NOT NULL DEFAULT 50,
  smtp_reserve          int(10) NOT NULL DEFAULT 5,
  smtp_load_reserve     int(10) NOT NULL DEFAULT 30,
  smtp_accept_queue_per_connection int(10) NOT NULL DEFAULT 10,
  smtp_accept_max_per_connection int(10) NOT NULL DEFAULT 100,
  smtp_conn_access		varchar(10000) DEFAULT '*',
  host_reject			blob,
  sender_reject			blob,
  recipient_reject		blob,
  user_reject			blob,
  verify_sender			tinyint(1) NOT NULL DEFAULT '1',
  global_msg_max_size  char(50) DEFAULT '50M',
  max_rcpt			int(10) NOT NULL DEFAULT 1000,
  received_headers_max  int(10) NOT NULL DEFAULT 30,
  use_incoming_tls     tinyint(1) NOT NULL DEFAULT '0',
  tls_certificate       char(50) NOT NULL DEFAULT 'default',
  tls_use_ssmtp_port    tinyint(1) NOT NULL DEFAULT '0',
  tls_certificate_data  blob,
  tls_certificate_key   blob,
  hosts_require_tls     blob,
  domains_require_tls_from     blob,
  domains_require_tls_to     blob,
  hosts_require_incoming_tls  blob,
  use_syslog           tinyint(1) NOT NULL DEFAULT '0',
  smtp_banner          varchar(255) NOT NULL DEFAULT '$smtp_active_hostname ESMTP Exim $version_number $tod_full',
  errors_reply_to      varchar(255) DEFAULT '',
  rbls                 varchar(255),
  rbls_timeout          int(10) DEFAULT 5,
  rbls_ignore_hosts     blob DEFAULT '',
  bs_rbls              varchar(255),
  rbls_after_rcpt       tinyint(1) NOT NULL DEFAULT '1',
  callout_timeout       int(10) DEFAULT 10,
  retry_rule            varchar(255) NOT NULL DEFAULT 'F,4d,2m',
  ratelimit_enable      int(1) DEFAULT '0',
  ratelimit_rule        varchar(255) DEFAULT '30 / 1m / strict',
  ratelimit_delay       int(10) DEFAULT 10,
  trusted_ratelimit_enable  int(1) DEFAULT '0',
  trusted_ratelimit_rule    varchar(255) DEFAULT '60 / 1m / strict',
  trusted_ratelimit_delay   int(10) DEFAULT 10,
  outgoing_virus_scan   tinyint(1) NOT NULL DEFAULT '0',
  mask_relayed_ip       tinyint(1) NOT NULL DEFAULT '0',
  block_25_auth         tinyint(1) NOT NULL DEFAULT '0',
  masquerade_outgoing_helo tinyint(1) NOT NULL DEFAULT '0',
  forbid_clear_auth     tinyint(1) NOT NULL DEFAULT '0',
  relay_refused_to_domains    blob,
  dkim_default_domain   varchar(255),
  dkim_default_selector varchar(255),
  dkim_default_pkey     blob,
  reject_bad_spf        tinyint(1) NOT NULL DEFAULT '0',
  reject_bad_rdns       tinyint(1) NOT NULL DEFAULT '0',
  dmarc_follow_reject_policy   tinyint(1) NOT NULL DEFAULT '0',
  dmarc_enable_reports  tinyint(1) NOT NULL DEFAULT '0',
  spf_dmarc_ignore_hosts blob DEFAULT '',
  log_subject tinyint(1) NOT NULL DEFAULT '0',
  log_attachments tinyint(1) NOT NULL DEFAULT '0',
  ciphers              varchar(255) NOT NULL DEFAULT 'ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM:!SSLv2',
  allow_long		tinyint(1) NOT NULL DEFAULT '1',
  folding		tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (set_id, stage)
);

INSERT INTO mta_config SET stage=1;

INSERT INTO mta_config SET stage=2;

INSERT INTO mta_config SET stage=4;

UPDATE mta_config SET tls_certificate_data='-----BEGIN CERTIFICATE-----
MIIDNzCCAqCgAwIBAgIJAIz+d3lfXnn8MA0GCSqGSIb3DQEBBAUAMHExCzAJBgNV
BAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYDVQQHEwhMYXVzYW5uZTEUMBIGA1UE
ChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01haWxDbGVhbmVyMRQwEgYDVQQDEwtt
YWlsY2xlYW5lcjAeFw0wNzAxMTIxMDA0NDJaFw0zNDA1MjkxMDA0NDJaMHExCzAJ
BgNVBAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYDVQQHEwhMYXVzYW5uZTEUMBIG
A1UEChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01haWxDbGVhbmVyMRQwEgYDVQQD
EwttYWlsY2xlYW5lcjCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEArsapbAh3
ouV3fj2z5vRxA0igC13fpx0RGJrTUfXW936OyPcEcelLUmoV+jXNybhGA3bvtrc1
/j3ooWqwDvjvmUIKVGmqY+DZCxIdx3cdy+OqrdstMoYFEcn80rEEAVUEIUv47EZM
KnP8GovQ105SZs64cM1mQJHwzWPBMWOYclUCAwEAAaOB1jCB0zAdBgNVHQ4EFgQU
gFF87gp4bNMmGtzOo61M31K/B0cwgaMGA1UdIwSBmzCBmIAUgFF87gp4bNMmGtzO
o61M31K/B0ehdaRzMHExCzAJBgNVBAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYD
VQQHEwhMYXVzYW5uZTEUMBIGA1UEChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01h
aWxDbGVhbmVyMRQwEgYDVQQDEwttYWlsY2xlYW5lcoIJAIz+d3lfXnn8MAwGA1Ud
EwQFMAMBAf8wDQYJKoZIhvcNAQEEBQADgYEAKETzA8aOHt0gRtTvxTTCBSYLGVg/
7+d2/xQBZ2zRZZe4n3Pagj+cePrPBbx3PYkOt6RvKUWBtG43YyavNks9SdcWpICv
SJacAyj+ioDV+9t15k6maSqUgfCN+yiq18xb/zyfrjZnLGZUGD8drQp1wTFsBP+d
EDmfGWmrn6nnX1g=
-----END CERTIFICATE-----' WHERE stage=1;

UPDATE mta_config SET tls_certificate_key='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCuxqlsCHei5Xd+PbPm9HEDSKALXd+nHREYmtNR9db3fo7I9wRx
6UtSahX6Nc3JuEYDdu+2tzX+PeiharAO+O+ZQgpUaapj4NkLEh3Hdx3L46qt2y0y
hgURyfzSsQQBVQQhS/jsRkwqc/wai9DXTlJmzrhwzWZAkfDNY8ExY5hyVQIDAQAB
AoGAZHeson6HjytLMlVz2fqAEHwqC/6tdxn9XuB5Q28HYIPuvlVIx9ZsxvZWpdtR
7XgxPwKar7THo9ugo1F53VF6IPMZi3VYWMd9FIkfcN07zCKL7arzr95ld4fJmsRp
A5M+6IJNm8M75nuRkHa2eJ7FUQOmiLVv2UknCi/5npveV0ECQQDX5VDVZ/QBxww7
kU77Sq9s0as9ypIXQmaYaDuBiy3ZUk3Q4VZTgEYUils+5b/ayprXKcSwg34ZZb+9
+yy1WtLFAkEAzz3xNJG12AgDUlUWwXHqTkCFlqtIj9S5cRTuEf0ToRRFgvlRplZ8
CMpFB8o9o6s+GfVR57qg3jZm18EfJuDaUQJADiEKzjyUYn1lVoym75kuq9945n1Y
XD9TOYwwwMScBon1X8MvhB1z+KopWI9uo+H4ijZIkgi4+u6GwucqQOAlxQJBAMwS
y+2fOnjD0zmE7oaI/VgXMzUd77Mqn31aReDS3Dx3MMf7aMqqSTOCsp0sKqx7mQiI
ySGuZnDLE1SMKHfpXTECQBTjimTv+BdFGxHjp4/+FP2Xys3gqyJRVB7CFXJ3tfpP
BuReZ9fpwTa3x07Uh/Ex1K/+IE3Xs3AarIfNnfK6VSk=
-----END RSA PRIVATE KEY-----' WHERE stage=1;

UPDATE mta_config SET tls_certificate_data='-----BEGIN CERTIFICATE-----
MIIDNzCCAqCgAwIBAgIJAIz+d3lfXnn8MA0GCSqGSIb3DQEBBAUAMHExCzAJBgNV
BAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYDVQQHEwhMYXVzYW5uZTEUMBIGA1UE
ChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01haWxDbGVhbmVyMRQwEgYDVQQDEwtt
YWlsY2xlYW5lcjAeFw0wNzAxMTIxMDA0NDJaFw0zNDA1MjkxMDA0NDJaMHExCzAJ
BgNVBAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYDVQQHEwhMYXVzYW5uZTEUMBIG
A1UEChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01haWxDbGVhbmVyMRQwEgYDVQQD
EwttYWlsY2xlYW5lcjCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEArsapbAh3
ouV3fj2z5vRxA0igC13fpx0RGJrTUfXW936OyPcEcelLUmoV+jXNybhGA3bvtrc1
/j3ooWqwDvjvmUIKVGmqY+DZCxIdx3cdy+OqrdstMoYFEcn80rEEAVUEIUv47EZM
KnP8GovQ105SZs64cM1mQJHwzWPBMWOYclUCAwEAAaOB1jCB0zAdBgNVHQ4EFgQU
gFF87gp4bNMmGtzOo61M31K/B0cwgaMGA1UdIwSBmzCBmIAUgFF87gp4bNMmGtzO
o61M31K/B0ehdaRzMHExCzAJBgNVBAYTAkNIMQ0wCwYDVQQIEwRWYXVkMREwDwYD
VQQHEwhMYXVzYW5uZTEUMBIGA1UEChMLTWFpbENsZWFuZXIxFDASBgNVBAsTC01h
aWxDbGVhbmVyMRQwEgYDVQQDEwttYWlsY2xlYW5lcoIJAIz+d3lfXnn8MAwGA1Ud
EwQFMAMBAf8wDQYJKoZIhvcNAQEEBQADgYEAKETzA8aOHt0gRtTvxTTCBSYLGVg/
7+d2/xQBZ2zRZZe4n3Pagj+cePrPBbx3PYkOt6RvKUWBtG43YyavNks9SdcWpICv
SJacAyj+ioDV+9t15k6maSqUgfCN+yiq18xb/zyfrjZnLGZUGD8drQp1wTFsBP+d
EDmfGWmrn6nnX1g=
-----END CERTIFICATE-----' WHERE stage=4;

UPDATE mta_config SET tls_certificate_key='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCuxqlsCHei5Xd+PbPm9HEDSKALXd+nHREYmtNR9db3fo7I9wRx
6UtSahX6Nc3JuEYDdu+2tzX+PeiharAO+O+ZQgpUaapj4NkLEh3Hdx3L46qt2y0y
hgURyfzSsQQBVQQhS/jsRkwqc/wai9DXTlJmzrhwzWZAkfDNY8ExY5hyVQIDAQAB
AoGAZHeson6HjytLMlVz2fqAEHwqC/6tdxn9XuB5Q28HYIPuvlVIx9ZsxvZWpdtR
7XgxPwKar7THo9ugo1F53VF6IPMZi3VYWMd9FIkfcN07zCKL7arzr95ld4fJmsRp
A5M+6IJNm8M75nuRkHa2eJ7FUQOmiLVv2UknCi/5npveV0ECQQDX5VDVZ/QBxww7
kU77Sq9s0as9ypIXQmaYaDuBiy3ZUk3Q4VZTgEYUils+5b/ayprXKcSwg34ZZb+9
+yy1WtLFAkEAzz3xNJG12AgDUlUWwXHqTkCFlqtIj9S5cRTuEf0ToRRFgvlRplZ8
CMpFB8o9o6s+GfVR57qg3jZm18EfJuDaUQJADiEKzjyUYn1lVoym75kuq9945n1Y
XD9TOYwwwMScBon1X8MvhB1z+KopWI9uo+H4ijZIkgi4+u6GwucqQOAlxQJBAMwS
y+2fOnjD0zmE7oaI/VgXMzUd77Mqn31aReDS3Dx3MMf7aMqqSTOCsp0sKqx7mQiI
ySGuZnDLE1SMKHfpXTECQBTjimTv+BdFGxHjp4/+FP2Xys3gqyJRVB7CFXJ3tfpP
BuReZ9fpwTa3x07Uh/Ex1K/+IE3Xs3AarIfNnfK6VSk=
-----END RSA PRIVATE KEY-----' WHERE stage=4;

