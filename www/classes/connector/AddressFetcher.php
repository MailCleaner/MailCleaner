<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class is the mother of the different address fetcher.
 * These are used to the email addresses bounded to a specific user
 * @package mailcleaner
 */
abstract class AddressFetcher {
    
  /**
  * List of available fetchers with corresponding description and classes
  * @var array
  */
  static private $fetchers_ = array (
                                     'at_login' => array('add domain', 'AddParam'),
                                     'ldap' => array('ldap lookup', 'LDAPLookup'),
                                     'text_file'  => array('text file lookup', 'FileLookup'),
                                     'param_add' => array('add a parameter', 'AddParam'),
                                     'mysql' => array('sql lookup', 'SQLLookup'),
                                     'local' => array('local', 'SQLLookup')
                                     );
                                     
  /**
   * internal type of fetcher
   * @var  string
   */
   private $type_ = 'local';
   
   /**
    * list of addresses
    * @var array
    */
   private $addresses_ = array();
   
   /**
    * constructor
    * @param  $type  string  internal fetcher type
    */         
   public function __construct($type) {
        $this->type_ = $type;
   }
   
   /**
   * AddressFetcher factory
   */
  static public function getFetcher($type) {
    if (!isset(self::$fetchers_[$type][1])) {
      return null;
    }
    $filename = "connector/fetcher/".self::$fetchers_[$type][1].".php";
    include_once($filename);
    $class = self::$fetchers_[$type][1];
    if (class_exists($class)) {
      return new $class($type);
    }
    return null;
  }
  
  /**
  * Main fetcher method
  * @param  $login_given  string  this is the username of the user
  * @param  $domain_name  Domain  the domain of the user
  * @return               array   array of email addresses (keys are addresses, value is 1 for main, 0 otherwise)
  */
 abstract public function fetch($username, $domain);
 
 
 /**
  * get the internal formatter type
  * @return  string  internal formatter type
  */
  public function getType() {
    return $this->type_;
  }
  
  /**
   * add an address to the list
   * @param  $address  string  address to add
   * @param  $main     bool    true if this is a main address, false otherwise
   * @return           bool    true on success, false on failure
   */
   protected function addAddress($address, $main) {
     $address = strtolower($address);
     $address = utf8_decode($address);
     $this->addresses_[$address] = $main;
   }
   
   /**
    * get the addresses list
    * @return  array  address list
    */
    protected function getAddresses() {
        // fetch already registered addresses
        return $this->addresses_;
    }
    
    /**
     * get the list of available address fetchers
     * return  array  list of available fetchers
     */
     static public function getAvailableFetchers() {
        $ret = array();
        foreach(self::$fetchers_ as $key => $val) {
            $ret[$val[0]] = $key;
        }
        return $ret;
     }
     
     /**
      * return if the list of address is static or if it can be externally modified
      * @return  bool  true if list can be modified, false if not
      */
      public function canModifyList() {
        return true;
      }

      public function isExhaustive() {
         return !$this->canModifyList(); 
      }
}
?>
