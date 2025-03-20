<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/** PEAR's MDB2 package */
require_once 'MDB2.php';
MDB2::loadFile('Date');

/**
 * The Log_mdb2 class is a concrete implementation of the Log:: abstract class
 * which sends messages to an SQL server.  Each entry occupies a separate row
 * in the database.
 *
 * This implementation uses PEAR's MDB2 database abstraction layer.
 *
 * CREATE TABLE log_table (
 *  id          INT NOT NULL,
 *  logtime     TIMESTAMP NOT NULL,
 *  ident       CHAR(16) NOT NULL,
 *  priority    INT NOT NULL,
 *  message     VARCHAR(200),
 *  PRIMARY KEY (id)
 * );
 *
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.9.0
 * @package Log
 */
class Log_mdb2 extends Log
{
    /**
     * Variable containing the DSN information.
     * @var mixed
     */
    private $dsn = '';

    /**
     * Array containing our set of DB configuration options.
     * @var array
     */
    private $options = ['persistent' => true];

    /**
     * Object holding the database handle.
     * @var object
     */
    private $db = null;

    /**
     * Resource holding the prepared statement handle.
     * @var resource
     */
    private $statement = null;

    /**
     * Flag indicating that we're using an existing database connection.
     * @var boolean
     */
    private $existingConnection = false;

    /**
     * String holding the database table to use.
     * @var string
     */
    private $table = 'log_table';

    /**
     * String holding the name of the ID sequence.
     * @var string
     */
    private $sequence = 'log_id';

    /**
     * Maximum length of the $ident string.  This corresponds to the size of
     * the 'ident' column in the SQL table.
     * @var integer
     */
    private $identLimit = 16;

    /**
     * Set of field types used in the database table.
     * @var array
     */
    private $types = [
        'id'        => 'integer',
        'logtime'   => 'timestamp',
        'ident'     => 'text',
        'priority'  => 'text',
        'message'   => 'clob',
    ];

    /**
     * Constructs a new sql logging object.
     *
     * @param string $name         The target SQL table.
     * @param string $ident        The identification field.
     * @param array $conf          The connection configuration array.
     * @param int $level           Log messages up to and including this level.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        $this->id = md5(microtime().random_int(0, mt_getrandmax()));
        $this->table = $name;
        $this->mask = Log::MAX($level);

        /* If an options array was provided, use it. */
        if (isset($conf['options']) && is_array($conf['options'])) {
            $this->options = $conf['options'];
        }

        /* If a specific sequence name was provided, use it. */
        if (!empty($conf['sequence'])) {
            $this->sequence = $conf['sequence'];
        }

        /* If a specific sequence name was provided, use it. */
        if (isset($conf['identLimit'])) {
            $this->identLimit = $conf['identLimit'];
        }

        /* Now that the ident limit is confirmed, set the ident string. */
        $this->setIdent($ident);

        /* If an existing database connection was provided, use it. */
        if (isset($conf['db'])) {
            $this->db = &$conf['db'];
            $this->existingConnection = true;
            $this->opened = true;
        } elseif (isset($conf['singleton'])) {
            $this->db = &MDB2::singleton($conf['singleton'], $this->options);
            $this->existingConnection = true;
            $this->opened = true;
        } else {
            $this->dsn = $conf['dsn'];
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
        if (!$this->opened) {
            /* Use the DSN and options to create a database connection. */
            $this->db = &MDB2::connect($this->dsn, $this->options);
            if (PEAR::isError($this->db)) {
                return false;
            }

            /* Create a prepared statement for repeated use in log(). */
            if (!$this->prepareStatement()) {
                return false;
            }

            /* We now consider out connection open. */
            $this->opened = true;
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
        /* If we have a statement object, free it. */
        if (is_object($this->statement)) {
            $this->statement->free();
            $this->statement = null;
        }

        /* If we opened the database connection, disconnect it. */
        if ($this->opened && !$this->existingConnection) {
            $this->opened = false;
            return $this->db->disconnect();
        }

        return ($this->opened === false);
    }

    /**
     * Sets this Log instance's identification string.  Note that this
     * SQL-specific implementation will limit the length of the $ident string
     * to sixteen (16) characters.
     *
     * @param string    $ident      The new identification string.
     *
     * @since   Log 1.8.5
     */
    public function setIdent(string $ident): void
    {
        $this->ident = substr($ident, 0, $this->identLimit);
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

        /* If we don't already have a statement object, create one. */
        if (!is_object($this->statement) && !$this->prepareStatement()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Build our set of values for this log entry. */
        $values = [
            'id'       => $this->db->nextId($this->sequence),
            'logtime'  => MDB2_Date::mdbNow(),
            'ident'    => $this->ident,
            'priority' => $priority,
            'message'  => $message
        ];

        /* Execute the SQL query for this log entry insertion. */
        $this->db->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = &$this->statement->execute($values);
        $this->db->popExpect();

        /* Attempt to handle any errors. */
        if (PEAR::isError($result)) {
            /* We can only handle MDB2_ERROR_NOSUCHTABLE errors. */
            if ($result->getCode() != MDB2_ERROR_NOSUCHTABLE) {
                return false;
            }

            /* Attempt to create the target table. */
            if (!$this->createTable()) {
                return false;
            }

            /* Recreate our prepared statement resource. */
            $this->statement->free();
            if (!$this->prepareStatement()) {
                return false;
            }

            /* Attempt to re-execute the insertion query. */
            $result = $this->statement->execute($values);
            if (PEAR::isError($result)) {
                return false;
            }
        }

        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

    /**
     * Create the log table in the database.
     *
     * @return boolean  True on success or false on failure.
     */
    private function createTable(): bool
    {
        $this->db->loadModule('Manager', null, true);
        $result = $this->db->manager->createTable(
            $this->table,
            [
                'id'        => ['type' => $this->types['id']],
                'logtime'   => ['type' => $this->types['logtime']],
                'ident'     => ['type' => $this->types['ident']],
                'priority'  => ['type' => $this->types['priority']],
                'message'   => ['type' => $this->types['message']],
            ]
        );
        if (PEAR::isError($result)) {
            return false;
        }

        $result = $this->db->manager->createIndex(
            $this->table,
            'unique_id',
            ['fields' => ['id' => true], 'unique' => true]
        );
        if (PEAR::isError($result)) {
            return false;
        }

        return true;
    }

    /**
     * Prepare the SQL insertion statement.
     *
     * @return boolean  True if the statement was successfully created.
     *
     * @since   Log 1.9.0
     */
    private function prepareStatement(): bool
    {
        $this->statement = &$this->db->prepare(
                'INSERT INTO ' . $this->table .
                ' (id, logtime, ident, priority, message)' .
                ' VALUES(:id, :logtime, :ident, :priority, :message)',
                $this->types, MDB2_PREPARE_MANIP);

        /* Return success if we didn't generate an error. */
        return (PEAR::isError($this->statement) === false);
    }
}
