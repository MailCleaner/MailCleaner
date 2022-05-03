<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this contains preferences
 */
require_once('helpers/PrefHandler.php');
/**
 * a slave object may connect to his own web service
 */
require_once('system/Soaper.php');
require_once('system/SoapTypes.php');

/**
 * this class takes care of the slave configuration and actions
 */
class Slave extends PrefHandler
{

    /**
     * slave configuration settings
     * @var array
     */
	private $pref_ = array(
                      'id' => 0,
		              'hostname' => '',
		              'port' => '',
		              'password' => '',
		              'ssh_pub_key' => ''
	               );
                   
     /**
      * soap connector to the slave
      * @var Soaper
      */
     private $soaper_;
     
    /**
     * last soap error
     * @var strong
     */
     private $soap_error_;
     
     private $soap_timeout_ = 20;
                   
/**
 * constructor
 */
public function __construct() {
  $this->addPrefSet('slave', 's', $this->pref_);
}

/**
 * load slave datas from database
 * @param  $id  numeric   slave id in the mailcleaner cluster
 * @return      boolean   true on success, false on failure
 */
public function load($id) {
  if (!is_numeric($id)) {
    return false;
  }
  $where = " id=$id";
  return $this->loadPrefs('', $where, true);
}

/**
 * save datas to database
 * @return    boolean  true on success, false on failure
 */
public function save() {
  return $this->savePrefs('', '', '');
}

/**
 * delete datas from database
 * @return    string  'OKDELETED' on success, error message on failure
 */
public function delete() {
  return $this->deletePrefs(null);
}

/**
 * connect to the soap service of the slave
 * @return  boolean  true on success, false on failure
 */
private function connect() {
  global $admin_;
  if ($this->soaper_ instanceof Soaper) {
    return true;
  }
  
  #if (!isset($admin_) || (! $admin_ instanceof Administrator)) {
  #  $this->soap_error_ = 'NOADMINAVAILABLE';
  #  return false;
  #}
  
  $this->soaper_ = new Soaper();
  if (! $this->soaper_ instanceof Soaper) {
    $this->soap_error_ = 'CANNOTINSTANCIATESOAPER';
    return false;
  }
  
  $ret = $this->soaper_->load($this->getPref('hostname'), $this->soap_timeout_);
  if ($ret != 'OK') {
    $this->soap_error_ = $ret;
    return false;
  }
  
  return true;
}

public function setSoapTimeout($timeout) {
  if (is_numeric($this->soap_timeout_)) {
    $this->soap_timeout_ = $timeout;
    return true;
  }
  return false;
}

/**
 * check if the slave can be joined by soap services
 * @return  string  'OK' on success, error code on failure
 */
public function isAvailable() {
   if (!$this->connect()) {
     return $this->getLastSoapError();
   }
   return 'OK'; 
}

/**
 * return the last soap error
 * @return  string last soap error
 */
public function getLastSoapError() {
  return $this->soap_error_;
}

/**
 * get the processes status of the slave
 * @return  array    status, array with process as key and status as value
 */
public function getProcessesStatus() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getProcessesStatus', array());
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * dump a configuration file
 * @param  $config   string   configuration to dump
 * @param  $params   string   command line parmaters to pass
 * @return           boolean  true on success, false on failure
 */
public function dumpConfiguration($config, $params) {
  if (!$this->connect()) {
    return false;
  }

  $value = $this->soaper_->queryParam('dumpConfiguration', array($config, $params));
  $return = $value;
  if (isset($value->enc_value)) {
    $return = $value->enc_value;
  }
  if ($return == 'OK' || $return == '') {
  	return true;
  }
  return false;
}

/**
 * set a process to be restarted
 * @param  $process   string  process to be restarted
 * @return            boolean true on success, false on failure
 */
public function setProcessToBeRestarted($process) {
  if (!$this->connect()) {
    return false;
  }
  
  $value = $this->soaper_->queryParam('setRestartStatus', array($process, 1));
  $status = $value;
  if (isset($value->enc_value)) {
    $status = $value->enc_value;
  }
  if ($status) {
    return true;
  }
  return false;
}

/**
 * return the processes status html string
 * @param  $t       string  html template
 * @param  $colors  array   colors to be used for status
 * @param  $nr      string  need restart character
 * @param  $rh      numeric restarter popup height
 * @param  $rw      numeric restarter popup width
 * @return          string  html process display
 */
public function showProcesses($t, $colors, $nr, $rh, $rw) {
  global $lang_;
    
  $status = $this->getProcessesStatus();
  if (! is_object($status) ) {
    return $this->getLastSoapError();
  }
  $processes = array(
                    'MTA1' => $status->mtaincoming,
                    'MTA2' => $status->mtafiltering,
                    'MTA4' => $status->mtaoutgoing,
                    'HTTPD' => $status->httpd,
                    'ENGINE' => $status->engine,
                    'MASTERDB' => $status->masterdb,
                    'SLAVEDB' => $status->slavedb,
                    'SNMPD' => $status->snmpd,
                    'GREYLISTD' => $status->greylistd,
                    'CRON' => $status->cron,
                    'FIREWALL' => $status->firewall
                 );
  $res = "";
  foreach ($processes as $tag => $soapvalue) {
    $template = str_replace("__STATUS__", $this->draw_status($soapvalue, $colors), $t);
    $template = str_replace("__NEEDRESTART__", $this->needRestart($nr, $tag), $template);
    $template = str_replace("__NAME__", $lang_->print_txt($tag), $template);
    if ($tag == "HTTPD" || $tag == "SLAVEDB") {
      $template = preg_replace("/__IF_STOP__(.*)__ENDIF_STOP__/", "", $template);
      $template = preg_replace("/__IF_START__(.*)__ENDIF_START__/", "", $template);
    } else {
      #$template = preg_replace("/__IF_RESTART__(.*)__ENDIF_RESTART__/", "", $template);
    }
    $template = preg_replace("/__(END)?IF_(STOP|START|RESTART)__/", "", $template);
    $query = http_build_query(array('h' => $this->getPref('id'), 'd' => $tag, 'a' => 'stop'));
    $template = str_replace("__LINK_STOP__", "javascript:open_popup('daemon_stopstart.php?".$query."',$rw,$rh);", $template);
    $query = http_build_query(array('h' => $this->getPref('id'), 'd' => $tag, 'a' => 'start'));
    $template = str_replace("__LINK_START__", "javascript:open_popup('daemon_stopstart.php?".$query."',$rw,$rh);", $template);
    $query = http_build_query(array('h' => $this->getPref('id'), 'd' => $tag, 'a' => 'restart'));
    $template = str_replace("__LINK_RESTART__", "javascript:open_popup('daemon_stopstart.php?".$query."',$rw,$rh);", $template);
    $res .= $template;
  }
  return $res;
}

/**
 * test if a process needs to be restarted
 * @param  $nr  array  need restart character
 * @param  $p   string process id to test
 * @return      string $nr character if a restart is needed, empty string otherwise
 */
private function needRestart($nr, $p) {
  if (!$this->connect()) {
    return false;
  }
  
  $params = array($p);
  $value = $this->soaper_->queryParam('processNeedsRestart', $params);
  $status = $value;
  if (isset($value->enc_value)) {
    $status = $value->enc_value;
  }
  if ($status) {
  	return $nr;
  }
  return "";
}

/**
 * return the html string for the status
 * @param  $status  string  actual status
 * @param  $colors  array   array of colors for differents status
 * @return          string  html status code
 */
private function draw_status($status, $colors) {
 global $lang_;

 $color = "black";
 $txt = "a";

 switch ($status) {
    case 1:
       $color = $colors['OK'];
       $txt = $lang_->print_txt('RUNNING');
       break;
    case 2:
       $color = $colors['INACTIVE'];
       $txt = $lang_->print_txt('INACTIVE');
       break;
    default:
       $color = $colors['CRITICAL'];
       $txt = $lang_->print_txt('CRITICAL');
 }

 $ret = "<font color=\"".$color."\">".$txt."</font>";
 return $ret;
}

/**
 * get the status of the spools
 * @return  array    spools counts as array (key is spool name, value is message count)
 */
public function getSpoolsCount() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getSpools', array());
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * return the html string representing the different spools
 * @param  $t  string  html template
 * @param  $rh      numeric restarter popup height
 * @param  $rw      numeric restarter popup width
 * @return     string  html code string
 */
public function showSpools($t, $rh, $rw) {
  global $lang_;
  $ret = "";
  $status = $this->getSpoolsCount();
  if (!is_object($status)) {
    return $this->getLastSoapError();
  }
  $spools = array(
              'MTA1' => $status->incoming,
              'MTA2' => $status->filtering,
              'MTA4' => $status->outgoing
           );
  foreach ($spools as $tag => $soapvalue) {
    $tmpl = str_replace("__SPOOL__", $soapvalue, $t);
    $tmpl = str_replace("__NAME__", $lang_->print_txt($tag), $tmpl);
    $query = http_build_query(array('h' => $this->getPref('id'), 's' => $tag));
    $tmpl = str_replace("__LINK__", "javascript:open_popup('view_spool.php?".$query."',$rw,$rh);", $tmpl);
    $ret .= $tmpl;
  }
  return $ret;
}

/**
 * get the load of the system
 * @return  array    actual load values as array (key is time offsets, value is load)
 */
public function getLoads() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getLoad', array());
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * return the html string for displaying the load averages
 * @param  $t  string  html template
 * @return     string  html load string
 */
public function showLoad($t) {
  global $lang_;
  $status = $this->getLoads();
  if (!is_object($status)) {
    return $this->getLastSoapError();
  }
  $loads = array(
                  'AVG5MIN'  => $status->avg5,
                  'AVG10MIN' => $status->avg10,
                  'AVG15MIN' => $status->avg15
               );
  foreach ($loads as $tag => $soapvalue) {
    $tmpl = str_replace('__VALUE__', $soapvalue, $t);
    $ret .= str_replace('__NAME__', $lang_->print_txt($tag), $tmpl);
  }
  return $ret;
}

/**
 * get the disks usage of the system
 * @return  array    actual partitions usage as array (key is mount point, value is usage in percent)
 */
private function getDiskUsage() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getDiskUsage', array());
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * return the html disk usage display
 * @param  $t  string  html template
 * @return     string  html usage string
 */
public function showDiskUsage($t) {
  global $lang_;
  
  $status = $this->getDiskUsage();
  if (!is_object($status)) {
    return $this->getLastSoapError();
  }
  $disks = array(
            'SYSTEMPART' => $status->root,
            'DATASPART' => $status->var
          );
  foreach ($disks as $tag => $soapvalue) {
    $tmpl = str_replace('__VALUE__', $soapvalue, $t);
    $ret .= str_replace('__NAME__', $lang_->print_txt($tag), $tmpl);
  }
  return $ret;
}

/**
 * get the memory usage of the system
 * @return  array    memory usage as array (key is memory type, value is usage)
 */
private function getMemoryUsage() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getMemUsage', array());
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * return the html memory usage display
 * @param  $t  string  html template
 * @return     string  html usage string
 */
public function showMemUsage($t, $colors) {
  global $lang_;
          
  $status = $this->getMemoryUsage();
  if (!is_object($status)) {
    return $this->getLastSoapError();
  }
  
  $mems = array(
             'TOTALMEMORY' => $status->total,
             'FREEMEMORY' => $status->free,
             'TOTALSWAP' => $status->swaptotal,
             'FREESWAP' => $status->swapfree
          );
  foreach ($mems as $tag => $soapvalue) {
    $tmpl = str_replace('__VALUE__', $this->format_size($soapvalue), $t);
    $ret .= str_replace('__NAME__', $lang_->print_txt($tag), $tmpl);
  }
  return $ret; 
 }


/**
 * get the last patch number of the system
 * @return  string    last patch number
 */
public function getLastPatch() {
  if (!$this->connect()) {
    return null;
  }
  return $this->soaper_->query('getLastPatch', array());
}

/**
 * get the messages counts for today
 * @return  array    today's counts as array (key is counted value, value is actual count)
 */
public function getTodaysCounts() {
  if (!$this->connect()) {
    return null;
  }
  $values = $this->soaper_->query('getTodaysCounts', array());
  if (isset($values->enc_value)) {
  	return $values->enc_value;
  }
  return $values;
}

/**
 * get the messages counts
 * @param   $what  string  what to search for (_global, domain name, username)
 * @param   $start string  start date
 * @param   $stop  string  stop date
 * @return  array    counts as array (key is counted value, value is actual count)
 */
public function getStats($what, $start, $stop) {
  if (!$this->connect()) {
    return null;
  }
  
  $values = $this->soaper_->queryParam('getStats', array($what, $start, $stop));
  if (isset($values->enc_value)) {
    return $values->enc_value;
  }
  return $values;
}

/**
 * return the html messages count to display
 * @param  $t        string  html template
 * @param  $colors   array   colors for the differents count types
 * @return     string  html counts string
 */
public function showTodaysCounts($t, $colors) {
  global $lang_;
   
  $status = $this->getTodaysCounts();
  if (!is_object($status)) {
    return $this->getLastSoapError();
  }     
  $types = array(
                  'MESSAGES' => $status->msg,
                  'SPAMS' => $status->spam,
                  'VIRUSES' => $status->virus,
                  'CONTENT' => $status->content
               );       
  foreach ($types as $tag => $soapvalue) {
    $tmpl =   str_replace('__VALUE__', "<font color=\"".$colors[$tag]."\">".$soapvalue."</font>", $t);
    $ret .=   str_replace('__NAME__', "<font color=\"".$colors[$tag]."\">".$lang_->print_txt($tag)."</font>", $tmpl);
  }
  return $ret;
}

/**
 * format a disk size into a human readable form
 * @param  $s  numeric  size to format
 * @return     string   formatted size string
 * @todo  may be placed somewhere else
 */
static private function format_size($s) {
  global $lang_;
  $ret = "";
  if ($s > 1000*1000) {
    $ret = sprintf("%.2f ".$lang_->print_txt('GB'), $s/(1000.0*1000.0));
  } elseif ($s > 1000) {
    $ret = sprintf("%.2f ".$lang_->print_txt('MB'), $s/(1024.0));
  } else { 
    $ret = $s." ".$lang_->print_txt('BYTES');
  }
  return $ret;
}
}
?>