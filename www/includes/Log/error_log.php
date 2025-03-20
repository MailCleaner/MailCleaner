<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_error_log class is a concrete implementation of the Log abstract
 * class that logs messages using PHP's error_log() function.
 *
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.7.0
 * @package Log
 *
 * @example error_log.php   Using the error_log handler.
 */
class Log_error_log extends Log
{
    /**
     * The error_log() log type.
     */
    private int $type;

    /**
     * The type-specific destination value.
     */
    private string $destination = '';

    /**
     * Additional headers to pass to the mail() function when the
     * PEAR_LOG_TYPE_MAIL type is used.
     */
    private string $extra_headers = '';

    /**
     * String containing the format of a log line.
     */
    private string $lineFormat = '%2$s: %4$s';

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
     * Constructs a new Log_error_log object.
     *
     * @param string $name     One of the PEAR_LOG_TYPE_* constants.
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
        $this->id = md5(microtime().random_int(0, mt_getrandmax()));

        /* Ensure we have a valid integer value for $name. */
        if (empty($name) || !is_numeric($name)) {
            $name = PEAR_LOG_TYPE_SYSTEM;
        }

        $this->type = (int)$name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (!empty($conf['destination'])) {
            $this->destination = $conf['destination'];
        }

        if (!empty($conf['extra_headers'])) {
            $this->extra_headers = $conf['extra_headers'];
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
    }

    /**
     * Opens the handler.
     *
     * @since   Log 1.9.6
     */
    public function open(): bool
    {
        $this->opened = true;
        return true;
    }

    /**
     * Closes the handler.
     *
     * @since   Log 1.9.6
     */
    public function close(): bool
    {
        $this->opened = false;
        return true;
    }

    /**
     * Logs $message using PHP's error_log() function.  The message is also
     * passed along to any Log_observer instances that are observing this Log.
     *
     * @param mixed  $message   String or object containing the message to log.
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

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Build the string containing the complete log line. */
        $line = $this->format($this->lineFormat,
                               $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                               $priority, $message);

        /* Pass the log line and parameters to the error_log() function. */
        $success = error_log($line, $this->type, $this->destination,
                             $this->extra_headers);

        $this->announce(['priority' => $priority, 'message' => $message]);

        return $success;
    }

}
