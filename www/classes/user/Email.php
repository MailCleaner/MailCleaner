<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 /**
  * Email use Domain and User objects
  * It inherits from the PrefHandler storage possibilites
  */
require_once("helpers/PrefHandler.php");
require_once("domain/Domain.php");
require_once("user/User.php");

/** 
 * Email preferences and management
 * This class is mainly a wrapper to the email object preferences
 * 
 * @package mailcleaner
 */
class Email extends PrefHandler {
        
  /**
  * Email preferences
  * @var array
  */
  private $pref_ = array(
	                   'delivery_type'      =>  1,
			           'spam_tag'           =>  '{Spam?}',
			           'daily_summary'      =>  0,
			           'weekly_summary'     =>  1,
			           'monthly_summary'    =>  0,
                       'summary_type'       => 'NOTSET',
                       'summary_to'         => '',
			           'quarantine_bounces' =>  0,
                       'has_whitelist'   =>    0,
                       'has_warnlist'    =>    0,
                       'has_blacklist'  => 0,
			           'language'           =>  'en',
                       'gui_displayed_spams' => '20',
                       'gui_displayed_days' => '7',
                       'gui_mask_forced' => '0',
                       'gui_graph_type' => 'bar',
                       'gui_group_quarantines' => '0',
                       
                       'allow_newsletters' => ''
	                  );
   
