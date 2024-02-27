 use mc_config;

DROP TABLE IF EXISTS user_gui;

CREATE TABLE user_gui (
   set_id			int(11) NOT NULL DEFAULT 1,
   want_domainchooser		tinyint(1) NOT NULL DEFAULT '1',
   want_aliases			tinyint(1) NOT NULL DEFAULT '1',
   want_search			tinyint(1) NOT NULL DEFAULT '1',
   want_submit_analyse		tinyint(1) NOT NULL DEFAULT '1',
   want_reasons			tinyint(1) NOT NULL DEFAULT '1',
   want_force			tinyint(1) NOT NULL DEFAULT '1',
   want_display_user_infos	tinyint(1) NOT NULL DEFAULT '1',
   want_summary_select		tinyint(1) NOT NULL DEFAULT '1',
   want_delivery_select		tinyint(1) NOT NULL DEFAULT '1',
   want_support			tinyint(1) NOT NULL DEFAULT '1',
   want_preview			tinyint(1) NOT NULL DEFAULT '1',
   want_quarantine_bounces	tinyint(1) NOT NULL DEFAULT '0',
   default_quarantine_days	int(3) NOT NULL DEFAULT '7',
   default_template 		varchar(50) NOT NULL DEFAULT 'default'
);

INSERT INTO user_gui SET set_id=1;

