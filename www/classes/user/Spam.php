<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * This is the class is mainly a data wrapper for the spam objects
 */
class Spam {

 /**
  * datas of the spam
  * @var array
  */
 private $datas_ = array(
                      'exim_id'         => '',
                      'to_user'         => '',
                      'to_domain'       => '',
                      'date_in'         => '',
                      'time_in'         => '',
                      'sender'          => '',
                      'M_subject'       => '',
                      'forced'          => 0,
                      'M_score'         => 0,
                      'M_rbls'          => '',
                      'M_prefilter'     => '',
                      'M_globalscore'   => 0,
                      'store_slave'     => 0,
                      'is_newsletter'   => '0');
/**
 * body of the spam
 * @var string
 */           
private $body_ = "";

/**
 * plain headers of the spam
 * @var string
 */           
private $plain_headers_ = "";
private $unrefined_headers_ = "";

/**
 * headers of the spam
 * @var array
 */
private $headers_ = array();

private $headersfields_ = array();
/**
 * set of filter rules
 * @var array
 */
private $ruleset_ = array();

/**
 * mime part types
 * @var array
 */
private $parts_type_ = array();
                     
/**
 * constuctor
 */
public function __construct() {
}

/**
 * set a data
 * @param  $field  string  data field name
 * @param  $value  mixed   data value
 * @return         bool    true on success, false on failure
 */
private function setData($field, $value) {
  if ($field == 'to' && preg_match('/(\S+)\@(\S+)/', $value, $matches)) {
    $this->datas_['to_user'] = $matches[1];
    $this->datas_['to_domain'] = $matches[2];
  }
  if (isset($this->datas_[$field])) {
    $this->datas_[$field] = $value;
    return true;
  }
  return false;
}

/**
 * get data
 * @param  $field  string  data field name
 * @return         mixed   data value
 */
public function getData($field) {
   if ($field == 'to') {
     return $this->datas_['to_user']."@".$this->datas_['to_domain'];
   }
   if (isset($this->datas_[$field])) {
    return $this->datas_[$field];
   }
   return null;
 }

public function getAllData() {
  $data = array();
  foreach ($this->datas_ as $key => $value) {
    $data[$key] = $this->getData($key);
  }
  return $data;
} 
 
/**
 * get data cleaned from html code
 * @param  $field  string  data field name
 * @return         mixed   cleaned data value
 */
 public function getCleanData($field) {
    $split_fields = array(
        'M_subject' => 80,
        'to' => 50,
        'sender' => 50
    );
    $data = $this->getData($field);
    $data = iconv_mime_decode($data, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
    $ret = htmlentities($data, ENT_COMPAT, "UTF-8");
    if (isset($split_fields[$field])) {
      $ret = substr($ret, 0, $split_fields[$field]);
    }
    return $ret;
 }

/**
 * load data into spam from an array
 * @param  $datas  array  datas of spam (probably fetched from database)
 * @return         bool   true on success, false on failure
 */
public function loadFromArray($datas) {
  if (!is_array($datas))  {
    return false;
  }
  foreach ($datas as $key => $value) {
    $this->setData($key, $value);
  }
  return true;  
}

/**
 * load messages datas from database
 * 
 */
public function loadDatas($id, $dest) {
  if (!preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2})$/', $id) || !preg_match('/^\S+\@\S+$/', $dest)) {
    return false;
  }
  
  require_once('helpers/DM_MasterSpool.php');
  $db = DM_MasterSpool::getInstance();
  $clean_id = $db->sanitize($id);
    
  // build the query
  $table = "spam";
  if (preg_match('/^(\S)/', $dest, $matches)) {
    if (is_numeric($matches[1])) {
      $table .= "_num";
    } elseif (preg_match('/^[a-zA-Z]$/', $matches[1])) {
      $table .= "_".$matches[1];
    } else {
      $table .= "_misc";
    }
  }
  $query = "SELECT * FROM $table WHERE exim_id='$id'";
  $res = $db->getHash($query);
  if (!$res || ! is_array($res)) {
  	return false;
  }

  foreach ($res as $key => $value) {
  	$this->setData($key, $value);
  }
  $this->setData('to', $dest);
  return true;
}

/**
 * load headers and body from slave
 * @param $id    string   message id
 * @param $dest  string   destination address
 * @param $slave string   slave host name
 * @return       bool     true on success, false on failure
 */
