<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * this is as list
 */
require_once('helpers/ListManager.php');
require_once('user/WWEntry.php');

/**
 * This will takes care of fetching list of administrators
 */
class WWList extends ListManager
{

private $address_ = "";
private $type_ = "";
/**
 * load ww entries from database
 * @return  boolean  true on success, false on failure
 */ 
public function Load($address, $type) {
  $this->address_ = $address;
  $this->type_ = $type;
  return $this->reload();
}

public function reload() {
  if ($this->type_ == "" || $this->address_ == "") {
  	return false;
  }
  
  require_once('helpers/DM_MasterConfig.php');
  $db_masterconf = DM_MasterConfig :: getInstance();
  
  if ($this->address_ == '0') {
    $this->address_ = '';
  }
 
  $query = "SELECT id FROM wwlists WHERE ";
  if ($this->type_ == 'white') {
	$query = $query."(type='white' OR type='wnews')";
  } else if ($this->type_ == 'warn') {
	$query = $query."type='warn'";
  } else if ($this->type_ == 'black') {
        $query = $query."type='black'";
  } else {
	$query = $query."false"; // Should never happen
  }

  $query = $query." AND recipient='".$this->address_."' ORDER BY sender";

  $row = $db_masterconf->getList($query);
  $this->clearList();
  foreach( $row as $id) {
    $entry = new WWEntry();
    if ( $entry->load($id)) {
      $this->setElement($id, $entry);
    }
  }
  $this->setSort(0);
  return true;

}

/**
 * set the selected entry to be edited
 * @param  $id  numeric  selected entry id
 * @param  $f   Form     formular for edition
 * @return      bool      true if found, false if not
 */
public function setEntryToEdit($id, $f) {
	foreach ($this->elements_ as $e) {
	  if ($e->getPref('id') == $id) {
	  	$e->setToBeEdited();
        $e->setFormularAndList($f, $this);
        return true;
	  }	
	}
    return false;
}

public function getEntry($id) {
	foreach ($this->elements_ as $e) {
      if ($e->getPref('id') == $id) {
        return $e;
      } 
    }
    return null;
}

public function getEntryByPref($pref, $value) {
	foreach ($this->elements_ as $e) {
      if ($e->getPref($pref) == $value) {
        return $e;
      } 
    }
    return null;
}

public function getAddress() {
	return $this->address_;
}

public function getType() {
	return $this->type_;
}
}
?>
