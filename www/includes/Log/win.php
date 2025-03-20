<?php

declare(strict_types=1);
/**
 * $Header$
 *
 * @version $Revision$
 * @package Log
 */

/**
 * The Log_win class is a concrete implementation of the Log abstract
 * class that logs messages to a separate browser window.
 *
 * The concept for this log handler is based on part by Craig Davis' article
 * entitled "JavaScript Power PHP Debugging:
 *
 *  http://www.zend.com/zend/tut/tutorial-DebugLib.php
 *
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.7.0
 * @package Log
 *
 * @example win.php     Using the window handler.
 */
class Log_win extends Log
{
    /**
     * The name of the output window.
     */
    private string $name = 'LogWindow';

    /**
     * The title of the output window.
     */
    private string $title = 'Log Output Window';

    /**
     * Mapping of log priorities to styles.
     */
    private array $styles = [
        PEAR_LOG_EMERG   => 'color: red;',
        PEAR_LOG_ALERT   => 'color: orange;',
        PEAR_LOG_CRIT    => 'color: yellow;',
        PEAR_LOG_ERR     => 'color: green;',
        PEAR_LOG_WARNING => 'color: blue;',
        PEAR_LOG_NOTICE  => 'color: indigo;',
        PEAR_LOG_INFO    => 'color: violet;',
        PEAR_LOG_DEBUG   => 'color: black;',
    ];

    /**
     * String buffer that holds line that are pending output.
     */
    private array $buffer = [];

    /**
     * Constructs a new Log_win object.
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
        $this->name = str_replace(' ', '_', $name);
        $this->ident = $ident;
        $this->mask = Log::MAX($level);

        if (isset($conf['title'])) {
            $this->title = $conf['title'];
        }
        if (isset($conf['styles']) && is_array($conf['styles'])) {
            $this->styles = $conf['styles'];
        }
        if (isset($conf['colors']) && is_array($conf['colors'])) {
            foreach ($conf['colors'] as $level => $color) {
                $this->styles[$level] .= "color: $color;";
            }
        }

        register_shutdown_function([&$this, 'log_win_destructor']);
    }

    /**
     * Destructor
     */
    public function log_win_destructor(): void
    {
        if ($this->opened || (count($this->buffer) > 0)) {
            $this->close();
        }
    }

    /**
     * The first time open() is called, it will open a new browser window and
     * prepare it for output.
     *
     * This is implicitly called by log(), if necessary.
     *
     */
    public function open(): bool
    {
        if (!$this->opened) {
            $win = $this->name;
            $styles = $this->styles;

            if (!empty($this->ident)) {
                $identHeader = "$win.document.writeln('<th>Ident</th>')";
            } else {
                $identHeader = '';
            }

            echo <<< EOT
<script>
$win = window.open('', '{$this->name}', 'toolbar=no,scrollbars,width=600,height=400');
$win.document.writeln('<html>');
$win.document.writeln('<head>');
$win.document.writeln('<title>{$this->title}</title>');
$win.document.writeln('<style type="text/css">');
$win.document.writeln('body { font-family: monospace; font-size: 8pt; }');
$win.document.writeln('td,th { font-size: 8pt; }');
$win.document.writeln('td,th { border-bottom: #999999 solid 1px; }');
$win.document.writeln('td,th { border-right: #999999 solid 1px; }');
$win.document.writeln('tr { text-align: left; vertical-align: top; }');
$win.document.writeln('td.l0 { $styles[0] }');
$win.document.writeln('td.l1 { $styles[1] }');
$win.document.writeln('td.l2 { $styles[2] }');
$win.document.writeln('td.l3 { $styles[3] }');
$win.document.writeln('td.l4 { $styles[4] }');
$win.document.writeln('td.l5 { $styles[5] }');
$win.document.writeln('td.l6 { $styles[6] }');
$win.document.writeln('td.l7 { $styles[7] }');
$win.document.writeln('</style>');
$win.document.writeln('<script type="text/javascript">');
$win.document.writeln('function scroll() {');
$win.document.writeln(' body = document.getElementById("{$this->name}");');
$win.document.writeln(' body.scrollTop = body.scrollHeight;');
$win.document.writeln('}');
$win.document.writeln('<\/script>');
$win.document.writeln('</head>');
$win.document.writeln('<body id="{$this->name}" onclick="scroll()">');
$win.document.writeln('<table border="0" cellpadding="2" cellspacing="0">');
$win.document.writeln('<tr><th>Time</th>');
$identHeader
$win.document.writeln('<th>Priority</th><th width="100%">Message</th></tr>');
</script>
EOT;
            $this->opened = true;
        }

        return $this->opened;
    }

    /**
     * Closes the output stream if it is open.  If there are still pending
     * lines in the output buffer, the output window will be opened so that
     * the buffer can be drained.
     *
     */
    public function close(): bool
    {
        /*
         * If there are still lines waiting to be written, open the output
         * window so that we can drain the buffer.
         */
        if (!$this->opened && (count($this->buffer) > 0)) {
            $this->open();
        }

        if ($this->opened) {
            $this->writeln('</table>');
            $this->writeln('</body></html>');
            $this->drainBuffer();
            $this->opened = false;
        }

        return ($this->opened === false);
    }

    /**
     * Writes the contents of the output buffer to the output window.
     *
     */
    private function drainBuffer(): void
    {
        $win = $this->name;
        foreach ($this->buffer as $line) {
            echo "<script language='JavaScript'>\n";
            echo "$win.document.writeln('" . addslashes($line) . "');\n";
            echo "self.focus();\n";
            echo "</script>\n";
        }

        /* Now that the buffer has been drained, clear it. */
        $this->buffer = [];
    }

    /**
     * Writes a single line of text to the output buffer.
     *
     * @param string    $line   The line of text to write.
     *
     */
    private function writeln(string $line): void
    {
        /* Add this line to our output buffer. */
        $this->buffer[] = $line;

        /* Buffer the output until this page's headers have been sent. */
        if (!headers_sent()) {
            return;
        }

        /* If we haven't already opened the output window, do so now. */
        if (!$this->opened && !$this->open()) {
            return;
        }

        /* Drain the buffer to the output window. */
        $this->drainBuffer();
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

        /* Extract the string representation of the message. */
        $message = $this->extractMessage($message);
        $message = preg_replace('/\r\n|\n|\r/', '<br />', $message);

        [$usec, $sec] = explode(' ', microtime());

        /* Build the output line that contains the log entry row. */
        $line  = '<tr>';
        $line .= sprintf('<td>%s.%s</td>',
                         $this->formatTime((int)$sec, 'H:i:s'), substr($usec, 2, 2));
        if (!empty($this->ident)) {
            $line .= '<td>' . $this->ident . '</td>';
        }
        $line .= '<td>' . ucfirst($this->priorityToString($priority)) . '</td>';
        $line .= sprintf('<td class="l%d">%s</td>', $priority, $message);
        $line .= '</tr>';

        $this->writeln($line);

        $this->announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

}