public function loadHeadersAndBody() {
  require_once("system/SystemConfig.php"); 
  require_once("system/Soaper.php");
  require_once("system/SoapTypes.php");

  if ($this->body_ != "" and !empty($this->headers_)) {
    return true;
  }
  
  $sysconf = Systemconfig::getInstance(); 
  $slave = $sysconf->getSlaveName($this->getData('store_slave'));
  if ($slave == "") {
    return false;
  }
  $dest  = $this->getData('to');
  $soaper = new Soaper();
  if (!$soaper->load($slave)) {
    echo "CANNOTCONNECTSOAPER";
    return false;
  }

  $soap_res = $soaper->queryParam('getHeaders', array($this->getData('exim_id'), $dest, 30));
  if (!is_object($soap_res) || !is_array($soap_res->text)) {
    echo "CANNOTLOADMESSAGEHEADERS";
    return false;
  } else {
    $headers = $soap_res->text;
  }
 
  $soap_res = $soaper->queryParam('getBody', array($this->getData('exim_id'), $dest, 30));
  if (is_object($soap_res) && is_array($soap_res->text)) {
    $body = $soap_res->text;
    foreach ($body as $line) {
      $line = utf8_decode($line);
      $tab = str_split($line, 72);
      foreach ($tab as $l) {
        $this->body_ .= $l;
      }
    }
  }
  ## remove html tags
  #$this->body_ = preg_replace('/<br>/', "\n", $this->body_);
  #$this->body_ = preg_replace('/<[^>]+>/', '', $this->body_);
  
  $last_header="";
  $matches = array();
  if (empty($headers)) {
  	return false;
  }
  
  $lh = "";
  foreach ($headers as $line) {
    $this->unrefined_headers_ .= $line;
    if (strlen($line) > 72) {
      $l = substr($line, 0, 72);
      $l2 = substr($line, 72);
      $this->plain_headers_ .= $l."...\n ...".$l2;
    } else {
      $this->plain_headers_ .= $line;
    }

    if (preg_match('/^([A-Z]\S+):(.*)/', $line, $matches)) {
        if (!isset($this->headers_[$matches[1]])) {
          $this->headers_[$matches[1]] = '';
        }
        if ($matches[1] == "Received") {
           $this->headers_[$matches[1]] .= $matches[1].": ".$matches[2];
        } else {
          $this->headers_[$matches[1]] .= $matches[2];
        }
        if ($last_header != "") {
          array_push($this->headersfields_, array($last_header, $lh));
        }
        $last_header=$matches[1];
        $lh = $matches[2];
    } else {
        $line = preg_replace('/\n/', '', $line);
        $lh .= "\n[tab]".$line;
    	$this->headers_[$last_header] .= $line;
    }
  }
  if ($last_header != "" && $lh != "") {
    array_push($this->headersfields_, array($last_header, $lh));
  }
  
  $soap_res = $soaper->queryParam('getMIMEPart', array($this->getData('exim_id'), $this->getData('to'), 0));
  if (is_object($soap_res) && is_array($soap_res->text)) {
    $this->parts_type_ = split('-', $soap_res->text[0]);
  }
  if (count($this->parts_type_) > 0) {
    $b = $this->getMIMEPartAsText('text/plain', $soaper);
    if (preg_match('/^\s*$/', $b)) {
      $b = $this->getMIMEPartAsText('text/html', $soaper);
    } 
    if (!preg_match('/^\s*$/', $b)) {
     $this->body_ = $b;
    }
  } 
  
  return true;
}

/**
 * return the headers
 * @return  array
 */
public function getHeadersArray() {
  return $this->headers_;
}

public function getRawHeaders() {
  return $this->plain_headers_;
}

public function getUnrefinedHeaders() {
  return $this->unrefined_headers_;
}

public function getPartsType() {
  return $this->parts_type_;
}

public function getRawBody() {
  return $this->body_;
}

/**
 * get the filter rules hit
 * @return  array  array of reason with score
 */
public function getReasons() {
  $header = $this->headers_['X-MailCleaner-SpamCheck'];
  $full = preg_replace('/\n/', ' ', $header);
  if (!preg_match('/(?:SpamAssassin|Spamc) \((.*\))/', $full, $matches)) {
  	return array();
  }
  $set = array();
  $rules_str = $matches[1];
  $rules = split(',', $rules_str);
  foreach ($rules as $rule) {
  	if (preg_match('/^\s*([A-Za-z_0-9]+)\ (-?\d+(?:\.\d+)?)/', $rule, $matches)) {
  	  array_push($this->ruleset_, array('tag' => $matches[1], 'score' => $matches[2], 'description' => $matches[1]));
  	}
  }
  $this->setRulesDescription();
  return $this->ruleset_;
}

