<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
require_once("system/SystemConfig.php");

/**
 * this class handles the exim spools
 */
class Spooler {

  /**
   * the different spools availables
   * @var array
   */
   private $spool_availables_ = array(
                    'MTA1' => 1,
                    'MTA2' => 2,
                    'MTA4' => 4
                    );
                
   /**
    * the spool actually being processed
    * @var string
    */    
   private $spool_ = "";
   /**
    * configuration file
    * @var string
    */
   private $conf_file_ = "";
   
/**
 * load the spool datas
 * @param $spool  string  spool to be fetched
 * @return        boolean true on success, false on failure
 */
public function load($spool) {
  if (!isset($this->spool_availables_[$spool])) {
    return false;
  }

  $sysconf = SystemConfig::getInstance();
  $this->conf_file_ = $sysconf->SRCDIR_."/etc/exim/exim_stage".$this->spool_availables_[$spool].".conf";
  if (file_exists($this->conf_file_)) {
    $this->spool_ = $spool;
    return true;
  }
  return false;
}

/**
 * return the number of mails in queue
 * @return  numeric number of mails in queue
 */
public function getCount() {
  $cmd = "/opt/exim4/bin/exim -C ".$this->conf_file_." -bpc";
  $ret = `$cmd`;
  if (preg_match('/^\d+\s*$/', $ret)) {
    return $ret;
  }
  return "__";
}

/**
 * return the html string of the spool array
 * @param  $template  string  html template string
 * @param  $images    string  html link to images to be used
 * @param  $sid       string  session id
 * @return            string  html string
 */
public function draw($template, $images, $sid) {
  // fields to search and display
  $fields = array('to' => array(), 'time' => '', 'size' => '', 'id' => '', 'from' => '', 'status' => '');
  // exim command to list queue
  $cmd = "/opt/exim4/bin/exim -C ".$this->conf_file_." -bp";
  $list  = popen($cmd, "r");

  $ret = "";
  $i = 0;
  $matches = array();
  while (!feof($list)) {
    $buffer = fgets($list, 4096);
    if (preg_match('/^\s*(\d+[a-z])\s+(\d+.?\d+[a-zA-Z]*)?\s+(\S{6}\-\S{6}\-\S{2})\s+(.*)/', $buffer, $matches)) {
      // new message found, so dump previous
      $ret .= $this->dumpMessage($template, $images, $sid, $fields, $i++);
      $fields['to'] = "";
      $fields['time'] = $matches[1];
      $fields['size'] = $matches[2];
      $fields['id'] = $matches[3];
      $fields['from'] = str_replace('<', '', $matches[4]);
      $fields['from'] = str_replace('>', '', $fields['from']);
      if (isset($matches[5]) && $matches[5] == "*** frozen ***") {
        $fields['status'] = 'FROZEN';
      }
    }
    if (preg_match('/^\s+(D)?\s+(.*)$/', $buffer, $matches)) {
      $to_tmp = str_replace('<', '', $matches[2]);
      $to_tmp = str_replace('>', '', $to_tmp);
      if (isset($matches[1]) && $matches[1] == 'D') {
        $fields['to'][$to_tmp] = 1;
      } else {
        $fields['to'][$to_tmp] = 0;
      }
    }
  }
  $ret .= $this->dumpMessage($template, $images, $sid, $fields, $i);
  pclose($list);
  return $ret;
}

/**
 * return the single html line for a queue element
 * @param  $t        string  html line template
 * @param  $images   string  html images line
 * @param  $sid      string  session id
 * @param  $f        array   fields to be displayed
 * @param  $i        numeric line number
 * @param            string  html line string
 */
private function dumpMessage($t, $images, $sid, $f, $i) {
  global $lang_;
  if (!isset($f['id']) || $f['id'] == '') {
    return "";
  }

  $ret = $t;
  $ret = str_replace('__TIME__', htmlentities($f['time']), $ret);
  $ret = str_replace('__ID__', htmlentities($f['id']), $ret);
  $ret = str_replace('__SIZE__', htmlentities($f['size']), $ret);
  $ret = str_replace('__FROM__', htmlentities($f['from']), $ret);
  if ($this->spool_ != 'MTA2') {
   $ret = str_replace('__ACTION__', "<a href=\"javascript:window.location.href='".$_SERVER['PHP_SELF']."?s=".$this->spool_."&sid=$sid&m=fo&mid=".$f['id']."'\"><img src=\"".$images['FORCEMSG']."\" border=\"0\"></a>", $ret);
  } else {
   $ret = str_replace('__ACTION__', "", $ret);
  }
  if ($i++ % 2) {
    $ret = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$1", $ret);
  } else {
    $ret = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$2", $ret);
  } 

  if (isset($f['status']) && $f['status'] != '') {
    $ret = str_replace('__STATUS__', $lang_->print_txt($f['status']), $ret);
  } else {
    $ret = str_replace('__STATUS__', $lang_->print_txt('WAITING'), $ret);
  }

  $tos = "";
  foreach ($f['to'] as $to => $status) {
    $tos .= "<br/>&nbsp;$to";
    if ($status == 1) {
      $tos .= " (D)";
    }
  }
  $tos = preg_replace('/^\<br\/\>\&nbsp\;/', '', $tos);
  $ret = str_replace('__TO__', htmlentities($tos), $ret);
  return $ret;
}

/**
 * launch a queue run to try to force every message
 * @return   boolean  true on success, false on failure
 */
public function runQueue() {
  $cmd = "/opt/exim4/bin/exim -C ".$this->conf_file_." -qff > /dev/null & echo \$!";  
  $ret = `$cmd`;
  $matches = array();
  if (preg_match('/^\s*(\d+)\s*$/', $ret, $matches)) {
    return true;
  }
  return false;
}

/**
 * try to force a single message
 * @return  boolean  true on success, false on failure
 */
public function forceOne($id) {
  $cmd = "/opt/exim4/bin/exim -C ".$this->conf_file_." -M $id > /dev/null & echo \$!";
  $ret = `$cmd`;
  $matches = array();
  if (preg_match('/^\s*(\d+)\s*$/', $ret, $matches)) {
     return true;
  }
  return false;
}

/**
 * return the status of potential queue run
 * @return   string  queue run status
 */
public function queueRunStatus() {
  $searches = array(
                 "ALREADYFORCINGONE" => "exim -C ".$this->conf_file_." -M",
                 "ALREADYRUNNING" => "exim -C ".$this->conf_file_." -qff"
                 );
  foreach ($searches as $msg => $search) {
    $cmd = "pgrep -f '".$search."'";
    $res = `$cmd`;
    if ($res != "") {
      return $msg;
    }
  }
  return ""; 
}
}

?>
