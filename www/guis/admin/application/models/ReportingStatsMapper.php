<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Reporting statistics mapper
 */

class Default_Model_ReportingStatsMapper
{

    public function find($id, $spam)
    {
    }

    public function startFetchAll($params)
    {
        $trace_id = 0;
        $slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();

        foreach ($slaves as $s) {
            $res = $s->sendSoapRequest('Logs_StartGetStat', $params);
            if (isset($res['search_id'])) {
                $search_id = $res['search_id'];
                $params['search_id'] = $search_id;
            } else {
                return [
                    'error' => "could not start search on host: " . $s->getHostname()
                ];
            }
        }
        return $search_id;
    }

    public function getStatusFetchAll($params)
    {
        $res = [
            'finished' => 0, 'count' => 0, 'data' => []
        ];
        $slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();

        $params['noresults'] = 1;
        $stillrunning = count($slaves);
        $globalrows = 0;
        foreach ($slaves as $s) {
            $sres = $s->sendSoapRequest('Logs_GetStatsResult', $params);
            if (isset($sres['error']) && $sres['error'] != "") {
                return $sres;
            }
            if (isset($sres['message']) && $sres['message'] == 'finished') {
                $stillrunning--;
            }
            if (isset($sres['nbrows'])) {
                $globalrows += $sres['nbrows'];
            }
        }
        $res['count'] = $globalrows;
        if (!$stillrunning) {
            $res['finished'] = 1;
        }
        return $res;
    }

    public function abortFetchAll($params)
    {
        $slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();

        foreach ($slaves as $s) {
            $res = $s->sendSoapRequest('Logs_AbortStats', $params);
        }
        return $res;
    }

    public function fetchAll($params)
    {
        $slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();

        $entriesflat = [];
        $sortarray = [];

        $params['noresults'] = 0;
        $stillrunning = count($slaves);
        $globalrows = 0;
        $whats = [];
        foreach ($slaves as $s) {
            $sres = $s->sendSoapRequest('Logs_GetStatsResult', $params);
            if (isset($sres['error']) && $sres['error'] != "") {
                return $sres;
            }
            if (isset($sres['message']) && $sres['message'] == 'finished') {
                $stillrunning--;
            }

            foreach ($sres['data'] as $line) {
                if (preg_match('/^([^:]+):([^:]+):(\S+)/', $line, $matches)) {
                    if (!isset($whats[$matches[1]])) {
                        $whats[$matches[1]] = new Default_Model_ReportingStats();
                        $whats[$matches[1]]->setWhat($matches[1]);
                        $whats[$matches[1]]->setFromDate(sprintf('%04d%02d%02d', $params['fy'], $params['fm'], $params['fd']));
                        $whats[$matches[1]]->setToDate(sprintf('%04d%02d%02d', $params['ty'], $params['tm'], $params['td']));
                    }
                    $whats[$matches[1]]->addFromLine($matches[3], $matches[2]);
                } else if (preg_match('/^([^:]+):(\S+)/', $line, $matches)) {
                    if (!isset($whats[$matches[1]])) {
                        $whats[$matches[1]] = new Default_Model_ReportingStats();
                        $whats[$matches[1]]->setWhat($matches[1]);
                    }
                    $whats[$matches[1]]->addFromLine($matches[2]);
                    $whats[$matches[1]]->setFromDate(sprintf('%04d%02d%02d', $params['fy'], $params['fm'], $params['fd']));
                    $whats[$matches[1]]->setToDate(sprintf('%04d%02d%02d', $params['ty'], $params['tm'], $params['td']));
                }
            }
        }
        $entries = [];
        $global;
        foreach ($whats as $w) {
            if ($w->getValue('msgs') > 0 && !preg_match('/^_global/', $w->getWhat())) {
                $entries[] = $w;
            }
            if (preg_match('/^_global/', $w->getWhat())) {
                $global = $w;
            }
        }

        $global_users = 0;
        foreach ($entries as $e) {
            if ($e->getWhat() != '_global') {
                $global_users += $e->getValue('users');
            }
        }
        if ($params['what'] == '*') {
            $global->setValue('users', $global_users);
        }
        if (preg_match('/\*\@\S+$/', $params['what']) && is_object($global)) {
            $global->setValue('users', count($entries));
        }

        if (isset($params['sort'])) {
            switch ($params['sort']) {
                case 'msgs':
                    usort($entries, ['Default_Model_ReportingStats', 'compareMsgs']);
                    break;
                case 'spams':
                    usort($entries, ['Default_Model_ReportingStats', 'compareSpams']);
                    break;
                case 'viruses':
                    usort($entries, ['Default_Model_ReportingStats', 'compareViruses']);
                    break;
                case 'spamspercent':
                    usort($entries, ['Default_Model_ReportingStats', 'compareSpamsPercent']);
                    break;
                case 'users':
                    usort($entries, ['Default_Model_ReportingStats', 'compareUsers']);
                    break;
                default:
                    usort($entries, ['Default_Model_ReportingStats', 'compareWhat']);
            }
        } else {
            usort($entries, ['Default_Model_ReportingStats', 'compareWhat']);
        }
        if (isset($params['top']) && $params['top']) {
            $entries = array_slice($entries, 0, $params['top']);
        }
        if (isset($global)) {
            array_unshift($entries, $global);
        }
        #$whats = array_reverse($whats);
        return $entries;
    }
}
