<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class is the mother of the Login Formatters.
 * These are used to modify and reformat the login entered by the user in order to be correctly passed to the authentication server
 * @package mailcleaner
 */
abstract class LoginFormatter {
 
 
 /**
  * List of available connector with corresponding classes
  * @var array
  */
  static private $formatters_ = array (
                                     'username_only' => array('username', 'SimpleFormatter'),
                                     'at_add' => array('username@domain', 'DomainAddFormatter'),
                                     'percent_add'  => array('username%domain', 'DomainAddFormatter')
                                     );
                           
  /**
   * internal type of formatter
   * @var  string
   */
   private $type_ = 'username_only';
            
   /**
    * constructor
    * @param  $type  string  internal formatter type
    */         
   public function __construct($type) {
        $this->type_ = $type;
   }
                    
  /**
   * Formatter factory
   */
  static public function getFormatter($type) {
    if (!isset(self::$formatters_[$type][1])) {
      $type = 'username_only';
    }
    $filename = "connector/formatter/".self::$formatters_[$type][1].".php";
    include_once($filename);
    $class = self::$formatters_[$type][1];
    if (class_exists($class)) {
      return new $class($type);
    }
    return null;
  }
    
 /**
  * Main formatting method
  * @param  $login_given  string  this is the username string entered by the user
  * @param  $domain_name  string  the name of the domain of the user
  * @return               string  reformatted login name
  */
 abstract public function format($login_given, $domain_name);
 
 
 /**
  * get the internal formatter type
  * @return  string  internal formatter type
  */
  public function getType() {
    return $this->type_;
  }
    
  /**
   * get the list available formatters
   * @return   array  list of available formatters
   */
   static public function getAvailableFormatters() {
     $ret = array();
     foreach (self::$formatters_ as $key => $value) {
        $ret[$value[0]] = $key;
     }
     return $ret;
   }
} 
?>
