<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
  
/**
 * a quarantine is filled with spam
 */
require_once("user/Spam.php");

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
 * This is the class that will fetch all spam in the quarantine according to filter criterias
 */
class SpamQuarantine extends Quarantine {
  
  /**
   * filter criterias
   * @var  array
   */
  protected  $filters_ = array(
                      'to_local'      => '',
                      'to_domain'     => '',
                      'from'          => '',
                      'subject'       => '',
                      'days'          => DEFAULT_DAYS,
                      'mask_forced'   => 0,
                      'mask_bounces'  => 0,
                      'spam_only'     => 0,
                      'newsl_only'    => 0,
                      'group_quarantines' => 0,
                      'msg_per_page'  => DEFAULT_MSGS,
                      'order'         => array('date', 'desc'),
                      'page'          => 1
                    );
                  
  /**
   * allowed orders with corresponding column names
   * @var array
   */
  protected $ordered_fields_ = array(
                     'date'       => 'date_in',
                     'time'       => 'time_in',
                     'globalscore'=> 'M_globalscore',
                     'tolocal'    => 'to_user',
                     'from'       => 'sender',
                     'subject'    => 'M_subject',
                     'forced'     => 'forced'
                    );
                    
  protected $order_tags_ = array(
      'date' => 'ODATE',
      'time' => 'OTIME',
      'globalscore'=> 'OSCORE',
      'tolocal'    => 'ODESTINATION',
      'from'       => 'OSENDER',
      'subject'    => 'OSUBJECT',
      'forced'     => 'OFORCED'
  );
                    
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
   // if not admin, then the user should be authorized to search this address
   if ( (! $admin_ instanceof Administrator) ) {
     global $user_;
     if ( (! $user_ instanceof User) || (! $user_->hasAddress($this->getSearchAddress()))) {
        $log_->log('-- user not allowed to access quarantine', PEAR_LOG_WARNING);  
        return false;
     }
   // if admin, then admin must have right to manage users and to manage this domain
   } else {
     if ( (! $admin_->checkPermissions(array('can_manage_users'))) || (! $admin_->canManageDomain($this->getFilter('to_domain')))) {
        $log_->log('-- admin not allowed to access quarantine', PEAR_LOG_WARNING); 
        return false;
     }
   }
   return true;
}

/**
 * Load the quarantine from the databse
 * @return   bool  true on success, false on failure
 */
