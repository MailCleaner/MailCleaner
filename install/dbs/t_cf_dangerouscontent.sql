USE mc_config;
DROP TABLE IF EXISTS dangerouscontent;

CREATE TABLE dangerouscontent (
  set_id			 int(11) NOT NULL DEFAULT 1,
  block_encrypt			 enum('yes', 'no') DEFAULT 'no',
  block_unencrypt		 enum('yes', 'no') DEFAULT 'no',
  allow_passwd_archives		 enum('yes', 'no') DEFAULT 'no',
  allow_partial			 enum('yes', 'no') DEFAULT 'no',
  allow_external_bodies		 enum('yes', 'no') DEFAULT 'no',
 
  allow_iframe			 enum('yes', 'no', 'disarm') DEFAULT 'no',
  silent_iframe			 enum('yes', 'no') DEFAULT 'yes',
  allow_form			 enum('yes', 'no', 'disarm') DEFAULT 'yes',
  silent_form			 enum('yes', 'no') DEFAULT 'no',
  allow_script			 enum('yes', 'no', 'disarm') DEFAULT 'yes',
  silent_script			 enum('yes', 'no') DEFAULT 'no',
  allow_webbugs			 enum('yes', 'no', 'disarm') DEFAULT 'disarm',
  silent_webbugs		 enum('yes', 'no') DEFAULT 'no',
  allow_codebase		 enum('yes', 'no', 'disarm') DEFAULT 'no',
  silent_codebase		 enum('yes', 'no') DEFAULT 'no',

  notify_sender			 enum('yes', 'no') DEFAULT 'no',

  PRIMARY KEY (set_id)
);

-- create default preferences set
INSERT INTO dangerouscontent SET set_id=1; 

 
