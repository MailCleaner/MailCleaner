<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SMTP destination server
 */

class Default_Model_SMTPDestinationServer
{

    protected $_hostname = '';
    protected $port = 25;

    public function __construct($hostname)
    {
        $this->_hostname = $hostname;
        if (preg_match('/(\S+)\:(\d+)$/', $this->_hostname, $matches)) {
            $this->_hostname = $matches[1];
            $this->_port = $matches[2];
        }
    }

    public function testDomain($domain)
    {

        require_once('Net/SMTP.php');
        if (!$smtp = new Net_SMTP($this->_hostname, $this->_port, $domain)) {
            return ['status' => 'NOK', 'message' => 'unable to instantiate SMTP'];
        }

        if (PEAR::isError($e = $smtp->connect())) {
            return [
                'status' => 'NOK', 'message' => $e->getMessage()
            ];
        }

        $from = '';
        if (PEAR::isError($e = $smtp->mailFrom($from))) {
            $smtp->disconnect();
            return [
                'status' => 'NOK',
                'message' => "Unable to set sender to <$from> (" . $e->getMessage() . ")"
            ];
        }

        $to  = 'postmaster@' . $domain;
        if (PEAR::isError($res = $smtp->rcptTo($to))) {
            $smtp->disconnect();
            return [
                'status' => 'NOK',
                'message' => "Unable to set recipient &lt;$to&gt; (" . $res->getCode() . " - " . $res->getMessage() . ")"
            ];
        }

        $response = $smtp->getResponse();
        if ($response[0] >= 500) {
            $smtp->disconnect();
            return ['status' => 'NOK', 'message' => "Message refused <$to>"];
        }

        if ($response[0] < 200 || $response[0] >= 300) {
            $smtp->disconnect();
            return ['status' => 'NOK', 'message' => "Message not immediately accepted <$to>"];
        }
        $smtp->disconnect();
        return ['status' => 'OK', 'message' => 'success !'];
    }

    public function testCallout($address, $expected)
    {
        require_once('Net/SMTP.php');
        if (!$smtp = new Net_SMTP($this->_hostname)) {
            return ['status' => 'NOK', 'message' => 'unable to instantiate SMTP'];
        }

        if (PEAR::isError($e = $smtp->connect())) {
            return [
                'status' => 'NOK',
                'message' => $e->getMessage()
            ];
        }

        if (PEAR::isError($smtp->mailFrom($address))) {
            $smtp->disconnect();
            return ['status' => 'NOK', 'message' => "Unable to set sender to <$address>"];
        }

        $message = "correctly accepted !";
        if (PEAR::isError($res = $smtp->rcptTo($address)) && $expected == 'OK') {
            $smtp->disconnect();
            return ['status' => 'NOK', 'message' => "Could not setup recipient <$address>"];
        }

        $response = $smtp->getResponse();
        if ($response[0] >= 500) {

            if ($expected == 'OK') {
                $smtp->disconnect();
                return ['status' => 'NOK', 'message' => "Recipient wrongly refused &lt;$address&gt;"];
            } else {
                $smtp->disconnect();
                return ['status' => 'OK', 'message' => "Recipient correctly refused"];
            }
        }

        if ($expected == 'NOK') {
            $smtp->disconnect();
            return ['status' => 'NOK', 'message' => "Recipient wrongly accepted &lt;$address&gt;"];
        }

        $smtp->disconnect();
        return ['status' => 'OK', 'message' => "Recipient correctly accepted"];
    }

    static function getRandomString($length)
    {
        $random = "";
        srand((float)microtime() * 1000000);
        $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $char_list .= "abcdefghijklmnopqrstuvwxyz";
        $char_list .= "1234567890-_";
        for ($i = 0; $i < $length; $i++) {
            $random .= substr($char_list, (rand() % (strlen($char_list))), 1);
        }
        return $random;
    }
}