public function load() {
   global $sysconf_;
   global $admin_;
   global $log_;
   global $user_;

   // First, we do some checks...
   if (!$this->isAllowed()) {
    return false;
   }
   
   $log_->log('-- searching spam quarantine', PEAR_LOG_INFO); 
   
   // required here for sanity checks
   require_once ('helpers/DM_MasterSpool.php');
   $db_masterspool = DM_MasterSpool :: getInstance();
    
   // now we clean up the filter criterias
   if (!is_numeric($this->getFilter('days'))) {
     $this->setFilter('days', DEFAULT_DAYS);
   }
   if (!is_numeric($this->getFilter('msg_per_page'))) {
     $this->setFilter('msg_per_page', DEFAULT_MSGS);
   }
   foreach ($this->filters_ as $key => $value) {
     $clean_filters[$key] = $db_masterspool->sanitize($value);
   }
   // index is the first letter of the local part of the address
   // this is used to select the spam table in the database
   $index = NULL;
   if ($this->getFilter('to_local') != "") {
    $index = substr($this->getFilter('to_local'), 0, 1);
    if (preg_match('/^[0-9]/', $index)) {
      $index = 'num';
    } 
    if (preg_match('/^[^a-z0-9]/', $index)) {
      $index = 'misc';
     }
   }
   if ($this->getFilter('group_quarantines'))  {
     $index = '';
   }
 
   // now prepare the where statement
   // domain and user filters
   if ( (! $admin_ instanceof Administrator) ) {
     $addresses = array($clean_filters['to_local'].'@'.$clean_filters['to_domain']);
     if ($this->getFilter('group_quarantines')) {
         $addresses = $user_->getAddresses();
     }
     $where = "( ";
     foreach ($addresses as $address) {
       if (preg_match('/(\S+)\@(\S+)/', $address, $matches)) {
         $where .= "(to_domain='".$matches[2]."' AND (to_user='".$matches[1]."' OR to_user LIKE '".$matches[1]."+%')) OR ";
       }
     }
     $where = preg_replace('/OR $/', '', $where);
     $where .= " )";
   } else {
     $where = "to_domain='".$clean_filters['to_domain']."' AND to_user LIKE '%".$clean_filters['to_local']."%'";
   }
   // date filters
   $where .= " AND (TO_DAYS(NOW())-TO_DAYS(date_in) < ".$clean_filters['days'].")";
   // sender filters
   if ($clean_filters['from'] != "") {
     $where .= " AND sender LIKE '%".$clean_filters['from']."%'";
   }
   // subject filters
   if ($clean_filters['subject'] != "") {
     $where .= " AND M_subject LIKE '%".$clean_filters['subject']."%'";
   }
   // mask forced
   if ($this->getFilter('mask_forced')) {
      $where .= " AND forced!=1";
   }
   // mask bounces
   if ($this->getFilter('mask_bounces')) {
      //@todo correct this filter
      //$where .= " AND NOT sender LIKE '\*%'";
   }
   if ($this->getFilter('showSpamOnly') || (isset($clean_filters['spam_only']) && $clean_filters['spam_only'] == 1)) {
      $where .= " AND is_newsletter != 1";
   } elseif ($this->getFilter('showNewslettersOnly') || (isset($clean_filters['newsl_only']) && $clean_filters['newsl_only'] == 1)) {
      $where .= " AND is_newsletter = 1";
   }

   // select the correct spam table
   $table = "spam";
   if ($index != "") {
     $table .= "_".strtolower($index);
   }
   
   // get the number of spam found
   $count_query = "SELECT COUNT(DISTINCT exim_id) as count FROM $table WHERE $where";

   $count_row = $db_masterspool->getHash($count_query);
   if (isset($count_row['count']) && is_numeric($count_row['count'])) {
      $this->setNbElements($count_row['count']);
   }
    
   // and get the spam themselfs
   $query = "SELECT date_in, time_in, sender, to_domain, to_user, exim_id, exim_id as id, M_subject, M_date, forced, in_master, store_slave, M_score, M_rbls, M_prefilter, M_globalscore, is_newsletter";
   $query .= " FROM $table WHERE ".$where;

   // ignore duplicate id
   $query .= " GROUP BY exim_id";

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
   if (!is_numeric($clean_filters['page'])) {
     $clean_filters['page'] = 1;
   }
   $start = ($clean_filters['page']-1) * $clean_filters['msg_per_page'];
   if ($clean_filters['msg_per_page'] != 0) {
     $limit = " LIMIT ".$start.",".$clean_filters['msg_per_page'];
   }
   $query .= $limit;

   // populate internal spam list
   $spam_list = $db_masterspool->getListOfHash($query);
   foreach ($spam_list as $spam) {
     $this->elements_[$spam['exim_id']] = new Spam();
     $this->elements_[$spam['exim_id']]->loadFromArray($spam);
   }
   
   if ($this->getFilter('page') > $this->getNBPages()) {
    $this->setFilter('page', 1);
   }
   
   // get statistics
   $sysconf = SystemConfig::getInstance();
   if (count($sysconf->getSlaves()) < 1) {
     $sysconf->loadSlaves();
   }
        
   $mesgs = 0;
   $today = @getdate();
   $today_date = $today['year'].sprintf('%02d', $today['mon']).sprintf('%02d', $today['mday']);
   if ($this->filters_['days'] > 0) {
     $delta = $this->filters_['days']-1;
   }

   foreach ($sysconf->getSlaves() as $slave) {
     $stats = $slave->getStats($this->getSearchAddress(), '-'.$delta, $today_date);
     if (is_object($stats)) {
       $this->stats_['msgs'] += $stats->msg;
       $this->stats_['spams'] += $stats->spam;
       $this->stats_['bytes'] += $stats->bytes;
       $this->stats_['virus'] += $stats->virus;
       $this->stats_['content'] += $stats->content;
       $this->stats_['clean'] += $stats->clean;
     } 
   }
   if ($this->stats_['msgs'] != 0) {
     $this->stats_['pspams'] = (100/$this->stats_['msgs'])*$this->stats_['spams'];
     $this->stats_['pvirus'] = (100/$this->stats_['msgs']) * $this->stats_['virus'];
     $this->stats_['pclean'] = (100/$this->stats_['msgs']) * $this->stats_['clean'];
     $this->stats_['pcontent'] = (100/$this->stats_['msgs']) * $this->stats_['content'];
   }
   return true;
}

