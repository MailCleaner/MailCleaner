USE mc_config;

ALTER TABLE `mc_config`.`user_pref` ADD COLUMN `allow_newsletters` TINYINT(1) NOT NULL DEFAULT 1 AFTER `bypass_filtering`;

ALTER TABLE `mc_config`.`domain_pref` ADD COLUMN `allow_newsletters` TINYINT(1) NOT NULL DEFAULT 1 AFTER `require_outgoing_tls`;

ALTER TABLE prefilter ADD COLUMN visible boolean NOT NULL DEFAULT 1;

INSERT INTO prefilter (id, set_id, `name`, active, `position`, neg_decisive, pos_decisive, decisive_field, timeOut, maxSize, header, putHamHeader, putSpamHeader, visible) VALUES (NULL, 1, 'Newsl', 1, 0, 0, 0, 'both', 10, 2000000, 'X-Newsl', 1, 1, 0);

USE mc_spool;

DROP TABLE spam;

ALTER TABLE `mc_spool`.`spam_a` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0'AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_b` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_c` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_d` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_e` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_f` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_g` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_h` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_i` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_j` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_k` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_l` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_m` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_n` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_o` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_p` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_q` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_r` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_s` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_t` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_u` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_v` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_w` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_x` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_y` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_z` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_num` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;
ALTER TABLE `mc_spool`.`spam_misc` 
ADD COLUMN `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0' AFTER `M_globalscore`;

CREATE TABLE `spam` (
  `date_in` date NOT NULL,
  `time_in` time NOT NULL,
  `to_domain` varchar(100) NOT NULL,
  `to_user` varchar(100) NOT NULL,
  `sender` varchar(120) NOT NULL,
  `exim_id` varchar(16) NOT NULL,
  `M_date` varchar(50) DEFAULT NULL,
  `M_subject` varchar(250) DEFAULT NULL,
  `forced` enum('1','0') NOT NULL DEFAULT '0',
  `in_master` enum('1','0') NOT NULL DEFAULT '0',
  `store_slave` int(11) NOT NULL,
  `M_rbls` varchar(250) DEFAULT NULL,
  `M_prefilter` varchar(250) DEFAULT NULL,
  `M_score` decimal(7,3) DEFAULT NULL,
  `M_globalscore` int(11) DEFAULT NULL,
  `is_newsletter` ENUM('1', '0') NOT NULL DEFAULT '0',
  KEY `exim_id_idx` (`exim_id`),
  KEY `to_user_idx` (`to_user`,`to_domain`),
  KEY `date_in_idx` (`date_in`)
) ENGINE=MRG_MyISAM DEFAULT CHARSET=latin1 UNION=(`spam_a`,`spam_b`,`spam_c`,`spam_d`,`spam_e`,`spam_f`,`spam_g`,`spam_h`,`spam_i`,`spam_j`,`spam_k`,`spam_l`,`spam_m`,`spam_n`,`spam_o`,`spam_p`,`spam_q`,`spam_r`,`spam_s`,`spam_t`,`spam_u`,`spam_v`,`spam_w`,`spam_x`,`spam_y`,`spam_z`,`spam_num`,`spam_misc`);
