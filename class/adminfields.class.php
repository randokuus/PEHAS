<?php

/**
 * HTML form fields generator
 *
 * @package modera_net
 * @access public
 */

class AdminFields {
    /**
    * @var string field name
    */
    var $object = false;
    /**
     * @var string current language
     */
        var $language = false;
    /**
     * @var mixed the value for the field, eg. from post/get/db operation
     */
    var $value = false;

    var $nameAttr = false;
    var $nameAttrArray = false;
    var $titleAttr = false;

    /** Initializes the HTML form field generator object.
     * Possible field types are:
     * select - regular select
     * select1 - select with fieldname as array eg. fieldname[]
     * select2 - multiple select
     * checkbox
     * checkboxm - checkbox multiple
     * checkboxp - permission fields checkbox (admin special case)
     * radio - radiobuttons
     * textinput - regular text field
     * password - password text input
     * textfield - htmlarea type field
     * file - file upload
     * @param string Field name
     * @param array Array with field data, associative, keys (max,cols,rows,size,type,list,java,wrap,class)
     */
    function AdminFields($object, $field) {
        $this->object = $object;
        $this->max = $field["max"];
        $this->cols = $field["cols"];
        $this->rows = $field["rows"];
        $this->size = $field["size"];
        $this->type = $field["type"];
        $this->list = $field["list"];
        $this->java = $field["java"];
        $this->class = $field["class"];
        $this->classRadio = $field["class"] ? $field["class"] : "checkbox"; //css style for radiobuttons and checkboxes
        $this->classTextarea = $field["class"] ? $field["class"] : "textarea";  //css style for textarea
        $this->wrap = $field["wrap"];
        $this->setNameAttr($this->object);
        $this->setNameAttrArray($this->object);
    }

