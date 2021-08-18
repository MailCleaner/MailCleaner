<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class MCSoap_Logs
{

	/**
	 * This function will start a messages tracing
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_StartTrace($params) {
		// escape params args
		array_walk($params, function(&$arg_value, $key) {
			if ($key == 'filter' || $key == 'trace_id' || $key == 'regexp') 
				$arg_value = escapeshellarg($arg_value);
		});

		$trace_id = 0;

		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();

		if (!isset($params['regexp'])
		|| !$params['datefrom'] || !preg_match('/^\d{8}$/', $params['datefrom'])
		|| !$params['dateto'] || !preg_match('/^\d{8}$/', $params['dateto']) ) {
			return array('trace_id' => $trace_id);
		}
		$cmd = $mcconfig->getOption('SRCDIR')."/bin/search_log.pl ".$params['datefrom']." ".$params['dateto']." ".$params['regexp'];
        if (isset($params['filter']) && $params['filter'] != '' && $params['filter'] != "''") {
            $params['filter'] = preg_replace("/^'(.*)'$/", "$1", $params['filter']);
            $params['filter'] = preg_replace("/'/", "\\'", $params['filter']);
            $params['filter'] = preg_replace("/\s+/", "' '", $params['filter']);
            $cmd .= " '" . $params['filter'] . "'";
        }

                if (isset($params['hiderejected']) && $params['hiderejected']) {
                    $cmd .= ' -R ';
                }

		if (isset($params['trace_id']) && $params['trace_id']) {
			$trace_id_matches = array();
			preg_match('/[a-f0-9]{32}/i', $params['trace_id'], $trace_id_matches);
			$trace_id = $trace_id_matches[0];
		} else {
			$trace_id = md5(uniqid(mt_rand(), true));
		}
                $cmd .= " -B ".$trace_id;

		$cmd .= "> ".$mcconfig->getOption('VARDIR')."/run/mailcleaner/log_search/".$trace_id." &";
		$res = `$cmd`;
		return array('trace_id' => $trace_id, 'cmd' => $cmd) ;
	}

	/**
	 * This function will fetch tracing results
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_GetTraceResult($params, $limit = 0) {
		$res = array();
		if (!$params['trace_id']) {
			return array('error' => 'no such results');
		}
		$trace_id = $params['trace_id'];

		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();

		$file = $mcconfig->getOption('VARDIR')."/run/mailcleaner/log_search/".$trace_id;
		if (!file_exists($file)) {
			return array('error' => 'no such results');
		}

		$lines = file($file);
                #foreach ($lines_tmp as $line) {
                #  array_push($lines, utf8_encode($line));
                #}
		$res = array('message' => 'search running');
		$pid = 0;
		$done = 0;
		$res['trace_id'] = $trace_id;
		foreach ($lines as $line) {
			if (preg_match('/^PID (\d+)/', $line, $matches)) {
				$pid = $matches[1];
				$res['pid'] = $pid;
			}
			if (preg_match('/^done/', $line)) {
				$done = 1;
				$res['message'] = 'finished';
			}
      // accept 'occurrence' and 'occurence'
			if (preg_match('/^Found (\d+) occur/', $line, $matches)) {
				$res['nbrows'] = $matches[1];
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
						$res['data'] = $lines;
					}
					return $res;
				}
			}
		}
                $res['data'] = array();
                $tmpdata = array();
		if ($limit && count($lines) >= $limit) {
			$res['error'] = 'too many results';
			if (!isset($params['noresults']) || !$params['noresults']) {
				$tmpdata = array_slice($lines, 0, $limit);
			}
		} else {
			if (!isset($params['noresults']) || !$params['noresults']) {
				$tmpdata = $lines;
			}
		}
                foreach ($tmpdata as $l) {
                    array_push($res['data'], utf8_encode($l));
                }
		return $res;
	}
	/**
	 * This function will stop a messages tracing
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_AbortTrace($params) {
		$res = array();
		if (!$params['trace_id']) {
			return array('message' => 'nothing to abort');
		}
		$trace_id = $params['trace_id'];
		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();

		$file = $mcconfig->getOption('VARDIR')."/run/mailcleaner/log_search/".$trace_id;
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

	static public function extFromId($id) {
		if ($id < 0) {
			return '';
		}
		if ($id == 0) {
			return '.0';
		}
		return '.'.$id.'.gz';
	}

	static public function getDateFromFile($file, $mode = 'asc') {
		if (!file_exists($file)) {
			if ($mode == 'desc') {
				return '99999999';
			}
			return '00000000';
		}
		if (preg_match('/\.gz$/', $file)) {
			$line = `zcat $file 2>&1 | head -2`;
		} else {
			$line = `head -2 $file`;
		}
		$months = array('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 'Jul' => '07',
                'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');

		## Exim
		if (preg_match('/^(\d\d\d\d)\-(\d\d)\-(\d\d)/', $line, $matches)) {
			return $matches[1].$matches[2].$matches[3];
		}

		## MailScanner
		if (preg_match('/^(\w{3})\s+(\d+)/', $line, $matches)) {
			$fm = $matches[1];
			$fd = $matches[2];
			$y = '0000'; $m = '00'; $d='00';
			$today = `date +%Y%m%d`;
			if (preg_match('/(\d\d\d\d)(\d\d)(\d\d)/', $today, $matches)) {
				$y = $matches[1];
				$m = $matches[2];
				$d = $matches[3];
			}
			if (isset($months[$fm])) {
				$m = $months[$fm];
			}
			$d = sprintf("%02d", $fd);
            if ("$y$m$d" > $today) {
            	$y = $y - 1;
            }
			return "$y$m$d";
		}

		## Spamd
		if (preg_match('/\w{3}\s+(\w{3})\s+(\d+)\s+\d\d:\d\d:\d\d\s+(\d+)/', $line, $matches)) {
			$fd = $matches[2];
			$fm = $months[$matches[1]];
			$fy = $matches[3];
			return sprintf("%04d%02d%02d", $fy, $fm, $fd);
		}
		## Apache
		if (preg_match('/\[(\d\d)\/(\w{3})\/(\d{4})/', $line, $matches)) {
			$fd = $matches[1];
			$fm = $months[$matches[2]];
			$fy = $matches[3];
			return sprintf("%04d%02d%02d", $fy, $fm, $fd);
		}
		## Mysql
		if (preg_match('/^(\d\d)(\d\d)(\d\d)/', $line, $matches)) {
			$fd = $matches[3];
			$fm = $matches[2];
			$fy = $matches[1]+2000;
			return sprintf("%04d%02d%02d", $fy, $fm, $fd);
		}
		## freshclam
		if (preg_match('/^\[(\d\d\d\d)-(\d\d)-(\d\d) /', $line, $matches)) {
			$fd = $matches[3];
                        $fm = $matches[2];
                        $fy = $matches[1];
                        return sprintf("%04d%02d%02d", $fy, $fm, $fd);
                }

		if ($mode == 'desc') {
			return '99999999';
		}
		return 'NODATE';
	}

	static public function Logs_FindLogFile($params) {

		if (!isset($params['basefile']) || !$params['date'] || !preg_match('/^\d{8}$/', $params['date'], $matches) ) {
			return null;
		}
		// Define path to application directory
		defined('APPLICATION_PATH')
		|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

		// Define application environment
		defined('APPLICATION_ENV')
		|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

		// Ensure library/ is on include_path
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../application/'),
		realpath(APPLICATION_PATH . '/../../guis/admin/application/models'),
		realpath(APPLICATION_PATH . '/../../guis/admin/application/library'),
		get_include_path(),
		)));

		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', 'on');

		## first offset approximation
		require_once('Zend/Date.php');
		$dateO = new Zend_Date($params['date'], 'yyyyMMdd');
		$date = $params['date'];
		$today = new Zend_Date();

		$diff = $today->get(Zend_Date::TIMESTAMP) - $dateO->get(Zend_Date::TIMESTAMP);
		$days = floor(($diff / 86400));

		$estimate_id = $days - 1;

		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();

		$params['basefile'] = $mcconfig->getOption('VARDIR')."/log/".$params['basefile'];

		$filename = $params['basefile'].MCSoap_Logs::extFromId($estimate_id);
		$filedate = MCSoap_Logs::getDateFromFile($filename);
		$filefound = '';
		if ($filedate == $date) {
			$filefound = $filename;
		}
		while ($filedate < $date && $filedate != 'NODATE' && $estimate_id > -1) {
			$filename = $params['basefile'].MCSoap_Logs::extFromId(--$estimate_id);
			$filedate = MCSoap_Logs::getDateFromFile($filename, 'desc');
			if ($filedate == $date) {
				$filefound = $filename;
			}
		}

		while ($filedate > $date && $filedate != 'NODATE') {
			$filename = $params['basefile'].MCSoap_Logs::extFromId(++$estimate_id);
			$filedate = MCSoap_Logs::getDateFromFile($filename, 'asc');
			if ($filedate == $date) {
				$filefound = $filename;
			}
		}

		if ($filefound != '') {
			return $filefound;
		}
		return null;
	}

	/**
	 * This function will list logs files
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_FindLogFiles($params) {
		$res = array('files' => array());
		if (!isset($params['files']) || !isset($params['date'])) {
			$res['error'] = 'Missing parameters';
			return $res;
		}
		foreach ($params['files'] as $f) {
			$filename = MCSoap_Logs::Logs_FindLogFile(array('basefile' => $f, 'date' => $params['date']));
			$file = array('file' => '', 'size' => 0);
			if ($filename) {
				$file['file'] = $filename;
				$file['size'] = filesize($filename);
			}
			$file['basefile'] = $f;
			$res['files'][$f] = $file;
		}
		return $res;
	}

	/**
	 * This function will fetch log lines
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_GetLogLines($params) {

		$res['cmds'] = array();
		if (!isset($params['file'])) {
			$res['error'] = 'Missing parameters';
			return $res;
		}
		$pos = 0;
		$posline = 0;
		$res['sres'] = array();

		// Define path to application directory
		defined('APPLICATION_PATH')
		|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

		// Define application environment
		defined('APPLICATION_ENV')
		|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

		// Ensure library/ is on include_path
		set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../application/'),
		realpath(APPLICATION_PATH . '/../../guis/admin/application/models'),
		realpath(APPLICATION_PATH . '/../../guis/admin/application/library'),
		get_include_path(),
		)));

		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', 'on');

		require_once('MailCleaner/Config.php');
		$mcconfig = MailCleaner_Config::getInstance();

		$params['file'] = preg_replace('/-/', '/', $params['file']);
		$file = $mcconfig->getOption('VARDIR')."/log/".$params['file'];
		if (!file_exists($file)) {
			$res['error'] = 'No such file ('.$file.')';
			return $res;
		}


		if (!is_numeric($params['fromline'])) {
			$params['fromline'] = 1;
		}
		if ($params['fromline'] < 1) {
			$params['fromline'] = 1;
		}
        $res['params'] = $params;

		$fromline = 1;
		$toline = 1 + $params['maxlines'];
		$fullnblines = 0;

		// first get logs number of lines
        if (preg_match('/.gz$/', $file)) {
            $wccmd = "/bin/zcat $file 2>&1 | /usr/bin/wc -l";
        } else {
            $wccmd = "/usr/bin/wc -l $file";
        }
        $fullnblines_str = `$wccmd`;
        array_push($res['cmds'], $wccmd);
	    if (preg_match('/^(\d+)/', $fullnblines_str, $matches)) {
	    	$fullnblines = $matches[1];
        }
        $res['nblines'] = $fullnblines;

        // handle the search
        $res['search_results'] = 0;
        if ($params['search']) {
            $search = preg_replace('/\'/', '', $params['search']);
            $search = preg_replace('/\./', '\.', $search);
            $cmd = "/bin/zgrep -n '".$search."' $file";
            $greplines = `$cmd 2>&1`;
            $greplines = preg_replace('/\n$/', '', $greplines);
            array_push($res['cmds'], $cmd);
            if ($greplines) {
                $wclines = preg_split("/[\n]+/", $greplines);
                foreach ($wclines as $line) {
                    $res['sres'][] = utf8_encode($line);
                }
                $res['search_results'] = count($wclines);
            }
        }
        // according to parameters, find out wich lines to retrieve
		switch ($params['last_element']) {
			case 'linefrom':
				$fromline = $params['fromline'];
				$toline = $fromline + $params['maxlines'];
				break;
			case 'linepercent':
				$fromline = floor(($fullnblines/100) * $params['percent']);
				$toline = $fromline + $params['maxlines'];
				break;
			case 'search':
		        foreach ($res['sres'] as $line) {
                    if (preg_match('/(\d+):/', $line, $matches)) {
                        if ($matches[1] >= $params['fromline'] ) {
                        	$fromline = $matches[1];
                        	$toline = $fromline + $params['maxlines'];
                            break;
                        }
                    }
                }
       		    break;
			case 'match':
				$i = 1;
				$pos = $params['position'];
				foreach ($res['sres'] as $line) {
                    if (preg_match('/(\d+):/', $line, $matches)) {
                        if ($i++ == $params['position'] ) {
                        	$fromline = $matches[1];
                            $toline = $fromline + $params['maxlines'];
                            $posline = $matches[1];
                            break;
                        }
                    }
				}
				break;
		}
		if ($fromline < 1) {
			$fromline = 1;
		}
		if ($fromline >= ($fullnblines - floor($params['maxlines'] / 2))) {
			$fromline = $fullnblines - floor($params['maxlines'] / 2);
		}
		if ($toline > $fullnblines) {
			$toline = $fullnblines;
		}
        if ($fromline < 1) {
            $fromline = 1;
        }

		$percent = round((100 / ($fullnblines)) * ($fromline));
		if ($fromline == 1) {
			$percent = 0;
		}
		if ($toline >= $fullnblines) {
			$percent = 100;
		}
        $res['percent'] = $percent;
		// now get the right lines
        $awktoline = $toline+1;
        $cmd = "/usr/bin/awk 'NR >= $fromline && NR < $awktoline'";
        if (preg_match('/.gz$/', $file)) {
            $cmd = "/bin/zcat $file 2>&1 | ".$cmd;
        } else {
            $cmd .= " $file";
        }
        $cmdres = `$cmd`;
        array_push($res['cmds'], $cmd);
        $lines = preg_split("/\n/", $cmdres);
        // remove one result line for scrollbar if lines are too long
        foreach ($lines as $line) {
        	if (strlen($line) > $params['maxchars'] && (count($lines)-1 >= $params['maxlines']) ) {
        		array_pop($lines);
                array_pop($lines);
        		--$toline;
        		break;
        	}
        }
        $res['fromline'] = $fromline;
        $res['toline'] = $toline;

        // recalculate position
        if (!$pos) {
            $pos = 1;
            foreach ($res['sres'] as $line) {
                if (preg_match('/(\d+):/', $line, $matches)) {
                    if ($matches[1] >= $res['fromline']) {
                    	$posline = $matches[1];
                        break;
                    }
                }
                $pos++;
            }
        }
        $res['position'] = $pos;
        $res['posline'] = $posline;

        require_once('Logfile.php');
        $logfile = new Default_Model_Logfile();
        $logfile->loadByFileName($params['file']);

		// and do some values formatting
        $msgid = '';
		if (isset($params['search'])) {
			$i = $fromline;
            foreach ($lines as $line) {
                $count = 0;
            	if ($i == $posline) {
            		 $bline = str_replace($params['search'], '___SFB___'.$params['search'].'___EFB___', $line, $count);
            		 $nextregexp = $logfile->getNextIdRegex();
            		 if ($nextregexp != '') {
                		 if (preg_match('/'.$nextregexp.'/', $line, $matches)) {
                    		 	$msgid = $matches[1];
                		 }
             		 }
            	} else {
                     $bline = str_replace($params['search'], '___SB___'.$params['search'].'___EB___', $line, $count);
            	}
                if ($count) {
                     $bline = '___SML___'.$bline.'___EML___';
                }

                $blines[] = utf8_encode($bline);
                $i++;
            }
        } else {
            $blines = $lines;
        }
        $res['lines'] = $blines;
        $res['msgid'] = $msgid;

		return $res;
	}

        /**
         * This function will fetch a log extract for a specific message from a previous trace
         *
         * @param  array $params
         * @return array
         */
        static public function Logs_ExtractLog($params) {
           $res = array('full_log' => '');
           require_once('MailCleaner/Config.php');
           $mcconfig = MailCleaner_Config::getInstance();
           $logfile = $mcconfig->getOption('VARDIR').'/run/mailcleaner/log_search/';

           if (isset($params['traceid']) && preg_match('/^[a-zA-Z0-9]+$/', $params['traceid'])) {
               $logfile .= $params['traceid'].'.full';
           }
           $res['log_stage1'] = '';
           $res['log_stage2'] = '';
           $res['log_engine'] = '';
           $res['log_stage4'] = '';
           $res['log_spamhandler'] = '';
           $res['log_finalstage4'] = '';
           $res['datetime'] = '';
           $inmsg = 0;
           $level = 0;
           $levels = array();
           $exit = 0;

           if (file_exists($logfile)) {
               $handle = @fopen($logfile, "r");
               if ($handle) {
                   while (($buffer = fgets($handle, 4096)) !== false) {
                       if ($inmsg && preg_match('/^\*\*\*/', $buffer)) {
                           $exit = 1;
                           continue;
                       }
                       if ($inmsg && preg_match('/^\s*$/', $buffer)) {
                           $level++;
                           continue;
                       }
                       if (preg_match('/^'.$params['msgid'].'\|(.*)/', $buffer, $matches)) {
                           $inmsg = 1;
                           $res['full_log'] .= $matches[1]."\n";
                           if (!is_array($levels[$level])) {
                               $levels[$level] = array();
                           }
                           array_push($levels[$level], $matches[1]);
                           if ($res['datetime'] == '' && preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/', $matches[1], $smatches)) {
                               array_shift($smatches);
                               $res['datetime'] = implode($smatches);
                           }
                       }
                   }
                   if (!feof($handle)) {
                       break;
                   }
                   fclose($handle);
               }
           }
           if (is_array($levels[0])) { $res['log_stage1'] = $levels[0]; }
           if (is_array($levels[1])) { $res['log_stage2'] = $levels[1]; }
           if (is_array($levels[2])) { $res['log_engine'] = $levels[2]; }
           if (is_array($levels[3])) { $res['log_stage4'] = $levels[3]; }
           if (is_array($levels[4])) { $res['log_spamhandler'] = $levels[4]; }
           if (is_array($levels[5])) { $res['log_finalstage4'] = $levels[5]; }

           return $res;
        }
}
