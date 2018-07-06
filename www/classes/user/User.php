<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * User contains Email addresses and belong to a Domain
 */ 
require_once('user/Email.php');
require_once('domain/Domain.php');
require_once('connector/AddressFetcher.php');

/**
 * User preference and management
 * This class takes care of the user preferences and settings. A User can manage many Email addresses
 * and usually belongs to a Domain.
 */
class User extends PrefHandler {
    
    /**
     * Addresses belonging to the user. This is an array of email addresses (strings)
     * @var array
     */
	private	$addresses_ = array();
    
    /**
     * User informations
     * @var array
     */
    private $infos_ = array(
                            'username' => '',
                            'domain' => '',
                           );
    /**
     * User preferences
     * @var array
     */
	private	$pref_ = array(
                       'language'   => 'en',
                       'gui_displayed_spams' => '20',
                       'gui_displayed_days' => '7',
                       'gui_mask_forced' => '0',
                       'gui_default_address' => '',
                       'gui_graph_type' => 'bar',
                       'gui_group_quarantines' => '0',
                       'summary_to' => '',
                       'summary_type' => 'NOTSET',
                       'allow_newsletters' => '',
        	          );	
                      
    /**
     * User local datas
     * @var array
     */
     private $datas_ = array(
                       'username' => '',
                       'password' => 'notchanged',
                       'email' => '',
                       'realname' => ''
                      );
    /**
     * User's main address
     * @var string
     */
     private $main_address_;
     
     /**
      * internal status of local user
      * @var bool
      */
      private $local_user_registered_ = false;
      
     /**
      * Is user empty (i.e. login only with email address from digest summary)
      * @var bool
      */
      private $is_stub_ = false;
      
     /**
      * temporary preferences, can be used or default display (i.e. quarantine)
      * @var array
      */
      private $tmp_prefs_ = array();

  /**
   * User constructor
   * this will set the preferences array to be fetched
   */
  public function __construct() {
    $this->addPrefSet('user', 'u', $this->infos_);
    $this->addPrefSet('user_pref', 'p', $this->pref_);
    $this->setPrefSetRelation('p', 'u.pref');
  }

  /**
   * Set the domain of the user
   * @param  $d  string  domain name
   * @return     bool    true on success, false on failure
   */
  function setDomain($d) {
    $this->setPref('domain', $d);
    return true;
  }
  
  /**
   * Get the Domain object of the user
   * Create a Domain object taken from the domain pref of the user
   * @return   Domain  domain object
   */
  public function getDomain() {
    $d = new Domain();
    $d->load($this->getPref('domain'));
    return $d;
  }
  
  /**
   * Get the addresses list belonging to the user
   * @return  array  addresses of the user
   */
  public function getAddresses() {
	return $this->addresses_;
  }
  
  public function getAddressesWithPending() {
    $list = array();
    foreach ($this->addresses_ as $add) {
      $list[$add] = 0;
    }
  	$query = "SELECT alias FROM pending_alias WHERE user=".$this->getID();
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $res = $db_slaveconf->getList($query);
    if (is_array($res)) {
      foreach($res as $add) {
        $list[$add] = 1;
      }
    }
    return $list;
  }
  
  /**
   * Get the addresses list in a format suitable for a select object
   * @return  array  addresses of the user, key are address, value are also address
   */
  public function getAddressesForSelect() {
    $ret = array();
    foreach ($this->addresses_ as $a => $v) {
       $ret[$a] = $a;
    }
    return $ret;
  }
  