private function setRulesDescription() {    
  global $lang_;
  
  if (empty($this->ruleset_)) {
  	return true;
  }
  
  $SA_BASE_PATH = "/var/lib/spamassassin/*/updates_spamassassin_org/";
  
  $i = 0;
  foreach ($this->ruleset_ as $rule) {
    $tag = $rule['tag'];
    $cmd = "egrep \"^\s*(describe|description).*$tag\" ".$SA_BASE_PATH."/*.cf 2>&1";
    $res = array();
    $ret = 0;
    exec($cmd, $res, $ret);
    foreach ($res as $line) {
      if ( preg_match("/text\_en.cf/", $line) || !preg_match("/text\_[a-z][a-z].cf/", $line)) {
        if (preg_match("/(describe|description)\s+$tag\s+(.*)/", $line, $matches)) {
          $this->ruleset_[$i]['description'] = substr($matches[2], 0, 80);
        }
      }
    }
    $i++;
  }
  
  $sysconf = SystemConfig::getInstance();
  $MC_BASE_PATH = $sysconf->SRCDIR_."/share/spamassassin";
  $i = 0;
  foreach ($this->ruleset_ as $rule) {
    $tag = $rule['tag'];
    $cmd = "egrep \"(describe|description).*$tag\" ".$MC_BASE_PATH."/*.cf";
    $res = array();
    $ret = 0;
    exec($cmd, $res, $ret);
    foreach ($res as $line) {
      if ( preg_match("/text\_en.cf/", $line) || !preg_match("/text\_[a-z][a-z].cf/", $line)) {
        if (preg_match("/(describe|description)\s+$tag\s+(.*)/", $line, $matches)) {
          $this->ruleset_[$i]['description'] = substr($matches[2], 0, 80);
        }
      }
    }
    $i++;
  }
  
  return true;
}

private function getMIMEPartAsText($part, $soaper) {
  $ret = "";

  $soap_res = $soaper->queryParam('getMIMEPart', array($this->getData('exim_id'), $this->getData('to'), $part));
  if (is_object($soap_res) && is_array($soap_res->text)) {
    foreach ($soap_res->text as $l) {
      $tab = str_split($l, 72);
      foreach ($tab as $line) {
        $line = utf8_decode($line);
        $ret .= $line."\n";
      }
    }
  }
  
  #$ret = preg_replace('/<br>/', '\n', $ret);
  #$ret = preg_replace('/<[^>]+>/', '', $ret);
  return $ret;
}

public function getGlobalScore($t) {
  $empty = $t->getDefaultValue('BULLETEMPTY_IMG');
  $filled = $t->getDefaultValue('BULLETFILLED_IMG');
  if (!isset($empty) || !isset($filled) || $empty == "" || $filled == "") {
  	return $this->getCleanData('M_globalscore');
  }
  $ret = "";
  $score = $this->getData('M_globalscore');
  for ($i=1; $i < 5; $i++) {
  	if ($score >= $i) {
  	  $ret .= $filled;
  	} else {
  	  $ret .= $empty;
  	}
  }
  return $ret;
}

public function getFormatedDate() {
  global $lang_;
  
  require_once('Zend/Locale.php');
  require_once('Zend/Registry.php');
  require_once('Zend/Date.php');
  
  $locale = new Zend_Locale($lang_->getLanguage());
  Zend_Registry::set('Zend_Locale', $locale);
  $date = new Zend_Date();
  $date->set($this->getCleanData('date_in'), 'yyyy-MM-dd');
  $date->set($this->getCleanData('time_in'), Zend_Date::TIMES);
  if ($date->isToday() && $lang_->print_txt('TODAY')!= '') {
  	return $lang_->print_txt('TODAY')." ".$date->get(Zend_Date::TIMES);
  }
  if ($date->isYesterday() && $lang_->print_txt('YESTERDAY')!= '') {
  	return $lang_->print_txt('YESTERDAY')." ".$date->get(Zend_Date::TIMES);
  }
  return $date;
}

