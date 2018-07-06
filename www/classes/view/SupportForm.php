<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * SupportForm still need some variable check function
 * @todo to be removed.. and checked against static class
 */
require_once("utils.php");

/**
 * this class will take care of sending support request
 */
class SupportForm {

    /**
     * is form correctly posted
     * @var boolean 
     */
	private $is_ok_ = false;
    
    /**
     * form fields
     * @var array
     */
    private $fields_ = array(
                            'name' => array('', 'name', true),
                            'firstname' => array('', 'name', true),
                            'email' => array('', 'email', true),
                            'whatcanwedo' => array('', 'name', true),
                            'phone' => array('', 'name', false),
                            'company' => array('', 'name', false),
                       );
                       
    /**
     * unfilled needed field
     * @var string
     */
    private $bad_field_ = '';

                       
public function __construct() {
    foreach ($this->fields_ as $key => $value) {
    	if (isset($_POST[$key]) && $_POST[$key]!= "") {
            if (!$this->setField($key, $_POST[$key])) {
            	$this->bad_field_ = $key;
                return false;
            }
    	} else {
    	
          // if not posted but required	
          if ($this->fields_[$key][2]) {
              $this->bad_field_ = $key;
    		  return false;
    	  }
        }
        
    }
	$this->is_ok_ = true;	
}

private function setField($field, $value) {
	if (isset($this->fields_[$field])) {
		$this->fields_[$field][0] = $value;
        return true;
	} 
    return false;
}

public function is_ok() {
	return $this->is_ok_;
}

public function get_badfield() {
	return $this->bad_field_;
}

public function send() {
  global $sysconf_;
  global $user_;
  require_once("Mail.php");

  $params['host']='localhost';
  $params['port']='2525';
  $mail_object =& Mail::factory('smtp', $params);
 
  $recipients              = $sysconf_->getPref('analyse_to');
  $headers['From']         = $sysconf_->getPref('summary_from');
  $headers['To']           = $recipients;
  $headers['Subject']      = "MailCleaner support request";
  $headers['MIME-Version'] = '1.0';
  $headers['Content-Type'] = 'text/plain; charset=ISO-8859-1; format=flowed';
  $headers['Content-Transfer-Encoding'] = 'quoted-printable';

  $body = "Host: ".$sysconf_->getPref('hostname')."(".$sysconf_->getPref('hostid').")\n";
  $body .= "User: ".$user_->getPref('username')."\n";
  $body .= "Email: ".$this->fields_['email'][0]."\n";
  $body .= "Name First name: ".$this->fields_['name'][0]." ".$this->fields_['firstname'][0]."\n";
  $body .= "Phone: ".$this->fields_['phone'][0]."\n";
  $body .= "Company: ".$this->fields_['company'][0]."\n";
  $body .= "\nMessage; ".$this->fields_['whatcanwedo'][0]."\n";

  if ($mail_object->send($recipients, $headers, $body) == TRUE) {
    return 'SUPFORMSENT';
  }
  return 'CANNOTSENDSUPFORM';
}

}

?>