  /**
   * Load the user datas from the username given. If user not found in database, then domain datas are used as default
   * @param  $u  string   username (formatted login)
   * @return     bool     true on success, false on failure
   */
  public function load($u) {
    global $log_;
    if ($log_) {
      $log_->log('-- BEGIN loading user: '.$u, PEAR_LOG_INFO);
    }
    global $sysconf_;
    global $admin_;

    // TODO: sanitize $u
    if ($u == "") {
        return false;
    }
    $this->setPref('username', $u);
    unset($this->addresses_);
    
    // check admin right on users domain
    if (isset($admin) && !$admin->canManageDomain($this->getPref('domain'))) {
       $log_->log('Admin not allowed to create user: '.$u, PEAR_LOG_WARNING);
       return false;
    }
    
    // first set domain preferences as default values
    $d = new Domain();
    if (in_array($this->getPref('domain'), $sysconf_->getFilteredDomains())) {
      $d->load($this->getPref('domain'));
    } else {
      $d->load($sysconf_->getPref('default_domain'));
    }
    foreach ($this->pref_ as $pref => $val) {
      $this->setPref($pref, $d->getPref($pref));
    }

    // search for already registered datas for this specific user
    $where_clause = "u.pref=p.id ";
    if (is_numeric($u)) {
      $where_clause .= "AND u.id=$u";
    } else {
      $where_clause .= "AND u.username='$u'";
    }
    if ($this->getPref('domain') != "") {
        $where_clause .= " AND u.domain='".$this->getPref('domain')."'";
    }

    if ($this->loadPrefs("u.id as uid, u.id as u_id, u.domain as domain, u.pref as pid, ", $where_clause, true)) {
    }
    // fetch email addresses bound to this user
    $address_fetcher = AddressFetcher::getFetcher($d->getPref('address_fetcher'));
    $this->addresses_ = $address_fetcher->fetch($this->getPref('username'), $d);
    if (!$this->addresses_) {
      $this->addresses_ = array();
    }
    $this->addRegisteredAddresses();
    if ($this->isLocalUser()) {
      $this->getLocalUserDatas();
    }
    
    if ($this->getPref('gui_default_address') == "" || ! $this->hasAddress($this->getPref('gui_default_address'))) {
    	$this->setPref('gui_default_address', $this->getMainAddress());
    }
    if ($log_) {
      $log_->log('-- END loading user: '.$u, PEAR_LOG_INFO);
    }
    return true;
  }
  
public function hasPrefs() {
  if ($this->isLoaded()) {
  	return true;
  }
  return false;
}

/**
 * fetch and add the addresses that are already registered for the user in the database
 * @return  bool  true on success, false on failure
 */
private function addRegisteredAddresses() {
  require_once('helpers/DataManager.php');
  global $sysconf_;
  
  $db_slaveconf = DM_SlaveConfig :: getInstance();
  $query = "SELECT e.address as id, e.address, e.is_main, e.pref FROM email e, user u WHERE u.username='".$db_slaveconf->sanitize($this->getPref('username'))."' ";
  $query .= "AND u.domain='".$db_slaveconf->sanitize($this->getPref('domain'))."' AND e.user=u.id";

  $res = $db_slaveconf->getListOfHash($query);
  if (is_array($res)) {
    foreach($res as $add_record) {
      $this->addAddress($add_record['address']);
      if ($add_record['is_main']) {
        $this->setMainAddress($add_record['address'])  ;
      } 
    }
    return true;
  }
  return false;
}

/**
 * Check if the address belongs to this user
 * @param  $str  string  address to be checked
 * @return       bool    true if address belongs to user, false if not
 */
public function hasAddress($str) {
  if (isset($this->addresses_[$str])) {
    return true;
  }
  return false;
}
 
/**
 * Check if address list can be modified or not, depending of the AddressFetcher
 * @return  bool  true if can be modified, false if not
 */
public function canModifyAddressList() {
  $address_fetcher = AddressFetcher::getFetcher($this->getDomain()->getPref('address_fetcher'));
  if ($address_fetcher instanceof AddressFetcher) {
    return $address_fetcher->canModifyList();
  }
  return false;
}

/**
 * check if user is locally defined (local connector)
 * @return  bool  true if local user, false if not
 */
public function isLocalUser() { 
  if ($this->getDomain()->getPref('auth_type') == 'local') { return true; }
  return false;
}

/**
 * get local user data
 * @param  $data  string  data to be retrieved
 * @return        mixed   data value
 */
 public function getData($data) {
   if (isset($this->datas_[$data])) {
      return $this->datas_[$data];
   }
   return "";
 }

/**
 * Load localy stored datas for user
 * @return   bool  true on success, false on failure
 */
private function getLocalUserDatas() {
  require_once('helpers/DataManager.php');
  global $sysconf_;
  
  $db_slaveconf = DM_SlaveConfig :: getInstance();
  $query = "SELECT realname, username, email from mysql_auth WHERE username='".$db_slaveconf->sanitize($this->getPref('username'))."' AND domain='".$db_slaveconf->sanitize($this->getPref('domain'))."'";
  $res = $db_slaveconf->getHash($query);
  if (!is_array($res) || empty($res)) {
    return false;
  }
  foreach ($res as $key => $value) {
    if (isset($this->datas_[$key])) {
      $this->datas_[$key] = $value;
    }
  }
  $this->local_user_registered_ = true;
  return true;  
}

/**
 * set the user preferences
 * it is the overloaded method here in order to use datas_ array
 * @param  $pref  string  preference or data
 * @param  $value mixed   value
 * @return        bool true on success, false on failure
 */
 public function setPref($pref, $value) {
    if ($pref == 'password' || $pref == 'realname' || $pref == 'email') {
        $this->datas_[$pref] = $value;
        return true;
    }
    return parent::setPref($pref, $value);
 }
 
public function setTmpPref($pref, $value) {
	$this->tmp_prefs_[$pref] = $value;
}

public function getTmpPref($pref) {
	if (isset($this->tmp_prefs_[$pref])) {
		return $this->tmp_prefs_[$pref];
	}
	return null;
}

public function clearTmpPref($pref) {
	if (isset($this->tmp_prefs_[$pref])) {
		unset($this->tmp_prefs_[$pref]);
	}
	return true;
}

public function getPref($pref) {
    $userpref = parent::getPref($pref);

    if (!isset($userpref)) {
      $domainpref = $this->getDomain()->getPref($pref);
      return $domainpref;
    }
    return $userpref;
}
 
/**
 * Save localy stored datas for user
 * @return  bool  true on success, false on failure
 */
 private function saveLocalUserDatas() {
    require_once('helpers/DataManager.php');
    global $sysconf_; 
    
    $password = $this->getData('password');
    
    $db_masterconf = DM_MasterConfig :: getInstance();
    if ($this->local_user_registered_) {
      $query = "UPDATE mysql_auth SET realname='".$db_masterconf->sanitize($this->getData('realname'))."', domain='".$db_masterconf->sanitize($this->getPref('domain'))."' ";
        if ($password != "notchanged") { 
            $salt = '$6$rounds=1000$'.dechex(rand(0,15)).dechex(rand(0,15)).'$';
            $crypted = crypt($password, $salt);

            $query .= ", password='".$crypted."' "; 
      }
      $query .= " WHERE username='".$db_masterconf->sanitize($this->getPref('username'))."'";
      $query .= " AND domain='".$db_masterconf->sanitize($this->getPref('domain'))."'";
    } else {
      $query = "INSERT INTO mysql_auth SET username='".$db_masterconf->sanitize($this->getPref('username'))."', realname='".$db_masterconf->sanitize($this->getData('realname'))."', domain='".$db_masterconf->sanitize($this->getPref('domain'))."' ";
      $query .= ", email='".$db_masterconf->sanitize($this->getMainAddress())."' ";
      if ($password != "notchanged") { 
         $salt = '$6$rounds=1000$'.dechex(rand(0,15)).dechex(rand(0,15)).'$';
         $crypted = crypt($password, $salt);
      
         $query .= ", password='".$crypted."' "; 
      }
    }
    if ($db_masterconf->doExecute($query)) {
      return true;
    }
    return false;
 }

