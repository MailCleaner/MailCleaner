USE mc_config;
DROP TABLE IF EXISTS filename;

CREATE TABLE filename (
  id				 int(11) NOT NULL AUTO_INCREMENT,
  status			 enum('allow', 'deny') DEFAULT 'deny',
  rule				 varchar(50) NOT NULL UNIQUE,
  name				 varchar(150),
  description			 varchar(150),

  PRIMARY KEY (id)
);

-- create default preferences set
INSERT INTO filename VALUES(NULL, 'deny', '.{150,}', 'Very long filename, possible OE attack', 'Very long filenames are good signs of attacks against Microsoft e-mail packages');
INSERT INTO filename VALUES(NULL, 'deny', 'pretty\\s+park\\.exe$', '\"Pretty Park\" virus', '\"Pretty Park\" virus');
INSERT INTO filename VALUES(NULL, 'deny', 'happy99\\.exe$', '\"Happy\" virus', '\"Happy\" virus');
INSERT INTO filename VALUES(NULL, 'deny', '\\.ceo$', 'WinEvar virus attachment', 'Often used by the WinEvar virus');
INSERT INTO filename VALUES(NULL, 'deny', 'webpage\\.rar$', 'I-Worm.Yanker virus attachment', 'Often used by the I-Worm.Yanker virus');
INSERT INTO filename VALUES(NULL, 'allow', '\\.jpg$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.gif$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.url$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.vcf$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.txt$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.zip$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.t?gz$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.bz2$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.Z$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.rpm$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.gpg$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.pgp$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.sit$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.asc$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.hqx$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.sit.bin$', '-', '-');
INSERT INTO filename VALUES(NULL, 'allow', '\\.sea$', '-', '-');
INSERT INTO filename VALUES(NULL, 'deny', '\\.reg$', 'Possible Windows registry attack', 'Windows registry entries are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.chm$', 'Possible compiled Help file-based virus', 'Compiled help files are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.cnf$', 'Possible SpeedDial attack', 'SpeedDials are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.hta$', 'Possible Microsoft HTML archive attack', 'HTML archives are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.ins$', 'Possible Microsoft Internet Comm. Settings attack', 'Windows Internet Settings are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.jse?$', 'Possible Microsoft JScript attack', 'JScript Scripts are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.job$', 'Possible Microsoft Task Scheduler attack', 'Task Scheduler requests are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.lnk$', 'Possible Eudora *.lnk security hole attack', 'Eudora *.lnk security hole attack');
INSERT INTO filename VALUES(NULL, 'deny', '\\.ma[dfgmqrstvw]$', 'Possible Microsoft Access Shortcut attack', 'Microsoft Access Shortcuts are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.pif$', 'Possible MS-Dos program shortcut attack', 'Shortcuts to MS-Dos programs are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.scf$', 'Possible Windows Explorer Command attack', 'Windows Explorer Commands are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.sct$', 'Possible Microsoft Windows Script Component attack', 'Windows Script Components are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.shb$', 'Possible document shortcut attack', 'Shortcuts Into Documents are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.shs$', 'Possible Shell Scrap Object attack', 'Shell Scrap Objects are very dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.vb[es]$', 'Possible Microsoft Visual Basic script attack', 'Visual Basic Scripts are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.ws[cfh]$', 'Possible Microsoft Windows Script Host attack', 'Windows Script Host files are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.xnk$', 'Possible Microsoft Exchange Shortcut attack', 'Microsoft Exchange Shortcuts are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.com$', 'Windows/DOS Executable', 'Executable DOS/Windows programs are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.exe$', 'Windows/DOS Executable', 'Executable DOS/Windows programs are dangerous in email');
INSERT INTO filename VALUES(NULL, 'deny', '\\.scr$', 'Possible virus hidden in a screensaver', 'Windows Screensavers are often used to hide viruses');
INSERT INTO filename VALUES(NULL, 'deny', '\\.bat$', 'Possible malicious batch file script', 'Batch files are often malicious');
INSERT INTO filename VALUES(NULL, 'deny', '\\.cmd$', 'Possible malicious batch file script', 'Batch files are often malicious');
INSERT INTO filename VALUES(NULL, 'deny', '\\.cpl$', 'Possible malicious control panel item', 'Control panel items are often used to hide viruses');
INSERT INTO filename VALUES(NULL, 'deny', '\\.mhtml$', 'Possible Eudora meta-refresh attack', 'MHTML files can be used in an attack against Eudora');
INSERT INTO filename VALUES(NULL, 'deny', '\\{[a-hA-H0-9-]{25,}\\}', 'Filename trying to hide its real type', 'Files containing CLSID\'s are trying to hide their real type');
INSERT INTO filename VALUES(NULL, 'deny', '\\s{10,}', 'Filename contains lots of white space', 'A long gap in a name is often used to hide part of it');
INSERT INTO filename VALUES(NULL, 'allow', '(\\.[a-z0-9]{3})\\1$', '-', '-');

