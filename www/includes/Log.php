<?php

declare(strict_types=1);

/**
 * $Header$
 * $Horde: horde/lib/Log.php,v 1.15 2000/06/29 23:39:45 jon Exp $
 *
 * @version $Revision$
 * @package Log
 */

define('PEAR_LOG_EMERG',    0);     /* System is unusable */
define('PEAR_LOG_ALERT',    1);     /* Immediate action required */
define('PEAR_LOG_CRIT',     2);     /* Critical conditions */
define('PEAR_LOG_ERR',      3);     /* Error conditions */
define('PEAR_LOG_WARNING',  4);     /* Warning conditions */
define('PEAR_LOG_NOTICE',   5);     /* Normal but significant */
define('PEAR_LOG_INFO',     6);     /* Informational */
define('PEAR_LOG_DEBUG',    7);     /* Debug-level messages */

define('PEAR_LOG_ALL',      0x7fffffff);    /* All messages */
define('PEAR_LOG_NONE',     0x00000000);    /* No message */

/* Log types for PHP's native error_log() function. */
define('PEAR_LOG_TYPE_SYSTEM',  0); /* Use PHP's system logger */
define('PEAR_LOG_TYPE_MAIL',    1); /* Use PHP's mail() function */
define('PEAR_LOG_TYPE_DEBUG',   2); /* Use PHP's debugging connection */
define('PEAR_LOG_TYPE_FILE',    3); /* Append to a file */
define('PEAR_LOG_TYPE_SAPI',    4); /* Use the SAPI logging handler */

/**
 * The Log:: class implements both an abstraction for various logging
 * mechanisms and the Subject end of a Subject-Observer pattern.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@php.net>
 * @since   Horde 1.3
 * @package Log
 */
class Log
{
    private const DEFAULT_TIME_FORMAT = 'M d H:i:s';

    /**
     * Indicates whether or not the log can been opened / connected.
     */
    protected bool $opened = false;

    /**
     * Instance-specific unique identification number.
     */
    protected string $id = '0';

    /**
     * The label that uniquely identifies this set of log messages.
     */
    protected string $ident = '';

    /**
     * The default priority to use when logging an event.
     */
    protected int $priority = PEAR_LOG_INFO;

    /**
     * The bitmask of allowed log levels.
     */
    protected int $mask = PEAR_LOG_ALL;

    /**
     * Holds all Log_observer objects that wish to be notified of new messages.
     */
    protected array $listeners = [];

    /**
     * Starting depth to use when walking a backtrace in search of the
     * function that invoked the log system.
     */
    protected int $backtrace_depth = 0;

    /**
     * Maps canonical format keys to position arguments for use in building
     * "line format" strings.
     */
    protected array $formatMap = [
        '%{timestamp}'  => '%1$s',
        '%{ident}'      => '%2$s',
        '%{priority}'   => '%3$s',
        '%{message}'    => '%4$s',
        '%{file}'       => '%5$s',
        '%{line}'       => '%6$s',
        '%{function}'   => '%7$s',
        '%{class}'      => '%8$s',
        '%\{'           => '%%{',
    ];

    public function __construct()
    {
    }

    /**
     * Attempts to return a concrete Log instance of type $handler.
     *
     * @param string $handler   The type of concrete Log subclass to return.
     *                          Attempt to dynamically include the code for
     *                          this subclass. Currently, valid values are
     *                          'console', 'syslog', 'sql', 'file', and 'mcal'.
     *
     * @param string $name      The name of the actually log file, table, or
     *                          other specific store to use. Defaults to an
     *                          empty string, with which the subclass will
     *                          attempt to do something intelligent.
     *
     * @param string $ident     The identity reported to the log system.
     *
     * @param array  $conf      A hash containing any additional configuration
     *                          information that a subclass might need.
     *
     * @param int $level        Log messages up to and including this level.
     *
     * @return object Log       The newly created concrete Log instance, or
     *                          null on an error.
     * @since Log 1.0
     */
    public static function factory(
        string $handler,
        string $name = '',
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ): ?Log {
        $handler = strtolower($handler);
        $class = 'Log_' . $handler;
        $classfile = 'Log/' . $handler . '.php';

        /*
         * Attempt to include our version of the named class, but don't treat
         * a failure as fatal.  The caller may have already included their own
         * version of the named class.
         */
        if (!class_exists($class, false)) {
            include_once $classfile;
        }

        /* If the class exists, return a new instance of it. */
        if (class_exists($class, false)) {
            $obj = new $class($name, $ident, $conf, $level);
            return $obj;
        }

        $null = null;
        return $null;
    }

