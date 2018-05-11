<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the main controller class, instantiates specific controllers
 */
 
/** 
 * page controller class
 * this class is a factory and mother class for all page controllers
 * 
 * @package mailcleaner
 */
class Controller {

  public function __construct() {}
  
  static public function factory($class) {
    if (@include_once('controllers/user/'.$class.".php")) {
      return new $class();
    }
    return new Controller();
  }
  
  public function processInput() {
  }
  
  public function addReplace($replace, $template) {
  	return $replace;
  }
}
?>
