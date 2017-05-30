<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * This is the class takes care of gathering and processing user, domain and system statistics
 */
class Statistics {
    
  /**
   * object for which to get statistics
   * may be full email address (user), a domain (@domain) or _global
   * @var string
   */
  private $object_ = "";
  
  /**
   * object type (user, domain or system)
   * @var string
   */
  private $object_type_ = "";
  
  /**
   * start date of the statistics
   * may be a full qualified date (YYYYMMDD), a delta of days '-XX' or 'today'
   * @var string
   */
  private $startdate_ = "";
 
  /**
   * stop date of the statistics
   * may be a full qualified date (YYYYMMDD), a delta of days '+XX' or 'today'
   * @var string
   */
  private $stopdate_ = ""; 
  
  /**
   * gathered statistics
   * @var  array
   */
  private $stats_ = array(
               'msgs'  => 0,
               'spams' => 0,
               'pspams' => 0,
               'bytes' => 0,
               'content' => 0,
               'pcontent' => 0,
               'virus' => 0,
               'pvirus' => 0,
               'clean' => 0,
               'pclean' => 0
   );
   
   private $graph_id_;
   private $date_type_ = 'date';
  
/**
 * constuctor
 */
public function __construct() {
  // set start and stop dates
  $this->setDate('start', 'today');
  $this->setDate('stop', 'today');
}

/**
 * load datas
 * @var  $object  string   object to process
 * @var  $start   string   start date or delta
 * @var  $stop    string   stop date or delta
 * @return  true on success, false on failure
 */
public function load($object, $startdate, $stopdate) {
   if (!$this->setObject($object)) { return false; }
   if (!$this->setDate('start', $startdate)) { return false; }
   if (!$this->setDate('stop', $stopdate)) { return false; }
	
   // load slave
   $sysconf = SystemConfig::getInstance();
   if (count($sysconf->getSlaves()) < 1) {
     $sysconf->loadSlaves();
   }
   
   // gather stats
   foreach ($sysconf->getSlaves() as $slave) {
     $stats = $slave->getStats($this->object_, $this->startdate_, $this->stopdate_);
     $this->stats_['msgs'] += $stats->msg;
     $this->stats_['spams'] += $stats->spam;
     $this->stats_['bytes'] += $stats->bytes;
     $this->stats_['virus'] += $stats->virus;
     $this->stats_['content'] += $stats->content;
     $this->stats_['clean'] += $stats->clean;
   }
   // compute stats
   if ($this->stats_['msgs'] != 0) {
     $this->stats_['pspams'] = (100/$this->stats_['msgs'])*$this->stats_['spams'];
     $this->stats_['pvirus'] = (100/$this->stats_['msgs']) * $this->stats_['virus'];
     $this->stats_['pclean'] = (100/$this->stats_['msgs']) * $this->stats_['clean'];
     $this->stats_['pcontent'] = (100/$this->stats_['msgs']) * $this->stats_['content'];
   }
}

public function generateGraphs($template) {
     //create stat graphs
   $this->graph_id_=uniqid();
   require_once('view/graphics/Pie.php');
   $pie_stats = new Pie();
   $pie_stats->setFilename('/stats/'.$this->graph_id_."_pie.png");
   $pie_stats->setSize((int)$template->getDefaultValue('PIEWIDTH'), (int)$template->getDefaultValue('PIEHEIGHT'));

   $pie_stats->addValue($this->stats_['spams'], 'spams', Statistics::colorToArray($template->getDefaultValue('SPAMCOLOR')));
   $pie_stats->addValue($this->stats_['virus']+$this->stats_['content'], 'dangerous', Statistics::colorToArray($template->getDefaultValue('VIRUSCOLOR')));
   $pie_stats->addValue($this->stats_['clean'], 'clean', Statistics::colorToArray($template->getDefaultValue('CLEANCOLOR')));

   $pie_stats->generate();
}


/**
 * set the object
 * @var   $object  string  object to process
 * @return  true on success, false on failure
 */
private function setObject($object) {
  if (preg_match('/^[-a-z0-9._]+@[-a-z0-9._]+$/i', $object)) {
  	$this->object_ = $object;
    $this->object_type_ = 'user';
    return true;
  }
  if (preg_match('/^@[-a-z0-9._]+$/i', $object)) {
    $this->object_ = $object;
    $this->object_type_ = 'domain';
    return true;
  }
  if ($object == '_global') {
  	$this->object_ = '_global';
    $this->object_type_ = 'global';
    return true;
  }
  return false;
}

/**
 * set the date
 * @var   $type  string   start or stop
 * @var   $date  string  date
 * @return  true on success, false on failure
 */
public function setDate($type, $date) {
  if ( ! ($type == 'start' || $type == 'stop') ) {
  	return false;
  }
  
  if ($date == 'today') {
  	$today = @getdate();
    if ($type == 'start') {
     $this->startdate_ = $today['year'].sprintf('%02d', $today['mon']).sprintf('%02d', $today['mday']);
     return true;
    }
    $this->stopdate_ = $today['year'].sprintf('%02d', $today['mon']).sprintf('%02d', $today['mday']);
    return true;
  }
  if (preg_match('/^\d{8}$/', $date, $matches)) {
  	if ($type == 'start') {
  	  $this->startdate_ = $date;
      return true;
  	}
    $this->stopdate_ = $date;
    return true;
  }
  
  if (preg_match('/^[-+]\d+$/', $date, $matches)) {
    $this->date_type_ = 'period';
  	if ($type == 'start') {
      $this->startdate_ = $date;
      return true;
    }
    $this->stopdate_ = $date;
    return true;
  }
  
  return false;
}

public function getDateArray($type) {
  $matches = array();
  $date = $this->startdate_;
  if ($type == 'stop') {
  	$date = $this->stopdate_;
  }
  if (!preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $matches)) {
  	return array('day' => 1, 'month' => 1, 'year' => 1900);
  }
  return array('day' => $matches[3], 'month' => $matches[2], 'year' => $matches[1]);
}