    /**
     * Attempts to return a reference to a concrete Log instance of type
     * $handler, only creating a new instance if no log instance with the same
     * parameters currently exists.
     *
     * You should use this if there are multiple places you might create a
     * logger, you don't want to create multiple loggers, and you don't want to
     * check for the existance of one each time. The singleton pattern does all
     * the checking work for you.
     *
     * <b>You MUST call this method with the $var = &Log::singleton() syntax.
     * Without the ampersand (&) in front of the method name, you will not get
     * a reference, you will get a copy.</b>
     *
     * @param string $handler   The type of concrete Log subclass to return.
     *                          Attempt to dynamically include the code for
     *                          this subclass. Currently, valid values are
     *                          'console', 'syslog', 'sql', 'file', and 'mcal'.
     *
     * @param string $name      The name of the actually log file, table, or
     *                          other specific store to use.  Defaults to an
     *                          empty string, with which the subclass will
     *                          attempt to do something intelligent.
     *
     * @param string $ident     The identity reported to the log system.
     *
     * @param array $conf       A hash containing any additional configuration
     *                          information that a subclass might need.
     *
     * @param int $level        Log messages up to and including this level.
     *
     * @return object Log       The newly created concrete Log instance, or
     *                          null on an error.
     * @since Log 1.0
     */
    public static function singleton(
        string $handler,
        string $name = '',
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ): ?Log {
        static $instances;
        if (!isset($instances)) $instances = [];

        $signature = serialize([$handler, $name, $ident, $conf, $level]);
        if (!isset($instances[$signature])) {
            $instances[$signature] = Log::factory($handler, $name, $ident,
                                                  $conf, $level);
        }

        return $instances[$signature];
    }

    /**
     * Abstract implementation of the open() method.
     * @since Log 1.0
     */
    public function open(): bool
    {
        return false;
    }

    /**
     * Abstract implementation of the close() method.
     * @since Log 1.0
     */
    public function close(): bool
    {
        return false;
    }

    /**
     * Abstract implementation of the flush() method.
     * @since Log 1.8.2
     */
    public function flush(): bool
    {
        return false;
    }

    /**
     * Abstract implementation of the log() method.
     * @since Log 1.0
     */
    public function log($message, int $priority = null): bool
    {
        return false;
    }

    /**
     * A convenience function for logging a emergency event.  It will log a
     * message at the PEAR_LOG_EMERG log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function emerg($message): bool
    {
        return $this->log($message, PEAR_LOG_EMERG);
    }

    /**
     * A convenience function for logging an alert event.  It will log a
     * message at the PEAR_LOG_ALERT log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function alert($message): bool
    {
        return $this->log($message, PEAR_LOG_ALERT);
    }

    /**
     * A convenience function for logging a critical event.  It will log a
     * message at the PEAR_LOG_CRIT log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function crit($message): bool
    {
        return $this->log($message, PEAR_LOG_CRIT);
    }

    /**
     * A convenience function for logging a error event.  It will log a
     * message at the PEAR_LOG_ERR log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function err($message): bool
    {
        return $this->log($message, PEAR_LOG_ERR);
    }

    /**
     * A convenience function for logging a warning event.  It will log a
     * message at the PEAR_LOG_WARNING log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function warning($message): bool
    {
        return $this->log($message, PEAR_LOG_WARNING);
    }

    /**
     * A convenience function for logging a notice event.  It will log a
     * message at the PEAR_LOG_NOTICE log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function notice($message): bool
    {
        return $this->log($message, PEAR_LOG_NOTICE);
    }

    /**
     * A convenience function for logging a information event.  It will log a
     * message at the PEAR_LOG_INFO log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function info($message): bool
    {
        return $this->log($message, PEAR_LOG_INFO);
    }

    /**
     * A convenience function for logging a debug event.  It will log a
     * message at the PEAR_LOG_DEBUG log level.
     *
     * @param   mixed   $message    String or object containing the message
     *                              to log.
     *
     * @return  boolean True if the message was successfully logged.
     *
     * @since   Log 1.7.0
     */
    public function debug($message): bool
    {
        return $this->log($message, PEAR_LOG_DEBUG);
    }

