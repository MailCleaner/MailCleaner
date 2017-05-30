<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this is as list
 */
require_once('helpers/ListManager.php');

/**
 * This will take car of fetching list of users against registered users or against connector
 */
class UserList extends ListManager {
  /**
   * this is the username to be searched for
   * @var  string
   */
  private $s_username_ = "";
  /**
   * this is the domain in where the users are to be searched for
   * @var   string
   */
  private $s_domain_ = "";

/**
 * Search usernames given username part and domain
 * @param  $username  string  string to be search in usernames
 * @param  $domain    string  domain in which users shouls be searched
 * @param  $remote    bool    if we must do the remote connector search (may take some time)
 * @return            bool    true on success, false on failure
 */
public function search($username, $domain, $remote) {
  global $admin_;
  global $sysconf_;
  $this->s_username_ = $username;
  $this->s_domain_ = $domain;

  // check for administrator rights
  if (isset($admin_) && !$admin_->canManageDomain($domain)) {
      return false;
  }
  if (!in_array($domain, $sysconf_->getFilteredDomains())) {
      return false;
  }

  // first search for registered users
  $this->doRegisteredUsers();
  
  // then, if wanted, search for remote users against the connector
  if ($remote) {
     $d = new Domain();
     $d->load($this->s_domain_);
     
     $address_fetcher = AddressFetcher::getFetcher($d->getPref('address_fetcher'));
     $users = $address_fetcher->searchUsers($this->s_username_, $d);
     foreach ($users as $username => $value) {
        $this->setElement($username, $username);
     }
  }
  return true;
}


/**
 * fetch already registered users and set the internal array
 * @return    bool   true on success, false on failure
 */
private function doRegisteredUsers() {
  global $log_;
  $log_->log('-- BEGIN searchin local user list: ('.$this->s_username_.",".$this->s_domain_.")", PEAR_LOG_INFO);
  global $sysconf_;

  $db_slaveconf = DM_SlaveConfig :: getInstance();
  $query = "SELECT username FROM user WHERE ";
  $query .= "username LIKE '%".$db_slaveconf->sanitize($this->s_username_)."%' AND domain='".$db_slaveconf->sanitize($this->s_domain_)."'";

  $res = $db_slaveconf->getList($query);
  foreach ($res as $user) {
    $username = $user;
    if ($username != $this->s_username_) {
        $this->setElement($username, $username);
    }
  }
  $log_->log('-- END searchin local user list: ('.$this->s_username_.",".$this->s_domain_.")", PEAR_LOG_INFO);
  return true;
}

}
?>
