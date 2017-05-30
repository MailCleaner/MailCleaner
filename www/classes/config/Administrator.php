<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this is a preference handler
 */
require_once('helpers/PrefHandler.php');
/**
 * this is the datas wrapper for the administrator objects
 */
class Administrator extends PrefHandler {

  /**
   * administrator settings
   * @var array
   */
  private $pref_ = array(
          'username' => '',
          'password' => '',
 	      'can_manage_users'  => 0,
	      'can_manage_domains' => 0,
	      'can_configure' => 0,
	      'can_view_stats' => 0,
	      'can_manage_host' => 0,
	      'domains' => '',
          'web_template' => 'default'
	      );
          
  /**
   * password confirmation
   * @var string
   */
  private $confirmation_ = "";
  
  /**
   * password hash backup
   * @var string
   */
  private $password_ = "";
    
  /**
   * domains manageable by administrator
   * @var array
   */   
  private $domains_ = array();

/**
 * constructor
 */ 
public function __construct() {
     $this->addPrefSet('administrator', 'a', $this->pref_);
}

/**
 * set the password confirmation
 * @param  $confirmation  string  password confirmation
 * @return                boolean true on success, false on failure
 */
public function setPasswordConfirmation($confirmation) {
  if (is_string($confirmation)) {
    $this->confirmation_ = $confirmation;
    return true;
  }
  return false;
}

/**
 * add a domain to the manageable domains
 * @param  $domain  string  domain name to add
 * @return          boolean true on success, false on failure
 */
private function addDomain($domain) {
  if (is_string($domain)) {
    $this->domains_[$domain] = $domain;
    return true;
  }
  return false;   
}

/**
 * return the domain list manageable by administrator
 * @return   array  domain list
 */
public function getDomains() {
    return $this->domains_;
}

/**
 * check if administrator can add a domain
 * @return  boolean  true if authorized, false if not
 */
public function canAddDomain() {
  if (isset($this->domains_['*'])) {
    return true;
  }
  return false;
}

/**
 * check id admin can manage a given domain or a given domains list
 * @param  $domains  mixed   domain name or list of domains
 * @return           boolean true if authorized, false if not
 */
public function canManageDomain($domain) {
  if (empty($this->domains_)) {
    return false;
  }
  if (isset($this->domains_['*'])) {
    return true;
  }
  $d = array();
  if (is_string($domain)) {
    array_push($d, $domain);
  } elseif (!is_array($domain)) {
    return false;
  } else {
    $d = $domain;
  }
  foreach ($d as $dom) {
    if (!isset($this->domains_[$dom])) {
       return false;
    }
  }
  return true;
}

/**
 * load administrator datas from database
 * @param  $name  string  administrator login name
 * @return        boolean true on success, false on failure
 */
public function load($name) {
  
  if (!is_string($name) || $name == "") {
    return false;
  }
   
  require_once('helpers/DM_SlaveConfig.php');
  $db_slaveconf = DM_SlaveConfig :: getInstance();
  $name = $db_slaveconf->sanitize($name);
  $where = "username='".$name."'";
  $ret = $this->loadPrefs('username as a_id, ', $where, false);
  $domains = split(',', $this->getPref('domains'));
  foreach ($domains as $domain) {
    if ($domain != "") {
     $this->domains_[$domain] = $domain; 
    }
  }
  $this->password_ = $this->getPref('password');

  return $ret;
}

/**
 * save administrator datas to database
 * @return  'OKSAVED' or 'OKADDED' on success, error message on failure
 */
public function save() {
 global $sysconf_;
 $retok = "";
 // check if username is given
 if ($this->getPref('username') == "") {
    return "NOUSERNAMEGIVEN";
 }
 // check if password and confirmation are identical, unless password is *******
 if ($this->getPref('password') != $this->confirmation_ && $this->getPref('password') != "******") {
   return "PASSWORDSDONOTMATCH";
 }
 // check if a password is given in case of a new administrator
 if (!$this->isLoaded() && $this->getPref('password') == "******") {
    return "PLEASEGIVEPASSWORD";
 }
 
 // if password are identical, then save
 if ($this->getPref('password') == $this->confirmation_) {
 	// encrypt password
    $this->setPref('password', crypt($this->getPref('password')));
 } else {
 	$this->setPref('password', $this->password_);
 }
 
 $where = "username='".$this->getPref('username')."'";
 
 return $this->savePrefs('', $where, '');
}

/**
 * delete an administrator datas
 * @return  string  'OKDELETED' on success, error message on failure
 */
public function delete() {
  $where = "username='".$this->getPref('username')."'";
  return $this->deletePrefs($where);
}

/**
 * check administrator permissions and redirect if not authorized
 * @params  $perms  array    permissions to check
 * @return          boolean  true on success, false on failure
 */
public function checkPermissions($perms) {
  if (!$this->hasPerm($perms)) {
      header('Location: notallowed.php');
  }
  return true;
}

/**
 * check if adminsitrator has some right to do that
 * @param  $perms  array   permissions to check
 * @return         boolean true if  authorized, false if not
 */
public function hasPerm($perms) {
  foreach($perms as $perm) {
    if (!$this->getPref($perm) != "" || $this->getPref($perm) != 1) {
     return false;
    }
    return true;
  }
}

/**
 * check if admin already exists in database
 * @return  boolean  true on success, false on failure
 */
public function isNew() {
  return !$this->loaded_;
}

/**
 * check if administrator is authorized to view template block
 * @param  $access  string  access name
 * @return          boolean true if allowed, false if not
 */
public function canSeeBlock($access) {
  global $sysconf_;
  $access = strtolower($access);
  // not allowed if we are on a slave except for base config
  if (!$sysconf_->ismaster_ && $access != 'baseconfig') {
    return false;
  }
  // do not show base config if master
  if ($sysconf_->ismaster_ && $access == 'baseconfig') {
    return false;
  }
  if ($access == 'baseconfig') {
    $access = 'configure';
  }
  if ($this->getPref("can_".$access) == "1") {
    return true;
  }
  return false;
}
}
?>