 public function resetLocalPassword() {
     if (!$this->isLocalUser()) {
         return 'NOTLOCALUSER';
     }

     $sysconf_ = SystemConfig::getInstance();

     require_once($sysconf_->SRCDIR_."/www/guis/admin/application/library/Pear/Text/Password.php");
     $password = Text_Password::create(12, 'pronounceable', 'numeric');
            
     $this->getLocalUserDatas();
     $this->datas_['password'] = $password;
     $this->saveLocalUserDatas();

     $address = $this->getMainAddress();
     $command = $sysconf_->SRCDIR_."/bin/send_userpassword.pl '$address' '".$this->getPref('username')."' '".$password."' 1";
     $command = escapeshellcmd($command);
     $result = `$command`;
     $result = trim($result);

     $tmp = array();
     if (preg_match('/REQUESTSENT (\S+\@\S+)/', $result, $tmp)) {
       return 'PASSWORDRESET';
     }
     return $result;
 }

 /**
  * Delete localy stored datas for user
  * @return  bool  true on success, false on failure
  */
private function deleteLocalUserDatas() {
    require_once('helpers/DataManager.php');
    global $sysconf_;
    
    $db_masterconf = DM_MasterConfig :: getInstance();
    if ($this->local_user_registered_) {
       $query = "DELETE FROM mysql_auth WHERE username='".$db_masterconf->sanitize($this->getPref('username'))."' AND domain='".$db_masterconf->sanitize($this->getPref('domain'))."'";
       if ($db_masterconf->doExecute($query)) {
        return true;
       }
    }
    return false;
}
    

/**
 * Save user datas to database
 * @return   string  'OKSAVED' or 'OKADDED' on success, error message on failure
 */
public function save() {
  global $log_;
  $log_->log('-- BEGIN saving user: '.$this->getPref('username'), PEAR_LOG_INFO);
  global $sysconf_;
  global $admin_;

  if (isset($admin) && !$admin->canManageDomain($this->getPref('domain'))) {
     return "NOTALLOWED";
  }
  if ($this->getPref('username') == '') {
     return 'NOUSERNAME';
  }
  $ret = $this->savePrefs(null, null, '');
  if ($this->isLocalUser()) {
      $this->saveLocalUserDatas();
  }
  
  // save addresses belonging to the user
  foreach ($this->addresses_ as $add => $val) {
     $email = new Email();
     $email->load($add);
     $email->setUser($this->getRecordId('user'));
     if ($this->main_address_ == $add) {
       $email->setAsMain(1);
     } else {
       $email->setAsMain(0);
     }
     $email->save();
   }

  $_SESSION['user'] = serialize($this);
  $log_->log('-- END saving user: '.$this->getPref('username'), PEAR_LOG_INFO);
  return $ret;
}

/**
 * Delete user datas
 * @return 'OKDELETED' on success, error code on failure
 */
public function delete() {
  global $log_;
  $log_->log('-- BEGIN deleting user: '.$this->getPref('username'), PEAR_LOG_INFO);
  global $sysconf_;
  global $admin_;

  if (isset($admin) && !$admin->canManageDomain($this->getPref('domain'))) {
     return "NOTALLOWED";
  }
  
  $ret = $this->deletePrefs(null);
  
  foreach ($this->addresses_ as $add => $val) {
    $this->removeAddress($add);
  }
  
  if ($this->isLocalUser()) {
      $this->deleteLocalUserDatas();
  }
  
  $log_->log('-- END deleting user: '.$this->getPref('username'), PEAR_LOG_INFO);
  return $ret;
}

/**
 * remove an address from the user's addresses list
 * remove the address from the list and reset the user dependency in the Email user preference
 * @param  $a  string address to be removed
 * @return     true on success, false on failure
 */
public function removeAddress($a) {
  if (isset($this->addresses_[$a])) {
    $add = new Email();
    $add->load($a);
    // save address that is not already registered
    if ($add->isRegistered()) {
      $add->setUser(null);
      $add->save();
    }
    unset($this->addresses_[$a]);
  }
  $this->save();
  return true;
}

/**
 * set this address as the user's main address
 * @param  $a  string  address to be set as main
 * @return     bool    true on success, false on failure
 */
public function setMainAddress($a) {
  if (isset($this->addresses_[$a])) {
    $this->main_address_ = $a;
  }
  return true;
}

/**
 * return the main address of the user
 * @return  string main address
 */
public function getMainAddress() {
  if (isset($this->addresses_[$this->main_address_])) {
    return $this->main_address_;
  }

  $sysconf_ = SystemConfig::getInstance();
  $main_domain = $sysconf_->getPref('default_domain');
  foreach ($this->addresses_ as $addkey => $add) {
    if (preg_match('/@'.$main_domain.'$/', $addkey)) {
      return $addkey;
    }
  }
   
  reset($this->addresses_);
  return key($this->addresses_);
}

/**
 * add an address to the user's list
 * @param  $a  string  address to add
 * @return true on success, false on failure
 */
public function addAddress($a) {
  $a = strtolower($a);
  if (preg_match('/^\S+\@\S+$/', $a)) {
    $this->addresses_[$a] = $a;
    return true;
  }
  return false;
}

/**
 * return the addresses select html code
 * @todo  this has to be moved in the view layer
 * @param  $selected     string  selected value
 * @param  $onchangefct  string  javascript to be run on change event
 * @return               string  select html code
  */
public function html_getAddressesSelect($selected, $onchangefct) {
    $lang_ = Language::getInstance('user');

    $res = "<select name=\"address\" onChange=\"".$onchangefct."();\">";
    foreach ($this->addresses_ as $add => $main) {
        $res .= "<option value=\"".$add."\"";
        if ($add == $selected) {
            $res .= " selected=\"selected\"";
                }
        $res .= ">".$add;
        /*if ($add == $this->main_address_) {
            if ($lang_->print_txt('MAIN') != "") {
                $res .= " (".$lang_->print_txt('MAIN').")";
            } else {
                $res .= " (main)";
            }
        }*/
        $res .= "</option>";
    }
    $res .= "</select>";
    return $res;
}

/**
 * return the html code for the "apply all" checkbox
 * @todo  this has to be moved in the view layer
 * @return  string  html code for the checkbox
 */
public function html_ApplyAllCheckBox() {
    global $lang_;
    $sysconf_ = SystemConfig::getInstance();

        if ($sysconf_->getPref('want_aliases') > 0) {
                $ret = "<input type=\"checkbox\" name=\"update_all_addresses\" value=\"ok\" />".$lang_->print_txt('APPLYALLADDRESSES')."\n";
        } else {
                $ret = "<input type=\"hidden\" name=\"update_all_addresses\" value=\"no\" />";
        }
        return $ret;
}

/**
 * set the language preference of the user
 * @param $lang  string  language
 * @return       bool    true on success, false on failure
 */
public function set_language($lang) {
    global $lang_;
    if (!$lang_->is_available($lang)) {
        return false;
    }
    $this->setPref('language', $lang);
    $lang_->setLanguage($this->getPref('language'));
    $lang_->reload();
    $this->save();

    foreach ($this->addresses_ as $add => $val) {
            $email = new Email();
            $email->load($add);
            $email->set_language($lang);
            $email->save();
     }
     return true;
}

/**
 * Get the username of a given user by its ID
 * @param $id  numeric  id of the user
 * @return     string   username
 */
 public static function getUsernameFromID($id) {
    if (!is_numeric($id)) {
        return "";
    }
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $query = "SELECT username FROM user WHERE id=$id";
    $res = $db_slaveconf->getHash($query);
    return $res['username'];
 }

