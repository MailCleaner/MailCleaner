<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * This class takes care of alias adding requests
 */ 
class AliasRequest {
    
/**
 * Generate the alias request
 * Will store the request in the database and generate the mail message
 * @param  $alias    string    alias requested
 * @return           string    html status string 
 */
public function requestForm($alias) {
    require_once('objects.php');
	global $lang_;
    global $user_;
    global $sysconf_;
    if (! $user_ instanceof User) {
        return "";
    }

    $alias = strtolower($alias);
    //check address format and domain validity   
    $matches = array();
    if (! preg_match('/[a-zA-Z0-9_\-.]+\@([a-zA-Z0-9_\-.]+)/', $alias, $matches)) {
       return 'BADADDRESSFORMAT';
    }
    if (! in_array($matches[1], $sysconf_->getFilteredDomains())) {
       return 'NOTFILTEREDDOMAIN';
    }

    //check if address is already registered
    require_once('helpers/DataManager.php');
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $alias = $db_slaveconf->sanitize($alias);
    $query = "SELECT address FROM email where address='$alias' and user!=0";
	$res = $db_slaveconf->getHash($query);
    if (is_array($res) && isset($res['address']) && $res['address'] == $alias) {
      return 'ALIASALREADYREGISTERD';
    }
    
    //check if no previous request are pending for this address
    
    // first delete old records
    $query = "DELETE FROM pending_alias WHERE date_in != CURDATE();";
    $db_slaveconf->doExecute($query);
    
    // and the check if still pending request exists
    $query = "SELECT alias FROM pending_alias WHERE alias='$alias'";
    $res = $db_slaveconf->getHash($query);
    if (is_array($res) && isset($res['alias']) &&  $res['alias'] == $alias) {
      return 'ALIASALREADYPENDING';
    }
    
    // save user if not registered, so that alias request can have a user reference
    if (!$user_->isRegistered()) {
      $user_->save();
    }

    // generate unique id
    $token = md5 (uniqid (rand()));
    $query = "INSERT INTO pending_alias SET id='$token', date_in=NOW(), alias='$alias', user='".$user_->getID()."'";
	$db_slaveconf->doExecute($query);

/*
    // generate and send the confirmation mail
    require_once("Mail.php");

    $template = fopen($sysconf_->SRCDIR_."/templates/aliasquery/".$lang_->getLanguage()."/default.txt", "r");
    if(! $template) {
       return;
    }
    while (!feof($template)) {
      $buffer = fgets($template, 4096);
      if (preg_match('/^Subject: (.*)$/', $buffer, $matches)) {
          $headers['Subject'] = trim($matches[1]);
      }else {
          $body .= $buffer;
      }
    }
    fclose($template);

    $params['host']='localhost';
    $params['port']='2525';
    $mail_object =& Mail::factory('smtp', $params);

    $recipients = $alias;
    $headers['From']                        = $sysconf_->getPref('summary_from');
    $headers['To']                          = $alias;
    $headers['Content-Type']                = 'text/plain; charset=ISO-8859-1; format=flowed';
    $headers['Content-Transfer-Encoding']   = 'quoted-printable';

	$http = "http://";
    //@todo check if we need http or https
    require_once('config/HTTPDConfig.php');
    $httpd = new HTTPDConfig();
    $httpd->load();
	if ($httpd->getPref('use_ssl') == "true") {
	  $http = "https://";
	}

    $body = str_replace('__ALIAS__', $alias, $body);
    $body = str_replace('__USER__', $user_->getPref('username'), $body);
    $query = 'id=3D'.$token.'&add=3D'.$alias.'&lang=3D'.$lang_->getLanguage(); // for iso8895-1
    $body = str_replace('__VALIDATEURL__', $http.$_SERVER['SERVER_NAME']."/aa.php?".$query, $body);
    $query .= '&m=3Dd';
    $body = str_replace('__REFUSEURL__', $http.$_SERVER['SERVER_NAME']."/aa.php?".$query, $body);

    $res = "";
    if ($mail_object->send($recipients, $headers, $body) == TRUE) {
       $res = 'ALIASPENDING';
    } else {
       $res = 'ALIASERRORSENDIG';
	}
	return $res;
*/
   $sysconf_ = SystemConfig::getInstance();
   
   // create the command string
   $command = $sysconf_->SRCDIR_."/bin/send_aliasrequest.pl ".$user_->getPref('username')." ".$alias." ".$token." ".$lang_->getLanguage();
   $command = escapeshellcmd($command);
   // and launch
   $result = `$command`;
   $result = trim($result);

   $tmp = array();
   if (preg_match('/REQUESTSENT (\S+\@\S+)/', $result, $tmp)) {
     return 'ALIASPENDING';
   } 
   return  $result;
}

/**
 * This will check the request and add the alias if correct
 * @param  $id     string  MD5 hash of unique id
 * @param  $alias  string  alias requested
 * @return         string    html status string 
 */
public function addAlias($id, $alias) {
    if (!is_string($id)) {
        return false;
    }
    require_once('helpers/DataManager.php');
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $alias = $db_slaveconf->sanitize($alias);
    $id = $db_slaveconf->sanitize($id);
    $lang_ = Language::getInstance('user');

    // check if pending alias exists and id is correct
    $query = "SELECT a.user, u.username, u.id, u.domain FROM pending_alias a, user u WHERE a.id='$id' AND a.alias='$alias' AND a.user=u.id";
    $res = $db_slaveconf->getHash($query);
    
    if (!is_array($res) || ! isset($res['username'])) {
        return 'ALIASNOTPENDING';
    }
    
    // ok, so we create the user instance
    require_once("user/User.php");
	$user_ = new User();
    $user_->setDomain($res['domain']);
    $user_->load($res['username']);
	
    // and we delete the pending request
	$query = "DELETE FROM pending_alias WHERE id='$id' AND alias='$alias'";
	$db_slaveconf->doExecute($query);

    // finally, we add the address to the user
    //@todo check return codes
	$user_->addAddress($alias);
	$user_->save();

	return 'ALIASADDED';
}

/**
 * This will check the request and remove the request if correct
 * @param  $id     string  MD5 hash of unique id
 * @param  $alias  string  alias requested
 * @return         string  html status string 
 */
public function remAlias($id, $alias) {
    if (!is_string($id)) {
        return false;
    }
    require_once('helpers/DataManager.php');
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $alias = $db_slaveconf->sanitize($alias);
    $id = $db_slaveconf->sanitize($id);
    $lang_ = Language::getInstance('user');

    // check if pending alias exists and id is correct
    $query = "SELECT a.user, u.username, u.id, u.domain FROM pending_alias a, user u WHERE a.id='$id' AND a.alias='$alias' AND a.user=u.id";
    $res = $db_slaveconf->getHash($query);
    
    if (!is_array($res)) {
        return "<font color=\"red\">".$lang_->print_txt('ALIASNOTPENDING')."</font><br/><br/>";
    }
    
	$query = "DELETE FROM pending_alias WHERE id='$id' AND alias='$alias'";
	$db_slaveconf->doExecute($query);

	return 'ALIASREQUESTREMOVED';
}

public function remAliasWithoutID($alias) {
    if (!is_string($alias)) {
        return false;
    }
    require_once('helpers/DataManager.php');
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    $alias = $db_slaveconf->sanitize($alias);
    
    $query = "DELETE FROM pending_alias WHERE alias='$alias'";
    $db_slaveconf->doExecute($query);
    return 'ALIASREQUESTREMOVED';
}

}

?>
