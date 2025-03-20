<?php

declare(strict_types=1);
/**
 * $Header$
 * $Horde: horde/lib/Log/mcal.php,v 1.2 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_mcal class is a concrete implementation of the Log::
 * abstract class which sends messages to a local or remote calendar
 * store accessed through MCAL.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since Horde 1.3
 * @since Log 1.0
 * @package Log
 */
class Log_mcal extends Log
{
    /**
     * holding the calendar specification to connect to.
     */
    private string $calendar = '{localhost/mstore}';

    /**
     * holding the username to use.
     */
    private string $username = '';

    /**
     * holding the password to use.
     */
    private string $password = '';

    /**
     * holding the options to pass to the calendar stream.
     */
    private int $options = 0;

    /**
     * ResourceID of the MCAL stream.
     */
    private string $stream = '';

    /**
     * Integer holding the log facility to use.
     */
    private string $name;

    /**
     * Constructs a new Log_mcal object.
     *
     * @param string $name     The category to use for our events.
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
        $this->name = $name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);
        $this->calendar = $conf['calendar'];
        $this->username = $conf['username'];
        $this->password = $conf['password'];
        $this->options = $conf['options'];
    }

    /**
     * Opens a calendar stream, if it has not already been
     * opened. This is implicitly called by log(), if necessary.
     */
    public function open(): bool
    {
        if (!$this->opened) {
            $this->stream = mcal_open($this->calendar, $this->username,
                $this->password, $this->options);
            $this->opened = true;
        }

        return $this->opened;
    }

    /**
     * Closes the calendar stream, if it is open.
     */
    public function close(): bool
    {
        if ($this->opened) {
            mcal_close($this->stream);
            $this->opened = false;
        }

        return ($this->opened === false);
    }

    /**
     * Logs $message and associated information to the currently open
     * calendar stream. Calls open() if necessary. Also passes the
     * message along to any Log_observer instances that are observing
     * this Log.
     *
     * @param mixed  $message  String or object containing the message to log.
     * @param int|null $priority The priority of the message. Valid
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

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        $date_str = date('Y:n:j:G:i:s');
        $dates = explode(':', $date_str);

        mcal_event_init($this->stream);
        mcal_event_set_title($this->stream, $this->ident);
        mcal_event_set_category($this->stream, $this->name);
        mcal_event_set_description($this->stream, $message);
        mcal_event_add_attribute($this->stream, 'priority', $priority);
        mcal_event_set_start($this->stream, $dates[0], $dates[1], $dates[2],
            $dates[3], $dates[4], $dates[5]);
        mcal_event_set_end($this->stream, $dates[0], $dates[1], $dates[2],
            $dates[3], $dates[4], $dates[5]);
        mcal_append_event($this->stream);

        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

}
