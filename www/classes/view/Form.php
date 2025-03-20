<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * This class will manage the html form elements
 */
class Form
{
    /**
     * form name
     * @var string
     */
    private $name_ = '';
    /**
     * form method
     * @var string
     */
    private $method_ = "post";
    /**
     * action on form submit
     * @var string
     */
    private $action_ = '';

    /**
     * array of checkboxes
     * @var array
     */
    private $checkboxes_ = [];

    /**
     * constructor
     * @param  $name   string  name of the form
     * @param  $method string  method to be used (POST or GET)
     * @param  $action string  action on form submit
     */
    public function __construct($name, $method, $action)
    {
        $this->name_ = $name;
        $this->method_ = $method;
        $this->action_ = $action;
    }

    /**
     * get the form name
     * @return  string  form name
     */
    public function getName()
    {
        return $this->name_;
    }

    /**
     * return the opening tag of the form
     * @return string  html form opening tag
     */
    public function open()
    {
        $ret = "<form  id=\"$this->name_\" method=\"$this->method_\" action=\"$this->action_\">\n";
        $ret .= "<div>\n";
        $ret .= "<input type=\"hidden\" name=\"" . $this->name_ . "_save_on_submit\" value=\"1\" />\n";
        return $ret;
    }

    /**
     * return the closing tag of the form
     * @return string  html form closing tag
     */
    public function close()
    {
        return "</div></form>\n";
    }

