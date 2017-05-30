<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * This is the class takes care of the system time configuration
 */
class TimeConfig 
{
  /**
   * whether we use ntp servers of local time
   * @var  boolean
   */
  private $use_ntp_ = false;
  
  /**
   * ntp servers list
   * @var  array
   */
  private $servers_ = array();
  
  /**
   * ntp config file
   * @var  string
   */
  private $ntp_conf_file_ = "/etc/ntp.conf";
  
  /**
   * actual date
   * @var  array
   */
  private $date_ = array (
                    "year" => 1900,
		            "month" => 01,
		            "day" => 01,
		            "hour" => 00,
		            "minute" => 00,
		            "second" => 00
	               );

  /**
   * ntp config file template
   * @var string
   */
  private $ntp_conf_template_ = 
"
driftfile /var/lib/ntp/ntp.drift
statsdir /var/log/ntpstats/

statistics loopstats peerstats clockstats
filegen loopstats file loopstats type day enable
filegen peerstats file peerstats type day enable
filegen clockstats file clockstats type day enable

__SERVERS__

restrict default ignore
";

/**
 * load the actual config from ntp configuration file
 * @return   boolean  true on success false on failure
 */
function load() {
  if (!file_exists($this->getConfigFilePath())) {
    return false;
  }
  $lines = file($this->getConfigFilePath());
  $matches = array();

  foreach($lines as $line) {
    if (preg_match('/^\s*server\s+(\S+)/', $line, $matches)) {
      $this->setUseNTP(true);
      $this->addServer($matches[1]);
    }
  }
  return true;
}

/**
 * return the ntp configuration file name with full path
 * @return  string  ntp configuration file path
 */
private function getConfigFilePath() {
    return $this->ntp_conf_file_;
}

/**
 * return true if we should use ntp protocol, false if not
 * @return  boolean   true id ntp is to be used, false otherwise
 */
public function getUseNTP() {
   return $this->use_ntp_;
}

/**
 * set if we should use ntp protocol or not
 * @param  $use   boolean  true if we have to use ntp, false if not
 * @return        boolean  true on success, false on failure
 */
private function setUseNTP($use) {
   if (is_numeric($use) && $use == 1) {
     $this->use_ntp_ = true;
     return true;
   }
   if (is_bool($use)) {
    $this->use_ntp_ = $use;
    return true;
   }
   $this->use_ntp_ = false;
   return true;
}

/**
 * add a ntp server to be used
 * @param  $server  string  server name or ip to be used
 * @return          boolean 
 */
private function addServer($server) {
  if (!is_string($server)) {
    return false;
  }
  $this->servers_[$server] = true;
  return true;   
}

/**
 * reset the servers list
 * @return  boolean  true on success, false on failure
 */
private function resetServers() {
  unset($this->servers_);
  $this->servers = array();
}

/**
 * set to servers given the servers string list
 * @param  $list  string  servers list, separated by comas
 * @return        boolean true on success, false on failure
 */
private function setServers($list) {
  if (!is_string($list)) {
    return false;
  }
  $this->resetServers();
  $ta = split('\,', $list);
  foreach ($ta as $s) {
     $se = trim($s);
     $this->addServer($se);
  }
  return true;    
}

/**
 * return the server list to be displayed (separated by comas)
 * @return  string  servers list
 */
public function getServers() {
  $ret = "";
  foreach($this->servers_ as $server => $active) {
    $ret .= $server.", ";  
  }
  $ret = rtrim($ret);
  $ret = rtrim($ret, '\,');

  return htmlentities($ret);
}

/**
 * save the configuration to file
 * @return   string  OKSAVED on success, error code on failure
 */
public function save() {
  $res_a = array();
  $res = "";
  
  $sudocmd = "/usr/bin/sudo";
    if (file_exists("/usr/sudo/bin/sudo")) {
      $sudocmd = "/usr/sudo/bin/sudo";
    }

  if ($this->getUseNTP()) {
    $servers = "";
    foreach( $this->servers_ as $server => $active) {
      if ($active) {
        $servers .= "server $server\n";
      }
    }
    $template = preg_replace('/\_\_SERVERS\_\_/', $servers, $this->ntp_conf_template_);

    $file = fopen($this->getConfigFilePath(), 'w');
    if (!$file) { 
      return "CANNOTOPENNTPFILE"; 
    }
    if (fwrite($file, $template) === FALSE) {
      return "CANNOTWRITENTPFILE";
    }
    fclose($file);

    if (file_exists('/etc/init.d/ntp')) {
      exec("$sudocmd /etc/init.d/ntp stop", $res_a, $res);
      if ((!preg_match('/ntpd\.$/', $res_a[0]) && !preg_match('/No such process/', $res_a[0])) || $res != 0) {
        return "CANNOTSTOPNTPSERVER";
      }
    }

    if (file_exists('/usr/sbin/ntpdate')) {
      $ntpservs = split(',', $servers);
      exec("$sudocmd /usr/sbin/ntpdate ".$ntpservs[0], $res_a, $res);
      #if (!preg_match('/step time server\.$/', $res_a[0]) || $res != 0) {
      # return "CANNOTSYNCHRONIZECLOCK";
      #}
    }

    if (file_exists('/etc/init.d/ntp')) {
      exec("$sudocmd /etc/init.d/ntp start", $res_a, $res);
      #if ((!preg_match('/done\.$/', $res_a[0]) && !preg_match('/No such process/', $res_a[0])) || $res != 0) {
      # return "CANNOTRESTARTNTPSERVER";
      #}
      unset($res_a);
    }

  } else {
    if (file_exists('/etc/init.d/ntpd')) {
      exec("$sudocmd /etc/init.d/ntp-server stop", $res_a, $res);
      if ((!preg_match('/ntpd\.$/', $res_a[0]) && !preg_match('/No such process/', $res_a[0])) || $res != 0) {
        return "CANNOTSTOPNTPSERVER";
      }
    }
    $date_str = $this->date_['month'].$this->date_['day'].$this->date_['hour'].$this->date_['minute'].$this->date_['year'].".".$this->date_['second'];
    $date_str = escapeshellarg($date_str);
    exec("$sudocmd /bin/date $date_str", $res_a, $res);
  }

  return "OKSAVED";
}

/**
 * set a preference
 * @param $pref   string  preference name
 * @param $value  mixed   preference value
 * @return        boolean true on success, false on failure
 */
public function setPref($pref, $value) {
  switch($pref) {
    case "usentp":
      $this->setUseNTP($value);
      break;
    case "ntpservers":
      $this->setServers($value);
      break;
    case "year":
    case "month":
    case "day":
    case "hour":
    case "minute":
    case "second":
      $this->date_[$pref] = $value;
      break;
    default:
      break;
  }
}

/**
 * return the formatted date string
 * @return  string  formatted date
 */
public function getDate() {
  $str = sprintf("%.2d%.2d%.2d%.2d%.4d.%.2d", $this->date_['month'], $this->date_['day'], $this->date_['hour'], $this->date_['minute'], $this->date_['year'], $this->date_['second']);
  return $str;
}
}
?>
