<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * message reasons
 */
class SoapReasons {
  public $reasons = array();    
}

/**
 * message text
 */
class SoapText {
  public $text = array();
}

/**
 * service restarter status
 */
class SoapServiceStatus {
  public $status;
  public $result;
}

/**
 * spools count
 */
class SoapSpools {
  public $incoming;
  public $filtering;
  public $outgoing;
  
  public function __construct($i, $f, $o) {
    $this->incoming = $i;
    $this->filtering = $f;
    $this->outgoing = $o;
  }
}

/**
 * processes status
 */
class SoapProcesses {
  public $mtaincoming;
  public $mtafiltering;
  public $mtaoutgoing;
  public $httpd;
  public $engine;
  public $masterdb;
  public $slavedb;
  public $snmpd;
  public $greylistd;
  public $cron;
  public $prefdaemon;
  public $spamd;
  public $clamd;
  public $clamspamd;
  public $spamhandler;
  public $firewall;
  
  static public function isOK($status) {
    if ( $status->mtaincoming  == 0 ||
         $status->mtafiltering  == 0 ||
         $status->mtaoutgoing  == 0 ||
         $status->httpd  == 0 ||
         $status->engine  == 0 ||
         $status->masterdb  == 0 ||
         $status->slavedb  == 0 ||
         $status->snmpd == 0 ||
         $status->greylistd == 0 || 
         $status->cron == 0 ||
         $status->prefdaemon = 0 ||
         $status->firewall == 0
     ) {
       return false;
     }
     return true; 
  }
}

/**
 * system loads
 */
class SoapLoad {
  public $avg5;
  public $avg10;
  public $avg15;
  
  public function __construct($l5, $l10, $l15) {
    $this->avg5 = $l5;
    $this->avg10 = $l10;
    $this->avg15 = $l15;
  }
}

/**
 * system disks usage
 */
class SoapDiskUsage {
  public $root;
  public $var;
  
  public function __construct($r, $v) {
    $this->root = $r;
    $this->var = $v;
  }
}

/**
 * system memory usages
 */
class SoapMemoryUsage {
  public $total;
  public $free;
  public $swaptotal;
  public $swapfree;
  
  public function __construct($t, $f, $st, $sf) {
    $this->total = $t;
    $this->free = $f;
    $this->swaptotal = $st;
    $this->swapfree = $sf;
  }
}

/**
 * system counts
 */
class SoapStats {
  public $bytes;    
  public $msg;
  public $spam;
  public $pspam;
  public $virus;
  public $pvirus;
  public $content;
  public $pcontent;
  public $user;
  public $clean;
  public $pclean;
  
  public function __construct($b, $m, $s, $ps, $v, $pv, $c, $pc, $u, $l, $pl) {
    $this->bytes = $b;
    $this->msg = $m;
    $this->spam = $s;
    $this->pspam = $ps;
    $this->virus = $v;
    $this->pvirus = $pv;
    $this->content = $c;
    $this->pcontent = $pc;
    $this->user = $u;
    $this->clean = $l;
    $this->pclean = $pl;
  }
}

$SoapClassMap = array( 
                    "reasons" => "SoapReasons",
                    "messagelines" => "SoapText",
                    "servicestatus" => "SoapServiceStatus",
                    "processes" => "SoapProcesses",
                    "spools" => "SoapSpools",
                    "load" => "SoapLoad",
                    "diskusage" => "SoapDiskUsage",
                    "memusage" => "SoapMemoryUsage",
                    "stats" => "SoapStats"
                    );

?>
