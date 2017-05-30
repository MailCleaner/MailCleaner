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
 * This is the IMAPAuthenticator class
 * This will take care of authenticate user against an IMAPserver
 * @package mailcleaner
 */
class IMAPAuthenticator extends AuthManager {
    
    protected $exhaustive_ = false;
    
    function create($domain) {
       $settings = $domain->getConnectorSettings();
       if (! $settings instanceof SimpleServerSettings) {
            return false;
        }
       
       $basedsn = '/imap/notls/norsh';
       if ($settings->getSetting('usessl') == "true" || $settings->getSetting('usessl')) { 
         $basedsn = '/imap/ssl/novalidate-cert';
       }
         
       $funct = array ("LoginDialog", "loginFunction");
       $params = array (
                        "host" => $settings->getSetting('server'),
                        "port" => $settings->getSetting('port'),
                        "baseDSN" => $basedsn,
                        'enableLogging' => true,
                        );
      $this->auth_ = new Auth('IMAP', $params, $funct);
      if ($this->auth_ instanceof Auth) {
        $this->setUpAuth();
        return true;
      }
      return false;
    }
}
?>
