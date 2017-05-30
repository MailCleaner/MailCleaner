/*
 * @version    $Id: queue_sqlite.sql,v 1.1.2.1 2011-05-30 08:31:08 root Exp $
Sample grant for SQLite

CREATE ROLE queue LOGIN
  PASSWORD '[CHANGE ME]'
  NOSUPERUSER NOINHERIT NOCREATEDB NOCREATEROLE;

*/

--
-- Table structure for table `queue`
--

CREATE TABLE queue
(
  queue_id INTEGER PRIMARY KEY AUTOINCREMENT,
  queue_name VARCHAR(100) NOT NULL,
  timeout INTEGER NOT NULL DEFAULT 30
);




-- --------------------------------------------------------
--
-- Table structure for table `message`
--

CREATE TABLE message
(
  message_id INTEGER PRIMARY KEY AUTOINCREMENT,
  queue_id INTEGER PRIMARY KEY,
  handle CHAR(32),
  body VARCHAR(8192) NOT NULL,
  md5 CHAR(32) NOT NULL,
  timeout REAL,
  created INTEGER,
  FOREIGN KEY (queue_id) REFERENCES queue(queue_id)
);

