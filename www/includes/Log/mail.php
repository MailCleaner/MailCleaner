<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_mail class is a concrete implementation of the Log:: abstract class
 * which sends log messages to a mailbox.
 * The mail is actually sent when you close() the logger, or when the destructor
 * is called (when the script is terminated).
 *
 * PLEASE NOTE that you must create a Log_mail object using =&, like this :
 *  $logger =& Log::factory("mail", "recipient@example.com", ...)
 *
 * This is a PEAR requirement for destructors to work properly.
 * See http://pear.php.net/manual/en/class.pear.php
 *
 * @author  Ronnie Garcia <ronnie@mk2.net>
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.3
 * @package Log
 *
 * @example mail.php    Using the mail handler.
 */
class Log_mail extends Log
{
    /**
     * String holding the recipients' email addresses.  Multiple addresses
     * should be separated with commas.
     */
    private string $recipients = '';

    /**
     * String holding the sender's email address.
     */
    private string $from = '';

    /**
     * String holding the email's subject.
     */
    private string $subject = '[Log_mail] Log message';

    /**
     * String holding an optional preamble for the log messages.
     */
    private string $preamble = '';

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
     * String holding the mail message body.
     */
    private string $message = '';

    /**
     * Flag used to indicated that log lines have been written to the message
     * body and the message should be sent on close().
     */
    private bool $shouldSend = false;

    /**
     * String holding the backend name of PEAR::Mail
     */
    private string $mailBackend = '';

    /**
     * Array holding the params for PEAR::Mail
     */
    private array $mailParams = [];

    /**
     * Constructs a new Log_mail object.
     *
     * Here is how you can customize the mail driver with the conf[] hash :
     *   $conf['from']:        the mail's "From" header line,
     *   $conf['subject']:     the mail's "Subject" line.
     *   $conf['mailBackend']: backend name of PEAR::Mail
     *   $conf['mailParams']:  parameters for the PEAR::Mail backend
     *
     * @param string $name      The message's recipients.
     * @param string $ident     The identity string.
     * @param array  $conf      The configuration array.
     * @param int    $level     Log messages up to and including this level.
     */
    public function __construct(
        string $name,
        string $ident = '',
        array $conf = [],
        int $level = PEAR_LOG_DEBUG
    ) {
        $this->id = md5(microtime().random_int(0, mt_getrandmax()));
        $this->recipients = $name;
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (!empty($conf['from'])) {
            $this->from = $conf['from'];
        } else {
            $this->from = ini_get('sendmail_from');
        }

        if (!empty($conf['subject'])) {
            $this->subject = $conf['subject'];
        }

        if (!empty($conf['preamble'])) {
            $this->preamble = $conf['preamble'];
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

        if (!empty($conf['mailBackend'])) {
            $this->mailBackend = $conf['mailBackend'];
        }

        if (!empty($conf['mailParams'])) {
            $this->mailParams = $conf['mailParams'];
        }

        /* register the destructor */
        register_shutdown_function([&$this, 'log_mail_destructor']);
    }

    /**
     * Destructor. Calls close().
     *
     */
    public function log_mail_destructor(): void
    {
        $this->close();
    }

    /**
     * Starts a new mail message.
     * This is implicitly called by log(), if necessary.
     *
     */
    public function open(): bool
    {
        if (!$this->opened) {
            if (!empty($this->preamble)) {
                $this->message = $this->preamble . "\r\n\r\n";
            }
            $this->opened = true;
        }

        return $this->opened;
    }

    /**
     * Closes the message, if it is open, and sends the mail.
     * This is implicitly called by the destructor, if necessary.
     *
     */
    public function close(): bool
    {
        if ($this->opened) {
            if ($this->shouldSend && !empty($this->message)) {
                if ($this->mailBackend === '') {  // use mail()
                    $headers = "From: $this->from\r\n";
                    $headers .= 'User-Agent: PEAR Log Package';
                    if (mail($this->recipients, $this->subject,
                             $this->message, $headers) == false) {
                        return false;
                    }
                } else {  // use PEAR::Mail
                    include_once 'Mail.php';
                    $headers = [
                        'From' => $this->from,
                        'To' => $this->recipients,
                        'User-Agent' => 'PEAR Log Package',
                        'Subject' => $this->subject
                    ];
                    $mailer = &Mail::factory($this->mailBackend,
                                             $this->mailParams);
                    $res = $mailer->send($this->recipients, $headers,
                                         $this->message);
                    if (PEAR::isError($res)) {
                        return false;
                    }
                }

                /* Clear the message string now that the email has been sent. */
                $this->message = '';
                $this->shouldSend = false;
            }
            $this->opened = false;
        }

        return ($this->opened === false);
    }

    /**
     * Flushes the log output by forcing the email message to be sent now.
     * Events that are logged after flush() is called will be appended to a
     * new email message.
     *
     * @since Log 1.8.2
     */
    public function flush(): bool
    {
        /*
         * It's sufficient to simply call close() to flush the output.
         * The next call to log() will cause the handler to be reopened.
         */
        return $this->close();
    }

    /**
     * Writes $message to the currently open mail message.
     * Calls open(), if necessary.
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

        /* If the message isn't open and can't be opened, return failure. */
        if (!$this->opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);

        /* Append the string containing the complete log line. */
        $this->message .= $this->format($this->lineFormat,
                                          $this->formatTime(time(), $this->timeFormat, $this->timeFormatter),
                                          $priority, $message) . "\r\n";
        $this->shouldSend = true;

        /* Notify observers about this log message. */
        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }
}
