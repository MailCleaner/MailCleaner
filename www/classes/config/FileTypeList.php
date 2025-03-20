<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this list contains FileType objects
 */
require_once("config/FileType.php");

/**
 * this class manage the list of file type rules
 */
class FileTypeList
{

    /**
     * file type rules list
     * @var array
     */
    private $filetypes_ = [];

    /**
     * keep which rule is selected
     * @var numeric
     */
    private $selected_ = 0;


    /**
     * load the FileType objects to the list
     * @return   boolean  true on success, false on failure
     */
    public function load()
    {

        $query = "SELECT id FROM filetype";
        $db_slaveconf = DM_SlaveConfig::getInstance();

        unset($this->filetypes_);
        $this->filetypes_ = [];
        $list = $db_slaveconf->getList($query);
        foreach ($list as $id) {
            if ($id != 0) {
                $this->filetypes_[$id] = new FileType();
                $this->filetypes_[$id]->load($id);
            }
        }
        return true;
    }

    /**
     * set a rule as selected one
     * @param  $filename  numeric  filetype rule id to set as selected
     * @return            boolean  true on success, false on failure
     */
    public function setSelected($filetype)
    {
        if (isset($this->filetypes_[$filetype]) || $filetype == 0) {
            $this->selected_ = $filetype;
            return true;
        }
        return false;
    }
    /**
     * return the selected filetype rule id
     * @return  numeric  filetype rule id
     */
    public  function getSelected()
    {
        return $this->selected_;
    }

    /**
     * return the file type rule object given by its id
     * @param  $filetype  numeric  filetype rule id
     * @return            FileType filetype rule
     */
    public function getFileType($filetype)
    {
        if (isset($this->filetypes_[$filetype])) {
            return $this->filetypes_[$filetype];
        }
        return null;
    }

    /**
     * set a preference of a given file type rule
     * @param  $filetype  numeric  filetype rule id
     * @param  $p         string   preference name
     * @param  $v         mixed    preference value
     * @return            boolean  true on success, false on failure
     */
    public function setFiletypePref($filetype, $p, $v)
    {
        if (isset($this->filetypes_[$filetype])) {
            $this->filetypes_[$filetype]->setPref($p, $v);
        }
    }

    /**
     * save the whole filetype rules list
     * @return   string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {
        $retok = "OKSAVED";

        foreach ($this->filetype_ as $filetype) {
            $ret = $filetype->save();
            if ($ret != 'OKSAVED' || $ret != 'OKADDED') {
                return $ret;
            }
        }
        $sysconf_ = SystemConfig::getInstance();
        $sysconf_->setProcessToBeRestarted('ENGINE');
        return $retok;
    }


    /**
     * get the html for the filetype rule list table
     * @param  $t   string  html template to be used
     * @param  $f   string  html formular name
     * @return      string  html table string
     */
    public function drawFileTypes($t, $f)
    {
        require_once("view/Form.php");
        global $lang_;

        $ret = "";
        $previous_rule_id = 0;
        foreach ($this->filetypes_ as $id => $filetype) {
            if ($id == 0) {
                continue;
            }
            $template = str_replace('__NAME__', $this->drawField('name', $f, $id, 35), $t);
            $template = str_replace('__TYPE__', $filetype->getPref('type'), $template);
            $template = str_replace('__DESCRIPTION__', $this->drawField('description', $f, $id, 35), $template);
            $template = str_replace('__STATUS__', $this->drawStatus($f, $id), $template);
            $template = str_replace('__FORM_BEGIN_EDIT__', $this->drawBeginEdit($f, $id), $template);
            $template = str_replace('__FORM_CLOSE_EDIT__', $this->drawCloseEdit($f, $id), $template);
            $template = str_replace('__SELECTLINE_LINK__', "document.location.href='" . $_SERVER['PHP_SELF'] . "?s=" . $id . "'", $template);
            $template = preg_replace('/__IF_SELECTED__(.*)__FI__/', $this->if_selected('$1', $id), $template);
            $template = preg_replace('/__IF_NOTSELECTED__(.*)__FI__/', $this->ifnot_selected('$1', $id), $template);
            $template = str_replace('__SUBMIT_EDIT_LINK__', "window.document.forms['" . $f->getName() . "'].submit()", $template);
            $template = str_replace('__DELETELINE_LINK__', "javascript:delete_confirm('" . $id . "', '" . $filetype->getPref('rule') . "')", $template);
            $template = str_replace('__RULE_ID__', "rule_" . $filetype->id_, $template);
            $previous_rule_id = $id;
            $ret .= $template;
        }
        return $ret;
    }

    /**
     * return the given string if element is selected
     * @param  $s   string   string to resppond if selected
     * @param  $id  numeric  id of element to check
     * @return      string   string given id element to check is the selected element, "" if not
     */
    private function if_selected($s, $id)
    {
        if ($this->getSelected() == $id) {
            return $s;
        }
        return "";
    }

    /**
     * return the given string if element is NOT selected
     * @param  $s   string   string to resppond if NOT selected
     * @param  $id  numeric  id of element to check
     * @return      string   string given id element to check is NOT the selected element, "" if not
     */
    private function ifnot_selected($s, $id)
    {
        if ($this->getSelected() != $id) {
            return $s;
        }
        return "";
    }


    /**
     * get the formular beginning for the selected element
     * @param  $f  Form    html formular
     * @param  $id numeric id of the file type rule to be edited
     * @return     string  html string for the rule's row
     */
    private function drawBeginEdit($f, $id)
    {
        if ($this->getSelected() == $id) {
            return $f->open() . $f->hidden('id', $id);
        }
        return "";
    }

    /**
     * get the formular ending for the selected element
     * @param  $f  Form    html formular
     * @param  $id numeric id of the file type rule to be edited
     * @return     string  html string for the rule's row
     */
    private function drawCloseEdit($f, $id)
    {
        if ($this->getSelected() == $id) {
            return $f->close();
        }
        return "";
    }

    /**
     * get a single row field, or text input if selected
     * @param  $field  string   field type to be edited
     * @param  $f      Form     html formular
     * @param  $id     numeric  id of the rule
     * @param  $size   numeric  size of the input text field
     * @return         string   html string for the field
     */
    private function drawField($field, $f, $id, $size)
    {
        if ($this->getSelected() == $id) {
            return $f->input($field, $size, $this->getFileType($id)->getPref($field));
        }
        return $this->getFileType($id)->getPref($field);
    }

    /**
     * get the status html string of the rule
     * @param  $f  Form     html formular
     * @param  $id numeric  id of the rule
     * @return     string   html string of the status
     * @todo  try to remove any html code from here...
     */
    private function drawStatus($f, $id)
    {
        global $lang_;
        $allow_reject = [$lang_->print_txt('ALLOW') => 'allow', $lang_->print_txt('DENY') => 'deny'];
        if ($this->getSelected() == $id) {
            return $f->select('status', $allow_reject, $this->getFileType($id)->getPref('status'), ';');
        }
        if ($this->getFileType($id)->getPref('status') == 'allow') {
            $color = "green";
            $value = "ALLOW";
        } else {
            $color = "red";
            $value = "DENY";
        }

        return "<font color=\"$color\">" . $lang_->print_txt($value) . "</font>";
    }

    /**
     * add a file type rule to the actual set
     * @param  $new  FileType  file type rule to be added
     * @return       boolean   true on success, false on failure
     */
    public function addFileType($new)
    {
        $this->filetypes_[0] = $new;
        return true;
    }
}
