use mc_spool;
DROP TABLE IF EXISTS spam_t;
CREATE TABLE spam_t (
--	id  			bigint NOT NULL auto_increment,
	date_in			date NOT NULL,
	time_in			time NOT NULL,
	to_domain		varchar(100) NOT NULL,
	to_user			varchar(100) NOT NULL,
	sender			varchar(120) NOT NULL,
	exim_id			varchar(16) NOT NULL,
	M_date			varchar(50),
	M_subject		varchar(250),
	forced			enum('1','0') NOT NULL DEFAULT '0',
	in_master		enum('1','0') NOT NULL DEFAULT '0',
	store_slave		int NOT NULL,
	M_rbls          varchar(250),
	M_prefilter     varchar(250),
	M_score			decimal(7,3),
	M_globalscore	int,
        is_newsletter           enum('1','0') NOT NULL DEFAULT '0',
	KEY exim_id_idx (exim_id),
	KEY to_user_idx (to_user, to_domain),
	KEY date_in_idx (date_in),
	UNIQUE KEY to_eximid (to_domain, to_user, exim_id)
) ENGINE=MyISAM;

