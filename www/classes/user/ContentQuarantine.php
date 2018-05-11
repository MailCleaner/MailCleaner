<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
  
/**
 * a quarantine is filled with content objects
 */
require_once("user/Content.php");
 
/**
 * this is a quarantine
 */
require_once("user/Quarantine.php");

/**
 * some constants
 */
define ("DEFAULT_DAYS", 7);
define ("DEFAULT_MSGS", 20);

/**
 * This is the class that will fetch all blocked content in the quarantine according to filter criteria
 */
class ContentQuarantine extends Quarantine {

  /**
   * filter criteria
   * @var  array
   */
  protected  $filters_ = array(
                      'searchid'      => '',
                      'slave'         => '127.0.0.1',
                      'to_local'      => '',
                      'to_domain'     => '',
                      'from'          => '',
                      'subject'       => '',
                      'days'          => DEFAULT_DAYS,
                      'order'         => array('date', 'desc'),
                      'page'          => 1,
                      'msg_per_page'  => DEFAULT_MSGS
                    );
                  
  /**
   * allowed orders with corresponding column names
   * @var array
   */
  protected $ordered_fields_ = array(
                     'date'       => 'date',
                     'time'       => 'time',
                     'tolocal'    => 'to_address',
                     'from'       => 'from_address',
                     'subject'    => 'subject',
                    );
                    