    /**
     * Returns the string representation of the message data.
     *
     * If $message is an object, _extractMessage() will attempt to extract
     * the message text using a known method (such as a PEAR_Error object's
     * getMessage() method).  If a known method, cannot be found, the
     * serialized representation of the object will be returned.
     *
     * If the message data is already a string, it will be returned unchanged.
     *
     * @param  mixed $message   The original message data.  This may be a
     *                          string or any object.
     *
     * @return string           The string representation of the message.
     *
     */
    protected function extractMessage($message): string
    {
        /*
         * If we've been given an object, attempt to extract the message using
         * a known method.  If we can't find such a method, default to the
         * "human-readable" version of the object.
         *
         * We also use the human-readable format for arrays.
         */
        if (is_object($message)) {
            if (method_exists($message, 'getmessage')) {
                $message = (string)$message->getMessage();
            } else if (method_exists($message, 'tostring')) {
                $message = $message->toString();
            } else if (method_exists($message, '__tostring')) {
                $message = (string)$message;
            } else {
                $message = var_export($message, true);
            }
        } else if (is_array($message)) {
            if (isset($message['message'])) {
                if (is_scalar($message['message'])) {
                    $message = (string)$message['message'];
                } else {
                    $message = var_export($message['message'], true);
                }
            } else {
                $message = var_export($message, true);
            }
        } else if (is_bool($message) || $message === NULL) {
            $message = var_export($message, true);
        } else {
            $message = (string)$message;
        }

        /* Otherwise, we assume the message is a string. */
        return $message;
    }

    /**
     * Using debug_backtrace(), returns the file, line, and enclosing function
     * name of the source code context from which log() was invoked.
     *
     * @param   int     $depth  The initial number of frames we should step
     *                          back into the trace.
     *
     * @return  array   Array containing four strings: the filename, the line,
     *                  the function name, and the class name from which log()
     *                  was called.
     *
     * @since   Log 1.9.4
     */
    private function getBacktraceVars(int $depth): array
    {
        /* Start by generating a backtrace from the current call (here). */
        $bt = debug_backtrace();

        /* Store some handy shortcuts to our previous frames. */
        $bt0 = $bt[$depth] ?? null;
        $bt1 = $bt[$depth + 1] ?? null;

        /*
         * If we were ultimately invoked by the composite handler, we need to
         * increase our depth one additional level to compensate.
         */
        $class = $bt1['class'] ?? null;
        if ($class !== null && strcasecmp($class, 'Log_composite') == 0) {
            $depth++;
            $bt0 = $bt[$depth] ?? null;
            $bt1 = $bt[$depth + 1] ?? null;
            $class = $bt1['class'] ?? null;
        }

        /*
         * We're interested in the frame which invoked the log() function, so
         * we need to walk back some number of frames into the backtrace.  The
         * $depth parameter tells us where to start looking.   We go one step
         * further back to find the name of the encapsulating function from
         * which log() was called.
         */
        $file = isset($bt0) ? $bt0['file'] : null;
        $line = isset($bt0) ? $bt0['line'] : 0;
        $func = isset($bt1) ? $bt1['function'] : null;

        /*
         * However, if log() was called from one of our "shortcut" functions,
         * we're going to need to go back an additional step.
         */
        if (in_array($func, ['emerg', 'alert', 'crit', 'err', 'warning', 'notice', 'info', 'debug'])) {
            $bt2 = $bt[$depth + 2] ?? null;

            $file = is_array($bt1) ? $bt1['file'] : null;
            $line = is_array($bt1) ? $bt1['line'] : 0;
            $func = is_array($bt2) ? $bt2['function'] : null;
            $class = $bt2['class'] ?? null;
        }

        /*
         * If we couldn't extract a function name (perhaps because we were
         * executed from the "main" context), provide a default value.
         */
        if ($func === null) {
            $func = '(none)';
        }

        /* Return a 4-tuple containing (file, line, function, class). */
        return [$file, $line, $func, $class];
    }

    /**
     * Sets the starting depth to use when walking a backtrace in search of
     * the function that invoked the log system.  This is used on conjunction
     * with the 'file', 'line', 'function', and 'class' formatters.
     *
     * @param int $depth    The new backtrace depth.
     *
     * @since   Log 1.12.7
     */
    public function setBacktraceDepth(int $depth): void
    {
        $this->backtrace_depth = $depth;
    }

