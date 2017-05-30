<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page that will that will redirect to the actual patch status of the corresponding system
 */

/**
 * require administrative access
 */       
require_once('admin_objects.php');
require_once('system/Soaper.php');
require_once('system/Slave.php');

global $sysconf_;
global $lang_;

// check parameters and set values
if (!isset($_GET['h']) || !is_numeric($_GET['h'])) {
  $error = "BADARGS";
} else {
  $spool = $_GET['s']; 
  // connect to slave
  $host = $sysconf_->getSlaveName($_GET['h']);
  $soaper = new Soaper();
  $ret = $soaper->load($host);
  if ($ret != "OK") {
    $error = "CANNOTCONNECTTOSLAVE";
  }

  // authenticate admin
  $sid = $soaper->authenticateAdmin();
  if (preg_match('/^[A-Z]+$/', $sid)) {
    $error = $sid;
  }

  // redirect to the correct host
  if ($host == '127.0.0.1' || $host == 'localhost') {
   header("Location: patches.php?sid=$sid");
  } else {
    $hostip = gethostbyname($host);
    $proto = 'http';
    if (isset($_SERVER['HTTPS'])) {
      $proto = "https";
    }
    header("Location: $proto://$hostip/admin/patches.php?sid=$sid");
  }
} 
?>