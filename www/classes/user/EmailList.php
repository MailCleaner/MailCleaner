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
 * This will take car of fetching list of email addresses against registered addresses or against connector
 */
class EmailList extends ListManager 
{
  /**
  * this is the local part of the address to be searched for
  * @var  string
  */
  private $s_local_part_ = "";
  /**
  * this is the domain part of the address to be searched for
  * @var  string
  */
  private $s_domain_part_ = "";

/**
 * Search email addresses given local part and domain
 * @param  $local     string  string to be search as local part
 * @param  $domain    string  domain in which email should be searched
 * @param  $remote    bool    if we must do the remote connector search (may take some time)
 * @return            bool    true on success, false on failure
 */ 
  public function search($local, $domain, $remote) {
    global $admin_;
    global $sysconf_;
    $this->s_local_part_ = $local;
    $this->s_domain_part_ = $domain;

    // check for administrator rights
    if (isset($admin) && !$admin->canManageDomain($this->s_domain_part_)) {
      return false;
    }
    if (!in_array($this->s_domain_part_, $sysconf_->getFilteredDomains())) {
      return false;
    }

    // first search for registered users
    $this->doRegisteredEmails();
    
    // then, if wanted, search for remote addresses against the connector
    if ($remote) {
      $d = new Domain();
      $d->load($this->s_domain_part_);
     
      $address_fetcher = AddressFetcher::getFetcher($d->getPref('address_fetcher'));
      $emails = $address_fetcher->searchEmails($this->s_local_part_, $d);
      foreach ($emails as $address => $value) {
         $address = strtolower($address);
        $this->setElement($address, $address);
      }
    }
  }

  /**
   * fetch already registered emails and set the internal array
   * @return    bool   true on success, false on failure
   */
  private function doRegisteredEmails() {
    global $log_;
    $log_->log('-- BEGIN searching local email list: ('.$this->s_local_part_.",".$this->s_domain_part_.")", PEAR_LOG_INFO);
    global $sysconf_;

    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $query = "SELECT address FROM email WHERE ";
    $query .= "address LIKE '".$db_slaveconf->sanitize($this->s_local_part_)."%@".$db_slaveconf->sanitize($this->s_domain_part_)."'";

    $res = $db_slaveconf->getList($query);
    foreach ($res as $email) {
      $email = strtolower($email);
      $this->setElement($email, $email);
    }
    $log_->log('-- END searching local email list: ('.$this->s_local_part_.",".$this->s_domain_part_.")", PEAR_LOG_INFO);
    return true;
  }
}
?>
