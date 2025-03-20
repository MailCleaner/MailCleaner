<?php

declare(strict_types=1);
/**
 * $Header$
 * $Horde: horde/lib/Log/syslog.php,v 1.6 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_syslog class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog on UNIX-like machines
 * (PHP emulates this with the Event Log on Windows machines).
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@php.net>
 * @since   Horde 1.3
 * @since   Log 1.0
 * @package Log
 *
 * @example syslog.php      Using the syslog handler.
 */
class Log_syslog extends Log
{
    /**
     * Integer holding the log facility to use.
     */
    private int $name;

    /**
     * Should we inherit the current syslog connection for this process, or
     * should we call openlog() to start a new syslog connection?
     */
    private bool $inherit = false;

    /**
     * Should we re-open the syslog connection for each log event?
     */
    private bool $reopen = false;

    /**
     * Maximum message length that will be sent to syslog().  If the handler
     * receives a message longer than this length limit, it will be split into
     * multiple syslog() calls.
     */
    private int $maxLength = 500;

    /**
     * String containing the format of a message.
     */
    private string $lineFormat = '%4$s';

    /**
     * String containing the timestamp format. It will be passed to date().
     * If timeFormatter configured, it will be used.
     * current locale.
     */
    private string $timeFormat = 'M d H:i:s';

    /**
     * @var callable
     */
    private $timeFormatter;


    /**
     * Constructs a new syslog object.
     *
     * @param string $name     The syslog facility.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        /* Ensure we have a valid integer value for $name. */
        if (empty($name) || !is_numeric($name)) {
            $name = LOG_SYSLOG;
        }

        if (isset($conf['inherit'])) {
            $this->inherit = $conf['inherit'];
            $this->opened = $this->inherit;
        }
        if (isset($conf['reopen'])) {
            $this->reopen = $conf['reopen'];
        }
        if (isset($conf['maxLength'])) {
            $this->maxLength = $conf['maxLength'];
        }
        if (!empty($conf['lineFormat'])) {
            $this->lineFormat = str_replace(array_keys($this->formatMap),
                                             array_values($this->formatMap),
                                             $conf['lineFormat']);
        }
        if (!empty($conf['timeFormat'])) {
            $this->timeFormat = $conf['timeFormat'];
        }

        if (!empty($conf['timeFormatter'])) {
            $this->timeFormatter = $conf['timeFormatter'];
        }

        $this->id = md5(microtime().random_int(0, mt_getrandmax()));
        $this->name = (int)$name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     */
    public function open(): bool
    {
        if (!$this->opened || $this->reopen) {
            $this->opened = openlog($this->ident, LOG_PID, $this->name);
        }

        return $this->opened;
    }

    /**
     * Closes the connection to the system logger, if it is open.
     */
    public function close(): bool
    {
        if ($this->opened && !$this->inherit) {
            closelog();
            $this->opened = false;
        }

        return true;
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param mixed $message String or object containing the message to log.
     * @param int|null $priority (optional) The priority of the message.  Valid
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

        /* If we need to (re)open the connection and open() fails, abort. */
        if ((!$this->opened || $this->reopen) && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Build a syslog priority value based on our current configuration. */
        $syslogPriority = $this->toSyslog($priority);
        if ($this->inherit) {
            $syslogPriority |= $this->name;
        }

        /* Apply the configured line format to the message string. */
        $message = $this->format($this->lineFormat,
                                  $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                                  $priority, $message);

        /* Split the string into parts based on our maximum length setting. */
        $parts = str_split($message, $this->maxLength);
        if ($parts === false) {
            return false;
        }

        foreach ($parts as $part) {
            if (!syslog($syslogPriority, $part)) {
                return false;
            }
        }

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
     * @return int The LOG_* representation of $priority.
     *
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
