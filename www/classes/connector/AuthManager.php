<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * requires PEAR's Auth class
 */
require_once("Auth.php");
require_once 'Log.php';
require_once 'Log/observer.php';
 
/**
 * This class is the main authenticator factory
 * These are used to let the users authenticate against different kind of server
 * @package mailcleaner
 */
abstract class AuthManager {
    
  /**
  * List of available authenticators with correspondig classes
  * @var array
  */
  static private $authenticators_ = array (
                                     'local' => array('local', 'SQLAuthenticator'),
                                     'imap' => array('imap', 'POPIMAPAuthenticator'),
                                     'pop3'  => array('pop3', 'POPIMAPAuthenticator'),
                                     'ldap' => array('ldap/active directory', 'LDAPAuthenticator'),
                                     'radius' => array('radius', 'RadiusAuthenticator'),
                                     'smtp' => array('smtp', 'SMTPAuthenticator'),
                                     'sql' => array('sql database', 'SQLAuthenticator'),
                                     'admin' => array('admin', 'AdminAuthenticator'),
                                     'tequila' => array('tequila', 'TequilaAuthenticator'),
                                     'digest' => array('digest', 'DigestAuthenticator')
                                     );
                                     
  /**
   * internal type of authenticator
   * @var  string
   */
   private $type_ = 'local';
   
   /**
    * PEAR's Auth object
    * @var Auth
    */
    protected $auth_;
    
    protected $logObserver_;
    protected $hiddenerrors_ = array('No login session.');
 
   /**
    * constructor
    * @param  $type  string  internal authenticator type
    */         
   public function __construct($type) {
        if (isset(self::$authenticators_[$type][1])) {
           $this->type_ = $type;
        } 
   }  
   
   /**
   * Authenticator factory
   */
  static public function getAuthenticator($type) {
    if (!isset(self::$authenticators_[$type][1])) {
      $type = "local";
    }
    $filename = "connector/authenticator/".self::$authenticators_[$type][1].".php";
    include_once($filename);
    $class = self::$authenticators_[$type][1];
    if (class_exists($class)) {
      return new $class($type);
    }
    return null;
  }
  
  /**
  * create authenticator using pear's Auth object
  * @param  $domain_name  Domain  the domain of the user
  * @return               bool    true on success, false on failure
  */
 abstract public function create($domain);
 
 /**
  * set some default value once the Auth object is set up
  * @return               bool   true on succes, false on failure
  */
 protected function setUpAuth() {
    $this->auth_->setExpire(0);
    $this->auth_->setIdle(0);
    $this->auth_->setShowLogin(0);
 }
 
 
 /**
  * start function needed by Auth
  */
  public function start() {
    $this->auth_->start();
  }
  
  /**
   * get authentication status
   */
  public function getStatus() {
    return $this->auth_->getStatus();
  }
  
 /**
  * do the authentication job here
  * username is normally passed via $_POST['username'] but will be replaced here by the formatted value
  * password is passed via the $_POST['password']
  * this method may be overloaded in child class if we don't use the Auth class
  * @param $username    string  username to be used for authentication (formatted by LoginFormatter)
  * @return             bool    true if user is succesfully authenticated, false otherwise  
  */
  public function doAuth($username) {
    if (!isset($this->auth_) || ! $this->auth_ instanceof Auth) {
        return false;
    }
    if ($_POST['password'] == '') {
        return false;
    }
    
    $this->logObserver_ = new Auth_Log_Observer(PEAR_LOG_DEBUG);
    $this->auth_->attachLogObserver($this->logObserver_);
    $this->auth_->start();
    if ($this->auth_->getAuth()) {
        return true;
    }
    
    return false;
  }
  
  
 /**
  * get the internal formatter type
  * @return  string  internal formatter type
  */
  public function getType() {
    return $this->type_;
  }
  
  /**
   * get available connectors
   * @return  array   array of available connectors
   */
   static public function getAvailableConnectors() {
     $ret = array();
     foreach (self::$authenticators_ as $key => $value) {
        if ($key != 'admin') {
          $ret[$value[0]] = $key;
        }
     }
     return $ret; 
   }
   
   /**
    * get a value that could have been retrieved during authentication
    * @param   value   string  value to be retrieved
    * @return          mixed  value retrieved
    */
   public function getValue($value) {
   	 return "";
   }
   
   public function getMessages() {
     $ret = array();
     foreach ($this->logObserver_->messages as $msg) {
     	if (!preg_match('/called./', $msg['message'])) {
     		$errmsg = preg_replace('/AUTH: /', '', $msg['message']);
     		if (!in_array($errmsg, $this->hiddenerrors_)) {
     			$ret[] = $errmsg;
     		}
     	}
     }
   	 return $ret;
   }
   
   public function isExhaustive() {
       if (isset($this->exhaustive_)) {
         return $this->exhaustive_;
       }
       return false;
   }
}

class Auth_Log_Observer extends Log_observer {

    var $messages = array();

    function notify($event) {
        $this->messages[] = $event;
    }

}
?>
