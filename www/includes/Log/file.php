<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_file class is a concrete implementation of the Log abstract
 * class that logs messages to a text file.
 *
 * @author  Jon Parise <jon@php.net>
 * @author  Roman Neuhauser <neuhauser@bellavista.cz>
 * @since   Log 1.0
 * @package Log
 *
 * @example file.php    Using the file handler.
 */
class Log_file extends Log
{
    /**
     * String containing the name of the log file.
     */
    private string $filename = 'php.log';

    /**
     * Handle to the log file.
     * @var resource
     */
    private $fp = false;

    /**
     * Should new log entries be append to an existing log file, or should the
     * a new log file overwrite an existing one?
     */
    private bool $append = true;

    /**
     * Should advisory file locking (i.e., flock()) be used?
     */
    private bool $locking = false;

    /**
     * Integer (in octal) containing the log file's permissions mode.
     */
    private int $mode = 0644;

    /**
     * Integer (in octal) specifying the file permission mode that will be
     * used when creating directories that do not already exist.
     */
    private int $dirmode = 0755;

    /**
     * String containing the format of a log line.
     */
    private string $lineFormat = '%1$s %2$s [%3$s] %4$s';

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
     * String containing the end-on-line character sequence.
     */
    private string $eol = "\n";

    /**
     * Constructs a new Log_file object.
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
        $this->filename = $name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (isset($conf['append'])) {
            $this->append = $conf['append'];
        }

        if (isset($conf['locking'])) {
            $this->locking = $conf['locking'];
        }

        if (!empty($conf['mode'])) {
            if (is_string($conf['mode'])) {
                $this->mode = octdec($conf['mode']);
            } else {
                $this->mode = $conf['mode'];
            }
        }

        if (!empty($conf['dirmode'])) {
            if (is_string($conf['dirmode'])) {
                $this->dirmode = octdec($conf['dirmode']);
            } else {
                $this->dirmode = $conf['dirmode'];
            }
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

        if (!empty($conf['eol'])) {
            $this->eol = $conf['eol'];
        } else {
            $this->eol = (strstr(PHP_OS, 'WIN')) ? "\r\n" : "\n";
        }

        register_shutdown_function([&$this, 'log_file_destructor']);
    }

    /**
     * Destructor
     */
    public function log_file_destructor(): void
    {
        if ($this->opened) {
            $this->close();
        }
    }

    /**
     * Creates the given directory path.  If the parent directories don't
     * already exist, they will be created, too.
     *
     * This implementation is inspired by Python's os.makedirs function.
     *
     * @param   string  $path       The full directory path to create.
     * @param   integer $mode       The permissions mode with which the
     *                              directories will be created.
     *
     * @return bool  True if the full path is successfully created or already
     *          exists.
     */
    private function mkpath(string $path, int $mode = 0700): bool
    {
        /* Separate the last pathname component from the rest of the path. */
        $head = dirname($path);
        $tail = basename($path);

        /* Make sure we've split the path into two complete components. */
        if (empty($tail)) {
            $head = dirname($path);
            $tail = basename($path);
        }

        /* Recurse up the path if our current segment does not exist. */
        if (!empty($head) && !empty($tail) && !is_dir($head)) {
            $this->mkpath($head, $mode);
        }

        /* Create this segment of the path. */
        return @mkdir($head, $mode);
    }

    /**
     * Opens the log file for output.  If the specified log file does not
     * already exist, it will be created.  By default, new log entries are
     * appended to the end of the log file.
     *
     * This is implicitly called by log(), if necessary.
     */
    public function open(): bool
    {
        if (!$this->opened) {
            /* If the log file's directory doesn't exist, create it. */
            if (!is_dir(dirname($this->filename))) {
                $this->mkpath($this->filename, $this->dirmode);
            }

            /* Determine whether the log file needs to be created. */
            $creating = !file_exists($this->filename);

            /* Obtain a handle to the log file. */
            $this->fp = fopen($this->filename, ($this->append) ? 'a' : 'w');

            /* We consider the file "opened" if we have a valid file pointer. */
            $this->opened = ($this->fp !== false);

            /* Attempt to set the file's permissions if we just created it. */
            if ($creating && $this->opened) {
                chmod($this->filename, $this->mode);
            }
        }

        return $this->opened;
    }

    /**
     * Closes the log file if it is open.
     */
    public function close(): bool
    {
        /* If the log file is open, close it. */
        if ($this->opened && fclose($this->fp)) {
            $this->opened = false;
        }

        return ($this->opened === false);
    }

    /**
     * Flushes all pending data to the file handle.
     *
     * @since Log 1.8.2
     */
    public function flush(): bool
    {
        if (is_resource($this->fp)) {
            return fflush($this->fp);
        }

        return false;
    }

    /**
     * Logs $message to the output window.  The message is also passed along
     * to any Log_observer instances that are observing this Log.
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

        /* If the log file isn't already open, open it now. */
        if (!$this->opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Build the string containing the complete log line. */
        $line = $this->format($this->lineFormat,
                               $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                               $priority, $message) . $this->eol;

        /* If locking is enabled, acquire an exclusive lock on the file. */
        if ($this->locking) {
            flock($this->fp, LOCK_EX);
        }

        /* Write the log line to the log file. */
        $success = (fwrite($this->fp, $line) !== false);

        /* Unlock the file now that we're finished writing to it. */
        if ($this->locking) {
            flock($this->fp, LOCK_UN);
        }

        /* Notify observers about this log message. */
        $this->announce(['priority' => $priority, 'message' => $message]);

        return $success;
    }

}
