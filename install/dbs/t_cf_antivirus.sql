USE mc_config;
DROP TABLE IF EXISTS antivirus;

CREATE TABLE antivirus (
  set_id			 int(11) NOT NULL DEFAULT 1,
  scanners			 varchar(120) NOT NULL DEFAULT 'clamav',
  scanner_timeout		 int(11) NOT NULL DEFAULT  '300',
  silent			 enum('yes', 'no') DEFAULT 'yes',
  file_timeout			 int(11) NOT NULL DEFAULT  '20',
  expand_tnef			 enum('yes', 'no') DEFAULT 'yes',
  deliver_bad_tnef		 enum('yes', 'no') DEFAULT 'no',
  tnef_timeout			 int(11) NOT NULL DEFAULT  '120',
  usetnefcontent        enum('no', 'add', 'replace') DEFAULT 'no',
  max_message_size		 int(11) NOT NULL DEFAULT  '0',
  max_attach_size		 int(11) NOT NULL DEFAULT  '-1',
  max_archive_depth		 int(11) NOT NULL DEFAULT  '0',
  max_attachments_per_message    int(11) NOT NULL DEFAULT  200,
  send_notices			 enum('yes', 'no') DEFAULT 'no',
  notices_to			 varchar(120) NOT NULL DEFAULT 'root',

  PRIMARY KEY (set_id)
);

-- create default preferences set
INSERT INTO antivirus SET set_id=1; 

 
