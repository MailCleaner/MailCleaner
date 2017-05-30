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
 * This class handle an external access rule
 */
class ExternalAccessRule extends PrefHandler {

    /**
     * rule properties
     */
	private $pref_ = array(
                        'id' => 0,
		                'service' => '',
		                'port' => '',
		                'protocol' => '',
		                'allowed_ip' => '',
		                'auth' => ''
	                 );
                     
   /**
    * list of possible services with default properties
    * @var array
    */
   static public $available_services_ = array(
                                    'web'    => array('80|443', 'TCP'),
                                    'mysql'  => array('3306:3307', 'TCP'),
                                    'snmp'   => array('161', 'UDP'),
                                    'ssh'    => array('22', 'TCP'),
                                    'mail'   => array('25', 'TCP'),
                                    'soap'   => array('5132', 'TCP')
                                 );

/**
 * constructor
 * @param  $service  string  service
 * @return           boolean true on success, false on failure
 */
public function __construct($service) {
    if (!isset(self::$available_services_[$service])) {
        return false;
    }
    $this->addPrefSet('external_access', 'r', $this->pref_);
    $this->setPref('service', $service);
    $this->setPref('port', self::$available_services_[$service][0]);
    $this->setPref('protocol', self::$available_services_[$service][1]);
}

/**
 * load datas from database
 * @param    $id   numeric  id of file type record
 * @return         boolean  true on success, false on failure
 */
public function load($id) {
  if (!is_numeric($id)) {
    return false;
  }
  $where = " id=$id";
  return $this->loadPrefs('', $where, true);
}

/**
 * save datas to database
 * @return    boolean  true on success, false on failure
 */
public function save() {
  if ($this->getPref('service') == "") {
    return 'BADPARAMETERS';
  }
 
  return $this->savePrefs('', '', '');
}

/**
 * delete datas from database
 * @return    string  'OKDELETED' on success, error message on failure
 */
public function delete() {
  return $this->deletePrefs(null);
}

}
?>