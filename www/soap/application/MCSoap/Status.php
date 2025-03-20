<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

class MCSoap_Status
{

    static private function getMcStatusElement($element)
    {
        $ret = '';

        require_once('MailCleaner/Config.php');

        $config = new MailCleaner_Config();

        if (preg_match('/^loadavg(05|10|15)/', $element, $matches)) {
            $cmdres = `/bin/cat /proc/loadavg`;
            if (preg_match('/^([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)/', $cmdres, $cmdmatches)) {
                if ($matches[1] == '05') {
                    return $cmdmatches[1];
                }
                if ($matches[1] == '10') {
                    return $cmdmatches[2];
                }
                if ($matches[1] == '15') {
                    return $cmdmatches[3];
                }
            }
        }

        if ($element == 'disksusage') {
            $data = [];
            $cmdres = `/bin/df -lP`;
            foreach (preg_split("/\n/", $cmdres) as $line) {
                if (preg_match('/^(\/\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+(\S+)/', $line, $matches)) {
                    $data[] = [
                        'dev' => $matches[1],
                        'size' => $matches[2],
                        'used' => $matches[3],
                        'free' => $matches[4],
                        'puse' => $matches[5],
                        'mount' => $matches[6]
                    ];
                }
            }
            return $data;
        }

        if ($element == 'memoryusage') {
            $data = [];
            $cmdres = `/usr/bin/free`;
            foreach (preg_split("/\n/", $cmdres) as $line) {
                if (preg_match('/^Mem:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                    $data['memtotal'] = $matches[1];
                    $data['memused'] = $matches[2];
                    $data['memfree'] = $matches[3];
                    $data['memshared'] = $matches[4];
                    $data['membuffers'] = $matches[5];
                    $data['memcached'] = $matches[6];
                }
                if (preg_match('/^Swap:\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                    $data['swaptotal'] = $matches[1];
                    $data['swapused'] = $matches[2];
                    $data['swapfree'] = $matches[3];
                }
            }
            return $data;
        }

        if ($element == 'spools') {
            $data = [];
            $cmd = $config->getOption('SRCDIR') . "/bin/check_spools.sh";
            $cmdres = `$cmd`;
            foreach (preg_split("/\n/", $cmdres) as $line) {
                if (preg_match('/^Stage\ (\d):\s+(\d+)/', $line, $matches)) {
                    $data[$matches[1]] = $matches[2];
                }
            }
            return $data;
        }

        if ($element == 'processes') {
            $data = [];
            $cmd = $config->getOption('SRCDIR') . "/bin/get_status.pl -s";
            $order = [
                'exim_stage1',
                'exim_stage2',
                'exim_stage4',
                'apache',
                'mailscanner',
                'mysql_master',
                'mysql_slave',
                'snmpd',
                'greylistd',
                'cron',
                'preftdaemon',
                'spamd',
                'clamd',
                'clamspamd',
                'spamhandler',
                'newsld',
                'firewall'
            ];
            $reg = str_repeat('\|([0-9])', count($order));
            $cmdres = `$cmd`;
            $data['cmd'] = $cmdres;
            $data['reg'] = $reg;

            if (preg_match('/' . $reg . '/', $cmdres, $matches)) {
                $i = 1;
                foreach ($order as $key) {
                    $data[$key] = $matches[$i++];
                }
            }
            return $data;
        }
        return $ret;
    }

    /**
     * This function will gather status
     *
     * @param  array
     * @return array
     */
    static public function Status_getStatusValues($params)
    {
        $res = ['data' => []];

        $data = [];
        foreach ($params as $param) {
            $data[$param] = MCSoap_Status::getMcStatusElement($param);
        }
        $res['data'] = $data;
        return $res;
    }

    /**
     * This function simply answer with the question
     *
     * @return array
     */
    static public function Status_getProcessesStatus()
    {
        return MCSoap_Status::getMcStatusElement('processes');
    }

    /**
     * This function return the current load of the system (*100)
     *
     * @return float
     */
    static public function Status_getSystemLoad()
    {
        return MCSoap_Status::getMcStatusElement('load');
    }

    /**
     * This function return the current hardware status
     *
     * @return array
     */
    static public function Status_getHardwareHealth()
    {
        $disks = MCSoap_Status::getMcStatusElement('disks');
        $swap =  MCSoap_Status::getMcStatusElement('swap');
        return [];
    }

    /**
     * This function will fetch today's stats
     *
     * @return array
     */
    static public function Status_getTodayStats($params)
    {
        $res = ['data' => []];

        $data = [
            'bytes' => 0,
            'msgs' => 0,
            'spams' => 0,
            'pspam' => 0,
            'viruses' => 0,
            'pvirus' => 0,
            'contents' => 0,
            'pcontent' => 0,
            'cleans' => 0,
            'pclean' => 0
        ];
        $order = [
            'bytes',
            'msgs',
            'spams',
            'pspam',
            'viruses',
            'pvirus',
            'contents',
            'pcontent',
            'users',
            'cleans',
            'pclean'
        ];

        require_once('MailCleaner/Config.php');

        $config = new MailCleaner_Config();
        $cmd = $config->getOption('SRCDIR') . "/bin/get_today_stats.pl -A";
        $cmdres = `$cmd`;
        $res['cmd'] = $cmd;
        if (preg_match('/^(\d+)\|(\d+)\|(\d+)\|([0-9.]+)\|(\d+)\|([0-9.]+)\|(\d+)\|([0-9.]+)\|(\d+)\|(\d+)\|([0-9.]+)/', $cmdres, $matches)) {
            $i = 1;
            foreach ($order as $o) {
                $data[$o] = $matches[$i++];
            }
            $res['data'] = $data;
        }
        return $res;
    }

    /**
     * This function will fetch messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_getSpool($params)
    {
        $ret = ['message' => 'OK', 'msgs' => [], 'nbmsgs' => 0];

        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();

        $available_spools = [1, 2, 4];
        $spool = 1;
        if (isset($params['spool']) && in_array($params['spool'], $available_spools)) {
            $spool = $params['spool'];
        }
        $cmd = "/opt/exim4/bin/exipick --spool " . $config->getOption('VARDIR') . "/spool/exim_stage" . $spool . " -flatq --show-vars deliver_freeze,dont_deliver,first_delivery,warning_count,shown_message_size,message_age";
        $cmd_res = `$cmd`;

        $limit = 200;
        if (isset($params['limit']) && is_numeric($params['limit'])) {
            $limit = $params['limit'];
        }
        $offset = 0;
        if (isset($params['offset']) && is_numeric($params['offset'])) {
            $offset = $params['offset'];
        }

        $totallines = preg_split('/[\n\r]+/', $cmd_res, -1, PREG_SPLIT_NO_EMPTY);
        $ret['nbmsgs'] = count($totallines);
        $ret['page'] = floor($offset / $limit) + 1;
        $ret['pages'] = ceil($ret['nbmsgs'] / $limit);

        $lines = array_splice($totallines, $offset, $limit);
        unset($totallines);

        foreach ($lines as $line) {
            if (preg_match('/\s*(\d+[mhdy])\s+(\S+)\s+(\S+)\s+(<[^>]*>)\s+(.*)/', $line, $m)) {
                $message = [
                    'time_in_queue' => $m[1],
                    'flags' => $m[2],
                    'id' => $m[3],
                    'raw_from' => utf8_encode($m[4]),
                    'raw_to' => utf8_encode($m[5]),
                    'frozen' => 0,
                    'from' => '',
                    'to' => [],
                    'size' => 0,
                    'age' => 0,
                    'sending' => 0,
                    'first_delivery' => 0
                ];

                if (preg_match('/deliver_freeze=\'1\'/', $message['flags'])) {
                    $message['frozen'] = 1;
                }
                if (preg_match('/shown_message_size=\'([^\']+)\'/', $message['flags'], $m)) {
                    $message['size'] = $m[1];
                }
                if (preg_match('/message_age=\'([^\']+)\'/', $message['flags'], $m)) {
                    $message['age'] = $m[1];
                }
                if (preg_match('/first_delivery=\'1\'/', $message['flags'])) {
                    $message['first_delivery'] = 1;
                }
                if (preg_match('/<([^>]+)>/', $message['raw_from'], $m)) {
                    $message['from'] = utf8_encode($m[1]);
                }
                $tos_lines = preg_split('/\s/', $message['raw_to']);
                $message['to'] = [];
                foreach ($tos_lines as $line) {
                    array_push($message['to'], utf8_encode($line));
                }

                ## check if message is being forced
                $pscmd = "/bin/ps aux | grep " . $message['id'] . " | grep -v grep";
                $psres = `$pscmd`;
                if ($psres != '') {
                    $message['sending'] = 1;
                }

                ## got msglogs
                $msglogpath = $config->getOption('VARDIR') . "/spool/exim_stage" . $spool . "/msglog";
                $msglogfile = '';

                $msglog = ['Message log file not found.', 'Message is probably being sent at the moment.'];
                if (file_exists($msglogpath . "/" . $message['id'])) {
                    $msglogfile = $msglogpath . "/" . $message['id'];
                } elseif (file_exists($msglogpath . "/" . substr($message['id'], 5, 1) . "/" . $message['id'])) {
                    $msglogfile = $msglogpath . "/" . substr($message['id'], 5, 1) . "/" . $message['id'];
                }
                if ($msglogfile != '') {
                    $logcmd = "/usr/bin/tail -n2 $msglogfile";
                    $log = `$logcmd`;

                    if ($log != '') {
                        $msglog = preg_split('/[\n\r]+/', $log, -1, PREG_SPLIT_NO_EMPTY);
                    }
                }
                $log_lines = [];
                $message['log'] = [];
                foreach ($msglog as $line) {
                    array_push($message['log'], utf8_encode($line));
                }

                foreach ($message as $key => $value) {
                    if (is_string($value)) {
                        $message[$key] = utf8_encode($value);
                    }
                }

                array_push($ret['msgs'], $message);
            }
        }
        return $ret;
    }


    /**
     * This function will delete messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_spoolDelete($params)
    {

        $ret = ['message' => 'OK', 'msgsdeleted' => [], 'errors' => []];

        $msgs = [];
        if (is_array($params['msg'])) {
            $msgs = $params['msg'];
        } else {
            $msgs = [$params['msg']];
        }

        $available_spools = [1, 2, 4];
        $spool = 1;
        if (isset($params['spool']) && in_array($params['spool'], $available_spools)) {
            $spool = $params['spool'];
        }

        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        require_once('Zend/Validate/Abstract.php');
        require_once('Validate/MessageID.php');
        $msgvalidator = new Validate_MessageID();
        foreach ($msgs as $msg) {
            if ($msgvalidator->isValid($msg)) {
                $cmd = "/opt/exim4/bin/exim -C " . $config->getOption('SRCDIR') . "/etc/exim/exim_stage" . $spool . ".conf -Mrm " . $msg . " &";
                $cmd_res = `$cmd`;
                $ret['msgsdeleted'][] = $msg;
                $ret['lastcmd'] = $cmd;
            }
        }
        return $ret;
    }

    /**
     * This function will try to send messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_spoolTry($params)
    {

        $ret = ['message' => 'OK', 'msgstried' => [], 'errors' => []];

        $msgs = [];
        if (is_array($params['msg'])) {
            $msgs = $params['msg'];
        } else {
            $msgs = [$params['msg']];
        }

        $available_spools = [1, 2, 4];
        $spool = 1;
        if (isset($params['spool']) && in_array($params['spool'], $available_spools)) {
            $spool = $params['spool'];
        }

        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        require_once('Zend/Validate/Abstract.php');
        require_once('Validate/MessageID.php');
        $msgvalidator = new Validate_MessageID();
        foreach ($msgs as $msg) {
            if ($msgvalidator->isValid($msg)) {
                $cmd = "/opt/exim4/bin/exim -C " . $config->getOption('SRCDIR') . "/etc/exim/exim_stage" . $spool . ".conf -M " . $msg . " &";
                $cmd_res = `$cmd`;
                $ret['msgstried'][] = $msg;
                $ret['lastcmd'] = $cmd;
            }
        }
        return $ret;
    }

    /**
     * This function will return all informational messages of the host
     *
     * @param  array $params
     * @return array
     */
    static public function Status_getInformationalMessages($params)
    {
        require_once('InformationalMessage.php');

        $messages = [];

        ## we need to check here:
        # - registration
        # - services restart

        require_once('InformationalMessage/Registration.php');
        $reg = new Default_Model_InformationalMessage_Registration();
        $reg->check();
        if ($reg->shouldShow()) {
            $messages[] = base64_encode(serialize($reg));
        }
        require_once('InformationalMessage/ServiceRestart.php');
        $ser = new Default_Model_InformationalMessage_ServiceRestart();
        $ser->check();
        if ($ser->shouldShow()) {
            $messages[] = base64_encode(serialize($ser));
        }
        require_once('InformationalMessage/PasswordReset.php');
        $ser = new Default_Model_InformationalMessage_PasswordReset();
        $ser->check();
        if ($ser->shouldShow()) {
            $messages[] = base64_encode(serialize($ser));
        }
        return $messages;
    }
}
