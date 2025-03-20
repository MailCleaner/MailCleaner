<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_firebug class is a concrete implementation of the Log::
 * abstract class which writes message into Firebug console.
 *
 * http://www.getfirebug.com/
 *
 * @author  Mika Tuupola <tuupola@appelsiini.net>
 * @since   Log 1.9.11
 * @package Log
 *
 * @example firebug.php     Using the firebug handler.
 */
class Log_firebug extends Log
{
    /**
     * Should the output be buffered or displayed immediately?
     */
    private bool $buffering = false;

    /**
     * String holding the buffered output.
     */
    private array $buffer = [];

    /**
     * String containing the format of a log line.
     */
    private string $lineFormat = '%2$s [%3$s] %4$s';

    /**
     * String containing the timestamp format. It will be passed to date().
     * If timeFormatter configured, it will be used.
     *
     * Note! Default lineFormat of this driver does not display time.
     */
    private string $timeFormat = 'M d H:i:s';

    /**
     * @var callable
     */
    private $timeFormatter;

    /**
     * Mapping of log priorities to Firebug methods.
     * @var array
     */
    private array $methods = [
        PEAR_LOG_EMERG   => 'error',
        PEAR_LOG_ALERT   => 'error',
        PEAR_LOG_CRIT    => 'error',
        PEAR_LOG_ERR     => 'error',
        PEAR_LOG_WARNING => 'warn',
        PEAR_LOG_NOTICE  => 'info',
        PEAR_LOG_INFO    => 'info',
        PEAR_LOG_DEBUG   => 'debug',
    ];

    /**
     * Constructs a new Log_firebug object.
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
        if (isset($conf['buffering'])) {
            $this->buffering = $conf['buffering'];
        }

        if ($this->buffering) {
            register_shutdown_function([&$this, 'log_firebug_destructor']);
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
     * Opens the firebug handler.
     *
     */
    public function open(): bool
    {
        $this->opened = true;
        return true;
    }

    /**
     * Destructor
     */
    public function log_firebug_destructor(): void
    {
        $this->close();
    }

    /**
     * Closes the firebug handler.
     *
     */
    public function close(): bool
    {
        $this->flush();
        $this->opened = false;
        return true;
    }

    /**
     * Flushes all pending ("buffered") data.
     *
     */
    public function flush(): bool
    {
        if (count($this->buffer)) {
            print '<script type="text/javascript">';
            print "\nif ('console' in window) {\n";
            foreach ($this->buffer as $line) {
                print "  $line\n";
            }
            print "}\n";
            print "</script>\n";
        }
        $this->buffer = [];

        return true;
    }

    /**
     * Writes $message to Firebug console. Also, passes the message
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
        $method  = $this->methods[$priority];

        /* normalize line breaks and escape quotes*/
        $message = preg_replace("/\r?\n/", "\\n", addslashes($message));

        /* Build the string containing the complete log line. */
        $line = $this->format($this->lineFormat,
                               $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                               $priority,
                               $message);

        if ($this->buffering) {
            $this->buffer[] = sprintf('console.%s("%s");', $method, $line);
        } else {
            print '<script type="text/javascript">';
            print "\nif ('console' in window) {\n";
            /* Build and output the complete log line. */
            printf('  console.%s("%s");', $method, $line);
            print "\n}\n";
            print "</script>\n";
        }
        /* Notify observers about this log message. */
        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }
}
