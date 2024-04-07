<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */


/**
 * defines the services available, with file dumpers and stopper/starter
 * @var array
 */
$services_ = array(
  'MTA1' => array('restartfile' => "exim_stage1.rn", 'starter' => "S_exim_stage1.sh", 'stopper' => "H_exim_stage1.sh", 'restarter' => "R_exim_stage1.sh"),
  'MTA2' => array('restartfile' => "exim_stage2.rn", 'starter' => "S_exim_stage2.sh", 'stopper' => "H_exim_stage2.sh", 'restarter' => "R_exim_stage2.sh"),
  'MTA4' => array('restartfile' => "exim_stage4.rn", 'starter' => "S_exim_stage4.sh", 'stopper' => "H_exim_stage4.sh", 'restarter' => "R_exim_stage4.sh"),
  'ENGINE' => array('restartfile' => "mailscanner.rn", 'starter' => "S_mailscanner.sh", 'stopper' => "H_mailscanner.sh", 'restarter' => "R_mailscanner.sh"),
  'HTTPD' => array('restartfile' => "apache.rn", 'starter' => "S_apache.sh", 'stopper' => "H_exim_apache.sh", 'restarter' => "R_apache.sh"),
  'MASTERDB' => array('restartfile' => "mysql_master.rn", 'starter' => "S_mysql_master.sh", 'stopper' => "H_mysql_master.sh", 'restarter' => "R_mysql_master.sh"),
  'SLAVEDB' => array('restartfile' => "mysql_slave.rn", 'starter' => "S_mysql_slave.sh", 'stopper' => "H_mysql_slave.sh", 'restarter' => "R_mysql_slave.sh"),
  'SNMPD' => array('restartfile' => "snmpd.rn", 'starter' => "S_snmpd.sh", 'stopper' => "H_snmpd.sh", 'restarter' => "R_snmpd.sh"),
  'GREYLISTD' => array('restartfile' => "greylistd.rn", 'starter' => "S_greylistd.sh", 'stopper' => "H_greylistd.sh", 'restarter' => "R_greylistd.sh"),  
  'CRON' => array('restartfile' => "cron.rn", 'starter' => "S_cron.sh", 'stopper' => "H_cron.sh", 'restarter' => "R_cron.sh"),
  'PREFDAEMON' => array('restartfile' => "prefdaemon.rn", 'starter' => "S_prefdaemon.sh", 'stopper' => "H_prefdaemon", 'restarter' => "R_prefdaemon.sh"),
  'SPAMD' => array('restartfile' => "spamd.rn", 'starter' => "S_spamd.sh", 'stopper' => "H_spamd", 'restarter' => "R_spamd.sh"),
  'CLAMD' => array('restartfile' => "clamd.rn", 'starter' => "S_clamd.sh", 'stopper' => "H_clamd", 'restarter' => "R_clamd.sh"),
  'SPAMCLAMD' => array('restartfile' => "clamspamd.rn", 'starter' => "S_clamspamd.sh", 'stopper' => "H_clamspamd", 'restarter' => "R_clamspamd.sh"),
  'SPAMHANDLER' => array('restartfile' => "spamhandler.rn", 'starter' => "S_spamhandler.sh", 'stopper' => "H_spamhandler", 'restarter' => "R_spamhandler.sh"),
  'FIREWALL' => array('restartfile' => "firewall.rn", 'starter' => "S_firewall.sh", 'stopper' => "H_firewall.sh" ,'restarter' => "R_firewall.sh"),
);
         
/**
 * return the status of each critical process (running/stopped)
 * @param   $sid    string   soap session id
 * @return          array    status, array with process as key and status as value
 */
function getProcessesStatus($sid) {
  $processes = array();

  $ret = getStatus($sid, "-s");
  if (!preg_match('/^(\|[012]){16}$/', $ret)) {
     return "ERRORFETCHINGPROCESSESSTATUS";
   }
   $processes = split('\|', $ret);
   $ret = new SoapProcesses();
   $ret->mtaincoming = $processes[1];
   $ret->mtafiltering = $processes[2];
   $ret->mtaoutgoing = $processes[3];
   $ret->httpd = $processes[4];
   $ret->engine = $processes[5];
   $ret->masterdb = $processes[6];
   $ret->slavedb = $processes[7];
   $ret->snmpd = $processes[8];
   $ret->greylistd = $processes[9];
   $ret->cron = $processes[10];
   $ret->prefdaemon = $processes[11];
   $ret->spamd = $processes[12];
   $ret->clamd = $processes[13];
   $ret->clamspamd = $processes[14];
   $ret->spamhandler = $processes[15];
   $ret->firewall = $processes[16];
   return $ret;
}
 
/**
 * stop a given service daemon
 * @param $sid     string   soap session id
 * @param $service string   service name
 * @return         string   OK result set on success, error result set on failure
 */ 
function stopService($sid, $service) {
  global $services_;
  
  $soap_ret = new SoapServiceStatus();
  if (!array_key_exists($service, $services_)) {
    $soap_ret->status = 'FAILED';
    $soap_ret->result = "SERVICENOTKNOWN $service";
    return $soap_ret;
  }
  $sysconf_ = SystemConfig::getInstance();

  $sudocmd = "/usr/sudo/bin/sudo";
  if (! file_exists($sudocmd) ) {
  	$sudocmd = "/usr/bin/sudo";
  }
  $cmd = "$sudocmd ".$sysconf_->SRCDIR_."/scripts/starters/".$services_[$service]['stopper'];
  $res = `$cmd`;
  $res = trim($res);
  if ($res == "SUCCESSFULL") {
    $soap_ret->status = 'OK';
    $soap_ret->result = $res;
    return $soap_ret;
  }
  $soap_ret->status = 'STOPFAILED';
  $soap_ret->result = "STOPFAILED - $res";
  return $soap_ret;
}

