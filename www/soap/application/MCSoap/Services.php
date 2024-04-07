<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class MCSoap_Services
{
    public static $STATUS_LOG = array('syslog' => '/tmp/local_service_syslog.log');
   
  /**
   * This function restart syslog services
   *
   * @return string
   */
	static public function Services_restartSyslog() {
		
		require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        
		$s = 'syslog';
		
		## restart syslog (or rsyslog)
		$starter = '/etc/init.d/syslogd';
		if (file_exists('/etc/init.d/rsyslog')) {
			$starter = '/etc/init.d/rsyslog';
		}
	    $cmd = $starter.' restart >> '.MCSoap_Services::$STATUS_LOG[$s];
		$res = `$cmd`;
		$res = preg_replace('/\n/', '', $res);
		if (preg_match('/syslogd.$/', $res, $matches)) {
		     $status = $matches[1];
		}
		$restart_file = $config->getOption('VARDIR')."/run/syslog.rn";
		if (file_exists($restart_file)) {
			unlink($restart_file);
		}
		$cmd = "echo 'DONE TASK syslog' >> ".MCSoap_Services::$STATUS_LOG[$s];
		`$cmd`;
		return 'OK service restarted';
   }
   
   /**
   * This function restart MTA (Exim) services
   *
   * @param  array  stages
   * @param  string command (stop|start|restart)
   * @return string
   */
	static public function Services_stopstartMTA($stages, $command, $outfile = NULL) {
		require_once('MailCleaner/Config.php');
		$config = new MailCleaner_Config();
		
		if (empty($stages)) {
			$stages = array(1,2,4);
		}
		$status = 'unknown status';
		$outcmd = '';
		if ($outfile) {
			$cmd = "echo 'START TASK exim ' >> ".escapeshellcmd($outfile);
		    `$cmd`;
			$outcmd = ' >> '.$outfile;
		}
		foreach ($stages as $stage) {
			if (preg_match('/^[124]$/', $stage)) {
				if (preg_match('/(start|stop|restart)/', $command)) {
					$cmd = $config->getOption('SRCDIR').'/etc/init.d/exim_stage'.$stage." ".$command.$outcmd;
					$res = `$cmd`;
					$res = preg_replace('/\n/', '', $res);
					if (preg_match('/(started|stopped).$/', $res, $matches)) {
						$status = $matches[1];
					}
				}
			}
		}
		if ($outfile) {
			$cmd = "echo 'DONE TASK exim' >> ".escapeshellcmd($outfile);
			`$cmd`;
		}
		return 'OK service(s) '.$status;
	}
   
  /**
   * This function get the actual service stop/start log
   *
   * @param  string service
   * @return string
   */
	static public function Services_getStarterLog($service) {
		$logfile = MCSoap_Services::$STATUS_LOG[$service];
		
		$res = "";
		$logexprs = array();
		require_once('MailCleaner/Config.php');
		$config = new MailCleaner_Config();
		$service = preg_replace('/\//', '', $service);
		$filepath = $config->getOption('SRCDIR').'/www/soap/application/MCSoap/commands/'.urlencode($service).".php";
		if (file_exists($filepath)) {
			include_once($filepath);
		}
		if (file_exists($logfile)) {
			$lines = file($logfile);
			foreach ($lines as $line) {
	          foreach ($logexprs as $key => $regexp) {
	          	if (preg_match("/".$regexp."/", $line)) {
	          		$key .= "<br />";
	          		$res .= preg_replace("/".$regexp.".*/",$key, $line);
	          	} else {
	          		#$res .= $line;
	          	}
	          }
			}
		}
	    $res = preg_replace('/\.\.\.\s*<br \/>/', '... ', $res);
		return $res;
	}
	
   /*
    * This function will set one process's status to be restarted
    * 
    * @param  array  services
    * @return string
    */
	static public function Service_setServiceToRestart($services) {
		require_once('MailCleaner/Config.php');
		$config = new MailCleaner_Config();
		
		foreach ($services as $service) {
          $restart_file = $config->getOption('VARDIR')."/run/".$service.".rn";
          if (!file_exists($restart_file)) {
             if ( ! touch($restart_file) ) {
    	  	   return 'NOK service to restart, $service';
    	     }
          }
        }
		return 'OK services to restart';
	}
	
	/*
	 * This function will silently stop/start/restart a service
	 * 
	 * @param array params
	 * @return array
	 */
	static public function Service_silentStopStart($params) {
        $ret = array('status' => '', 'message' => '');		

        $service = '';
        $action = 'start';
        if (isset($params['service'])) {
        	$service = $params['service'];
        }
    	if (isset($params['action'])) {
            $action = $params['action'];
        }
        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        $service = preg_replace('/[^a-zA-Z0-9_]/', '', $service);
        $starter = $config->getOption('SRCDIR')."/etc/init.d/".$service;
        if (!file_exists($starter)) {
        	$ret['error'] = 'no such process starter';
        	$ret['message'] = $starter;
        	return $ret;
        }
        $cmd = $starter." start";
        if (isset($action) && $action == 'stop') {
          $cmd = $starter." stop";
        } else if ($action == 'restart') {
        	if ($service == 'apache') {
        		$cmd = $starter." graceful";
        	} else {
             	$cmd = $starter." restart";
        	}
        }
        $cmd .= " >/dev/null 2>&1 &";
        $res = `$cmd`;
        $ret['status'] = 'done';
        $ret['message'] = $res;
        $ret['cmd'] = $cmd;
        $ret['params'] = $params;
        return $ret;
	}
	
	/*
     * This function will silently dump a config file
     * 
     * @param array params
     * @return array
     */
    static public function Service_silentDump($params) {
        $ret = array('status' => '', 'message' => '');      
    
        $cmd = "";
    	require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        if ($params['what'] == 'domains') {       
        if (isset($params['domain']) && $params['domain'] != "") {
                $cmd = $config->getOption('SRCDIR')."/bin/dump_domains.pl ".escapeshellcmd($params['domain']);
            } else {
        	    $cmd = $config->getOption('SRCDIR')."/bin/dump_domains.pl";
            }
        } elseif ($params['what'] == 'archiving') {
        	$cmd = $config->getOption('SRCDIR')."/bin/dump_archiving.pl";
        }
         
        if ($cmd != '') {
        	$cmd .= " >/dev/null 2>&1 &";
            $res = `$cmd`;
            $ret['status'] = 'done';
            $ret['message'] = $res;
            $ret['cmd'] = $cmd;
            $ret['params'] = $params;
        }
        return $ret;
    }
    
    /*
    * This function will clear the callout cache
    *
    * @param array params
    * @return array
    */
    static public function Service_clearCalloutCache($params) {	
    	$ret = array('status' => 'OK', 'message' => 'Cache cleared', 'debug' => '');
    	$cmd = "/bin/rm ";
    	
    	require_once('MailCleaner/Config.php');
    	$config = new MailCleaner_Config();
    	$dir = $config->getOption('VARDIR')."/spool/exim_stage1/db";
    	$files = array($dir.'/callout', $dir.'/callout.lockfile');
    	
    	foreach ($files as $file) {
         	if (file_exists($file) && is_file($file)) {
            	$cmd = "/bin/rm $file";
            	$res = `$cmd 2>&1`;
            	if ($res != '') {
            	   $ret['status'] = 'NOK';
            	   $ret['message'] .= "Command: \'$cmd\' failed with error: \'$res\'";
    	        } else {
    	        	$ret['debug'] .= "File $file removed. ";
    	        }
         	} else {
         		$ret['debug'] .= "File $file does not exist. ";
         	}
    	}
    	return $ret;
    }

    static public function Service_clearSMTPAutCache($params) {
        $ret = array('status' => 'OK', 'message' => 'Cache cleared', 'debug' => '');
        $cmd = "/bin/rm ";

        require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
        if (!isset($params['domain'])) {
            $ret['status'] = 'NOK';
            $ret['message'] = 'No domain name provided';
            $ret['domain'] = $params['domain'];
            return $ret;
        }
        $file = $config->getOption('VARDIR')."/spool/tmp/exim_stage1/auth_cache/".escapeshellcmd($params['domain']).'.db';
        $ret['file'] = $file;
        if (!file_exists($file)) {
            $ret['message'] = 'No cache present';
            return $ret;
        }
        $cmd = "/bin/rm $file";
        $res = `$cmd 2>&1`;
        if ($res != '') {
            $ret['status'] = 'NOK';
            $ret['message'] .= "Command: \'$cmd\' failed with error: \'$res\'";
        } else {
            $ret['debug'] .= "File $file removed. ";
        }
        return $ret;
    }
}
