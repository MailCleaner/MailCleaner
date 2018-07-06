<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * requires Net_SMTP
 */
require_once("Net/SMTP.php");
 
/**
 * This is the IMAPAuthenticator class
 * This will take care of authenticate user against an IMAPserver
 * @package mailcleaner
 */
class SMTPAuthenticator extends AuthManager {
    
    
    /**
     * values to be fetched from authentication
     * @var array
     */
    private $values_ = array();
    
    protected $exhaustive_ = false;
    
    private $server_;
    private $status_ = 0;
    
    /**
     * create the authenticator
     */
    function create($domain) {
       $settings = $domain->getConnectorSettings();
       if (! $settings instanceof SimpleServerSettings) {
            return false;
        }
        
        $this->server_ = new Net_SMTP($settings->getSetting('server'));
        if (!$this->server_) {
        	return false;
        }
        return true;
    }
    
    /**
     * overridden from AuthManager
     */
    public function start() {}
    
    /**
     * overridden from AuthManager
     */
    public function getStatus() {
    	return $this->status_;
    }
    
    /**
     * overridden from Authmanager
     */
    public function doAuth($username) {
       if ($username == '') {
         $this->status_ = 0;
         return false;
       }
       if (PEAR::isError($e = $this->server_->connect())) {
         return false;
       }
       if (PEAR::isError($e = $this->server_->auth($username, $_POST['password']))) {
       	$this->status_ = -3;
       	$this->server_->disconnect();
         return false;
       }
       $this->server_->disconnect();
       return true;
    }
    
    /**
     * overridden from Authmanager
     */
    public function getValue($value) {
      switch($value) {
        case 'username':
             return $this->values_['login'];
        case 'realname':
             return $this->values_['login'];
        default:
             return "";
      }
     return "";
   }
}
?>
