<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class takes care of reformatting the login passed by removing any domain eventually given.
 * @package mailcleaner
 */
class SimpleFormatter extends LoginFormatter {
     
     
     public function format($login_given, $domain_name) {
       $matches = array();
       if (preg_match('/^(\S+)[\@\%](\S+)$/', $login_given, $matches)) {
        return $matches[1];
       }
       return $login_given; 
     }
}
?>
