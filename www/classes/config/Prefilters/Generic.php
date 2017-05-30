<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this is a preference handler
 */
 require_once('helpers/PrefHandler.php');

/**
 * This class is only a settings wrapper for the PreFilter modules configuration
 */
class Generic extends PreFilter {

public function subload() {}

public function addSpecPrefs() {}

public function getSpecificTMPL() {
  return "";  
}

public function getSpeciticReplace($template, $form) {  
  return array();
}

public function subsave($posted) {}
}
?>
