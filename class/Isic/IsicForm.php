<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");

class IsicForm {
    private $tpl = false;
    private $txt = false;
    private $action = false;
    private $error = false;
    private $requiredFields = false;
    
    function IsicForm($tpl, $txt, $action, $error, $requiredFields) {
        $this->tpl = $tpl;
        $this->txt = $txt;
        $this->action = $action;
        $this->error = $error;
        $this->requiredFields = $requiredFields;
    }
    
    function showActionButton($suffix = '') {
        if ($this->action == "add") {
            $this->tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("button_add" . $suffix));
        } else if ($this->action == "modify") {
            $this->tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("button_mod" . $suffix));
        }
    }   
     
    function showFields($fields) {
        foreach ($fields as $key => $val) {
            $sub_tpl_name = strtoupper($key);
            $this->tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $this->getFieldData($key, $val));
            $this->tpl->addDataItem($sub_tpl_name . ".REQUIRED", $this->getFieldRequired($key));
            if ($val[8]) {
                $this->tpl->addDataItem($sub_tpl_name . ".TOOLTIP", nl2br($this->txt->display($val[8])));
            }
        }
    }
    
    function getFieldData($key, $val) {
        $fdata["type"] = $val[0];
        $fdata["size"] = $val[1];
        $fdata["cols"] = $val[1];
        $fdata["rows"] = $val[2];
        $fdata["list"] = $val[4];
        $fdata["java"] = $val[5];
        $fdata["class"] = $val[6];

        if ($this->action == "add" || $this->action == "modify" && $val[7]) {
            $f = new AdminFields($key, $fdata);
            if ($fdata['type'] == 'file') {
                $f->setTitleAttr($this->txt->display('browse'));
            }
            $field_data = $f->display($val[3]);
            $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);
        } else {
            if (is_array($fdata["list"])) {
                if ($fdata["type"] == "select2") {
                    $field_data = $this->getFieldListAsString($fdata, $val[3]);
                } else {
                    $field_data = $fdata["list"][$val[3]];
                }
            } else {
                if ($fdata["type"] == "checkbox") {
                    $field_data = $this->txt->display("active" . $val[3]);
                } else {
                    $field_data = $val[3];
                }
            }
        }
        return $field_data;        
    }
    
    function getFieldListAsString($fdata, $val) {
        $t_field_data = array();
        $t_grp_list = explode(",", $val);
        if (is_array($t_grp_list)) {
            foreach ($t_grp_list as $t_grp_id) {
                if (array_key_exists($t_grp_id, $fdata["list"])) {
                    $t_field_data[] = $fdata["list"][$t_grp_id];
                }
            }
        }
        return implode("<br />", $t_field_data);
    }
    
    function getFieldRequired($key) {
        if (!$this->isRequiredField($key)) {
            return '';
        }
        
        $required_field = "fRequired";
        if ($this->error->isBadField($key)) {
            $required_field .= " fError";
        }
        return $required_field;
    }
    
    function getHiddenField($name, $value) {
        return "<input type=\"hidden\" name=\"" . htmlspecialchars($name) . "\" value=\"" . htmlspecialchars($value) . "\">\n";
    }
    
    function isRequiredField($field) {
        return in_array($field, $this->requiredFields);
    }
}
