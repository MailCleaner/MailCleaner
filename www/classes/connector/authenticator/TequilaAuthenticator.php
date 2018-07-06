<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * requires Tequila's Code
 */
require_once("tequila/TequilaClient.php");
 
/**
 * This is the TequilyAuthenticator class
 * This will take care of authenticate user against a Radius server
 * @package mailcleaner
 */
class TequilaAuthenticator extends AuthManager {
    
    /**
     * Tequila client object
     * @var TequilaClient
     */
    private $tclient;
    
    /**
     * are we authenticated ?
     * @var boolean
     */
    private $authenticated_ = false;
    
    /**
     * values to be fetched from authentication
     * @var array
     */
    private $values_ = array();
    
    protected $exhaustive_ = true;
    
    /**
     * create the authenticator
     * we will do all the authentication stuff here, instead of in doAuth() because we need to bypass
     * normal login prompt to redirect to the tequila prompt
     */
    function create($domain) {
       $settings = $domain->getConnectorSettings();
       if (! $settings instanceof TequilaSettings) {
            return false;
        }

        // construct server string
        $url = "http://";
        if ($settings->getSetting('usessl')) {
        	$url = 'https://';
        }
        $url .= $settings->getSetting('server').":".$settings->getSetting('port');
        $tequila = new TequilaClient($url, '/tmp/tequila');
        $requestInfo = array(
                                'urlacces'  => $tequila->getCurrentUrl(),
                                'service'   => 'MailCleaner',
                                'request'   => $settings->getSetting('fields'),
                             );
        $tequila->setApplicationName('MailCleaner');
        $tequila->SetWantedAttributes(split(',', $settings->getSetting('fields')));
        $tequila->SetAllowsFilter($settings->getSetting('allowsfilter'));
        $tequila->Authenticate();    
        if ($tequila->Authenticate()) {
            $att = $tequila->GetAttributes();
            if (!empty($att)) {
                
            	foreach(split(',', $settings->getSetting('fields')) as $f) {
                   
            		if (! isset($att[$f])) {
            			// redirects user to the Tequila auth form
                      $tequila->Authenticate();
            		}
            	}
                // if here, then authentication passed !
                $this->authenticated_ = true;
                $this->values_['login'] = $att[$settings->getSetting('loginfield')];
                $realname = $settings->getSetting('realnameformat');
                foreach($att as $field => $value) {
                  $realname = preg_replace("/\_\_$field\_\_/", $value, $realname);
                }
                $this->values_['realname'] = $realname;
                return true;
            }
            // redirects user to the Tequila auth form
	        $tequila->Authenticate();
        }
        $tequila->KillSessionCookie();
        // got error here...
    }
    
    /**
     * overridden from AuthManager
     */
    public function start() {}
    
    /**
     * overridden from AuthManager
     */
    public function getStatus() {}
    
    /**
     * overridden from Authmanager
     */
    public function doAuth($username) {
       return $this->authenticated_;
    }
    
    /**
     * overridden from Authmanager
     */
    public function getValue($value) {
      switch($value) {
      	case 'username':
             return $this->values_['login'];
        case 'realname':
             return $this->values_['realname'];
        default:
             return "";
      }
     return "";
   }
}
?>
