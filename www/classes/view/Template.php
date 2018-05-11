<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * needs some defaults
 */
require_once ("system/SystemConfig.php");

/**
 * this class takes care of the page output according to the template file
 */
class Template {
    
  /**
   * file use as template
   * @var string
   */
  private $file_;
  
  /**
   * content of the template
   * @var array
   */
  private $content_ = array();
  
  /**
   * template model to be used
   * @var string
   */
  private $model_;
  
  /**
   * if conditions
   * @var array
   * @todo set as private !
   */
  private $ifs = array (); 
 
  /**
   * conditions
   * @var array
   */
  private $conditions_ = array();
  /**
   * defaults value set in template
   * @var array
   */  
  private $defaults_ = array();

  /**
   * sub-templates list
   * @var array
   */
  private $templates_ = array();
  
/**
 * constructor
 * @param $tmpl  string  template model to use
 */
public function __construct($tmpl) {
  global $lang_;
  global $user_;

  $sysconf_ = SystemConfig :: getInstance();

  // first get default template
  $this->model_ = $sysconf_->getPref('default_template');
  // then get default domain template
  $default_dom = $sysconf_->getPref('default_domain');
  if ($default_dom != "") {
    require_once('domain/Domain.php');
    $domain = new Domain();
    if ( $domain->load($default_dom)) {
     $this->model_ = $domain->getPref('web_template');
    }
  }
  // then get domain template
  if (isset ($user_) && $user_ instanceof User) {
    require_once('domain/Domain.php');
    $domain = new Domain();
    if ( $domain->load($user_->getPref('domain'))) {
      $this->model_ = $domain->getPref('web_template');
    } 
  }

  // if an address as been passed through url
  $matches = array();
  if (isset($_GET['a']) && preg_match('/^(\S+)\@(\S+)$/', $_GET['a'], $matches)) {
    $dom = $matches[2];
    if (in_array($dom, $sysconf_->getFilteredDomains())) {
      require_once('domain/Domain.php');
      $domain = new Domain();
      $domain->load($dom);
      $this->model_ = $domain->getPref('web_template');
    }
  }

  // admin only as default model for now
  $mode = 'user';
  if (isset ($_SESSION['admin'])) {
    $this->model_ = 'default';
    $mode = 'admin';
  }

  // set template file to use
  $file = "templates/".$this->model_."/".$tmpl;
  // but return to default if it does not exists
  if (file_exists($file)) {
    $this->file_ = $file;
  } else {
    $this->file_ = "templates/default/".$tmpl;
    $this->model_ = "default";
  }

  // just in case...
  if (!isset($lang_)) {
    if (isset ($_SESSION['admin'])) {
      $lang_ = Language::getInstance('user');
    } else {
      $lang_ = Language::getInstance('user');
    }
  }
  
  // fetch the template language file to override language texts
  $template_lang = $sysconf_->SRCDIR_."/www/".$mode."/htdocs/templates/".$this->model_."/lang/".$lang_->getLanguage()."/texts.php";
  if (file_exists($template_lang)) {
    $txt = array ();
    include ($template_lang);
    foreach ($txt as $t => $str) {
      $lang_->setTextValue($t, $str);
    }
  }
  
  // pre-process template
  $lines = file($this->file_);
  $this->parseLines($lines);
}

/**
 * return the template file to be used
 * @return string template filename
 */
public function getFile() {
  return $this->file_;
}
  
/**
 * return the template model to be used
 * @return string template filename
 */
public function getModel() {
  return $this->model_;
}
  
/**
 * main output method, will send html page to browser
 * @param  $replace  array   list of tag with replacement to use
 * @return           boolean true on success, false on failure
 */
public function output($replace) {
  global $lang_;
  $sysconf_ = SystemConfig :: getInstance();

  $template = file($this->file_);

  // first set mime type
#  header("Vary: Accept");
#  header("Content-Type: application/xhtml+xml; charset=utf-8");
#  if (stristr($_SERVER[HTTP_ACCEPT], "application/xhtml+xml")) 
#    header("Content-Type: application/xhtml+xml; charset=utf-8");
#  else
#    header("Content-Type: text/html; charset=utf-8");
    
  // loop through template file
  foreach ($this->content_ as $line) {

    $matches = array ();

    // process line
    $this->processLine($line, $replace);
  }
  return true;
}

public function addTMPLFile($name, $file) {
  if ($file == "") {
  	return true;
  }
  $this->added_tmpl_[$name] = $file;
  // RE-pre-process template
  $this->content_ = array();
  $lines = file($this->file_);
  $this->parseLines($lines);
}

/**
 * process a template line
 * @param $line    string  template line to be processed
 * @param $replace array   list of tag with replacements
 * @return         boolean true on success, false on failure
 */
private function processLine($line, $replace) {
  global $sysconf_;
  global $lang_;
  // global between lines
  global $if_hidden;
  global $hidden_condition;

  $line = rtrim($line);

  $matches = array ();
  // ignore comments
  if (preg_match('/^\#/', $line, $matches)) {
    return;
  }
  
  if (preg_match('/__REDIRECT__\s+(\S+)/', $line, $matches)) {
    if (isset($replace[$matches[1]])) {
      header("Location: /".$replace[$matches[1]]);
      exit();
    }
    header("Location: /".$matches[1]);
    exit();
  }
 
  // ignore hidden condition until fi or else
  if ($if_hidden && 
        ! preg_match("/\_\_(FI|ELSE)\_\_\s+$hidden_condition/", $line)) {
    return;
  }
  // replace __LANG_xxx_ tags with the corresponding text
  if (preg_match('/\_\_LANG\_([A-Z0-9]+)\_\_/', $line, $matches)) {
    $line = preg_replace('/\_\_LANG\_([A-Z0-9]+)\_\_/', $lang_->print_txt($matches[1]), $line);
  }
  if (preg_match('/\_\_LANGJS\_([A-Z0-9]+)\_\_/', $line, $matches)) {
    $line = preg_replace('/\_\_LANGJS\_([A-Z0-9]+)\_\_/', preg_replace('/\'/', '\\\'', $lang_->print_txt($matches[1])), $line);
  }
  // replace __LANG__ tag with language actually used
  $line = preg_replace('/\_\_LANG\_\_/', $lang_->getLanguage(), $line);

  // replace date formatting
  if (preg_match('/\_\_FORMAT\_DATE\(([^),]+)\s*,\s*([^),]+)\s*,\s*([^),]+)\s*,\s*([^),]+)\s*\)\_\_/', $line, $matches)) {
    $line =  preg_replace('/\_\_FORMAT\_DATE\(([^),]+)\s*,\s*([^),]+)\s*,\s*([^),]+)\s*,\s*([^),]+)\s*\)\_\_/', $this->formatDate($matches), $line);
  }

 
   $line = str_replace('__TEMPLATE_PATH__', "templates/".$this->model_, $line);

  // check for if/else/fi conditions
  if (preg_match('/\_\_IF\_\_\s+(\S+)/', $line, $matches)) {
    $hidden_condition = $matches[1];
    if ($this->getCondition($matches[1])) {
      $if_hidden = false;
    } else {
      $if_hidden = true;
    }
    return;
  }

  if (preg_match('/\_\_ELSE\_\_/', $line, $matches)) {
    if (!$if_hidden) {
      $if_hidden = true;
    } else {
      $if_hidden = false;
    }
    return;
  }
  
  if (preg_match('/\_\_FI\_\_\s+(\S+)/', $line, $matches)) {
    $if_hidden = false;
    return;
  }

  // replace tag with values
  foreach ($replace as $tag => $value) {
    $line = str_replace($tag, $value, $line);
  }

  // replace relative links
 // $line = preg_replace('/href="([^\/])/', 'href="templates/'.$this->model_.'/\\1', $line);
  // fix javascript exceptions
 // $line = preg_replace('/href="(\S+)javascript:/', 'href="javascript:', $line);
  
  // and finally output line if not hidden
  if (!$if_hidden) {
    echo $line."\n";
  }
}

/**
 * process a text for simple replacements
 * @param  $text      string  text to process
 * @param   $replace  array   list of replacements
 * @return            string  processed text
 */
public function processText($text, $replace) {
   $text = preg_replace('/([^\/])images\//', "$1"."templates/".$this->model_."/images/", $text);
   $text = preg_replace('/([^\/])styles\//', "$1"."templates/".$this->model_."/styles/", $text);
   $text = preg_replace('/([^\/])scripts\//', "$1"."templates/".$this->model_."/scripts/", $text);
   $text = preg_replace('/([^\/])css\//', "$1"."templates/".$this->model_."/css/", $text);
   
  
  // replace tag with values
  foreach ($replace as $tag => $value) {
    $line = str_replace($tag, $value, $line);
  }
  
  return $text;  
}

/**
 * return the value of a default  set in the template
 * @param  $tag  string  tag name
 * @return       string  tag default value
 */
public function getDefaultValue($tag) {
  if (isset($this->defaults_[$tag])) {
    return trim($this->defaults_[$tag]);
  }
  return "";
}

/**
 * return the sub-template
 * @param  $tag  string  sub template name
 * @return       string  sub template string
 */
public function getTemplate($tag) {
  if (isset($this->templates_[$tag])) {
    return $this->templates_[$tag];
  }
  return "";
}

/**
 * set a condition
 * @param  $tag   string  condition tag
 * @param  $value boolean condition value
 * @return        boolean true on success, false on failure
 */
public function setCondition($tag, $value) {
  $this->conditions_[$tag] = $value;
  return true;
}

/**
 * get a condition value
 * @param  $tag  string  condition tag
 * @return       boolean condition value
 */
private function getCondition($tag) {
  if (isset($this->conditions_[$tag])) {
    return $this->conditions_[$tag];
  }
  return false;
}

/**
 * parse bunch of line and add it to content if needed
 * @param  $lines  array   lines to be parsed
 * @return         boolean true on success, false on failure
 */
private function parseLines($lines) {
  $matches = array();
  $hidden = false;
  
  foreach ($lines as $line) {
    
    // check for included files
    if (preg_match('/\_\_INCLUDE\_\_\((\S+)\)/', $line, $matches)) {
      $included_lines = file("templates/".$this->model_."/".$matches[1]);
      $this->parseLines($included_lines);
      continue;
    }
    // check for added TMPL
    if (preg_match('/__DINCLUDE_(\S+)__/', $line, $matches)) {
      $included_lines = file("templates/".$this->model_."/".$this->added_tmpl_[$matches[1]]);
      $this->parseLines($included_lines);
      continue;
    }
        
    // check for images and styles links
    $line = preg_replace('/([^\/])images\//', "$1"."templates/".$this->model_."/images/", $line);
    $line = preg_replace('/([^\/])styles\//', "$1"."templates/".$this->model_."/styles/", $line);
    $line = preg_replace('/([^\/])scripts\//', "$1"."templates/".$this->model_."/scripts/", $line);
    $line = preg_replace('/([^\/])css\//', "$1"."templates/".$this->model_."/css/", $line);
  
    // find __DEFAULT__ tags
    if (preg_match('/\_\_DEFAULT\_\_\ ([A-Za-z\_]+)\ ?=\ ?(.*)\s*$/', $line, $matches)) {
      $this->defaults_[$matches[1]] = $matches[2];
      continue;
    }
    
    // get the sub-templates
    if (preg_match('/\_\_TMPL\_([A-Z0-9]+)\_(START)\_\_/', $line, $matches)) {
      $sub_tmpl = $matches[1]; continue;
    }
    if (preg_match('/\_\_TMPL\_([A-Z0-9]+)\_(STOP)\_\_/', $line, $matches)) {
      $sub_tmpl = ""; continue;
    }
    if (isset($sub_tmpl) && $sub_tmpl != "") {
      if (!isset($this->templates_[$sub_tmpl])) {
          $this->templates_[$sub_tmpl] = '';
      }
      $this->templates_[$sub_tmpl] .= $line;
      continue;
    }
    
    // find starting/ending of blocks to be displayed or hidden
    if (preg_match('/\_\_(BEGIN)\_([A-Z0-9\_]+)\_BLOCK\_\_/', $line, $matches)) {
      $sysconf_ = SystemConfig::getInstance();
      if ( !$sysconf_->gui_prefs_["want_".strtolower($matches[2])]) {
        $hidden = true; 
      }
      continue;
    }
    if (preg_match('/\_\_(CLOSE)\_([A-Z0-9\_]+)\_BLOCK\_\_/', $line, $matches)) {
      $hidden = false; continue;
    }
  
    // find starting/ending of administration blocks to be displayed or hidden (used for navigation)
    global $admin_;
    if (preg_match('/\_\_(BEGIN)\_([A-Z0-9\_]+)\_ABLOCK\_\_/', $line, $matches)) {
      if ( $admin_ instanceof Administrator && !$admin_->canSeeBlock($matches[2])) {
        $hidden = true; 
      }
      continue;
    }
    if (preg_match('/\_\_(CLOSE)\_([A-Z0-9\_]+)\_ABLOCK\_\_/', $line, $matches)) {
      $hidden = false; continue;
    }
  
    // add line to content
    if (!$hidden) {
      array_push($this->content_, $line);
    }
  }
  return true; 
}

private function formatDate($params) {
  global $lang_;

  $day = $params[1];
  $month = $params[2];
  $year = $params[3];
  $separator = $params[4];

  $format = $lang_->print_txt('DATEFORMAT');
  if ($format == '') {
   $format = '_D__M__Y_';
  }

  $str = preg_replace('/__/', '_'.$separator.'_', $format);
  $str = preg_replace('/_D_/', $day, $str);
  $str = preg_replace('/_M_/', $month, $str);
  $str = preg_replace('/_Y_/', $year, $str);
   
  return $str;
}

}
?>
