<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this list contains FileName objects
 */
require_once("config/FileName.php");

/**
 * this class manage the list of file name rules
 */
class FileNameList
{

    /**
     * file names rules list
     * @var array
     */
    private $filenames_ = [];

    /**
     * keep which rule is selected
     * @var numeric
     */
    private $selected_ = 0;


    /**
     * load the FileName objects to the list
     * @return   boolean  true on success, false on failure
     */
    public function load()
    {

        $query = "SELECT id FROM filename";
        $db_slaveconf = DM_SlaveConfig::getInstance();

        unset($this->filenames_);
        $this->filenames_ = [];
        $list = $db_slaveconf->getList($query);
        foreach ($list as $id) {
            if ($id != 0) {
                $this->filenames_[$id] = new FileName();
                $this->filenames_[$id]->load($id);
            }
        }
        return true;
    }

    /**
     * set a rule as selected one
     * @param  $filename  numeric  filename rule id to set as selected
     * @return            boolean  true on success, false on failure
     */
    public function setSelected($filename)
    {
        if (isset($this->filenames_[$filename]) || $filename == 0) {
            $this->selected_ = $filename;
            return true;
        }
        return false;
    }
    /**
     * return the selected filename rule id
     * @return  numeric  filename rule id
     */
    public  function getSelected()
    {
        return $this->selected_;
    }

    /**
     * return the file name rule object given by its id
     * @param  $filename  numeric  filename rule id
     * @return            FileName filename rule
     */
    public function getFileName($filename)
    {
        if (isset($this->filenames_[$filename])) {
            return $this->filenames_[$filename];
        }
        return null;
    }

    /**
     * set a preference of a given file name rule
     * @param  $filename  numeric  filename rule id
     * @param  $p         string   preference name
     * @param  $v         mixed    preference value
     * @return            boolean  true on success, false on failure
     */
    public function setFilenamePref($filename, $p, $v)
    {
        if (isset($this->filenames_[$filename])) {
            $this->filenames_[$filename]->setPref($p, $v);
        }
    }

    /**
     * save the whole filename rules list
     * @return   string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {
        $retok = "OKSAVED";
        foreach ($this->filenames_ as $filename) {
            $ret = $filename->save();
            if ($ret != 'OKSAVED' || $ret != 'OKADDED') {
                return $ret;
            }
        }
        return $retok;
    }


    /**
     * get the html for the filename rule list table
     * @param  $t   string  html template to be used
     * @param  $f   string  html form name
     * @return      string  html table string
     */
    public function drawFilenames($t, $f)
    {
        require_once("view/Form.php");
        global $lang_;

        $ret = "";
        $previous_rule_id = 0;
        foreach ($this->filenames_ as $id => $filename) {
            if ($id == 0) {
                continue;
            }
            $template = str_replace('__NAME__', $this->drawField('name', $f, $id, 35), $t);
            $template = str_replace('__RULE__', $this->drawField('rule', $f, $id, 10), $template);
            $template = str_replace('__DESCRIPTION__', $this->drawField('description', $f, $id, 35), $template);
            $template = str_replace('__STATUS__', $this->drawStatus($f, $id), $template);
            $template = str_replace('__FORM_BEGIN_EDIT__', $this->drawBeginEdit($f, $id), $template);
            $template = str_replace('__FORM_CLOSE_EDIT__', $this->drawCloseEdit($f, $id), $template);
            $template = str_replace('__SELECTLINE_LINK__', "document.location.href='" . $_SERVER['PHP_SELF'] . "?s=" . $id . "#rule_" . $previous_rule_id . "'", $template);
            $template = preg_replace('/__IF_SELECTED__(.*)__FI__/', $this->if_selected('$1', $id), $template);
            $template = preg_replace('/__IF_NOTSELECTED__(.*)__FI__/', $this->ifnot_selected('$1', $id), $template);
            $template = str_replace('__SUBMIT_EDIT_LINK__', "window.document.forms['" . $f->getName() . "'].submit()", $template);
            $template = str_replace('__DELETELINE_LINK__', "javascript:delete_confirm('" . $id . "', '" . $filename->getPref('rule') . "')", $template);
            $template = str_replace('__RULE_ID__', "rule_" . $filename->id_, $template);
            $previous_rule_id = $filename->id_;
            $ret .= $template;
        }
        return $ret;
    }

    /**
     * return the given string if element is selected
     * @param  $s   string   string to respond if selected
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
     * @param  $s   string   string to respond if NOT selected
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
     * get the form beginning for the selected element
     * @param  $f  Form    html form
     * @param  $id numeric id of the file name rule to be edited
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
     * get the form ending for the selected element
     * @param  $f  Form    html form
     * @param  $id numeric id of the file name rule to be edited
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
     * @param  $field  string   field name to be edited
     * @param  $f      Form     html form
     * @param  $id     numeric  id of the rule
     * @param  $size   numeric  size of the input text field
     * @return         string   html string for the field
     */
    private function drawField($field, $f, $id, $size)
    {
        if ($this->getSelected() == $id) {
            return $f->input($field, $size, $this->getFileName($id)->getPref($field));
        }
        return $this->getFileName($id)->getPref($field);
    }

    /**
     * get the status html string of the rule
     * @param  $f  Form     html form
     * @param  $id numeric  id of the rule
     * @return     string   html string of the status
     * @todo  try to remove any html code from here...
     */
    private function drawStatus($f, $id)
    {
        global $lang_;
        $allow_reject = [$lang_->print_txt('ALLOW') => 'allow', $lang_->print_txt('DENY') => 'deny'];
        if ($this->getSelected() == $id) {
            return $f->select('status', $allow_reject, $this->getFileName($id)->getPref('status'), ';');
        }
        if ($this->getFileName($id)->getPref('status') == 'allow') {
            $color = "green";
            $value = "ALLOW";
        } else {
            $color = "red";
            $value = "DENY";
        }

        return "<font color=\"$color\">" . $lang_->print_txt($value) . "</font>";
    }

    /**
     * add a file name rule to the actual set
     * @param  $new  FileName  file name rule to be added
     * @return       boolean   true on success, false on failure
     */
    public function addFileName($new)
    {
        $this->filenames_[0] = $new;
        return true;
    }
}
