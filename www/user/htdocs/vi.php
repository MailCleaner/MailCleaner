<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the reasons list display page
 */  
require_once('variables.php');
require_once("view/Language.php");
require_once("user/Spam.php");
require_once("view/Template.php");

$sysconf = SystemConfig::getInstance();
$viewrules = 0;
$viewbody = 0;
$viewheaders = 0;
$firstopen = 1;

## check params
if (!isset($_GET['id']) || !isset($_GET['a'])) {
  die ("BADPARAMS");
}

if (isset($_GET['vr']) || isset($_GET['vh']) || isset($_GET['vb'])) {
  $firstopen = 0;
}
$spam = new Spam();
if (! $spam->loadDatas($_GET['id'], $_GET['a'])) {
  die ("CANNOTLOADMESSAGE");
}

if (isset($_GET['vr']) && $_GET['vr'] == 1) {
  $viewrules = 1;
}
if (isset($_GET['vh']) && $_GET['vh'] == 1) {
  $viewheaders = 1;
}
if (isset($_GET['vb']) && $_GET['vb'] == 1) {
  $viewbody = 1;
}

if (! $spam->loadHeadersAndBody()) {
  die ("CANNOTLOAD HEADERSANDBODY");
}

// create view
$template_ = new Template('vi.tmpl');
if ($spam->getData('M_score') == "0.000")
  $template_->setCondition('SCORENOTNULL', 0);
else {
  $template_->setCondition('SCORENOTNULL', 1);
}
if ($viewrules) {
  $template_->setCondition('VIEWSCORE', 1);
} else {
  $template_->setCondition('VIEWSCORE', 0);
}
$template_->setCondition('FIRSTOPEN', $firstopen);

// prepare replacements
$replace = array(
   '__MSG_ID__' => $spam->getData('exim_id'),
   '__TO__' => addslashes($spam->getCleanData('to')),
   '__FROM__' => urlencode($spam->getCleanData('sender')),
   '__DATE__' => $spam->getCleanData('date_in')." ".$spam->getData('time_in'),
   '__SUBJECT__' => htmlentities($spam->getCleanData('M_subject')),
   '__PREFILTERS__' => htmlentities(displayHit($spam->getData('M_prefilter'))),
   '__RBLS__' => htmlentities(displayHit($spam->getData('M_rbls'))),
   '__FILTERSCORE__' => displayRules(),
   '__TOTAL_SCORE__' => htmlentities(displayHit($spam->getData('M_score'))),
   '__SCOREARROWGIF__' => getArrowGif($viewrules),
   '__SCOREARROWLINK__' => getLink('score'),
   '__HEADERSARROWGIF__' => getArrowGif($viewheaders),
   '__HEADERSARROWLINK__' => getLink('headers'),
   '__BODYARROWGIF__' => getArrowGif($viewbody),
   '__BODYARROWLINK__' => getLink('body'),
   '__HEADERS__' => displayHeaders(),
   '__BODY__' => displayBody(),
   '__NEWS__' => $spam->getData('is_newsletter'),
   '__PARTS__' => getMIMEParts(),
   '__STORESLAVE__' => $spam->getData('store_slave')
);

$replace = $spam->setReplacements($template_, $replace);
//display page
$template_->output($replace);

function displayHit($value) {
  global $lang_;
  if ($value == "" || $value== "0.000") {
  	return $lang_->print_txt('NONE');
  }
  if (! is_numeric($value) ) {
      return $value;
  }
  return number_format($value, 1, '.', '');
}

function displayRules() {
  global $spam;
  global $template_;
  global $viewrules;
  
  if (! $viewrules) {
  	return '';
  }
  $t = $template_->getTemplate('RULES');
  $list = $spam->getReasons();
  $full = "";
  $i = 0;
  foreach ($list as $tag => $value) {
  	$line = $t;
    $line = preg_replace('/__RULE__/', htmlentities($value['description']), $line);
    $line = preg_replace('/__SCORE__/', htmlentities($value['score']), $line);
    
    if ($i++ % 2) {
      $line = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$1", $line);
    } else {
      $line = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$2", $line);
    }
    
    $full .= $line;
  }
  return $full;
}

function displayHeaders() {
  global $viewheaders;
  global $spam;
  
  if (!$viewheaders) {
  	return '';
  }
  $txt = htmlentities($spam->getRawHeaders());
  $txt = preg_replace('/\n/', '<br>', $txt);
  $txt = preg_replace('/\b([A-Z][-a-zA-Z0-9]+): /','<b>$1</b>:', $txt);
  $txt = preg_replace('/\s/', '&nbsp;', $txt);
  return $txt;
}

function displayBody() {
  global $viewbody;
  global $spam;
  
  if (!$viewbody) {
    return '';
  }
  $txt = htmlentities($spam->getRawBody());
  $txt = preg_replace('/\n/', '<br>', $txt);
  $txt = preg_replace("/&lt;/", "<font color=\"#CCCCCC\">&lt;", $txt);
  $txt = preg_replace("/&gt;/", "&gt;</font>", $txt);
  return $txt;
}

function getArrowGif($var) {
  if ($var) {
    return 'downarrow.gif';
  }
  return 'rightarrow.gif';
}

function getLink($var) {
  global $viewrules;
  global $viewheaders;
  global $viewbody;
  global $spam;
  global $lang_;
  
  $baseurl = "vi.php?id=".$spam->getData('exim_id')."&a=".$spam->getData('to')."&s=".$_GET['s'];
  $baseurl .= "&lang=".$lang_->getLanguage();
  $vr = $viewrules;
  if ($var == 'score') {
    if ($viewrules) {
      $vr = 0;
    } else {
  	  $vr = 1;
    }
  }
  $vh = $viewheaders;
  if ($var == 'headers') {
    if ($viewheaders) {
      $vh = 0;
    } else {
      $vh = 1;
    }
  }
  $vb = $viewbody;
  if ($var == 'body') {
    if ($viewbody) {
      $vb = 0;
    } else {
      $vb = 1;
    }
  }
  $baseurl .= "&vr=$vr&vh=$vh&vb=$vb";
  return $baseurl;    
}

function getMIMEParts() {
  global $spam;
  global $lang_;
  
  $parts = $spam->getPartsType();
  if (count($parts) < 1) {
  	return $lang_->print_txt('NONE');
  }
  if (count($parts) == 1) {
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

?>
