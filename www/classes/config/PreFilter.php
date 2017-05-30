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
abstract class PreFilter extends PrefHandler {

    /**
     * prefilter properties
     * @var array
     */
	private $pref_ = array(
                      'name' => '',
		              'active' => 0,
                      'position' => 0,
                      'neg_decisive' => 0,
                      'pos_decisive' => 0,
                      'header' => '',
                      'timeOut' => 10,
                      'maxSize' => 500000,
                      'putSpamHeader' => 1,
                      'putHamHeader' => 0, 
	                 );
                     
    protected $where_clause_ = "";

public static function factory($name) {
  if (preg_match('/^[^a-zA-Z0-9]+$/', $name)) {
    echo "bad prefilter";
  	return null;
  }
  $filename = "config/Prefilters/".$name.".php";
  @include_once($filename);
  $class = $name;
  if (class_exists($class)) {
    return new $class();
  }
  include_once("config/Prefilters/Generic.php");
  return new Generic;
}

/**
 * constructor
 */
public function __construct() {
    $this->addPrefSet('prefilter', 'p', $this->pref_);
    $this->addSpecPrefs();
    
    $this->subload();
}

abstract protected function subload();
abstract public function addSpecPrefs();
abstract protected function subsave($posted);

/**
 * load datas from database
 * @param  $prefilter_name  string  prefilter name
 * @return                boolean  true on success, false on failure
 */
public function load($prefilter_name) {
  $this->where_clause_ .= "p.name='$prefilter_name'";
  return $this->loadPrefs('', $this->where_clause_, false);
}

/**
 * save datas to database
 * @return    string  'OKSAVED' on success, error message on failure
 */
public function save($posted) {
  $this->subsave($posted);
  $ret = $this->savePrefs('', $this->where_clause_, 'p');
  $sysconf_ = SystemConfig::getInstance();
  $sysconf_->setProcessToBeRestarted('ENGINE');
  return $ret;
}

abstract public function getSpecificTMPL();
}
?>