  /**
   * Email datas and relations
   */                   
   private $infos_ = array(
                       'address' =>  '',
                       'user'    =>  '',
                       'is_main' =>  0
                      );

/**
 * Email constructor
 * this will set the preferences array to be fetched
 */
public function __construct() {
    $this->addPrefSet('email', 'e', $this->infos_);
    $this->addPrefSet('user_pref', 'p', $this->pref_);
    $this->setPrefSetRelation('p', 'e.pref');
    $this->setIDField(array('email' => 'address'));
}

/**
 * Set the user id the address belongs to
 * @param  $uid  numeric  user id
 * @return       bool     true on succes, false on failure
 */
public function setUser($uid) {
  if (is_numeric($uid)) {
    $this->setPref('user', $uid);
    return true;
  }
  $this->setPref('user', null);
  return true;
}


/**
 * return the domain name of the address
 * @return  string  domain name
 */
public function getDomain() {
  $tmp = array();
  if (! preg_match('/(\S+)@(\S+)/', $this->getPref('address'), $tmp)) {
    return "";
  }
  return $tmp[2];     
}

/**
 * Load the address preferences and datas, if not found default domain's prefs are used
 * @param  $ad  string   address to load
 * @return      bool     true on success, false on failure
 */
public function load($ad) {
  global $log_;
  $log_->log('-- BEGIN loading email: '.$ad, PEAR_LOG_INFO);
  global $sysconf_;
  
  // check address format and find domain of address
  $a = strtolower($ad);
  $tmp = array();
  if (! preg_match('/(\S+)@(\S+)/', $a, $tmp)) {
    return false;
  }
  $address = $a;
  $domain_name = $tmp[2];
  
  // check admin right on users domain
  if (isset($admin_) && !$admin_->canManageDomain($domain_name)) {
        return false;
  }
  
  // first set domain preferences as default values
  $d = new Domain();
  if (in_array($domain_name, $sysconf_->getFilteredDomains())) {
    $d->load($domain_name);
  } else {
    $d->load($sysconf_->getPref('default_domain'));
  }
  foreach ($this->pref_ as $pref => $val) {
    if ($d->getPref($pref) != null) {
      $this->setPref($pref, $d->getPref($pref));
    }
  }
  
  $this->setPref('address', $a);
  $this->setPref('domain', $tmp[2]);
  
  // then load the addresse's preferences if exists
  $where_clause = "e.pref=p.id AND e.address='".$this->getPref('address')."'";
  if ($this->loadPrefs('e.address as e_id, p.id as pid, e.address as address, e.is_main as is_main, e.user as uid, ', $where_clause, true)) {
   }
  
  $log_->log('-- END loading email: '.$this->getPref('address'), PEAR_LOG_INFO);
  return true;
}

/**
 * set the language preference of the address
 * @param $lang  string  language
 * @return       bool    true on success, false on failure
 */
public function set_language($lang) {
    global $lang_;
    if (!$lang_->is_available($lang)) {
        return false;
    }
    $this->setPref('language', $lang);
    return true;
}

/**
 * Save the address's preferences to database
 * @return  string  'OKSAVED' or 'OKADDED' on success, error message on failure
 */
public function save() {
  if ($this->getPref('address') == "") {
  	return false;
  }
  global $log_;
  $log_->log('-- BEGIN saving email: '.$this->getPref('address'), PEAR_LOG_INFO);
  global $sysconf_;
  global $admin_;

  if (isset($admin_) && !$admin_->canManageDomain($this->getDomain())) {
    return "NOTALLOWED";
  }
  if (! preg_match('/^(NOTSET|text|html|digest)$/', $this->getPref('summary_type'))) {
  	$this->setPref('summary_type', 'NOTSET');
  }
  $ret = $this->savePrefs(null, null, '');
  $log_->log('-- END saving email: '.$this->getPref('address'), PEAR_LOG_INFO);

  return $ret;
}

/**
 * Delete address's preferences from database
 * @return 'OKDELETED' on success, error code on failure
 */
public function delete() {
  global $log_;
  global $sysconf_;
  $log_->log('-- BEGIN deleting email: '.$this->getPref('address'), PEAR_LOG_INFO);
  global $admin_;

  if (isset($admin_) && !$admin_->canManageDomain($this->getDomain())) {
    return "NOTALLOWED";
  }
  
  $ret = $this->deletePrefs(null);
  
  $log_->log('-- END deleting email: '.$this->getPref('address'), PEAR_LOG_INFO);
  $res = $sysconf_->dumpConfiguration('wwlist', $this->getPref('address'));
  return $ret;
}

/**
 * Set this address as main or not
 * @param  $v  numeric  1 to set address as main, 0 otherwise
 * @return     bool     true on success, false on failure
 */
public function setAsMain($v) {
 $this->setPref('is_main', 0);
 if ($v) {
   $this->setPref('is_main', 1);
 }
 return true;
}

/**
 * get the html code for the action on spam radio buttons
 * @return   string  html code
 * @todo  this has to be moved in the view layer
 */
public function html_ActionRadio() {
        global $lang_;

        $ret = "<input type=\"radio\" name=\"spam_action\" value=\"2\"";
        if ($this->getPref('delivery_type') == 2) { $ret .= " checked=\"checked\"";};
        $ret .= " />".$lang_->print_txt('PUTINQUARANTINE');
        $ret .= "<br/>\n";

        $ret .= "<input type=\"radio\" name=\"spam_action\" value=\"1\"";
        if ($this->getPref('delivery_type') == 1) { $ret .= " checked=\"checked\"";};
        $ret .= " />".$lang_->print_txt('TAGSUBJECT');
        $ret .= "<br/>\n";

        $ret .= "<input type=\"radio\" name=\"spam_action\" value=\"3\"";
        if ($this->getPref('delivery_type') > 2) { $ret .= " checked=\"checked\"";};
        $ret .= " />".$lang_->print_txt('DROP');

        return $ret;
}

/**
 * get the html code for the spam tag input field
 * @return   string  html code
 * @todo  this has to be moved in the view layer
 */
public function html_TagTextInput() {
        $ret = "<input type=\"text\" size=\"10\" name=\"tag\" value=\"".$this->getPref('spam_tag')."\" />";
        return $ret;
}

/**
 * get the html code for the summary frequency radio buttons
 * @return   string  html code
 * @todo  this has to be moved in the view layer
 */
public function html_SumFreqRadio() {
        global $lang_;

        $ret = "<input type=\"radio\" name=\"period_rap\" value=\"1\"";
        if ($this->getPref('daily_summary') == 1) { $ret .= " checked=\"checked\""; $checked=1;};
        $ret .= " />".$lang_->print_txt('DAILY');
        $ret .= "<br />";

        $ret .= "<input type=\"radio\" name=\"period_rap\" value=\"2\"";
        if ($this->getPref('weekly_summary') == 1) { $ret .= " checked=\"checked\""; $checked=1;};
        $ret .= " />".$lang_->print_txt('WEEKLY');
        $ret .= "<br />";

        $ret .= "<input type=\"radio\" name=\"period_rap\" value=\"3\"";
        if ($this->getPref('monthly_summary') == 1) { $ret .= " checked=\"checked\""; $checked=1;};
        $ret .= " />".$lang_->print_txt('MONTHLY');
        $ret .= "<br />";

        $ret .= "<input type=\"radio\" name=\"period_rap\" value=\"0\"";
        if ($checked != 1) { $ret .= " checked=\"checked\"";};
        $ret .= " />".$lang_->print_txt('NOSUMMARY');
        $ret .= "<br />";

        return $ret;
}

/**
 * get the html code for the quarantine bounces checkbox
 * @return   string  html code
 * @todo  this has to be moved in the view layer
 */
public function html_QuarBouncesCheckbox() {
    $ret = "<input type=\"checkbox\" name=\"quarantine_bounces\" value=\"1\"";
    if ($this->getPref('quarantine_bounces') > 0) { $ret .= " checked"; } 
    $ret .= " />";
    return $ret;
}

public function html_SumTypeSelect() {
    global $lang_;
	$ret = "\n<select name=\"summary_type\" onChange=\";\">\n";
    $types = array($lang_->print_txt('USEDEFAULT') => 'NOTSET', $lang_->print_txt('SUMMHTML') => 'html', $lang_->print_txt('SUMMTEXT') => 'text', $lang_->print_txt('SUMMDIGEST') => 'digest');
    foreach ($types as $tag => $value) {
    	$ret .= "  <option value=\"$value\"";
        if ($this->getPref('summary_type') == $value) {
        	$ret .= ' selected';
        }
        $ret .= ">$tag</option>\n";
    }
    $ret .= "</select>\n";
    return $ret;
}

/**
 * get the user input given in the $_POST
 * @return   bool  true on success, false on failure
 * @todo  this has to be moved at least in the controller
 */
public function getModifs() {
    //@todo check input !!
    if (isset($_POST['spam_action'])) {
      $this->setPref('delivery_type', $_POST['spam_action']);
      $this->setPref('delivery_type', $_POST['spam_action']);
    }
    if (isset($_POST['tag'])) {
      $this->setPref('spam_tag', $_POST['tag']);
    }
    if (isset($_POST['period_rap'])) {
      $this->setPref('daily_summary', '0');
      $this->setPref('weekly_summary', '0');
      $this->setPref('monthly_summary', '0');
      if ($_POST['period_rap'] == 1) {
           $this->setPref('daily_summary', 1);
      } else if ($_POST['period_rap'] == 2) {
           $this->setPref('weekly_summary', 1);
      } elseif ($_POST['period_rap'] == 3) {
           $this->setPref('monthly_summary', 1);
      }
    }
    if (isset($_POST['summary_type']) && preg_match('/^(NOTSET|html|text|digest)$/', $_POST['summary_type'])) {
    	$this->setPref('summary_type', $_POST['summary_type']);
    } else {
    	$this->setPref('summary_type', 'NOTSET');
    }
    if (isset($_POST['quarantine_bounces'])) {
      $this->setPref('quarantine_bounces', 0);
      if ($_POST['quarantine_bounces'] == 'true' || $_POST['quarantine_bounces'] > 0) {
        $this->setPref('quarantine_bounces', 1);
      }
    } else {
      $this->setPref('quarantine_bounces', 0);
    }
    if (isset($_POST['language'])) {
      $this->setPref('language', $_POST['language']);
    }
}

}
 ?>
