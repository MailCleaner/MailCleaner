<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class MCSoap_Stats 
{

 /**
   * This function will start a stats gathering
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_StartGetStat($params) {
		
		$stats_id = 0;
		
		require_once('MailCleaner/Config.php');
    	$mcconfig = MailCleaner_Config::getInstance();
    	
    	if (!isset($params['what'])
    	        || !$params['datefrom'] || !preg_match('/^\d{8}$/', $params['datefrom'])
    	        || !$params['dateto'] || !preg_match('/^\d{8}$/', $params['dateto']) ) {
    		return array('stats_id' => $stats_id);
    	}
    	$cmd = $mcconfig->getOption('SRCDIR')."/bin/get_stats.pl '".$params['what']."' ".$params['datefrom']." ".$params['dateto'];
    	$cmd .= " -b";
    	if (isset($params['fulldays']) && $params['fulldays']) {
    		$cmd .= " -f";
    	}
    	
    	if (isset($params['search_id']) && $params['search_id']) {
    		$stats_id = $params['search_id'];
    	} else {
            $stats_id = md5(uniqid(mt_rand(), true));
    	}
    	
    	$cmd .= "> ".$mcconfig->getOption('VARDIR')."/run/mailcleaner/stats_search/".$stats_id." &";
        $res = `$cmd`;
    	return array('search_id' => $stats_id, 'cmd' => $cmd) ;
	}
	
  /**
   * This function will fetch stats results
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_GetStatsResult($params, $limit = 0) {
		$res = array();
                $data_res = array();
		if (!$params['search_id']) {
			return array('error' => 'no such results');
		}
		$stats_id = $params['search_id'];
		
		require_once('MailCleaner/Config.php');
    	$mcconfig = MailCleaner_Config::getInstance();
    	
		$file = $mcconfig->getOption('VARDIR')."/run/mailcleaner/stats_search/".$stats_id;
		if (!file_exists($file)) {
			return array('error' => 'no such results');
		}
		
		$lines = file($file);
		$res = array('message' => 'search running');
		$pid = 0;
		$done = 0;
		$res['stats_id'] = $stats_id;
		foreach ($lines as $line) {
		    if (preg_match('/^PID (\d+)/', $line, $matches)) {
				$pid = $matches[1];
				$res['pid'] = $pid;
			}
			if (preg_match('/^done/', $line)) {
				$done = 1;
				$res['message'] = 'finished';
			}
		}
		if (!$done) {
			$cmd = "ls -ld /proc/".$pid." 2> /dev/null | wc -l";
			$cmdres = `$cmd`;
			if ($cmdres == '0') {
				$lines = file($file);
		        $done = 0;
		        foreach ($lines as $line) {
		            if (preg_match('/^done/', $line)) {
				        $done = 1;
			        }
		        }
		        if (!done) {
		        	$res['error'] = 'process killed before finishing';
		        	if (!isset($params['noresults']) || !$params['noresults']) {
                                   $data_res = $lines;
		        	}
		        	return $res;
		        }
			}
		}
		if ($limit && count($lines) >= $limit) {
			$res['error'] = 'too many results';
		    if (!isset($params['noresults']) || !$params['noresults']) {
                      $data_res = array_slice($lines, 0, $limit);
		    }
		} else {
			if (!isset($params['noresults']) || !$params['noresults']) {
                          $data_res = $lines;
			}
		}
               
                foreach ($data_res as $line) {
                   $res['data'][] = utf8_encode($line);
                } 
		return $res;
	}
  /**
   * This function will stop a stats tracing
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_AbortStats($params) {
		$res = array();
		if (!$params['search_id']) {
			return array('message' => 'nothing to abort');
		}
		$stats_id = $params['search_id'];
	    require_once('MailCleaner/Config.php');
    	$mcconfig = MailCleaner_Config::getInstance();
    	
		$file = $mcconfig->getOption('VARDIR')."/run/mailcleaner/stats_search/".$stats_id;
		if (!file_exists($file)) {
			return array('error' => 'no such results');
		}
		
		$lines = file($file);
		$pid = 0;
		foreach ($lines as $line) {
			if (preg_match('/^PID (\d+)/', $line, $matches)) {
				$pid = $matches[1];
			}
			if ($pid) {
				break;
			}
		}
		if ($pid) {
			$cmd = "/bin/kill ".$pid;
			$res = `$cmd >/dev/null 2>&1`;
			if (file_exists($file)) {
				@unlink($file);
			}
			return array('message' => 'killed');
		}
		return array('error' => 'no such process');
	}

}
