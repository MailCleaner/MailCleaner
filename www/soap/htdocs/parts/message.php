<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * This file is mainly a soap wrapper around common message actions
 */

/**
 * force a quarantined content
 * @param  $sid  string  soap session id
 * @param  $path string  path identifier used to find the message file (YYYYMMDD/xxxxxx-xxxxxx-xx)
 * @return       string  command string result, or error message
 */
function forceContent($sid, $path) {

  $admin_ = getAdmin($sid);
  if (!is_object($admin_) || $admin_->getPref('username') == "") {
    if (isset($admin_)) {
       return $admin_;
    }
    return "NOTAUTHENTICATED";
  }
  if (!$admin_->hasPerm(array('can_manage_users'))) {
     return "NOTALLOWED";
  }

  if (! preg_match('/\d{8}\/([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $path)) {
    return "BADPARAMS";
  }
  $sysconf_ = SystemConfig::getInstance();
  $path = escapeshellarg($path);
  $cmd = $sysconf_->SRCDIR_."/bin/force_quarantined.pl ".$path;
  $res_a = array();
  exec($cmd, $res_a);

  return $res_a[0];
}


/**
 * force a quarantined spam message
 * @param  $id   string   message id
 * @param  $dest string   original destination email address
 * @return       string   command string result, or error message
 */
function forceSpam($id, $dest) {

 if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
 }
 if (! preg_match('/^\S+\@\S+$/', $dest)) {
   return "BADPARAMS";
 }

 $sysconf_ = SystemConfig::getInstance();
 $id = escapeshellarg($id);
 $dest = escapeshellarg($dest);
 $cmd = $sysconf_->SRCDIR_."/bin/force_message.pl $id $dest";
 $res_a = array();
 exec($cmd, $res_a);

 return $res_a[0];
}

/**
 * Add a spam to the white list as newsletter
 * @param $dest string original destination email address
 * @param $sender string original sender
 * @return  string 'OK' or 'NOTOK'
 */
function addToNewslist($dest, $sender) {
  if (!preg_match('/^\S+\@\S+$/', $dest)) {
      return "BADPARAMS";
  }
  if (!preg_match('/^\S*\@\S+$/', $sender)) {
      return "BADPARAMS";
  }
  $sysconf_ = SystemConfig::getInstance();
  $dest = escapeshellarg($dest);
  $sender = escapeshellarg($sender);
  $cmd = $sysconf_->SRCDIR_."/bin/add_to_newslist.pl $dest $sender";
  $res_a = array();
  exec($cmd, $res_a);

  return $res_a[0];
}

/**
 * Add a spam to the white list
 * @param $dest string original destination email address
 * @param $sender string original sender
 * @return  string 'OK' or 'NOTOK'
 */
function addToWhitelist($dest, $sender) {
  if (!preg_match('/^\S+\@\S+$/', $dest)) {
      return "BADPARAMS";
  }
  if (!preg_match('/^\S*\@\S+$/', $sender)) {
      return "BADPARAMS";
  }
  $sysconf_ = SystemConfig::getInstance();
  $dest = escapeshellarg($dest);
  $sender = escapeshellarg($sender);
  $cmd = $sysconf_->SRCDIR_."/bin/add_to_whitelist.pl $dest $sender";
  $res_a = array();
  exec($cmd, $res_a);

  return $res_a[0];
}

/**
 * Add a spam to the black list
 * @param $dest string original destination email address
 * @param $sender string original sender
 * @return  string 'OK' or 'NOTOK'
 */
function addToBlacklist($dest, $sender) {
  if (!preg_match('/^\S+\@\S+$/', $dest)) {
      return "BADPARAMS";
  }
  if (!preg_match('/^\S*\@\S+$/', $sender)) {
      return "BADPARAMS";
  }
  $sysconf_ = SystemConfig::getInstance();
  $dest = escapeshellarg($dest);
  $sender = escapeshellarg($sender);
  $cmd = $sysconf_->SRCDIR_."/bin/add_to_blacklist.pl $dest $sender";
  $res_a = array();
  exec($cmd, $res_a);

  return $res_a[0];
}

/**
 * get the headers lines  of a quarantined spam message
 * @param  $id       string   message id
 * @param  $dest     string   original recipient of the message
 * @return           array    array of message headers lines
 */
function getHeaders($id, $dest) {
  if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
  }
  $matches = array();
  if (! preg_match('/^\S+\@(\S+)$/', $dest, $matches)) {
   return "BADPARAMS";
  }
  $domain = $matches[1];
  $sysconf_ = SystemConfig::getInstance();
  if (!in_array($domain, $sysconf_->getFilteredDomains())) {
    return "BADDOMAIN";
  }

  $filepath = $sysconf_->VARDIR_."/spam/$domain/$dest/$id";
  if (!file_exists($filepath)) {
    return "MESSAGEFILENOTAVAILABLE $filepath";
  }

  $file = file($filepath);

  $ret = array();
  $line = 0;
  $soap_ret = new SoapText();
  while( !preg_match('/^$/',$file[$line])) {
    array_push($ret, utf8_encode($file[$line]));
    $line++;
  }
  $ret = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $ret);
  $soap_ret->text = $ret;
  return $soap_ret;
}

