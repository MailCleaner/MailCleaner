<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 */
 
 /** 
 * Preference Set Handler (store/create/delete)
 * This class takes care of loading/storing/deleting a set of preference
 * to or from a database table.
 * It can manage multiple table with simple constrains (id reference)
 * 
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
class PrefHandler
{
  /**
   * The main preferences set arrays
   * This is an array of array containing arrays of preferences set, one per database table
   * Keys are table names
   * @var array array of arrays of preferences
   */
  private $pref_tables_ = array();
  /**
   * Relation array between table name and shortcut
   * Keys are table names
   * @var array
   */
  private $tables_shortcuts_ = array();
  /**
   * Array of database id for each preference set
   * Keys are table shortcuts
   * @var array
   */
  private $record_ids_ = array();
  /**
   * Array of database relation between preferences set
   * Keys are table names
   * @var array
   */
  private $relations_ = array();
  /**
   * set if we need to automatically fetch row ids
   * @var bool
   */
  private $use_ids_ = false;
  /**
   * set to the last database query executed
   * @var  string
   */
  private $last_query_ = "";
  /**
   * the table we want to use another field for id
   * @var  array
   */
  private  $id_fields_ = array();
  /**
   * keep if loaded or not
   * @var  boolean
   */ 
  private $loaded_ = false;
  
  private function __construct() {}
  
  /**
   * Add a preference set
   * Used to add a preference set (an array). Multiple set can be added.
   * @param $table_name     string the database name of the table
   * @param $table_shortcut string the database short name of the table use in queries (must be unique)
   * @param $pref_array     array  array of preferences with default values
   * @return                bool   false on failure, true on success
   */
  protected function addPrefSet($table_name, $table_shortcut, $pref_array) {
    $this->tables_shortcuts_[$table_name] = $table_shortcut;
    foreach ($pref_array as $preference => $default_value) {
       $this->pref_tables_[$table_name][$preference] = $default_value;
    }
    return true;
  }
  
  /**
   * Set a relation between to preferences set
   * Used to set a relation between to preferences set. This is useful if you have one field of
   * a preference set that contain the id of an other preference set. This will take care of correctly
   * insert the sets in the good order and setting the link
   * @param $target_table   string the table which will be linked to
   * @param $link_field     string the field and table in which the link will be set (in format: table_shortcut.field_name)
   * @return                bool   false on failure, true on success
   */
  protected function setPrefSetRelation($target_table, $link_field) {
    if (is_string($target_table)) {
        $this->relations_[$target_table] = $link_field;
    }
    return true;
  }
  
  /**
   * Set a preference value
   * Set a preference value. Will control that the preference exists.
   * @param $pref    string preference name
   * @param $value   mixed  new value
   * @return         bool   false on failure, true on success
   */
  public function setPref($pref, $value) {
    foreach ($this->pref_tables_ as $table_name => $table) {
        if (array_key_exists($pref, $this->pref_tables_[$table_name])) {
            $this->pref_tables_[$table_name][$pref] = $value;
            return true;
        }
    }
    return false;
  }
  
  /**
   * Get a preference value
   * Get the preference value
   * @param $pref  string preference name
   * @return       mixed  preference value, or null if doesn't exists
   */
  public function getPref($pref) {
    foreach ($this->pref_tables_ as $table) {
        if (isset($table[$pref])) {
          if ($table[$pref] == 'true') {
            return true;
          }
          if ($table[$pref] == 'false') {
            return false;
          }
          return $table[$pref];
        }
    }
    return null;   
  }
  
  /**
   * Set the tables where we want to use another field than id
   * @param $tables array  array of table (keys are table name, values are id field)
   * @return        bool    true on success, false on failure
   */
   public function setIDField($tables) {
    if (is_array($tables)) {
       $this->id_fields_ = $tables;
    }
   }
  
  /**
   * Get the last database query executed
   * Get the last database query executed, mainly for debug purpose
   * @return  string  last query executed
   */
   public function getLastQuery() {
     return $this->last_query_;
   }
   
  /**
   * Get the id of a record
   * @param $table_name  string  name of the table of the record
   * @return             numeric id of the record 
   */
   public function getRecordId($table_name) {
    if (isset($this->record_ids_[$this->tables_shortcuts_[$table_name]])) {
        return $this->record_ids_[$this->tables_shortcuts_[$table_name]];
    }
    return 0;
   }
   
  /**
   * Load preferences from database
   * Load preferences values from the database
   * @param $additionnal_field  string fields that are not present in the preferences set but should be loaded
   * @param $where_clause       string WHERE clause that can be added to find the good set (without the 'WHERE' keyword)
   * @return                    bool   false on failure, true on success
   */
  protected function loadPrefs($additional_fields, $where_clause, $use_id) {
    
    $query = "SELECT ";
    $this->use_ids_ = $use_id;
    
    if (is_string($additional_fields)) {
        $query .= $additional_fields." ";
    }

    foreach ($this->pref_tables_ as $table_name => $table) {
        if ($this->tables_shortcuts_[$table_name] != "") {
            if ($this->use_ids_) {
              if (!isset($this->id_fields_[$table_name])) {
                $query .= $this->tables_shortcuts_[$table_name].".id as ".$this->tables_shortcuts_[$table_name]."_id, ";
              } else {
                $query .= $this->tables_shortcuts_[$table_name].".".$this->id_fields_[$table_name]." as ".$this->tables_shortcuts_[$table_name]."_id, ";
              }
            }
        }  
        foreach ($table as $pref => $val) {
            if ($this->tables_shortcuts_[$table_name] != "") {
                $query .= $this->tables_shortcuts_[$table_name].".";
            }
            $query .= $pref." as ".$pref.", ";
        }
    }
    $query = rtrim($query);
    $query = rtrim($query, '\,');
    
    $query .= " FROM ";
    foreach ($this->pref_tables_ as $table_name => $table) { 
      $query .= $table_name." ";
      if ($this->tables_shortcuts_[$table_name] != "") {
        $query .= $this->tables_shortcuts_[$table_name];
      }
      $query .= ", ";
    } 
    $query = rtrim($query);
    $query = rtrim($query, '\,');
    
    if (is_string($where_clause) && $where_clause != "") {
        $query .= " WHERE ".$where_clause;
    }
    require_once ('helpers/DataManager.php');
    $db_masterconf = DM_MasterConfig :: getInstance();
    $this->last_query_ = $query;
    $res = $db_masterconf->getHash($query);
    if (!is_array($res) || empty($res)) {
        return false;
    }
    $matches = array();
    //var_dump($res);
    foreach ($res as $key => $value) {
        if (preg_match('/^(\S+)\_id$/', $key, $matches)) {
            $this->record_ids_[$matches[1]] = $value;
        }
        $this->setPref($key, $value);
    }
    $this->loaded_ = true;
    return true;
  }
  
  /**
   * Return if preferences set should be updated or created
   * Check if one of the preferences set doesn't already exists in database. If yes, then
   * return false so that we should create them.
   * @return  bool   true if all sets are already present in database. False if not.
   */
  protected function shouldUpdate() {
    if (!$this->use_ids_ && empty($this->relations_) && $this->loaded_) {
        return true;
    }
    foreach ($this->pref_tables_ as $table_name => $table) {
        if (!isset($this->record_ids_[$this->tables_shortcuts_[$table_name]])) {
            return false;
        }
        $id = $this->record_ids_[$this->tables_shortcuts_[$table_name]];
        if ( (is_numeric($id) && $id == 0) || $id == "") {
            return false;
        }
    }
    return true;
  }
  
  /**
   * Return if object already exists in database or not
   * @return  bool   true if already present, false if not
   */
  public function isRegistered() {
    return $this->shouldUpdate();
  }
  
  /**
   * Save the preferences to the database
   * Save the preferences to the database. Taking care of relations and to create record if
   * they doesn't already exists
   * @param $additional_fields  string not used yet
   * @param $where_clause       string if a WHERE clause should be added
   * @return                    string 'OKSAVED' if successfully updated, 'OKADDED' id successfully added, error message if neither
   */
  protected function savePrefs($additional_fields, $where_clause, $w_table) {
    $retok = "NOTOK";
    require_once ('helpers/DataManager.php');
    $db_masterconf = DM_MasterConfig :: getInstance();
    if ($this->shouldUpdate()) {
        foreach ($this->pref_tables_ as $table_name => $table) {
            $query = "UPDATE $table_name ";
            if ($this->tables_shortcuts_[$table_name] != "") {
               $query .= $this->tables_shortcuts_[$table_name];
            }
            $query .= " SET ";
            $query .= $this->getSQLPrefSet($table_name);
            if ($this->use_ids_) {
              if (!isset($this->id_fields_[$table_name])) {
                $query .= " WHERE id=".$this->record_ids_[$this->tables_shortcuts_[$table_name]];
              } else {
                $query .= " WHERE ".$this->id_fields_[$table_name]."='".$this->record_ids_[$this->tables_shortcuts_[$table_name]]."'";
              }
            } else {
                if ($where_clause != "" && ($w_table == "" || $w_table == $this->tables_shortcuts_[$table_name])) {
                $query .= " WHERE ".$where_clause;
                }
            }
            $this->last_query = $query;
            if (!$db_masterconf->doExecute($query)) {
                return 'ERR_SAVEPREF_EXECUTEQUERY';
            }
            continue;
        }
        $retok = 'OKSAVED';
    } else {
        $query = array();
        if (count($this->relations_) > 0) {
            foreach ($this->relations_ as $target_table => $link_field) {
              $target_table_name = $this->getTableNameFromShortcut($target_table);
              $query[$target_table_name] = "INSERT INTO $target_table_name SET ".$this->getSQLPrefSet($target_table_name);
              $link_array=array();
              if (preg_match('/^(\S+)\.(\S+)$/', $link_field, $link_array)) {
                  $link_table_name = $this->getTableNameFromShortcut($link_array[1]);
                  $query[$link_table_name] = "INSERT INTO ".$link_table_name." SET ".$this->getSQLPrefSet($link_table_name);
                  $query[$link_table_name] .= ", ".$link_array[2]."=LAST_INSERT_ID()";
                }
            }
        } else {
            foreach ($this->pref_tables_ as $table_name => $table) {
                $query[$table_name] = "INSERT INTO $table_name SET ".$this->getSQLPrefSet($table_name);
            }
        }
        foreach ($query as $q_table => $q) {
            $this->last_query_ = $q;
            if (!$db_masterconf->doExecute($q)) {
                if ($db_masterconf->getLastError() == "RECORDALREADYEXISTS") {
                	return $db_masterconf->getLastError();
                }
                return 'ERR_SADDPREF_EXECUTEQUERY';
            }
            // get last_id
            $id_query = "SELECT LAST_INSERT_ID() as id";
            $res = $db_masterconf->getHash($id_query);
            if (is_array($res)) {
               $this->record_ids_[$this->tables_shortcuts_[$q_table]] = $res['id'];
            }
        }
        $retok = 'OKADDED';
    }
    return $retok;
  }
  
  /**
   * Get the SQL query part for all the preferences
   * For a preferences set, get the SQL string that will set the values for each preference
   * @param $table_name string table name of the preferences set
   * @return            string SQL string
   */
  private function getSQLPrefSet($table_name) {
    $ret = "";
    require_once ('helpers/DataManager.php');
    $db_slaveconf = DM_SlaveConfig :: getInstance();
    foreach ($this->pref_tables_[$table_name] as $pref => $value) {
        $ret .= $pref."='".$db_slaveconf->sanitize($value)."', ";
    }
    $ret = rtrim($ret);
    $ret = rtrim($ret, '\,');
    return $ret;
  }
  
  /**
   * Get the table name for a given table shortcut
   * Will return the table name associated to the given table shortcut
   * @param $shortcut string table shortcut
   * @return          string table name
   */
  private function getTableNameFromShortcut($shortcut) {
    return array_search($shortcut, $this->tables_shortcuts_);
  }
  
  /**
   * Delete a preference set
   * Will delete the database record of the preferences set
   * @param $where_clause string if a WHERE clause should be added
   * @return              string 'OK' if successful, error otherwise
   */
  protected function deletePrefs($where_clause) {
    require_once ('helpers/DataManager.php');
    $db_masterconf = DM_MasterConfig :: getInstance();
    foreach ($this->pref_tables_ as $table_name => $table) {
        $query = "DELETE FROM ".$table_name;
        if ($this->use_ids_) {
          if (!isset($this->id_fields_[$table_name])) {
            $query .= " WHERE id=".$this->record_ids_[$this->tables_shortcuts_[$table_name]];
          } else {
            $query .= " WHERE ".$this->id_fields_[$table_name]."='".$this->record_ids_[$this->tables_shortcuts_[$table_name]]."'";
          }
        } else {
          if (is_string($where_clause)) {
              $query .= " WHERE ".$where_clause;
          }
        }
        $this->last_query_ = $query;
        if (!$db_masterconf->doExecute($query)) {
            return 'ERR_DELETEPREF_EXECUTEQUERY';
        }
    }
    return 'OKDELETED';
  }
  
  /**
   * return if the preferences have been correctly loaded
   * @return   boolean  true if loaded, false if not
   */
  protected function isLoaded() {
    return $this->loaded_;
  }
}
?>
