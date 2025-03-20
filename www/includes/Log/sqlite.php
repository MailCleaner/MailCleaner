<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_sqlite class is a concrete implementation of the Log::
 * abstract class which sends messages to an Sqlite database.
 * Each entry occupies a separate row in the database.
 *
 * This implementation uses PHP native Sqlite functions.
 *
 * CREATE TABLE log_table (
 *  id          INTEGER PRIMARY KEY NOT NULL,
 *  logtime     NOT NULL,
 *  ident       CHAR(16) NOT NULL,
 *  priority    INT NOT NULL,
 *  message
 * );
 *
 * @author  Bertrand Mansion <bmansion@mamasam.com>
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.8.3
 * @package Log
 *
 * @example sqlite.php      Using the Sqlite handler.
 */
class Log_sqlite extends Log
{
    /**
     * Array containing the connection defaults
     */
    private array $options = ['mode' => 0666, 'persistent' => false];

    /**
     * Object holding the database handle.
     * @var resource
     */
    private $db = null;

    /**
     * Flag indicating that we're using an existing database connection.
     */
    private bool $existingConnection = false;

    /**
     * String holding the database table to use.
     */
    private string $table = 'log_table';

    /**
     * Constructs a new sql logging object.
     *
     * @param string $name         The target SQL table.
     * @param string $ident        The identification field.
     * @param mixed  $conf         Can be an array of configuration options used
     *                             to open a new database connection
     *                             or an already opened sqlite connection.
     * @param int    $level        Log messages up to and including this level.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        $this->id = md5(microtime().random_int(0, mt_getrandmax()));
        $this->table = $name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (is_array($conf)) {
            foreach ($conf as $k => $opt) {
                $this->options[$k] = $opt;
            }
        } else {
            // If an existing database connection was provided, use it.
            $this->db =& $conf;
            $this->existingConnection = true;
        }
    }

    /**
     * Opens a connection to the database, if it has not already
     * been opened. This is implicitly called by log(), if necessary.
     *
     * @return boolean   True on success, false on failure.
     */
    public function open(): bool
    {
        if (is_resource($this->db)) {
            $this->opened = true;
            return $this->createTable();
        } else {
            /* Set the connection function based on the 'persistent' option. */
            if (empty($this->options['persistent'])) {
                $connectFunction = 'sqlite_open';
            } else {
                $connectFunction = 'sqlite_popen';
            }
            $error = '';
            /* Attempt to connect to the database. */
            if ($this->db = $connectFunction($this->options['filename'],
                                              (int)$this->options['mode'],
                                              $error)) {
                $this->opened = true;
                return $this->createTable();
            }
        }

        return $this->opened;
    }

    /**
     * Closes the connection to the database if it is still open and we were
     * the ones that opened it.  It is the caller's responsible to close an
     * existing connection that was passed to us via $conf['db'].
     *
     * @return boolean   True on success, false on failure.
     */
    public function close(): bool
    {
        /* We never close existing connections. */
        if ($this->existingConnection) {
            return false;
        }

        if ($this->opened) {
            $this->opened = false;
            sqlite_close($this->db);
        }

        return ($this->opened === false);
    }

    /**
     * Inserts $message to the currently open database.  Calls open(),
     * if necessary.  Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param mixed  $message  String or object containing the message to log.
     * @param int|null $priority The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     * @return boolean  True on success or false on failure.
     */
    public function log($message, int $priority = null): bool
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->priority;
        }

        /* Abort early if the priority is above the maximum logging level. */
        if (!$this->isMasked($priority)) {
            return false;
        }

        /* If the connection isn't open and can't be opened, return failure. */
        if (!$this->opened && !$this->open()) {
            return false;
        }

        // Extract the string representation of the message.
        $message = $this->extractMessage($message);

        // Build the SQL query for this log entry insertion.
        $q = sprintf('INSERT INTO [%s] (logtime, ident, priority, message) ' .
                     "VALUES ('%s', '%s', %d, '%s')",
                     $this->table,
                     $this->formatTime(time(), 'Y-m-d H:i:s', $this->timeFormatter),
                     sqlite_escape_string($this->ident),
                     $priority,
                     sqlite_escape_string($message));
        if (!($res = @sqlite_unbuffered_query($this->db, $q))) {
            return false;
        }
        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

    /**
     * Checks whether the log table exists and creates it if necessary.
     *
     * @return boolean  True on success or false on failure.
     */
    private function createTable(): bool
    {
        $q = "SELECT name FROM sqlite_master WHERE name='" . $this->table .
             "' AND type='table'";

        $res = sqlite_query($this->db, $q);

        if (sqlite_num_rows($res) == 0) {
            $q = 'CREATE TABLE [' . $this->table . '] (' .
                 'id INTEGER PRIMARY KEY NOT NULL, ' .
                 'logtime NOT NULL, ' .
                 'ident CHAR(16) NOT NULL, ' .
                 'priority INT NOT NULL, ' .
                 'message)';

            if (!($res = sqlite_unbuffered_query($this->db, $q))) {
                return false;
            }
        }

        return true;
    }

}
