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
 * This class handle a file name rule
 */
class FileName extends PrefHandler {

    /**
     * file name properties
     */
	private $pref_ = array(
        'id' =>0,
		'status' => 'deny',
		'rule' => '',
		'name' => '',
		'description' => ''
	  );

/**
 * constructor
 */
public function __construct() {
    $this->addPrefSet('filename', 'f', $this->pref_);
}

/**
 * load datas from database
 * @param    $id   numeric  id of filename record
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
  if ($this->getPref('rule') == "") {
    return 'NORULENAMEGIVEN';
  }
  if ($this->getPref('name') == "") {
    $this->setPref('name', '-');
  }
  if ($this->getPref('description') == "") {
    $this->setPref('description', '-');
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