public function getStatInTemplate($template, $tpl_name) {
  global $lang_;
  $t = $template->getTemplate($tpl_name);
  
  $barwidth = $template->getDefaultValue('BARWIDTH');
  if ($barwidth == "" || !is_int($barwidth)) {
  	$barwidth = 300;
  }
  
  $startd = Statistics::getAnyDateAsArray($this->startdate_);
  $stopd = Statistics::getAnyDateAsArray($this->stopdate_);
  $date_string = $lang_->print_txt_mparam('FROMDATETODATE', array($startd['day'], $startd['month'], $startd['year'], $stopd['day'], $stopd['month'], $stopd['year']));
  if ($this->date_type_ == 'period') {
  	$date_string = abs($this->startdate_)." ".$lang_->print_txt('LASTDAYS');
  }
  
  foreach (preg_split('/\n/', $t) as $line) {
  	if (preg_match('/\_\_LANG\_([A-Z0-9]+)\_\_/', $line, $matches)) {
      $line = preg_replace('/\_\_LANG\_([A-Z0-9]+)\_\_/', $lang_->print_txt($matches[1]), $line);
    }
    if (!isset($mt)) {
        $mt = '';
    }
    $mt .= $line."\n";
  }
  $t = str_replace('__USERSTATS_TITLE__', $lang_->print_txt_param('STATFORADDRESS', $this->object_), $mt);
  $t = str_replace('__NBPROCESSEDMSGS__', $lang_->print_txt_param('NBPROCESSEDMSGS', $this->stats_['msgs']), $t);
  $t = str_replace('__COUNT_VIRUS__', $this->stats_['virus'] + $this->stats_['content'], $t);
  $t = str_replace('__COUNT_SPAM__', $this->stats_['spams'], $t);
  $t = str_replace('__COUNT_CLEAN__', $this->stats_['clean'], $t);
  $t = str_replace('__COUNT_MSGS__', $this->stats_['msgs'], $t);
  $t = str_replace('__PERCENT_VIRUS__', round($this->stats_['pvirus'] + $this->stats_['pcontent'], 2), $t);
  $t = str_replace('__PERCENT_SPAM__', round($this->stats_['pspams'], 2), $t);
  $t = str_replace('__PERCENT_CLEAN__', round($this->stats_['pclean'], 2), $t);
  $t = str_replace('__BARWIDTH_VIRUS__', $this->getBarWidth('pvirus', $barwidth), $t);
  $t = str_replace('__BARWIDTH_SPAM__', $this->getBarWidth('pspams', $barwidth), $t);
  $t = str_replace('__BARWIDTH_CLEAN__', $this->getBarWidth('pclean', $barwidth), $t);
  $t = str_replace('__PIE_GRAPH__', '/stats/'.$this->graph_id_.'_pie.png', $t);
  $t = str_replace('__DATE_STRING__', $date_string, $t);
    
  return $t;
}

private function getBarWidth($what, $fullwidth) {
  if($this->stats_[$what] == 0) {
  	return 0;
  }
  $ratio = $fullwidth / 100;
  if ($what == 'pclean') {
    return $fullwidth - (ceil($this->stats_['pspams']*$ratio) + ceil($this->stats_['pvirus']*$ratio));	
  }
  if ($what == 'pspams' && (ceil($this->stats_['pspams']*$ratio) + ceil($this->stats_['pvirus']*$ratio)) > $fullwidth) {
    return floor($this->stats_[$what]*$ratio);
  }
  return ceil( $this->stats_[$what]*$ratio );
}

public function getStats() {
  return $this->stats_;
}

public function addStats($stats) {
  foreach ($stats as $key => $value) {
  	$this->stats_[$key] += $value;
  }
  if ($this->stats_['msgs'] > 0) {
    $ratio = 100 / $this->stats_['msgs'];
    $this->stats_['pvirus'] = $this->stats_['virus'] * $ratio;
    $this->stats_['pcontent'] = $this->stats_['content'] * $ratio;
    $this->stats_['pspams'] = $this->stats_['spams'] * $ratio;
    $this->stats_['pclean'] = $this->stats_['clean'] * $ratio;
  }
  return true;
}

public static function getAnyDateAsArray($date) {
  $matches = array();
  if ($date == 'today' || !preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $matches)) {
  	$today = @getdate();
    return array('day' => $today['mday'], 'month' => $today['mon'], 'year' => $today['year']);
  }
  return array('day' => $matches[3], 'month' => $matches[2], 'year' => $matches[1]);
}

public static function colorToArray($color) {
  if (!preg_match('/^(\S{2})(\S{2})(\S{2})$/', $color, $matches) ) {
  	return array(0xFF, 0xFF, 0xFF);
  }
  $r = '0x'.$matches[1];
  $rv = eval("return $r;");
  $g = '0x'.$matches[2];
  $gv = eval("return $g;");
  $b = '0x'.$matches[3];
  $bv = eval("return $b;");
  
  return array($rv, $gv, $bv);
}

}

?>
