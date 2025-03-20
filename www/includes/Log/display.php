<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_display class is a concrete implementation of the Log::
 * abstract class which writes message into browser in usual PHP maner.
 * This may be useful because when you use PEAR::setErrorHandling in
 * PEAR_ERROR_CALLBACK mode error messages are not displayed by
 * PHP error handler.
 *
 * @author  Paul Yanchenko <pusher@inaco.ru>
 * @since   Log 1.8.0
 * @package Log
 *
 * @example display.php     Using the display handler.
 */
class Log_display extends Log
{
    /**
     * String containing the format of a log line.
     */
    private string $lineFormat = '<b>%3$s</b>: %4$s';

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
     * Flag indicating whether raw message text should be passed directly to
     * the log system.  Otherwise, the text will be converted to an HTML-safe
     * representation.
     */
    private bool $rawText = false;

    /**
     * Constructs a new Log_display object.
     *
     * @param string $name     Ignored.
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
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        /* Start by configuring the line format. */
        if (!empty($conf['lineFormat'])) {
            $this->lineFormat = str_replace(array_keys($this->formatMap),
                                             array_values($this->formatMap),
                                             $conf['lineFormat']);
        }

        /* We may need to prepend a string to our line format. */
        $prepend = $conf['error_prepend'] ?? ini_get('error_prepend_string');
        if (!empty($prepend)) {
            $this->lineFormat = $prepend . $this->lineFormat;
        }

        /* We may also need to append a string to our line format. */
        $append = $conf['error_append'] ?? ini_get('error_append_string');
        if (!empty($append)) {
            $this->lineFormat .= $append;
        }

        /* Lastly, the line ending sequence is also configurable. */
        if (isset($conf['linebreak'])) {
            $this->lineFormat .= $conf['linebreak'];
        } else {
            $this->lineFormat .= "<br />\n";
        }

        /* The user can also change the time format. */
        if (!empty($conf['timeFormat'])) {
            $this->timeFormat = $conf['timeFormat'];
        }

        if (!empty($conf['timeFormatter'])) {
            $this->timeFormatter = $conf['timeFormatter'];
        }

        /* Message text conversion can be disabled. */
        if (isset($conf['rawText'])) {
            $this->rawText = $conf['rawText'];
        }
    }

    /**
     * Opens the display handler.
     *
     * @since   Log 1.9.6
     */
    public function open(): bool
    {
        $this->opened = true;
        return true;
    }

    /**
     * Closes the display handler.
     *
     * @since   Log 1.9.6
     */
    public function close(): bool
    {
        $this->opened = false;
        return true;
    }

    /**
     * Writes $message to the text browser. Also, passes the message
     * along to any Log_observer instances that are observing this Log.
     *
     * @param mixed  $message    String or object containing the message to log.
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

        /* Convert the message to an HTML-friendly represention unless raw
         * text has been requested. */
        if ($this->rawText === false) {
            $message = nl2br(htmlspecialchars($message));
        }

        /* Build and output the complete log line. */
        echo $this->format($this->lineFormat,
                            $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                            $priority,
                            $message);

        /* Notify observers about this log message. */
        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

}
