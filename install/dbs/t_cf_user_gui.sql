 use mc_config;

DROP TABLE IF EXISTS user_gui;

CREATE TABLE user_gui (
   set_id			int(11) NOT NULL DEFAULT 1,
   want_domainchooser		bool NOT NULL DEFAULT '1',
   want_aliases			bool NOT NULL DEFAULT '1',
   want_search			bool NOT NULL DEFAULT '1',
   want_submit_analyse		bool NOT NULL DEFAULT '1',
   want_reasons			bool NOT NULL DEFAULT '1',
   want_force			bool NOT NULL DEFAULT '1',
   want_display_user_infos	bool NOT NULL DEFAULT '1',
   want_summary_select		bool NOT NULL DEFAULT '1',
   want_delivery_select		bool NOT NULL DEFAULT '1',
   want_support			bool NOT NULL DEFAULT '1',
   want_preview			bool NOT NULL DEFAULT '1',
   want_quarantine_bounces	bool NOT NULL DEFAULT '0',
   default_quarantine_days	int(3) NOT NULL DEFAULT '7',
   default_template 		varchar(50) NOT NULL DEFAULT 'default'
);

INSERT INTO user_gui SET set_id=1;