function getMIMEPart($id, $dest, $part) {
  if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
  }
  $matches = array();
  if (! preg_match('/^\S+\@(\S+)$/', $dest, $matches)) {
   return "BADPARAMS";
  }
  $domain = $matches[1];
  $sysconf_ = SystemConfig::getInstance();
  if (!in_array($domain, $sysconf_->getFilteredDomains())) {
    return "BADDOMAIN";
  }

  $filepath = $sysconf_->VARDIR_."/spam/$domain/$dest/$id";
  if (!file_exists($filepath)) {
    return "MESSAGEFILENOTAVAILABLE $filepath";
  }

  $file = file($filepath);
  $msg = "";
  $base64 = 0;
  $in_header = 1;
  foreach ($file as $line) {
    $msg .= $line;
  }

  require_once 'Mail/mimeDecode.php';
  $params['include_bodies'] = true;
  $params['decode_bodies']  = false;
  $params['decode_headers'] = false;
  $params['input']          = $msg;
  $params['crlf']           = "\r\n";

  $structure = Mail_mimeDecode::decode($params);
  $types = extractParts($structure, $part);
  $soap_ret = new SoapText();
  $soap_ret->text = array(utf8_encode($types));
  return $soap_ret;
}

/**
 * get the n first lines of the body of a quarantined spam message
 * @param  $id       string   message id
 * @param  $dest     string   original recipient of the message
 * @param  $nblines  numeric  number of lines to retrieve
 * @return           array    array of message lines
 */
function getBody($id, $dest, $nblines) {

  if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
  }
  if (!is_numeric($nblines)) {
   return "BADPARAMS";
  }
  $matches = array();
  if (! preg_match('/^\S+\@(\S+)$/', $dest, $matches)) {
   return "BADPARAMS";
  }
  $domain = $matches[1];
  $sysconf_ = SystemConfig::getInstance();
  if (!in_array($domain, $sysconf_->getFilteredDomains())) {
    return "BADDOMAIN";
  }

  $filepath = $sysconf_->VARDIR_."/spam/$domain/$dest/$id";
  if (!file_exists($filepath)) {
    return "MESSAGEFILENOTAVAILABLE $filepath";
  }
  $file = file($filepath);
  $ret = array();

  $in_header = 1;
  $base64 = 0;
  $pos = 1;
  foreach ($file as $line) {
    if ($pos > $nblines) { break; }
    if (preg_match('/Content-Transfer-Encoding:\s+base64/', $line)) {
      $ret = array();
      $base64 = 1;
    }
    if ($in_header && preg_match('/^\s*$/', $line)) {
      $in_header = 0;
      continue;
    }
    if ($in_header > 0) { continue; }
    if ($base64) {
      if (preg_match('/[:\-.\ ]/', $line) && !preg_match('/^\s*$/', $line)) {
        array_push($ret, utf8_encode(base64_decode($line)));
        $pos++;
      }
    } else {
      array_push($ret, utf8_encode($line));
      $pos++;
    }
  }

  $soap_ret = new SoapText();
  $soap_ret->text = $ret;
  return $soap_ret;
}


/**
 * return the array of reasons why the message has been detected as a spam
 * @param  $id   string   message id
 * @param  $dest string   original destination email address
 * @param  $lang string   language desired
 * @return       array    array of reasons on success, error code on failure
 */
function getReasons($id, $dest, $lang) {

  if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
 }
 if (! preg_match('/^\S+\@\S+$/', $dest)) {
   return "BADPARAMS";
 }
 $sysconf_ = SystemConfig::getInstance();
 $id = escapeshellarg($id);
 $dest = escapeshellarg($dest);
 $lang = escapeshellarg($lang);

 $cmd = $sysconf_->SRCDIR_."/bin/get_reasons.pl $id $dest $lang";
 $res = "";
 exec($cmd, $res);
 $ret = array();
 if (!is_array($res)) {
    return $res;
 }
 $soap_ret = new SoapReasons();
 foreach($res as $line) {
  array_push($ret, utf8_encode($line));
 }
 $soap_ret->reasons = $ret;
 return $soap_ret;
}

/**
 * send a message to the analysis center
 * @param  $id   string   message id
 * @param  $dest string   original destination email address
 * @return       array    array of reasons on success, error code on failure
 */
function sendToAnalyse($id, $dest) {
 if (! preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id)) {
   return "BADPARAMS";
 }
 if (! preg_match('/^\S+\@\S+$/', $dest)) {
   return "BADPARAMS";
 }
 $sysconf_ = SystemConfig::getInstance();
 $id = escapeshellarg($id);
 $dest = escapeshellarg($dest);

 $cmd = $sysconf_->SRCDIR_."/bin/send_to_analyse.pl $id $dest";
 $res_a = array();
 exec($cmd, $res_a);
 return $res_a[0];
}

function extractParts($structure, $part) {
  $types = "";

  foreach ($structure->parts as $lpart) {
    if ($lpart->ctype_primary == "multipart") {
      if ($part == '0') {
        $types .= extractParts($lpart, $part);
      } else {
        $types = extractParts($lpart, $part);
      }
    } else {
      if ($part == '0') {
        $types .= "-".$lpart->ctype_primary."/".$lpart->ctype_secondary;
      }
      if ($lpart->ctype_primary."/".$lpart->ctype_secondary == $part) {
         if (isset($lpart->ctype_parameters['charset'])) {
           return "=?".$lpart->ctype_parameters['charset']."?Q?".$lpart->body."?=";
         }
         return $lpart->body;
      }
    }
  }
  $types = preg_replace('/^-/', '', $types);
  return $types;
}
?>