/**
 * populate the splam list template with actual spams values
 * @param  $t  Template  template of each spam line
 * @return     string    completed spam array
 */
public function getHTMLList($to) {
  global $lang_;
  global $user_;
          
  $i = 0;
  $res = "";
  $t = $to->getTemplate('QUARANTINE');
  if ($this->getFilter('group_quarantines')) {
      $t = preg_replace('/__(IF|FI)_GROUPQUARANTINE__/', '', $t);
  } else {
      $t = preg_replace('/__IF_GROUPQUARANTINE__.*__FI_GROUPQUARANTINE__/', '', $t);
  }
  foreach ($this->elements_ as $spam) {
    if ($i++ % 2) {
      $template = preg_replace("/__COLOR1__([a-zA-Z0-9#.\-]+)__COLOR2__([a-zA-Z0-9#.\-]+)/", "$1", $t);
    } else {
      $template = preg_replace("/__COLOR1__([a-zA-Z0-9#.\-]+)__COLOR2__([a-zA-Z0-9#.\-]+)/", "$2", $t);
    }
    $template = str_replace('__LANG__', $lang_->getLanguage(), $template);
    $template = preg_replace("/__LANG_FORCESPAM__/", $lang_->print_txt("FORCESPAM"), $template);
    $template = preg_replace("/__LANG_ASKREASON__/", $lang_->print_txt("ASKREASON"), $template);
    $template = preg_replace("/__LANG_ASKANALYSE__/", $lang_->print_txt("ASKANALYSE"), $template);
    $template = str_replace('__DATE__', $spam->getFormatedDate(), $template);
    $template = str_replace('__TIME__', '', $template);
    $template = str_replace('__FROM__', $spam->getCleanData('sender'), $template);
    $template = str_replace('__TO__', addslashes($spam->getCleanData('to')), $template);
    $template = str_replace('__SUBJECT__', $spam->getCleanData('M_subject'), $template);
    $template = str_replace('__SCORE__', $spam->getGlobalScore($to), $template);
    $template = str_replace('__SCOREVALUE__', $spam->getCleanData('M_globalscore'), $template);
    $template = str_replace('__SCORETEXT__', $lang_->print_txt_param("SCORETEXT", $spam->getCleanData('M_globalscore')), $template);
    $template = str_replace('__TO_ADD__', urlencode($spam->getCleanData('to')), $template);
    if ($spam->getData('forced')) $template = preg_replace("/__FORCE__(.*)__FORCE__/", "$1", $template);
    else $template = preg_replace("/__FORCE__(.*)__FORCE__/", "", $template);

    $template = str_replace('__FORCETARGET__', urlencode("/fm.php?a=".urlencode($spam->getCleanData('to'))."&id=".$spam->getCleanData('exim_id')."&s=".$spam->getCleanData('store_slave')."&lang=".$lang_->getLanguage()."&n=".$spam->getCleanData('is_newsletter')), $template);
    $template = str_replace('__REASONSTARGET__', urlencode("/vi.php?a=".urlencode($spam->getCleanData('to'))."&id=".$spam->getCleanData('exim_id')."&s=".$spam->getCleanData('store_slave')."&lang=".$lang_->getLanguage()), $template);
    $template = str_replace('__ANALYSETARGET__', urlencode("/send_to_analyse.php?a=".urlencode($spam->getCleanData('to'))."&id=".$spam->getCleanData('exim_id')."&s=".$spam->getCleanData('store_slave')."&lang=".$lang_->getLanguage()), $template);
  
    $template = str_replace('__MSG_ID__', urlencode($spam->getCleanData('exim_id')), $template);
    $template = str_replace('__STORE_ID__', urlencode($spam->getCleanData('store_slave')), $template);
    $template = str_replace('__NEWS__', $spam->getCleanData('is_newsletter'), $template);
    $template = str_replace('__ROW_ID__', $i, $template);
    $template = str_replace('__FORCED__', $spam->getCleanData('forced'), $template);
    if ($spam->getCleanData('forced')) {
      $template = str_replace('__FORCE_ICON__', $to->getDefaultValue('FORCED_IMG'), $template);
      $template = str_replace('__FORCEDCLASS__', 'msgforced', $template);
    } else {
      $template = str_replace('__FORCE_ICON__', $to->getDefaultValue('FORCE_IMG'), $template);
      $template = str_replace('__FORCEDCLASS__', 'msgnotforced', $template);
    }
    
    require_once ('helpers/DM_MasterConfig.php');
    $db = DM_MasterConfig::getInstance();
    $query = "select type from wwlists where sender = '".$user_->getPref('pref')."'";
    $result = $db->getHash($query);   
    

    ### BEGIN newsl

    if (!empty($spam->getCleanData('is_newsletter'))) {

        $id = $spam->getCleanData('exim_id');
        $sender = $spam->getCleanData('sender');
        $recipient = $spam->getCleanData('to_user').'@'.$spam->getCleanData('to_domain');
        $query = "select type from wwlists where sender = '".$sender."' and recipient = '".$recipient."'";
        $result = $db->getHash($query);

        if (empty($result)) {
	    $hrefNews = "/newsletters.php?id=" . $id . "&a=" . urlencode($recipient);
            $link =  '<span style="float: right;"><a style="border: thin solid grey; padding: 2px; background-color: lightgrey; box-shadow: 2px 1px 0px lightgrey; text-decoration: none;" data-id="%s" data-a="%s" href="%s" class="allow">%s</a></span>';
            $rule = 'allow';
            $label = $lang_->print_txt('NEWSLETTERACCEPT');

            $output = sprintf($link, $id, $recipient, $hrefNews, $label);

            $template = str_replace('__IS_NEWSLETTER__', $output, $template);
        } else {
            $template = str_replace('__IS_NEWSLETTER__', '', $template);
        }
    } else {
       $template = str_replace('__IS_NEWSLETTER__', '', $template);
    }

    ### END newsl
    
    $res .= $template;
  }
  return  $res;
}