    /**
     * return the string for a hidden field
     * @param  $name  string name of the field
     * @param  $value string value of the field
     * @return        string html field string
     */
    public function hidden($name, $value)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"hidden\" name=\"$fname\" value=\"" . htmlentities($value) . "\" />";
    }

    /**
     * return the string for a simple input field
     * @param  $name   string  name of the field
     * @param  $length numeric length of the field
     * @param  $value  string  value of the field
     * @return         string  html field string
     */
    public function input($name, $length, $value)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"text\" id=\"$fname\" name=\"$fname\" size=\"$length\" value=\"" . htmlentities($value) . "\" />";
    }


    public function inputDisabled($name, $length, $value)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"text\" id=\"$fname\" name=\"$fname\" size=\"$length\" value=\"" . htmlentities($value) . "\" disabled=\"disabled\" />";
    }


    /**
     * return the string for a simple input field with javascript action
     * @param  $name  string name of the field
     * @param  $value string value of the field
     * @param  $js    string javascript to be embedded in tag (onkeydown event)
     * @return        string html field string
     */
    public function inputjs($name, $length, $value, $js)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"text\" name=\"$fname\" size=\"$length\" value=\"" . htmlentities($value) . "\" onkeydown=\"$js\" />";
    }

    /**
     * return the string for a simple password field
     * @param  $name  string name of the field
     * @param  $value string value of the field
     * @return        string html field string
     */
    public function password($name, $length, $value)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"password\" name=\"$fname\" size=\"$length\" value=\"" . htmlentities($value) . "\" />";
    }

    /**
     * return the string for a simple text area field
     * @param  $name   string  name of the field
     * @param  $width  numeric length of line of the field
     * @param  $height numeric number of line of the field
     * @return         string html field string
     */
    public function textarea($name, $width, $height, $value)
    {
        $fname = $this->name_ . "_" . $name;
        return "<textarea name=\"$fname\" cols=\"$width\" rows=\"$height\">$value</textarea>";
    }

    /**
     * create a simple on/off checkbox field
     * @param  $name     string  name of the field
     * @param  $value    string  value of the field if selected
     * @param  $selected string  value previously selected
     * @param  $js       string  javascript to be embedded in field (onClick event)
     * @param  $active   boolean if the field should be activated or not
     * @return           string html field string
     */
    public function checkbox($name, $value, $selected, $js, $active)
    {
        switch ($value) {
            case '1':
                $nvalue = '0';
                break;
            case '0':
                $nvalue = '1';
                break;
            case 'true':
                $nvalue = 'false';
                break;
            case 'false':
                $nvalue = 'true';
                break;
            case 'on':
                $nvalue = 'off';
                break;
            case 'off':
                $nvalue = 'on';
                break;
            case 'yes':
                $nvalue = 'no';
                break;
            case 'no':
                $nvalue = 'yes';
                break;
        }
        $fname = $this->name_ . "_" . $name;
        $fname = preg_replace('/-/', 'UUU', $fname);
        $fname = preg_replace('/\./', 'PPP', $fname);
        $ret = "<input type=\"checkbox\" class=\"checkbox\" name=\"" . $fname . "_cb\" id=\"" . $fname . "_cb\" value=\"$value\"";
        if ($selected == $value) {
            $ret .= " checked=\"checked\"";
        }
        $ret .= " onclick=\"javascript:if (window.document.forms['" . $this->name_ . "']." . $fname . "_cb.checked) { window.document.forms['" . $this->name_ . "'].$fname.value='$value'; } else { window.document.forms['" . $this->name_ . "'].$fname.value='$nvalue';}";
        if ($js != "") {
            $ret .= ";" . $js . ";";
        }
        $ret .= "\"";
        if (isset($active) && $active < 1) {
            $ret .= "disabled";
        }
        $ret .= " />";
        $ret .= "<input type=\"hidden\" name=\"$fname\" id=\"{$fname}_checkbox\" value=\"" . htmlentities($selected) . "\" />";
        return $ret;
    }

    /**
     * create a checkboxes block
     * @param $name     string   name of the block
     * @param $options  array    array of options
     * @param $selected string   value selected
     * @return          string html field string
     */
    public function checkboxblock($name, $options, $selected)
    {
        $fname = $this->name_ . "_" . $name;
        $ret = "";
        foreach ($options as $opt => $val) {
            if ($opt == $selected) {
                $ret .= $this->checkbox($name . "_" . $opt, $val, 1) . $opt . "&nbsp;&nbsp;";
            } else {
                $ret .= $this->checkbox($name . "_" . $opt, $val, 0) . $opt . "&nbsp;&nbsp;";
            }
        }
        return $ret;
    }

    /**
     * return a radio button string
     * @param  $name      string  name of the button
     * @param  $value     string  value if selected
     * @param  $selected  string  previously selected value
     * @return            string html field string
     */
    public function radio($name, $value, $selected)
    {
        $fname = $this->name_ . "_" . $name;
        $ret = "<input type=\"radio\" name=\"$fname\" class=\"radiobutton\" value=\"$value\"";
        if ($selected == $value) {
            $ret .= " checked=\"checked\"";
        }
        $ret .= " />";
        return $ret;
    }

    /**
     * return a radio button string with javascript
     * @param  $name      string  name of the button
     * @param  $value     string  value if selected
     * @param  $selected  string  previously selected value
     * @param  $js        string  javascript to include
     * @return            string html field string
     */
    public function radiojs($name, $value, $selected, $js)
    {
        $fname = $this->name_ . "_" . $name;
        $ret = "<input type=\"radio\" name=\"$fname\" id=\"" . $fname . "_" . $value . "\" class=\"radiobutton\" value=\"$value\"";
        if ($selected == $value) {
            $ret .= " checked=\"checked\"";
        }
        $ret .= " onclick=\"$js\"";
        $ret .= " />";
        return $ret;
    }

    /**
     * creates a radio buttons block
     * @param $name     string   name of the block
     * @param $options  array    array of different options available
     * @param $selected string   previously selected value
     * @param $align    string   buttons alignment (horizontal or vertical)
     * @param $disp     string   ?
     * @return          string html field string
     */
    public function radioblock($name, $options, $selected, $align, $disp)
    {
        $ret = "";
        $fname = $this->name_ . "_" . $name;
        if ($align == 'horizontal' || $align == 'horiz' || $align == 'h') {
            $ret .= "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>\n";
            foreach ($options as $opt => $val) {
                $ret .= "<td>";
                if ($disp == 'l') {
                    $ret .= $opt . " ";
                    $this->radio($name, $val, $selected);
                    $ret .= "</td>\n";
                } else {
                    $this->radio($name, $val, $selected);
                    $ret .= " " . $opt . "</td>\n";
                }
            }
            $ret .= "</tr></table>\n";
        } else {
            $ret .= "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
            foreach ($options as $opt => $val) {
                $ret .= " <tr>";
                if ($disp == 'l') {
                    $ret .= "<td>" . $opt . "</td><td>";
                    $ret .= $this->radio($name, $val, $selected);
                    $ret .= "</td>";
                } else {
                    $ret .= "<td>";
                    $ret .= $this->radio($name, $val, $selected);
                    $ret .= "</td><td>$opt</td>";
                }
                $ret .= "</tr>\n";
            }
            $ret .= "</table>\n";
        }
        return $ret;
    }

    /**
     * create a select field
     * @param $name     string   field name
     * @param $options  array    list of available options
     * @param $selected string   previously selected value
     * @param $onselect string   javascript to be embedded (onSelect event)
     * @return          string html field string
     */
    public function select($name, $options, $selected, $onselect, $disabled = false)
    {
        $ret = "";
        if ($onselect == "") {
            $onselect = "window.document.forms['" . $this->name_ . "']." . $this->name_ . "_save_on_submit.value=0;window.document.forms['" . $this->name_ . "'].submit();";
        }
        $fname = $this->name_ . "_" . $name;

        $ret .= "\n<select name=\"$fname\" id=\"$fname\" onchange=\"$onselect\"";
        if ($disabled) {
            $ret .= " disabled";
        }
        $ret .= ">\n";
        if (isset($options)) {
            foreach ($options as $opt => $val) {
                $ret .= "   <option value=\"$val\"";
                if ($val == $selected) {
                    $ret .= " selected=\"selected\"";
                }
                $ret .= ">" . $opt . "</option>\n";
            }
        }
        $ret .= "</select>\n";
        return $ret;
    }

    /**
     * create a reset button
     * @param  $name    string   name of the button
     * @param  $value   string   value of the button
     * @param  $img     string   image to be displayed
     * @return          string html field string
     */
    public function reset($name, $value, $img)
    {
        $fname = $this->name_ . "_" . $name;
        if ($img == '') {
            return "<input type=\"reset\" name=\"$fname\" value=\"$value\" />";
        } else {
            return "<img src=\"$img\" name=\"$fname\" border=\"0\" onclick=\"window.document.forms['" . $this->name_ . "'].reset();\" alt=\"\" />";
        }
    }

    /**
     * create a submit button
     * @param  $name    string   name of the button
     * @param  $value   string   value of the button
     * @param  $img     string   image to be displayed
     * @return          string html field string
     */
    public function submit($name, $value, $img)
    {
        $fname = $this->name_ . "_" . $name;
        if ($img == '') {
            return "<input type=\"submit\" class=\"submitbutton\" name=\"$fname\" value=\"$value\" />";
        } else {
            return "<input type=\"image\" name=\"$fname\" src=\"$img\" onclick=\"window.document.forms['" . $this->name_ . "'].submit();\" />";
        }
    }

    public function submitDisabled($name, $value, $img)
    {
        $fname = $this->name_ . "_" . $name;
        if ($img == '') {
            return "<input type=\"submit\" class=\"submitbutton\" name=\"$fname\" value=\"$value\" disabled=\"disabled\" />";
        } else {
            return "<input type=\"image\" name=\"$fname\" src=\"$img\" onclick=\"window.document.forms['" . $this->name_ . "'].submit();\" disabled=\"disabled\" />";
        }
    }

    /**
     * create a submit button
     * @param  $name    string   name of the button
     * @param  $value   string   value of the button
     * @param  $img     string   image to be displayed
     * @return          string html field string
     */
    public function submitWithJS($name, $value, $js)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"submit\" class=\"submitbutton\" name=\"$fname\" value=\"$value\" onclick=\"" . $js . "window.document.forms['" . $this->name_ . "'].submit();\" />";
    }

    public function button($name, $value, $js)
    {
        $fname = $this->name_ . "_" . $name;
        return "<input type=\"button\" name=\"$fname\" value=\"$value\" onclick=\"$js;window.document.forms['" . $this->name_ . "'].submit();\" />";
    }

    /**
     * return a form submission javascript
     * @return  string  submit javascript string
     */
    public function submitJS()
    {
        return "window.document.forms['" . $this->name_ . "'].submit();";
    }

    /**
     * return the array of field values entered
     * @return  array  list of form values
     */
    public function getResult()
    {
        $res = [];
        foreach ($_REQUEST as $opt => $val) {
            $shortopt = str_replace($this->name_ . "_", '', $opt);
            $val = preg_replace('/\\\\(.)/', '$1', $val); # remove escaped input
            $res[$shortopt] = $val;
        }
        return $res;
    }

    /**
     * check if form datas should be saved or not
     * @return  boolean  true if datas should be saved, false if not
     */
    public function shouldSave()
    {
        $subname = $this->name_ . "_save_on_submit";
        if (isset($_POST[$subname]) && $_POST[$subname] == 1) {
            return true;
        }
        return false;
    }
}
