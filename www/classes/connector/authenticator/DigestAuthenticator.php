<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * This is the DigestAuthenticator class
 * This will take care of authenticate using previously sent digest ID
 * @package mailcleaner
 */
class DigestAuthenticator extends AuthManager {
    
    
    /**
     * values to be fetched from authentication
     * @var array
     */
    private $values_ = array('stub_user' => 1);
    
    protected $exhaustive_ = false;
    
    private $server_;
    private $status_ = 0;
    
    /**
     * create the authenticator
     */
    function create($domain) {
        return true;
    }
    
    /**
     * overriden from AuthManager
     */
    public function start() {}
    
    /**
     * overriden from AuthManager
     */
    public function getStatus() {
    	return $this->status_;
    }
    
    /**
     * overriden from Authmanager
     */
    public function doAuth($username) {
       if (!isset($_REQUEST['d']) || !preg_match('/^[0-9a-f]{32}(?:[0-9a-f]{8})?$/i', $_REQUEST['d'])) {
       	  $this->status_ = -3;
       	  return false;
       }
       
       $digest_id = $_REQUEST['d'];
       $use_user_prefs = $_REQUEST['p'];
       
       // first purge very old accesses
       $query = "DELETE FROM digest_access WHERE DATEDIFF(date_expire, NOW()) < -60;";
       $query .= "WHERE id='".$digest_id."'";
       require_once ('helpers/DataManager.php');
       $db = DM_MasterConfig :: getInstance();
       $this->last_query_ = $query;
      
       // query access
       $query = "SELECT id, date_in, date_expire, address, DATEDIFF(date_expire, NOW()) as expire ";
       $query .= ", DATEDIFF(NOW(), date_start) as nbdays ";
       $query .= " FROM digest_access ";
       $query .= " WHERE id='".$digest_id."'";
       $this->last_query_ = $query;
       $res = $db->getHash($query);
       if (is_array($res) && !empty($res)) {
       	  if ($res['expire'] < 0) {
            // expires
            $this->status_ = -2;
            return false;
       	  }
       	  // auth passed !
       	  $address = $res['address'];
          if ($use_user_prefs != 'u') {
              $this->values_['gui_displayed_days'] = $res['nbdays'] + 1;
          }
          $this->values_['domain'] = '';
          
       	  // now check if address belongs to user
       	  $query = "SELECT u.username, u.domain FROM user u, email e WHERE e.user=u.id AND e.address='".$address."'";
       	  $this->last_query_ = $query;
          $res = $db->getHash($query);
          if (is_array($res) && !empty($res)) {
          	$this->values_['username'] = $res['username'];
                $this->values_['domain'] = $res['domain'];
          	$this->values_['stub_user'] = 0;
          } else {
       	  // or if we are stub
            $this->values_['username'] = $address;

            $this->values_['stub_user'] = 1;
          }
          if ($this->values_['domain'] == '' && preg_match('/\S+\@(\S+)/', $address, $matches)) {
              $this->values_['domain'] = $matches[1];
          }
          $this->values_['realname'] = $this->values_['username'];
          $this->values_['mainaddress'] = $address;
          $_SESSION['requestedaddress'] = $address;
          $this->status_ = 0;
          return true;
       }
       // wrong login
       $this->status_ = -3;
       return false;
    }
    
    /**
     * overriden from Authmanager
     */
    public function getValue($value) {
      if (isset($this->values_[$value])) {
      	return $this->values_[$value];
      }
     return "";
   }
}
?>
