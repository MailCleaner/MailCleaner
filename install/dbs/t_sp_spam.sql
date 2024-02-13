use mc_spool;
DROP TABLE IF EXISTS spam;
CREATE TABLE spam (
	date_in			date NOT NULL,
	time_in			time NOT NULL,
	to_domain		varchar(100) NOT NULL,
	to_user			varchar(100) NOT NULL,
	sender			varchar(120) NOT NULL,
	exim_id			varchar(24) NOT NULL,
	M_date			varchar(50),
	M_subject		varchar(250),
	forced			enum('1','0') NOT NULL DEFAULT '0',
	in_master		enum('1','0') NOT NULL DEFAULT '0',
	store_slave		int(11) NOT NULL,
	M_rbls          varchar(250),
	M_prefilter     varchar(250),
	M_score			decimal(7,3),
	M_globalscore	int(11),
        is_newsletter           enum('1', '0') NOT NULL DEFAULT '0'
) ENGINE=MERGE  UNION = (spam_a,spam_b,spam_c,spam_d,spam_e,spam_f,spam_g,spam_h,spam_i,spam_j,spam_k,spam_l,spam_m,spam_n,spam_o,spam_p,spam_q,spam_r,spam_s,spam_t,spam_u,spam_v,spam_w,spam_x,spam_y,spam_z,spam_num,spam_misc) INSERT_METHOD = NO;


