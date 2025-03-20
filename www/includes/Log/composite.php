<?php

declare(strict_types=1);

/**
 * $Header$
 * $Horde: horde/lib/Log/composite.php,v 1.2 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_composite:: class implements a Composite pattern which
 * allows multiple Log implementations to receive the same events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@php.net>
 *
 * @since Horde 1.3
 * @since Log 1.0
 * @package Log
 *
 * @example composite.php   Using the composite handler.
 */
class Log_composite extends Log
{
    /**
     * Array holding all of the Log instances to which log events should be
     * sent.
     */
    private array $children = [];


    /**
     * Constructs a new composite Log object.
     *
     * @param string   $name       This parameter is ignored.
     * @param string   $ident      The label that uniquely identifies this set of log messages.
     * @param array    $conf       This parameter is ignored.
     * @param int      $level      This parameter is ignored.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        $this->ident = $ident;
    }

    /**
     * Opens all of the child instances.
     *
     * @return bool True if all of the child instances were successfully opened.
     */
    public function open(): bool
    {
        /* Attempt to open each of our children. */
        $this->opened = true;
        foreach ($this->children as $child) {
            $this->opened = $this->opened && $child->open();
        }

        /* If all children were opened, return success. */
        return $this->opened;
    }

    /**
     * Closes all open child instances.
     *
     * @return bool True if all of the opened child instances were successfully
     *          closed.
     */
    public function close(): bool
    {
        /* If we haven't been opened, there's nothing more to do. */
        if (!$this->opened) {
            return true;
        }

        /* Attempt to close each of our children. */
        $closed = true;
        foreach ($this->children as $child) {
            if ($child->opened) {
                $closed = $closed && $child->close();
            }
        }

        /* Clear the opened state for consistency. */
        $this->opened = false;

        /* If all children were closed, return success. */
        return $closed;
    }

    /**
     * Flushes all child instances.  It is assumed that all of the children
     * have been successfully opened.
     *
     * @return bool True if all of the child instances were successfully flushed.
     *
     * @since Log 1.8.2
     */
    public function flush(): bool
    {
        /* Attempt to flush each of our children. */
        $flushed = true;
        foreach ($this->children as $child) {
            $flushed &= $child->flush();
        }

        /* If all children were flushed, return success. */
        return $flushed;
    }

    /**
     * Sends $message and $priority to each child of this composite.  If the
     * appropriate children aren't already open, they will be opened here.
     *
     * @param mixed     $message    String or object containing the message
     *                              to log.
     * @param int|null  $priority   (optional) The priority of the message.
     *                              Valid values are: PEAR_LOG_EMERG,
     *                              PEAR_LOG_ALERT, PEAR_LOG_CRIT,
     *                              PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                              PEAR_LOG_NOTICE, PEAR_LOG_INFO, and
     *                              PEAR_LOG_DEBUG.
     *
     * @return boolean  True if the entry is successfully logged.
     */
    public function log($message, int $priority = null): bool
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->priority;
        }

        /*
         * Abort early if the priority is above the composite handler's
         * maximum logging level.
         *
         * XXX: Consider whether or not introducing this change would break
         * backwards compatibility.  Some users may be expecting composite
         * handlers to pass on all events to their children regardless of
         * their own priority.
         */
        #if (!$this->isMasked($priority)) {
        #    return false;
        #}

        /*
         * Iterate over all of our children.  If a unopened child will respond
         * to this log event, we attempt to open it immediately.  The composite
         * handler's opened state will be enabled as soon as the first child
         * handler is successfully opened.
         *
         * We track an overall success state that indicates whether or not all
         * of the relevant child handlers were opened and successfully logged
         * the event.  If one handler fails, we still attempt any remaining
         * children, but we consider the overall result a failure.
         */
        $success = true;
        foreach ($this->children as $child) {
            /* If this child won't respond to this event, skip it. */
            if (!$child->isMasked($priority)) {
                continue;
            }

            /* If this child has yet to be opened, attempt to do so now. */
            if (!$child->opened) {
                $success &= $child->open();

                /*
                 * If we've successfully opened our first handler, the
                 * composite handler itself is considered to be opened.
                 */
                if (!$this->opened && $success) {
                    $this->opened = true;
                }
            }

            /* Finally, attempt to log the message to the child handler. */
            if ($child->opened) {
                $success = $success && $child->log($message, $priority);
            }
        }

        /* Notify the observers. */
        $this->announce(['priority' => $priority, 'message' => $message]);

        /* Return success if all of the open children logged the event. */
        return $success;
    }

    /**
     * Returns true if this is a composite.
     *
     * @return bool True if this is a composite class.
     */
    public function isComposite(): bool
    {
        return true;
    }

    /**
     * Sets this identification string for all of this composite's children.
     *
     * @param string    $ident      The new identification string.
     *
     * @since  Log 1.6.7
     */
    public function setIdent(string $ident): void
    {
        /* Call our base class's setIdent() method. */
        parent::setIdent($ident);

        /* ... and then call setIdent() on all of our children. */
        foreach ($this->children as $child) {
            $child->setIdent($ident);
        }
    }

    /**
     * Adds a Log instance to the list of children.
     *
     * @param Log    $child      The Log instance to add.
     *
     * @return boolean  True if the Log instance was successfully added.
     */
    public function addChild(Log $child): bool
    {
        $this->children[$child->id] = $child;

        return true;
    }

    /**
     * Removes a Log instance from the list of children.
     *
     * @param Log    $child      The Log instance to remove.
     *
     * @return bool True if the Log instance was successfully removed.
     */
    public function removeChild(Log $child): bool
    {
        if (!isset($this->children[$child->id])) {
            return false;
        }

        unset($this->children[$child->id]);

        return true;
    }

}
