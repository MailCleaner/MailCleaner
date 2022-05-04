<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
  

/**
 * This is a quarantine template class
 * Takes car of storing elements, generating filters and pagination
 */
class Quarantine {
  
  /**
   * filter criteria
   * @var  array
   */
  protected  $filters_ = array(
                          'to_local'      => '',
                          'to_domain'     => '',
                          'msg_per_page' => 20,
                          'page'         => 1,
                          );
                  
  /**
   * allowed orders with corresponding column names
   * @var array
   */
  protected $ordered_fields_ = array();
                    
 /**
  * the total number of elements found according to filters
  * @var  numeric
  */                 
  private $nb_elements_ = 0;
  
  /**
   * the actual list of elements (only those present in the actual displayed page)
   * @var  array
   */
  protected $elements_ = array();
  
/**
 * set a filter criteria
 * @param  $criteria string  filter criteria name
 * @param  $value    string  value to be searched for
 * @return           bool    true on success, false on failure
 */
public function setFilter($criteria, $value) {
  $matches = array();
  if ($criteria == 'order') {
    if (preg_match('/(\S+)_(asc|desc)/', $value, $matches)) {
      if (isset($this->ordered_fields_[$matches[1]])) {
       $this->filters_['order'] = array($matches[1], $matches[2]);
       return true; 
      }
    }
    return false;
  }  
  if ($criteria == 'page' && $value == "") {
    $value = 1;
  }
  
  if (isset($this->filters_[$criteria])) {
    $this->filters_[$criteria] = $value;
    return true;
  }
  return false;   
}
 
/**
 * get a filter criteria
 * @param  $criteria  string  filter criteria name
 * @return            mixed   value of the search filter
 */
public function getFilter($criteria) {
   if (isset($this->filters_[$criteria])) {
     return $this->filters_[$criteria];
   }
   return "";
}

/**
 * get the full search address. This is the concatenation of the local part and domain part
 * @return  string  search address
 */
 public function getSearchAddress() {
   return $this->getFilter('to_local')."@".$this->getFilter('to_domain');  
 }
/**
 * set the full search address.
 * @param $a  string  full address to be searched
 * @return    bool    true on success, false on failure
 */
 public function setSearchAddress($a) {
    $matches = array();
   if ( preg_match('/^([a-zA-Z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+)\@([a-zA-Z0-9\.\_\-]+)$/', $a, $matches)) {
     $this->setFilter('to_local', $matches[1]);
     $this->setFilter('to_domain', $matches[2]);
   }
   return false;
 }
 
/**
 * set the filter criteria given an array.
 * This is usefull when given a request array for exemple
 * @param  $a  array  array of filter criteria
 * @return     bool   true on success, false on failure
 */
public function setSettings($a) {
  foreach($a as $key => $value) {
    if ($key == 'a' || $key == 'address') {
       $this->setSearchAddress($value);
    }
    $this->setFilter($key, $value);
  }
  return true;
}

public function isFiltered() {
  if ($this->getFilter('subject') != "" || $this->getFilter('from') != "") {
  	return 1;
  }
  return 0;
}

public function getHTMLCriterias($template, $sep) {
  global $lang_;
  if ($template == '') {
  	return '';
  }
  $wantedfilters = array('from' => 'OSENDER', 'subject' => 'OSUBJECT');
  $t = "";
  foreach ($wantedfilters as $filter => $fname) {
    if ($this->getFilter($filter) == "") { continue; }
    $tmp = preg_replace('/__FILTERNAME__/', $lang_->print_txt($fname), $template);
    $tmp = preg_replace('/__FILTERVALUE__/', $this->getFilter($filter), $tmp);
    $t .= $tmp.$sep;
  }
  $t = preg_replace("/$sep$/", '', $t);
  return $t;
}
/**
 * get the order criteria, formatted as a single string
 * @return   string order criteria
 */
public function getOrderString() {
  return $this->filters_['order'][0]."_".$this->filters_['order'][1];
}

/**
 * set the total number of elements found (not in page, and not in $elements_ array)
 * @param  $nbelements   numeric   number of elements found
 * @return               bool      true on success, false on failure
 */
protected function setNbElements($nbelements) {
    if (is_numeric($nbelements)) {
       $this->nb_elements_ = $nbelements;
       return true;
    }
    return false;
}

/**
 * return the number of elements found according to the filtered
 * @return   numeric  number of elements
 */
public function getNbElements() {
   if (isset($this->nb_elements_) && $this->nb_elements_ != "") {
     return $this->nb_elements_;
   }
   return 0;
}


/**
 * return the number of pages needed for the whole quarantine
 * @return   numeric  number of pages
 */
public function getNBPages() {
    if ( $this->nb_elements_ > 0 && $this->getFilter('msg_per_page') > 0) {
      $nb_pages = ceil($this->nb_elements_/$this->getFilter('msg_per_page'));
      return $nb_pages; 
    }
    return 1;
}

/**
 * get the link for the next page (javascript)
 * @return  string  link
 */
public function getNextPageLink() {
  global $lang_;
  $page = $this->getFilter('page');
  if ($page < $this->getNBPages()) {
     return "<a class=\"pagelink\" href=\"javascript:page(".($page + 1).");\">".$lang_->print_txt('NEXTPAGE')."</a>"; 
  }
  return "";   
}

public function getPagesLinks($limit) {
  $ret = "";
  
  if ($this->getNBPages() < 2) {
  	return "";
  }
  $initial_page = $this->getFilter('page');
  $nbpages = $limit;
  $middle = $this->getFilter('page');
  if ($this->getNBPages() < $limit) {
  	$nbpages = $this->getNBPages();
    $start = 1;
    $stop = $this->getNBPages();
  } else {
    $start = 1;
    $stop = $nbpages;
  
    $left = round($nbpages / 2);
    $right = round($nbpages / 2);
    $start = $middle - $left;
    $stop = $middle + $right;
    if ($start < 1) {
  	  $stop += abs($start);
      $start = 1;
    }
    if ($stop > $this->getNBPages()) {
      $start = $start - ($stop - $this->getNBPages());
      $stop = $this->getNBPages();
    }
  
    $page = $start;
  }
  for ($i=$start; $i <= $stop; $i++) {
    $sep = "|";
    if ($i < 2) {
     $sep = "";
    }
    if ($i == $middle) {
      $ret .= "$sep&nbsp;<strong>$i</strong>&nbsp;";
    } else {
      $ret .= "$sep&nbsp;<a class=\"pagelink\" href=\"javascript:page($i);\">$i</a>&nbsp;";
    }
  }
  $ret = ltrim($ret, $sep);
  
  if ($start > 1) {
  	$ret = "&nbsp;<a class=\"pagelink\" href=\"javascript:page(1);\">1</a>&nbsp;...".$ret;
  }
  if ($stop < $this->getNBPages()) {
  	$ret .= "... <a class=\"pagelink\" href=\"javascript:page(".$this->getNBPages().");\">".$this->getNBPages()."</a>&nbsp;";
  }
  return $ret;
}

/**
 * get the link for the previous page (javascript)
 * @return  string  link
 */
public function getPreviousPageLink() {
  global   $lang_;
  $page = $this->getFilter('page');
  if ($page > 1) {
    return "<a class=\"pagelink\" href=\"javascript:page(".($page - 1).");\">".$lang_->print_txt('PREVIOUSPAGE')."</a>";
  }
  return "";
}

/**
 * get the separator character between page links if needed
 * @param  $sep  string  separator character
 * @return       string  separator character if needed
 */
public function getPagesSeparator($sep) {
  $page = $this->getFilter('page');
  if ($page > 1 && $page<$this->getNBPages()) {
    return $sep;
  }
  return "";   
}

/**
 * get the javascript link for the order buttons
 * @param  $field  string  order field
 * @return         string  link string
 */
public function getOrderLink($field) {
   if (isset($this->ordered_fields_[$field])) {
    $order = $this->getFilter('order');
    if ($order[0] != $field && ($field == 'date' || $field == 'globalscore')) {
      $o = 'desc';
    } else {
      $o = 'asc';
    }
    if ( $field == $order[0]) { 
      $o = 'desc';
      if ($order[1] == 'desc') {
        $o = 'asc';
      }
    } 
    return "javascript:order('$field"."_".$o."');";
   }
   return "";
}

/**
 * get the correct order image
 * @param  $asc_img  string  image link for ascending order
 * @param  $desc_img string  image link for descending order
 * @param  $field    string  field name where to display image
 * @return           string  correct image link
 */
public function getOrderImage($asc_img, $desc_img, $field) {
  if (isset($this->ordered_fields_[$field])) { 
    $order = $this->getFilter('order');
    if ($order[0] == $field) {
      if ($order[1] == 'asc') {
        return $asc_img;
      }
      return $desc_img;
    }
  }
  return "";    
}

protected function getQuarantineOrderTag($tags) {
  $order1 = $this->getFilter('order');
  $order = $order1[0];
  return $tags[$order];
}

public function getOrderName() {
  $order = $this->getFilter('order');
  return $order[0];
}

/**
 * get the pagination javascript used to navigate
 * @param  $form  string formular name
 * @return        string javascript
 */
public function getJavascripts($form) {
  $ret = "function page(p) {\n";
  $ret .= "  window.document.forms['".$form."'].".$form."_page.value=p;\n";
  $ret .= "  window.document.forms['".$form."'].submit();\n";
  $ret .= "}\n\n";
  $order = $this->getFilter('order');
  $ret .= "function order(field) {\n";
  $ret .= "  window.document.forms['".$form."'].".$form."_order.value=field;\n";
  $ret .= "  window.document.forms['".$form."'].submit();\n";
  $ret .= "}\n\n";
  return $ret;
}
}

?>