  /**
   * images that could be used in the quarantine html display
   * @var  array
   */            
  private  $images_ = array();


/**
 * check if user/admin as correct authorization
 * @return   bool  true if authorized, false if not
 */
private function isAllowed() {
   global $admin_;
   global $log_;
   
   // at least domain should be set
   if ($this->getFilter('to_domain') == "") {
     return false;
   }
   // only admins can check this
   if ( (! $admin_ instanceof Administrator) ) {
     return false;
   }
   
   // if admin, then admin must have right to manage users and to manage this domain
   if ( (! $admin_->checkPermissions(array('can_manage_users'))) || (! $admin_->canManageDomain($this->getFilter('to_domain')))) {
     $log_->log('-- admin not allowed to access quarantine', PEAR_LOG_WARNING); 
     return false;
   }
   return true;
}

/**
 * Load the quarantine from the database
 * @return   bool  true on success, false on failure
 */
public function load() {
   global $sysconf_;
   global $admin_;
   global $log_;
   
   // First, we do some checks...
   if (!$this->isAllowed()) {
    return false;
   }
   
   $log_->log('-- searching content quarantine', PEAR_LOG_INFO);
   
   // first, if we are given the id directly, no need to search in database
   $matches = array();
   if (preg_match('/(\S{8})\/(\S{16})/', $this->getFilter('searchid'), $matches)) {
      $content = new Content();
      if ($content->load($matches[2]) != 'OK') {
        return false;
      }
      $this->elements_[$matches[2]] = $content;
      $this->setNbElements(1);
      return true;
   }
 
   // required here for sanity checks
   require_once ('helpers/DM_Custom.php');
   $slaves = $sysconf_->getSlaves();
   $slave = $sysconf_->getSlavePortPasswordID($this->getFilter('slave'));
   if ($slave[0] == 0) {
     $current = current($slaves);
     $slave = $sysconf_->getSlavePortPasswordID($current->getPref('hostname'));
   }
   $slave_id = $slave[3];
   if ($slave[0] == 0) {
     $slaves = $sysconf_->getSlaves();
     $slave = $slaves[0];
     $slave_id = 1;
   } 
   $db = DM_Custom :: getInstance($this->getFilter('slave'), $slave[0], 'mailcleaner', $slave[1], 'mc_stats');
   // now we clean up the filter criteria
   // now we clean up the filter criteria
   if (!is_numeric($this->getFilter('days'))) {
     $this->setFilter('days', DEFAULT_DAYS);
   }
   if (!is_numeric($this->getFilter('msg_per_page'))) {
     $this->setFilter('msg_per_page', DEFAULT_MSGS);
   }
   foreach ($this->filters_ as $key => $value) {
     $clean_filters[$key] = $db->sanitize($value);
   }
   
   // next build query 
   $where .= " quarantined=1";
 
   // destination address
   $where .= " AND to_address LIKE '%".$clean_filters['to_local']."%@".$clean_filters['to_domain']."'";
   
   // nb days filter
   $where .= " AND (TO_DAYS(NOW())-TO_DAYS(date) < ".$this->getFilter('days').")";
   
   // sender filter
   if ($clean_filters['from'] != "") {
     $where .= " AND from_address LIKE '%".$clean_filters['from']."%'";
   }
   // subject filters
   if ($clean_filters['subject'] != "") {
     $where .= " AND subject LIKE '%".$clean_filters['subject']."%'";
   }
   
   // get the number of content found
   $count_query = "SELECT COUNT(*) as count FROM maillog WHERE $where";
   $count_row = $db->getHash($count_query);
   if (isset($count_row['count']) && is_numeric($count_row['count'])) {
      $this->setNbElements($count_row['count']);
   }
   
   // get the content themselves
   $query = "SELECT timestamp, id, from_address, to_address, subject, isspam, virusinfected, nameinfected, otherinfected, report, date, time FROM maillog WHERE ";
   $query .= $where;
   
   // set the sorting order wanted
   $order = $this->getFilter('order');
   $order_query = $this->ordered_fields_[$order[0]]." ".$order[1];
   if ($order[0] != 'date') {
     $order_query .= ", ".$this->ordered_fields_['date']." ".$order[1];
   }
   $order_query .= ", ".$this->ordered_fields_['time']." ".$order[1];
   $query .= " ORDER BY ".$order_query;
   
   // set the page limit
   $limit = "";
   if (! is_numeric($clean_filters['page'])) {
     $clean_filters['page'] = 1;
   }
   $start = ($clean_filters['page']-1) * $clean_filters['msg_per_page'];
   if ($clean_filters['msg_per_page'] != 0) {
     $limit = " LIMIT ".$start.",".$clean_filters['msg_per_page'];
   }
   $query .= $limit;
   
   // populate internal spam list
   $content_list = $db->getListOfHash($query);
   foreach ($content_list as $content) {
     $this->elements_[$content['id']] = new Content();
     $this->elements_[$content['id']]->setDatas($content);
     $this->elements_[$content['id']]->slave_ = $slave_id;
   }
   
   if ($this->getFilter('page') > $this->getNBPages()) {
    $this->setFilter('page', 1);
   }
   return true;
}

/**
 * populate the content list template with actual message values
 * @param  $t  string  template of each message line
 * @return     string  completed content array
 */
public function getHTMLList($t) {
  global $lang_;

  $i = 0;
  $res = "";
  foreach ($this->elements_ as $content) {
    if ($i++ % 2) {
      $template = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$1", $t);
    } else {
      $template = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$2", $t);
    }
    $template = str_replace('__LANG__', $lang_->getLanguage(), $template);
    $template = str_replace('__DATE__', $content->getCleanData('date'), $template);
    $template = str_replace('__TIME__', $content->getCleanData('time'), $template);
    $template = str_replace('__FROM__', $content->getCleanData('from_address'), $template);
    $template = str_replace('__SUBJECT__', $content->getCleanData('subject'), $template);
    $template = str_replace('__SCORE__', $content->getCleanData('isspam'), $template);
    $template = str_replace('__TO_ADD__', $content->getCleanData('to_address'), $template);
    $template = preg_replace('/__FORCE__(.*)__FORCE__/', "", $template);
    $template = str_replace('__CONTENT_FOUND_IMAGES__', $content->getContentFoundImages($this->images_), $template);

    $template = str_replace('__FORCETARGET__', "force_content.php?id=".$content->getCleanData('id'), $template);
    $template = str_replace('__REASONSTARGET__', "content_infos.php?id=".$content->getCleanData('id'), $template);
    $template = str_replace('__ANALYSETARGET__', "", $template);
    $res .= $template;
  }
  return  $res;
}

/**
 * set the images from the template to be eventually used in the quarantine display
 * @param  $images   array  array of images (key is tag, value is image file)
 * @return           bool   true on success, false on failure
 */
public function setImages($images) {
   if (is_array($images)) {
     $this->images_ = $images;
     return true;
   }
   return false;
}
}
?>