/**
 * return the number of spam found according to the filterd
 * @return   numeric  number of spam
 */
public function getNbSpams() {
   return $this->getNbElements();
}

/**
 * return the a staitstic
 * @param  $type  string  stat type to fetch
 * @return        numeric  number of messages
 */
public function getStat($type) {
   if (isset($this->stats_[$type])) {
     return $this->stats_[$type];
   }
}
 
/**
 * send the summary of the actual quarantine
 * This method actually just pass parameters to the send_summary.pl script and return the result
 * @return   string   status message that the send_summary.pl script answers
 */
public function doSendSummary() {
     // First, we do some checks...
   if (!$this->isAllowed()) {
    return "";
   }
   $sysconf_ = SystemConfig::getInstance();
   
   // create the command string
   $command = $sysconf_->SRCDIR_."/bin/send_summary.pl ".addslashes($this->getSearchAddress())." 0 ".$this->getFilter('days');
   $command = escapeshellcmd($command);

   // and launch
   $result = `$command`;
   $result = trim($result);

   $lang = Language::getInstance('user');
   $tmp = array();
   
   if (preg_match('/SUMSENT (?:to\s*)?(\S+\@\S+)/', $result, $tmp)) {
     return $lang->print_txt_param('SUMSENTTO', $tmp[1]);
   } 
   return  $lang->print_txt_param('SUMNOTSENTTO', addslashes($this->getSearchAddress()));
}

/**
 * purge the actual quarantine
 * this will only delete spam traces in the master databases. Real spams messages will be purged by periodic task
 * @return    bool   true on success, false on failure
 */

public function purge() {
     // First, we do some checks...
   if (!$this->isAllowed()) {   
    return false;
   }
   
   if ($this->getFilter('to_local') != "") {
    $index = strtolower(substr($this->getFilter('to_local'), 0, 1));
   } else {
    return false;
   }
   if (preg_match('/^[0-9]/', $index)) {
      $index = 'num';
   }
   if (preg_match('/^[^a-z0-9]/', $index)) {
     $index = 'misc';
   }

   // required here for sanity checks
   require_once ('helpers/DM_MasterSpool.php');
   $db_masterspool = DM_MasterSpool :: getInstance();
   // now we clean up the filter criterias
   foreach ($this->filters_ as $key => $value) {
     $clean_filters[$key] = $db_masterspool->sanitize($value);
   }

   $query = "DELETE FROM spam_".$index." WHERE to_domain='".$clean_filters['to_domain']."' AND to_user='".$clean_filters['to_local']."'";

   #return true;
   return $db_masterspool->doExecute($query);
}

public function getOrderTag() {
  return $this->getQuarantineOrderTag($this->order_tags_);
}

}

?>
