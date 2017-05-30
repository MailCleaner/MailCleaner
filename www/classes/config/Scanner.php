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
 * This class is only a settings wrapper for the antivirus scanner configuration
 */
class Scanner extends PrefHandler {

    /**
     * scanner properties
     * @var array
     */
	private $pref_ = array(
                      'name' => '',
		              'comm_name' => '',
		              'active' => 0,
		              'path' => '/usr/local',
		              'installed' => 0,
		              'version' => '',
		              'sig_version' => ''
	                 );


/**
 * constructor
 */
public function __construct() {
    $this->addPrefSet('scanner', 's', $this->pref_);
}

/**
 * load datas from database
 * @param  $scanner_name  string  scanner name
 * @return                boolean  true on success, false on failure
 */
public function load($scanner_name) {
  $where = "name='$scanner_name'";
  return $this->loadPrefs('', $where, false);
}

/**
 * save datas to database
 * @return    string  'OKSAVED' on success, error message on failure
 */
public function save() {
  $where = "name='".$this->getPref('name')."'";
  return $this->savePrefs('', $where, '');
}

/**
 * return the installation status string
 * @return  string  html installation status string
 */
public function getInstalledStatus() {
  global $lang_;
  if ($this->getPref('installed') > 0) {
    return $lang_->print_txt('INSTALLED');
  }
  return "<pan style=\"font-style:italic;\">".$lang_->print_txt('NOTINSTALLED')."</span>";     
}
}
?>