 /**
  * Get the id of the user
  * @return   numeric  user id
  */
  public function getID() {
    return $this->getRecordId('user'); 
  }
  
 /**
  * apply changes to all addresses
  * @return  boolean  true on success, false on failure
  */
  public function getAllAddressModifs() {
    foreach ($this->addresses_ as $add) {
      $email = new Email();
      $email->load($add);
      $email->getModifs();
      $email->save();
    }
    return true;
  }
  
  /**
   * set the real username (in case it is different from the login)
   * @param  string  user name
   */
  public function setName($name) {
  	if (is_string($name)) {
  		$this->datas_['realname'] = $name;
        return true;
  	}
    return false;
  }
  /**
   * return the name of the user to be displayed
   * @return  string  name of the user
   */
  public function getName() {
  	if (isset($this->datas_['realname']) && $this->datas_['realname'] != "") {
      return $this->datas_['realname'];
  	}
    return $this->getPref('username');
  }
  
  /**
   * set the user as a stub one or not
   * @param bool is_stub
   */
  public function setStub($is_stub = false) {
    $this->is_stub_ = false;
  	if ($is_stub) {
  		$this->is_stub_ = true;
  	}
  }
  
  /**
   * ask if user is stub
   * @return bool 
   */
  public function isStub() {
  	return $this->is_stub_;
  }
}
?>
