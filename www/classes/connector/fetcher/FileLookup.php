<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class takes care of fetching addresses in a text file
 * @package mailcleaner
 */
 class Filelookup extends AddressFetcher {
    
    
    public function fetch($username, $domain) {
      $sysconf_ = SystemConfig::getInstance();
      // for now, username <=> address relation file is in a predefined place
      $file = $sysconf_->VARDIR_."/spool/mailcleaner/addresses/".$domain->getPref('name');
      // and fields are statically set:  username,address1,address2,...
      $cmd = "grep -Ei '^".$username.",' ".$file." | cut -d',' -f3-";
      $res = `$cmd`;
      $res = trim($res);
      if ($res == '') {
         return $this->getAddresses();
      }
      $adds = split(',', $res);
      foreach ($adds as $a) {
        $this->addAddress($a, $a);
      }
  
      // old way of doing that: list($t1, $t2, $t3, $field, $file) = split (":", $domain->getPref('auth_param'));
      
      return $this->getAddresses();
    }
    
    public function searchUsers($u, $d) {
      // @todo  everything here ;o)
      return array();
    }
    
    public function searchEmails($l, $d) {
      $addresses = array();
      $sysconf_ = SystemConfig::getInstance();
      $file = $sysconf_->VARDIR_."/spool/mailcleaner/addresses/".$d->getPref('name');
      if (!file_exists($file)) {
        return $addresses;
      }
      $lines = file($file);
      foreach ($lines as $line) {
         $line2 = preg_replace('/^[^,]+/', '', $line);
         $fields = preg_split('/\,/', $line2);

         foreach ($fields as $field) {
            if (preg_match('/.*'.$l.'.*@'.$d->getPref('name').'/', $field)) {
              $addresses[$field] = $field;
            }
         }
      } 
      // @todo  everything here ;o)
      ksort($addresses);
      return $addresses;
    }
 }
?>
