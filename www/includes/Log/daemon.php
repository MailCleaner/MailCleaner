<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_daemon class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog daemon on UNIX-like machines.
 * This class uses the syslog protocol: http://www.ietf.org/rfc/rfc3164.txt
 *
 * @author  Bart van der Schans <schans@dds.nl>
 * @version $Revision$
 * @package Log
 */
class Log_daemon extends Log
{
    /**
     * Integer holding the log facility to use.
     */
    private int $name = LOG_DAEMON;

    /**
     * Var holding the resource pointer to the socket
     * @var resource
     */
    private $socket;

    /**
     * The ip address or servername
     * @see http://www.php.net/manual/en/transports.php
     */
    private string $ip = '127.0.0.1';

    /**
     * Protocol to use (tcp, udp, etc.)
     * @see http://www.php.net/manual/en/transports.php
     */
    private string $proto = 'udp';

    /**
     * Port to connect to
     */
    private int $port = 514;

    /**
     * Maximum message length in bytes
     */
    private int $maxsize = 4096;

    /**
     * Socket timeout in seconds
     */
    private int $timeout = 1;

    /**
     * Constructs a new syslog object.
     *
     * @param string $name  The syslog facility.
     * @param string $ident The identity string.
     * @param array  $conf  The configuration array.
     * @param int    $level Maximum level at which to log.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        /* Ensure we have a valid integer value for $name. */
        if (empty($name) || !is_int($name)) {
            $name = LOG_SYSLOG;
        }

        $this->id = md5(microtime().random_int(0, mt_getrandmax()));
        $this->name = $name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (isset($conf['ip'])) {
            $this->ip = $conf['ip'];
        }
        if (isset($conf['proto'])) {
            $this->proto = $conf['proto'];
        }
        if (isset($conf['port'])) {
            $this->port = $conf['port'];
        }
        if (isset($conf['maxsize'])) {
            $this->maxsize = $conf['maxsize'];
        }
        if (isset($conf['timeout'])) {
            $this->timeout = $conf['timeout'];
        }
        $this->proto = $this->proto . '://';

        register_shutdown_function([&$this, 'log_daemon_destructor']);
    }

    /**
     * Destructor.
     */
    public function log_daemon_destructor(): void
    {
        $this->close();
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     */
    public function open(): bool
    {
        if (!$this->opened) {
            $this->opened = (bool)($this->socket = @fsockopen(
                                                $this->proto . $this->ip,
                                                $this->port,
                                                $errno,
                                                $errstr,
                                                $this->timeout));
        }
        return $this->opened;
    }

    /**
     * Closes the connection to the system logger, if it is open.
     */
    public function close(): bool
    {
        if ($this->opened) {
            $this->opened = false;
            return fclose($this->socket);
        }
        return true;
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param string $message  The textual message to be logged.
     * @param int|null $priority (optional) The priority of the message.  Valid
     *                  values are: LOG_EMERG, LOG_ALERT, LOG_CRIT,
     *                  LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO,
     *                  and LOG_DEBUG.  The default is LOG_INFO.
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

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Set the facility level. */
        $facility_level = $this->name + $this->toSyslog($priority);

        /* Prepend ident info. */
        if (!empty($this->ident)) {
            $message = $this->ident . ' ' . $message;
        }

        /* Check for message length. */
        if (strlen($message) > $this->maxsize) {
            $message = substr($message, 0, ($this->maxsize) - 10) . ' [...]';
        }

        /* Write to socket. */
        fwrite($this->socket, '<' . $facility_level . '>' . $message . "\n");

        $this->announce(['priority' => $priority, 'message' => $message]);
        
        return true;
    }

    /**
     * Converts a PEAR_LOG_* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the PEAR_LOG_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $priority     PEAR_LOG_* value to convert to LOG_* value.
     *
     * @return integer  The LOG_* representation of $priority.
     */
    private function toSyslog(int $priority): int
    {
        static $priorities = [
            PEAR_LOG_EMERG   => LOG_EMERG,
            PEAR_LOG_ALERT   => LOG_ALERT,
            PEAR_LOG_CRIT    => LOG_CRIT,
            PEAR_LOG_ERR     => LOG_ERR,
            PEAR_LOG_WARNING => LOG_WARNING,
            PEAR_LOG_NOTICE  => LOG_NOTICE,
            PEAR_LOG_INFO    => LOG_INFO,
            PEAR_LOG_DEBUG   => LOG_DEBUG,
        ];

        /* If we're passed an unknown priority, default to LOG_INFO. */
        if (!in_array($priority, $priorities)) {
            return LOG_INFO;
        }

        return $priorities[$priority];
    }

}