/**
 * start a given service daemon, will dump the configuration file first
 * @param $sid     string   soap session id
 * @param $service string   service name
 * @return         string   OK result set on success, error result set on failure
 */ 
function startService($sid, $service) {
  global $services_;

  $soap_ret = new SoapServiceStatus();
  if (!array_key_exists($service, $services_)) {
    $soap_ret->status = 'FAILED';
    $soap_ret->result = "SERVICENOTKNOWN $service";
    return $soap_ret;
  }
  $sysconf_ = SystemConfig::getInstance();

  // and start service
  $sudocmd = "/usr/sudo/bin/sudo";
  if (! file_exists($sudocmd) ) {
    $sudocmd = "/usr/bin/sudo";
  }
  $cmd = "$sudocmd ".$sysconf_->SRCDIR_."/scripts/starters/".$services_[$service]['starter'];
  $res = `$cmd`;
  $res = trim($res);
  if ($res == "SUCCESSFULL") {
    $soap_ret->status = 'OK';
    $soap_ret->result = $res;
    return $soap_ret;
  }
  $soap_ret->status = 'STARTFAILED';
  $soap_ret->result = "STARTFAILED - $res";
  return $soap_ret;
}

function restartService($sid, $service) {
  global $services_;

  $soap_ret = new SoapServiceStatus();
  if (!array_key_exists($service, $services_)) {
    $soap_ret->status = 'FAILED';
    $soap_ret->result = "SERVICENOTKNOWN $service";
    return $soap_ret;
  }
  $sysconf_ = SystemConfig::getInstance();

  // and restart service
  $sudocmd = "/usr/sudo/bin/sudo";
  if (! file_exists($sudocmd) ) {
    $sudocmd = "/usr/bin/sudo";
  }
  $cmd = "$sudocmd ".$sysconf_->SRCDIR_."/scripts/starters/".$services_[$service]['restarter'];
  $res = `$cmd`;
  $res = trim($res);
  if ($res == "SUCCESSFULL") {
    $soap_ret->status = 'OK';
    $soap_ret->result = $res;
    return $soap_ret;
  }
  $soap_ret->status = 'RESTARTFAILED';
  $soap_ret->result = "RESTARTFAILED - $res - $cmd";
  return $soap_ret;
}

/**
 * dump a configuration file
 * @param  $config  string   configuration to dump
 * @param  $params  string   command line parameters
 * @return          string   OK on success, error string on failure
 */
 function dumpConfiguration($config, $params) {
    $sysconf_ = SystemConfig::getInstance();
    
    if (!preg_match('/^(domains|wwlist|exim)$/', $config)) {
        $config = "";
    }
    `echo "conf: $config" > /tmp/out.log`;
    switch ($config) {
    	case 'domains':
           $cmd = $sysconf_->SRCDIR_."/bin/dump_domains.pl";
           if ($params != "") {
             $cmd .= " ".escapeshellcmd($params);
           }
           break;
        
        case 'wwlist':
           $cmd = $sysconf_->SRCDIR_."/bin/dump_wwlist.pl";
           if ($params != "") {
             $cmd .= " ".escapeshellcmd($params);
           }
           break;
        case 'exim':
          $cmd = $sysconf_->SRCDIR_."/bin/dump_exim_config.pl";
          if ($params != "") {
             $cmd .= " ".escapeshellcmd($params);
           }
           break;
    }

    # execute
    $res = `$cmd`;
    $res = trim($res);
    
   
    # postpone the job to let the mysql sync finish the propagation
    # but first find out previous already pending jobs
    $atcheck = `atq | cut -f1`;
    $atjobs = preg_split('/\n/', $atcheck);
    $already_pending = 0;
    foreach ($atjobs as $atjob) {
       if (! is_numeric($atjob)) { continue; }
       $atcheck = "at -c $atjob | grep '$cmd'";
       $atcheckv = `$atcheck`;
       $atcheckv = trim($atcheckv);
       if ($atcheckv == $cmd) {
          $already_pending = 1;
       }
    }
    
    if (! $already_pending) {
      # postpone by 2 minutes
      $atcmd = "echo \"$cmd\" | at now + 2 minutes 2>&1";
      $atres = `$atcmd`;
      # postpone by 10 minutes just to be sure
      $atcmd = "echo \"$cmd\" | at now + 10 minutes 2>&1";
      $atres = `$atcmd`;
    }
    if ( $res == "DUMPSUCCESSFUL") {
      return 'OK';
    }
    return $res;
 }
 
/**
 * return the status whereas the process must be restarted or not
 * @param $process string   process to ask for
 * @return         integer  1 process needs a restart, 0 otherwise
 */
 function processNeedsRestart($process) {
    global $services_;
 	$sysconf_ = SystemConfig::getInstance();
    
    $restart_file = $sysconf_->VARDIR_."/run/".$services_[$process]['restartfile'];
    if (file_exists($restart_file)) {
    	return 1;
    }
    return 0;
 }
 
/**
 * set the status of a process to be restarted or not
 * @param  $process  string  process to set
 * @param  $status   integer status to set (1=need restart, 0=no restart needed)
 * @return           boolean true on success, false on failure
 */
 function setRestartStatus($process, $status) {
 	global $services_;
    $sysconf_ = SystemConfig::getInstance();
    
    if ($status < 1) {
    	return true;
    }
    $restart_file = $sysconf_->VARDIR_."/run/".$services_[$process]['restartfile'];
    if (!file_exists($restart_file)) {
    	return touch($restart_file);
    }
    return true;
 }
?>
