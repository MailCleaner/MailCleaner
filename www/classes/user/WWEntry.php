<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * This is the class is mainly a data wrapper for the WWEntry objects
 */
class WWEntry extends PrefHandler {

 /**
  * datas of the entry
  * @var array
  */
 private $datas_ = array(
                      'id'         => '',
                      'sender'         => '',
                      'recipient'       => '',
                      'type'         => 'warn',
                      'expiracy'         => '',
                      'status'          => 0,
                      'comments'       => '',
                      );
         
/**
 * test if thie entry should be edited
 * @var bool
 */  
private $setEdition_ = false;   

private $edit_formular_ = null;
private $list_ = null;
                     
/**
 * constuctor
 */
public function __construct() {
}

/**
 * Load Entry data from database
 * @param   $id   numeric   id of the entry
 * @return        bool      true on success, false on failure
 */
public function load($id) {
  $this->addPrefSet('wwlists', 'l', $this->datas_);
  
  if (!$this->loadPrefs('', "id=$id", true)) {
    return false;
  }
  #echo "Loaded entry: ".$this->getPref('recipient')." <=> ".$this->getPref('sender');
  return $this->authorized();
}

public function setFormularAndList($f, $l) {
	$this->edit_formular_ = $f;
    $this->list_ = $l;
}

/**
 * return the html template with tag replaced
 * @param  $template  string   html template
 * @param  $selected  mixed    eventually selected object
 * @param  $n         numeric  position in the list
 * @return            string   html code
 */
public function getElementTemplate($template, $selected, $n) {
   global $lang_;
   
   $t = str_replace('__ID__', $this->getPref('id'), $template);
   if ($n % 2) {
     $t= str_replace('__ALTROWSTYLE__', 'dataTableContent', $t); 
   } else {
     $t= str_replace('__ALTROWSTYLE__', 'dataTableRow', $t); 
   }
   $t = str_replace('__FLATSENDER__', $this->getPref('sender'), $t);
   if (! $this->setEdition_) {
     $t = preg_replace('/__IF_NOTSELECTED__(.*)__FI__/', "$1", $t);
     $t = preg_replace('/__IF_SELECTED__(.*)__FI__/', "", $t);
     $t = str_replace('__FORM_BEGIN_EDIT__', "", $t);
     $t = str_replace('__FORM_CLOSE_EDIT__', "", $t);
   	 // flat display
     $t = str_replace('__SENDER__', $this->getPref('sender'), $t);
     if ($this->getPref('status')) {
       $status = "<font color=\"green\">".$lang_->print_txt('ACTIVE')."</font>";
     } else {
       $status = "<font color=\"red\">".$lang_->print_txt('INNACTIVE')."</font>";
     }
     $t = str_replace('__STATUS__', $status, $t);
     $t= str_replace('__COMMENT__', $this->getPref('comments'), $t);

   } else {
    $t = preg_replace('/__IF_NOTSELECTED__(.*)__FI__/', "", $t);
    $t = preg_replace('/__IF_SELECTED__(.*)__FI__/', "$1", $t);
    $t = str_replace('__FORM_BEGIN_EDIT__', $this->edit_formular_->open().$this->edit_formular_->hidden('a', $this->list_->getAddress()).$this->edit_formular_->hidden('id', $this->getPref('id')).$this->edit_formular_->hidden('type', $this->getPref('type')), $t);
    $t = str_replace('__FORM_CLOSE_EDIT__', $this->edit_formular_->close(), $t);
   	 // edition display
    $t = str_replace('__SENDER__', $this->edit_formular_->input('sender', 30, $this->getPref('sender'), ''), $t);
    $active_inactive = array($lang_->print_txt('ACTIVE') => '1', $lang_->print_txt('INNACTIVE') => '0');
    $t = str_replace('__STATUS__', $this->edit_formular_->select('status', $active_inactive , $this->getPref('status'), ';'), $t);
    $t= str_replace('__COMMENT__', $this->edit_formular_->input('comments', 35, $this->getPref('comments'), ''), $t);
    $t= str_replace('__SUBMIT_EDIT_LINK__', "window.document.forms['".$this->edit_formular_->getName()."'].submit()", $t);
   }
   return $t;
}

/**
 * save prefereneces
 * @return         string  string 'OKSAVED' if successfully updated, 'OKADDED' id successfully added, error message if neither
 */
public function save() {
    global $sysconf_;
    # save value
    if (! $this->getPref('recipient')) {
        $this->setPref('recipient', '');
    }
    
    if (!$this->authorized()) {
      return "NOTAUTHORIZED";
    }

    $this->setPref('sender', strtolower($this->getPref('sender')));
    $this->setPref('recipient', strtolower($this->getPref('recipient')));
    
    $ret = $this->savePrefs(null, null, '');
    if (!$ret) {
        return $this->getLastError();
    }
    ## dump the configuration through all hosts
    $param = $this->getPref('recipient');
    if ($param == '') {
        $param = "_global";
    }
    //$res = $sysconf_->dumpConfiguration('wwlist', $param);
    return $ret;
}

  /**
   * Delete the entry
   * Delete the entry instance in the database and the preferences associated
   * @return         string 'OK' if successfull, error otherwise
   */
  public function delete() {
    global $sysconf_;
    
    if (!$this->authorized()) {
      return "NOTAUTHORIZED";
    }
    
    $ret = $this->deletePrefs(null);
    if (!$ret) {
        return $ret;
    }
    ## dump the configuration through all hosts
    $param = $this->getPref('recipient');
    if ($param == '') {
    	$param = "_global";
    }
    //$res = $sysconf_->dumpConfiguration('wwlist', $param);
    return $ret;
  }

/**
 * set to be edited
 */
public function setToBeEdited() {
  $this->setEdition_ = true; 	
}

public function disable() {
  $this->setPref('status', '0');
  $this->save();
}

public function enable() {
  $this->setPref('status', '1');
  $this->save();
}

/**
 * check if user/admin authorized to manipulate this entry
 * return  boolean  true if authorized, false otherwise
 */
 private function authorized() {
  global $admin_;
  global $user_;
    
  if (isset($admin_) && $admin_ instanceof Administrator) {
    // ok if can manage all domains
    if ($admin_->canManageDomain('*')) {
      return true;
    }
    // ok if can manage this particular domain
    if (preg_match('/(\S*)\@(\S+)/', $this->getPref('recipient'), $matches)) {
      if ($admin_->canManageDomain($matches[2])) {
        return true;
      }
    }
  } elseif (isset($user_) && $user_ instanceof User) {
    // ok if address belongs to user
    if ($user_->hasAddress($this->getPref('recipient'))) {
    	return true;
    }  
  }
  return false;
}
}
?>
