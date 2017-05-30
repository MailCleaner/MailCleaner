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
 
/**
 * This is the LDAPAuthenticator class
 * This will take care of authenticate user against a LDAP server
 * @package mailcleaner
 */
class LDAPAuthenticator extends AuthManager {
    
    protected $exhaustive_ = true;
    protected $domain_ = null;
    
    function create($domain) {
       $settings = $domain->getConnectorSettings();
       if (! $settings instanceof LDAPSettings) {
            return false;
        }
       $this->domain_ = $domain;
       $url = $settings->getURL();
       
       $funct = array ("LoginDialog", "loginFunction");
       $params = array (
                        "url" => $url,
                        "basedn" => $settings->getSetting('basedn'),
                        "binddn" => $settings->getSetting('binduser'),
                        "bindpw" => $settings->getSetting('bindpassword'),
                        "userattr" => $settings->getSetting('useratt'),
                        "useroc" => '*',
                        "userfilter" => "",
                        "debug" => 'false',
                        "version" => intval($settings->getSetting('version')),
                        "referrals" => false,
			"start_tls" => true,
                      );

      $this->auth_ = new Auth('LDAP', $params, $funct);
      if ($this->auth_ instanceof Auth) {
        $this->setUpAuth();
        return true;
      }
      return false;
    }

    public function doAuth($username) {
      $ret =  parent::doAuth($username);

      if ($ret) {
        return true;
      }
      
      // if failed, try the same without startTLS enforcement
      $settings = $this->domain_->getConnectorSettings();
      if (! $settings instanceof LDAPSettings) {
        return false;
      }
      $url = $settings->getURL();
      $funct = array ("LoginDialog", "loginFunction");
      $params = array (
                        "url" => $url,
                        "basedn" => $settings->getSetting('basedn'),
                        "binddn" => $settings->getSetting('binduser'),
                        "bindpw" => $settings->getSetting('bindpassword'),
                        "userattr" => $settings->getSetting('useratt'),
                        "useroc" => '*',
                        "userfilter" => "",
                        "debug" => 'false',
                        "version" => intval($settings->getSetting('version')),
                        "referrals" => false,
                        "start_tls" => false,
                      ); 
      $this->auth_ = new Auth('LDAP', $params, $funct);
      if (!$this->auth_ instanceof Auth) {
        return false;
      }
      $this->setUpAuth();

      $this->logObserver_ = new Auth_Log_Observer(PEAR_LOG_DEBUG);
      $this->auth_->attachLogObserver($this->logObserver_);
      $this->auth_->start();
      if ($this->auth_->getAuth()) {
        return true;
      }

      return false;
    }
}
?>