    /**
     * Produces a formatted log line based on a format string and a set of
     * variables representing the current log record and state.
     *
     * @return  string  Formatted log string.
     *
     * @since   Log 1.9.4
     */
    protected function format(string $format, string $timestamp, int $priority, string $message): string
    {
        /*
         * If the format string references any of the backtrace-driven
         * variables (%5 %6,%7,%8), generate the backtrace and fetch them.
         */
        if (preg_match('/%[5678]/', $format)) {
            /* Plus 2 to account for our internal function calls. */
            $d = $this->backtrace_depth + 2;
            [$file, $line, $func, $class] = $this->getBacktraceVars($d);
        }

        /*
         * Build the formatted string.  We use the sprintf() function's
         * "argument swapping" capability to dynamically select and position
         * the variables which will ultimately appear in the log string.
         */
        return sprintf($format,
                       $timestamp,
                       $this->ident,
                       $this->priorityToString($priority),
                       $message,
                       $file ?? '',
                       $line ?? '',
                       $func ?? '',
                       $class ?? '');
    }

    /**
     * Returns the string representation of a PEAR_LOG_* integer constant.
     *
     * @param int $priority     A PEAR_LOG_* integer constant.
     *
     * @return string           The string representation of $level.
     *
     * @since   Log 1.0
     */
    public function priorityToString(int $priority): string
    {
        $levels = [
            PEAR_LOG_EMERG   => 'emergency',
            PEAR_LOG_ALERT   => 'alert',
            PEAR_LOG_CRIT    => 'critical',
            PEAR_LOG_ERR     => 'error',
            PEAR_LOG_WARNING => 'warning',
            PEAR_LOG_NOTICE  => 'notice',
            PEAR_LOG_INFO    => 'info',
            PEAR_LOG_DEBUG   => 'debug',
        ];

        return $levels[$priority];
    }

    /**
     * Returns the the PEAR_LOG_* integer constant for the given string
     * representation of a priority name.  This function performs a
     * case-insensitive search.
     *
     * @param string $name      String containing a priority name.
     *
     * @return int           The PEAR_LOG_* integer contstant corresponding
     *                          the the specified priority name.
     *
     * @since   Log 1.9.0
     */
    public function stringToPriority(string $name): int
    {
        $levels = [
            'emergency' => PEAR_LOG_EMERG,
            'alert'     => PEAR_LOG_ALERT,
            'critical'  => PEAR_LOG_CRIT,
            'error'     => PEAR_LOG_ERR,
            'warning'   => PEAR_LOG_WARNING,
            'notice'    => PEAR_LOG_NOTICE,
            'info'      => PEAR_LOG_INFO,
            'debug'     => PEAR_LOG_DEBUG
        ];

        return $levels[strtolower($name)];
    }

    /**
     * Calculate the log mask for the given priority.
     *
     * This method may be called statically.
     *
     * @param integer   $priority   The priority whose mask will be calculated.
     *
     * @return integer  The calculated log mask.
     *
     * @since   Log 1.7.0
     */
    public static function MASK(int $priority): int
    {
        return (1 << $priority);
    }

    /**
     * Calculate the log mask for all priorities greater than or equal to the
     * given priority.  In other words, $priority will be the lowest priority
     * matched by the resulting mask.
     *
     * This method may be called statically.
     *
     * @param integer   $priority   The minimum priority covered by this mask.
     *
     * @return integer  The resulting log mask.
     *
     * @since   Log 1.9.4
     */
    public static function MIN(int $priority): int
    {
        return PEAR_LOG_ALL ^ ((1 << $priority) - 1);
    }

    /**
     * Calculate the log mask for all priorities less than or equal to the
     * given priority.  In other words, $priority will be the highests priority
     * matched by the resulting mask.
     *
     * This method may be called statically.
     *
     * @param integer   $priority   The maximum priority covered by this mask.
     *
     * @return integer  The resulting log mask.
     *
     * @since   Log 1.9.4
     */
    public static function MAX(int $priority): int
    {
        return ((1 << ($priority + 1)) - 1);
    }

    /**
     * Set and return the level mask for the current Log instance.
     *
     * @param integer $mask     A bitwise mask of log levels.
     *
     * @return integer          The current level mask.
     *
     * @since   Log 1.7.0
     */
    public function setMask(int $mask): int
    {
        $this->mask = $mask;

        return $this->mask;
    }

    /**
     * Returns the current level mask.
     *
     * @return integer         The current level mask.
     *
     * @since   Log 1.7.0
     */
    public function getMask(): int
    {
        return $this->mask;
    }