    /** Returns the html code corresponding to the field chosen
     * @param mixed value to make the field pre-selected
     * @return string return html with field
     */
    function display($data) {
        if ($data != "") {
            $this->value = $data;
        }
        $result = '';
        switch($this->type) {
            case "select":
                $result .= "<select " . $this->getNameAttr() . " class=\"" . $this->class . "\" " . $this->java .">\n";
                if (is_array($this->list)) {
                    foreach ($this->list as $key => $val) {
                        if ($key == $this->value) {
                            $selected = "selected='selected'";
                        } else {
                            $selected = '';
                        }
                        $result .= "<option value=\"$key\" $selected>$val</option>\n";
                    }
                }
                $result .= "</select>";
            break;
            case "select1":
            $result .= "<select " . $this->getNameAttrArray() . " class=\"" . $this->class . "\">\n";
                if (is_array($this->list)) {
                foreach ($this->list as $key => $val) {
                    if ($key == $this->value) { $selected = "selected='selected'";  }
                    $result .= "<option value=\"$key\" $selected>$val</option>\n";
                    unset($selected);
                }
                }
            $result .= "</select>";
            break;

            case "select2":
            $result .= "<select size=\"" . $this->size . "\" " . $this->getNameAttrArray() . " class=\"" . $this->class . "\" " . $this->java . "\" multiple>\n";
                if (is_array($this->list)) {
                    if (!is_array($this->value)) {
                        $this->value = split(",", $this->value);
                    }
                    foreach ($this->list as $key => $val) {
                        for ($c = 0; $c < sizeof($this->value); $c++) {
                            if ($key == $this->value[$c]) {
                                $selected = "selected='selected'";
                                break;
                            }
                        }
                        $result .= "<option value=\"$key\" $selected>$val</option>\n";
                        unset($selected);
                    }
                }
            $result .= "</select>";
            break;

            case "checkboxm2":
            if (is_array($this->list) && sizeof($this->list) > 0) {
                if (!is_array($this->value)) {
                    $this->value = split(",", $this->value);
                }

                foreach ($this->list as $key => $val) {
                    for ($c = 0; $c < sizeof($this->value); $c++) {
                        if ($key == $this->value[$c]) {
                            $selected = "checked='checked'";
                            break;
                        }
                    }
                    $result .= "<input type=\"checkbox\" class=\"" . $this->classRadio . "\" " . $this->getNameAttrArray() . " value=\"" . $key . "\" $selected /> <label for=\"action\" class=\"left\">$val</label><br>\n";
                    unset($selected);
                }
            }
            break;

            case "checkboxm":
            if (sizeof($this->list) > 0) {
                foreach ($this->list as $key => $val) {
                    for ($c = 0; $c < sizeof($this->value); $c++) {
                        if ($key == $this->value[$c]) {
                            $selected = "checked='checked'";
                            break;
                        }
                    }
                    $result .= "<input type=\"checkbox\" class=\"" . $this->classRadio . "\" " . $this->getNameAttrArray() . " value=\"" . $key . "\" $selected /> <label for=\"action\" class=\"left\">$val</label><br>\n";
                    unset($selected);
                    reset($this->value);
                }
            }
            break;

            //added for Permission in admin
            case "checkboxp":
            if (sizeof($this->list) > 0) {
                if ($this->value != "" && !is_array($this->value)) { $this->value = split(",", $this->value); }
                foreach ($this->list as $key => $val) {
                    if ($this->value[$key] == 1) { $selected = "checked='checked'"; }
                    $result .= "<input type=\"checkbox\" class=\"" . $this->classRadio . "\" " . $this->getNameAttrArray() . " value=\"" . $key . "\" $selected /> <label for=\"action\" class=\"left\">$val</label><br>\n";
                    unset($selected);
                }
                reset($this->list);
            }
            break;

            case "checkbox":
            if ($this->value == "y" || $this->value == "Y" || $this->value == "1") { $selected = "checked='checked'"; }
                $result .= "<input type=\"checkbox\" class=\"" . $this->classRadio . "\" " . $this->getNameAttr() . " value=\"1\" $selected " . $this->java . " />";
                unset($selected);
            break;

            case "radio":
                if (is_array($this->list)) {
                foreach ($this->list as $key => $val) {
                    if ($key == $this->value) { $selected = "checked='checked'";    }
                    $result .= "<input type=\"radio\" class=\"" . $this->classRadio . "\" " . $this->getNameAttr() . " value=\"".$key."\" $selected " . $this->java . " />$val<br>\n";
                    unset($selected);
                }
                }
            break;

            case "textinput":
                $this->value = ereg_replace("\"", "&quot;", $this->value);
                $result .= "<input type=\"text\" " . $this->getNameAttr() . " value=\"" . $this->value . "\" class=\"" . $this->class . "\" maxlength=\"" . $this->max . "\" size=\"" . $this->size ."\" " . $this->java . " />";
            break;

            case "password":
                $result .= "<input type=\"password\" " . $this->getNameAttr() . " value=\"" . $this->value . "\" class=\"" . $this->class . "\" maxlength=\"" . $this->max . "\" size=\"" . $this->size ."\" />";
            break;

            case "textfield":
                if ($this->wrap) {
                    $wrap = "wrap=\"{$this->wrap}\"";
                } else {
                    $wrap = '';
                }

                $result .= "<textarea $wrap class=\"" . $this->classTextarea . "\" cols=\"" . $this->cols . "\" rows=\"" . $this->rows . "\" " . $this->getNameAttr() . ">" . $this->value . "</textarea>";
            break;

            case "file":
                $result .= "<input type=file " . $this->getNameAttr() . " " . $this->getTitleAttr() . " size=\"" . $this->size . "\" class=\"" . $this->class . "\" />";
            break;

            case "button":
                $result .= "<input type=\"button\" " . $this->getNameAttr() . " " . $this->getTitleAttr() . " value=\"" . $this->value . "\" class=\"" . $this->class . "\" " . $this->java . " />";
            break;

            case "hidden":
                $result .= "<input type=\"hidden\" " . $this->getNameAttr() . " value=\"" . $this->value . "\" />";
                break;
        }
        return $result;
    }

    function setNameAttr($name) {
        $this->nameAttr = 'name="' . $name . '"';
    }

    function getNameAttr() {
        return $this->nameAttr;
    }

    function setNameAttrArray($name) {
        $this->nameAttrArray = 'name="' . $name . '[]"';
    }

    function getNameAttrArray() {
        return $this->nameAttrArray;
    }

    function setTitleAttr($title) {
        $this->titleAttr = 'title="' . $title . '"';
    }

    function getTitleAttr() {
        return $this->titleAttr;
    }

}