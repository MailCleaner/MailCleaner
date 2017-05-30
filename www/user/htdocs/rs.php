<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the force message page
 */
require_once('variables.php');
require_once('domain/Domain.php');
require_once("system/SystemConfig.php");
include('Mail.php');
include('Mail/mime.php');

$sysconf = SystemConfig::getInstance();
$latest_plugin = '1.0.3';
if (isset($_REQUEST['ver'])) {
   $version = $_REQUEST['ver'];
   if (getNumericVersion($version) < getNumericVersion($latest_plugin)) {
      die('OLDVERSION');
   }
}

## get posted message
$b64message = $_REQUEST['message'];

$message = '';
if (file_exists('/tmp/rstest/message_b64.txt')) {
  $handle=fopen('/tmp/rstest/message_b64.txt', 'r');
  $b64message = fread ($handle, filesize ('/tmp/rstest/message_b64.txt'));
  fclose ($handle);
} else {
  if (isset($b64message) || $b64message != "") {

    ## backup data
    $fh = fopen("/tmp/backupspamdata","a");
    if ($fh) {
      fputs($fh, $b64message);
      fclose($fh);
    }
    $message = base64_decode($b64message);
  }
}

if (file_exists('/tmp/rstest/message_no_b64.txt')) {
  $handle=fopen('/tmp/rstest/message_no_b64.txt', 'r');
  $message = fread ($handle, filesize ('/tmp/rstest/message_no_b64.txt'));
  fclose ($handle);
}

if (!isset($message) || $message == '') {
  die ("NOMESSAGEGIVEN");
}

# work around suspected .net bug in base64 encoding
$message = preg_replace('/__lt__/', '<', $message);
$message = preg_replace('/__gt__/', '>', $message);

$lines = preg_split('/\n/', $message);
$inreceived = 0;
$matches = array();
$sender = "";
$sender_local = "";
$sender_domain = "";
$subject = "No Subject found";
$date = "No date found";
$infor = 0;
foreach ( $lines as $line) {
  if (preg_match('/^\*+$/', $line)) {
    break;
  }
  if (preg_match('/^Received: /', $line)) {
     $inreceived = 1;
     continue;
  }
  if (preg_match('/^\S+: /', $line)) {
  	$inreceived = 0;
  }
  if ($sender == "" && $inreceived && preg_match('/for\s+[<_](\S+)\@(\S+)[_>]/', $line, $matches)) {
    $sender_local = $matches[1];
    $sender_domain = $matches[2];
    $sender = $matches[1]."@".$matches[2];
  }
  if ($sender == "" && $inreceived && preg_match('/id\s+(\S+)\s+for$/', $line, $matches)) {
    $infor = 1;
    continue;
  }
  if ($sender == "" && $inreceived && $infor) {
    if (preg_match('/^\s+[<_](\S+)\@(\S+)[_>]/', $line, $matches)) {
      $sender_local = $matches[1];
      $sender_domain = $matches[2];
      $sender = $matches[1]."@".$matches[2];
    }
    $infor = 0;
  }
  if ($sender == "" && preg_match('/^To: [^>]*[<_](\S+)\@(\S+)[>_]/i', $line, $matches)) {
    $sender_local = $matches[1];
    $sender_domain = $matches[2];
    $sender = $matches[1]."@".$matches[2];
  }
  if ($sender == "" && preg_match('/^To: [^>]*[<_](\S+)\@(\S+)[>_]/i', $line, $matches)) {
    $sender_local = $matches[1];
    $sender_domain = $matches[2];
    $sender = $matches[1]."@".$matches[2];
  }
  if ($sender == "" && preg_match('/X-RCPT-TO: [<_](\S+)\@(\S+)[>_]/i', $line, $matches)) {
    $sender_local = $matches[1];
    $sender_domain = $matches[2];
    $sender = $matches[1]."@".$matches[2];
  }
  if (preg_match('/Subject: (.*)$/i', $line, $matches)) {
    $subject = $matches[1];
  }
  if (preg_match('/Date: (.*)$/i', $line, $matches)) {
    $date = $matches[1];
  }
}

if (!isset($sender_domain) || $sender_domain == "") {
  die ("NOSENDERFOUND");
}
$domain = new Domain();
if (!$domain->load($sender_domain)) {
  die ("CANNOTGETDOMAIN ($sender_domain)");
}
$destination = $domain->getPref('falseneg_to');
if ($destination == "") {
  $destination = $sysconf->getPref('falseneg_to');
}
if (! preg_match('/\S+\@\S+/', $destination)) {
  die("BADDESTINATION");
}

$dir = "/tmp/".uniqid();
if (!mkdir($dir)) {
  die("CANNOTCREATETMPDIR");
}

$filename = $dir."/message.txt";
$f=fopen($filename, 'w');
if (!$f) {
  die ("CANNOTCREATETEMPFILE");
}
fwrite($f, $message);
fclose($f);

$headers = array(
   'From' => $destination,
   'Subject' => 'Report Spam' 
);
$text = 'Spam not detected:'."\n
From: ".trim($sender)."
Subject: ".trim($subject)."
Date: ".trim($date)."\n\n";
$html = "<html><body><b>Spam analysis request:</b><br /><br />
<b>From: </b>$sender<br />
<b>Subject: </b>$subject<br />
<b>Date: </b>$date<br /><br /></body></html>";
$crlf = "\n";

$mime = new Mail_mime($crlf);

$mime->setTXTBody($text);
$mime->setHTMLBody($html);
$mime->addAttachment($filename, 'text/plain');

$body = $mime->get();
$hdrs = $mime->headers($headers);

$params['host']='localhost';
$params['port']='2525';
$mail_object = Mail::factory('smtp', $params);
if ($mail_object->send($destination, $hdrs, $body)) {
  header("status: 202");  
  header("HTTP/1.1 202 Accepted");
  echo "MESSAGESENT";
} else {
  echo "ERRORSENDING";
}

unlink($filename);
rmdir($dir);


function getNumericVersion($string) {
   if (preg_match('/(\d+)\.?(\d+)?\.?(\d+)?/', $string, $matches)) {
     $major = $matches[1];
     $minor = 0;
     $subminor = 0;
     if (isset($matches[2])) {
       $minor = $matches[2];
     }
     if (isset($matches[3])) {
       $subminor = $matches[3];
     }

     return $major*100000 + $minor*1000 + $subminor;
   }
   return 0;
}
?>