public function setReplacements($template, $replace) {
  global $lang_;

  $generalinfos = array(
     'SENDER' => $this->getData('sender'),
     'TO' => $this->getCleanData('to'),
     'DATE' => $this->getFormatedDate(),
     'SUBJECT' => $this->getCleanData('M_subject'),
     'PREFILTERHITS' => $this->displayGlobalValue($this->getData('M_prefilter')),
     'BLACKLISTS' => $this->displayGlobalValue($this->getData('M_rbls')),
     'FITLERSCORE' => $this->displayGlobalValue($this->getData('M_score')),
     'PARTS' => $this->getMIMEPartsType(),
     'STORESLAVE' => $this->getData('store_slave')
  );
  
  if (!empty($_SESSION['user'])) {
    $session = unserialize($_SESSION['user']);
    $userId = $session->getID();

    if ($this->getData('is_newsletter') == 1 && !empty($userId)) {
          require_once ('helpers/DM_MasterConfig.php');
          $db = DM_MasterConfig::getInstance();

          $id = $this->getCleanData('exim_id');
          $sender = $this->getCleanData('sender');
          $recipient = $this->getCleanData('to_user').'@'.$this->getCleanData('to_domain');
          $query = "select type from wwlists where sender = '".$sender."' and recipient = '".$recipient."'";
          $result = $db->getHash($query);

          if (empty($result)) {
	      $hrefNews = "/fm.php?id=" . $id . "&a=" . urlencode($recipient) . '&s=' . $this->getData('store_slave') . "&n=1";
              $generalinfos['NEWSLETTERMODULE'] = sprintf('<a data-id="%s" href="%s" class="allow">%s</a>', $this->getData('exim_id'), $hrefNews, $lang_->print_txt('NEWSLETTERACCEPT'));
          }
    }
  }
  
  $this->loadHeadersAndBody();
  
  $general_str = $template->getTemplate('GENERALINFO');
  $full_generalinfos = "";
  foreach ($generalinfos as $key => $value) {
  	$str = str_replace('__INFO_NAME__', $lang_->print_txt($key), $general_str);
    $str = str_replace('__INFO_VALUE__', $value, $str);
    $full_generalinfos .= $str;
  }
  $replace['__GENERALINFO_LIST__'] = $full_generalinfos;
  
  $replace['__MSG_BODY__'] = $this->getFormatedBody();
  $t = $template->getTemplate('MSGHEADERS');
  $replace['__MSGHEADERS_LIST__'] = $this->getHeadersInTemplate($t);
  $t = $template->getTemplate('SARULES');
  $replace['__SARULES_LIST__'] = $this->getSARulesInTemplate($t);
  
  return $replace;
}

public function getFormatedBody() {
  $body = $this->getRawBody();
  $matches = array();
  $charset = "=?UTF-8?Q?";

  $lines = preg_split("/\n/", $body);
  $fullbody = ""; 
  $csdefined = 0;
  foreach ($lines as $line) {
    if (!$csdefined && preg_match('/^(\=\?[^?]{3,15}\?Q\?)/', $line, $matches)) {
      $csdefined = 1;
      $charset = $matches[1];
      if(array_key_exists(2, $matches))
        $line = $matches[2];
    }
    if (preg_match('/(.*)\?\=$/', $line, $matches)) {
      $line = $matches[1];
    }
    $line = $charset.$line."?=";
    $dline = @iconv_mime_decode($line, ICONV_MIME_DECODE_STRICT, 'UTF-8');
    $fullbody .= $dline."\n";
  }
  #return $fullbody;
  $txt = htmlentities($fullbody, ENT_COMPAT, 'UTF-8', 0);
  #$txt = $fullbody;
  $txt = preg_replace('/\n/', '<br />', $txt);
  $txt = preg_replace("/&lt;/", "<span class=\"htmltag\">&lt;", $txt);
  $txt = preg_replace("/&gt;/", "&gt;</span>", $txt);
  return $txt;
}

private function getHeadersInTemplate($template) {
  $full_str = "";
  
  foreach ($this->headersfields_ as $head) {
    $key = $head[0];
    $fvalue = $head[1];
    
    
    $key = htmlentities($key);
    $fvalue = htmlentities($fvalue);
    $key = preg_replace('/\n/', '<br />', $key);
    $fvalue = preg_replace('/\n/', '<br />', $fvalue);
    $fvalue = str_replace('[tab]', '&nbsp;&nbsp;&nbsp;', $fvalue);
    $str = str_replace('__INFO_NAME__', $key, $template);
    $str = str_replace('__INFO_VALUE__', $fvalue, $str);
    $full_str .= $str;
  }
   return $full_str;
}

private function getSARulesInTemplate($template) {
  $rules = $this->getReasons();
  $full_str = "";
  
  foreach ($rules as $rule) {
  	$str = str_replace('__INFO_NAME__', number_format($rule['score'],1, '.', ''), $template);
    $str = str_replace('__INFO_VALUE__', $rule['description'], $str);
    $full_str .= $str;
  }
  return $full_str;
}

private function displayGlobalValue($value) {
  global $lang_;
  if ($value == "" || $value== "0.000") {
    return $lang_->print_txt('NONE');
  }
  if (is_numeric($value)) {
    return number_format($value, 1, '.', '');
  }
  return htmlentities($value);
}

private function getMIMEPartsType() {
  global $lang_;
  
  $parts = $this->getPartsType();
  if (count($parts) < 1) {
    return $lang_->print_txt('NONE');
  }
  if (count($parts) == 1 && $parts[0] == "") {
    return "1 (text)";
  }
  $ret = count($parts) . " (";
  foreach ($parts as $part) {
    switch ($part) {
        case "text/plain":
          $ret .= ",text";
          break;
        case "text/html":
          $ret .= ",html";
          break;
        default:
          $ts = split('/', $part);
          $ret .= ",$ts[1]";
    }
  }
  $ret .= ")";
  $ret = preg_replace('/\(,/', '(', $ret);
  return $ret;
}
}
?>