    /**
     * Check if the given priority is included in the current level mask.
     *
     * @param integer   $priority   The priority to check.
     *
     * @return boolean  True if the given priority is included in the current
     *                  log mask.
     *
     * @since   Log 1.7.0
     */
    protected function isMasked(int $priority): bool
    {
        return (bool)(Log::MASK($priority) & $this->mask);
    }

    /**
     * Returns the current default priority.
     *
     * @return integer  The current default priority.
     *
     * @since   Log 1.8.4
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Sets the default priority to the specified value.
     *
     * @param   integer $priority   The new default priority.
     *
     * @since   Log 1.8.4
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Adds a Log_observer instance to the list of observers that are listening
     * for messages emitted by this Log instance.
     *
     * @param Log_observer    $observer   The Log_observer instance to attach as a
     *                              listener.
     *
     * @return boolean  True if the observer is successfully attached.
     *
     * @since   Log 1.0
     */
    public function attach(Log_observer $observer): bool
    {
        $this->listeners[$observer->getId()] = $observer;

        return true;
    }

    /**
     * Removes a Log_observer instance from the list of observers.
     *
     * @param Log_observer    $observer   The Log_observer instance to detach from
     *                              the list of listeners.
     *
     * @return boolean  True if the observer is successfully detached.
     *
     * @since   Log 1.0
     */
    public function detach(Log_observer $observer): bool
    {
        if (!isset($this->listeners[$observer->getId()])) {
            return false;
        }

        unset($this->listeners[$observer->getId()]);

        return true;
    }

    /**
     * Informs each registered observer instance that a new message has been
     * logged.
     *
     * @param array     $event      A hash describing the log event.
     *
     */
    protected function announce(array $event): void
    {
        /**
         * @var Log_observer $listener
         */
        foreach ($this->listeners as $listener) {
            if ($event['priority'] <= $listener->getPriority()) {
                $listener->notify($event);
            }
        }
    }

    /**
     * Indicates whether this is a composite class.
     *
     * @return boolean          True if this is a composite class.
     *
     * @since   Log 1.0
     */
    public function isComposite(): bool
    {
        return false;
    }

    /**
     * Sets this Log instance's identification string.
     *
     * @param string    $ident      The new identification string.
     *
     * @since   Log 1.6.3
     */
    public function setIdent(string $ident): void
    {
        $this->ident = $ident;
    }

    /**
     * Returns the current identification string.
     *
     * @return string   The current Log instance's identification string.
     *
     * @since   Log 1.6.3
     */
    public function getIdent(): string
    {
        return $this->ident;
    }

    /**
     * Function to format unix timestamp in specified format, which will be used in log record
     * By default will be used format self::DEFAULT_TIME_FORMAT
     * timeFormatter function will be used if it is set
     *
     * @param int $time unix timestamp
     * @param string $timeFormat specified format, which will be used in log record
     * @param callable|null $timeFormatter function which will be used to format time
     * @return string
     */
    protected function formatTime(int $time, string $timeFormat = self::DEFAULT_TIME_FORMAT, ?callable $timeFormatter = null)
    {
        if (!is_null($timeFormatter) && is_callable($timeFormatter)) {
            return call_user_func($timeFormatter, $timeFormat, $time);
        }

        if (strpos($timeFormat, '%') !== false) {
            trigger_error('Using strftime-style formatting is deprecated', E_USER_WARNING);
            $timeFormat = $this->convertStrftimeFormatConverter($timeFormat);
        }

        return date($timeFormat, $time);
    }

    /**-
     * Function to convert strftime format to format acceptable by date function
     *
     * @param string $timeFormat
     * @return string
     */
    private function convertStrftimeFormatConverter(string $timeFormat): string
    {
        $strf_syntax = [
            '%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
            '%V',
            '%B', '%m', '%b', '%-m',
            '%G', '%Y', '%y',
            '%P', '%p', '%l', '%I', '%H', '%M', '%S',
            '%z', '%Z',
            '%s',
            '%x', '%X',
        ];

        // http://php.net/manual/en/function.date.php
        $date_syntax = [
            'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
            'W',
            'F', 'm', 'M', 'n',
            'o', 'Y', 'y',
            'a', 'A', 'g', 'h', 'H', 'i', 's',
            'O', 'T',
            'U',
            'm/d/Y', 'H:i:s',
        ];

        $pattern = array_map(
            fn($s) => '/(?<!\\\\|\%)' . $s . '/',
            $strf_syntax
        );

        return preg_replace($pattern, $date_syntax, $timeFormat);
    }
}
