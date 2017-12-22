<?php

/**
 * Form module, generate forms based on user chosen fields
 *
 * sample use in template:
 * <code>
 *  <TPL_OBJECT:form2>
 *      <TPL_OBJECT_OUTPUT:show()>
 *  </TPL_OBJECT:form2>
 * </code>
 *
 * @package modera_net
 * @version 1.2
 * @access public
 */

require_once(SITE_PATH . '/class/FormAction.php');

class form2 {

/**
 * @var integer form id
 */
  var $form = false;
/**
 * @var integer active template set
 */
  var $tmpl = false;
/**
 * @var string active language
 */
  var $language = false;
/**
 * @var string site root url location
 */
  var $siteroot = false;
/**
 * @var array merged array with _GET and _POST data
 */
  var $vars = array();
/**
 * @var integer database connection identifier
 */
  var $dbc = false;
/**
 * @var boolean does this module provide additional parameters to admin page admin
 */
  var $content_module = true;
/**
 * @var array additional parameters set at the page admin for this template
 */
  var $module_param = array();
/**
 * @var integer active user id
 */
  var $userid = false;
/**
 * @var string username
 */
  var $username = false;
/**
 * @var string user's full name
 */
  var $user_fullname = false;
/**
 * @var array fielddata for chosen form
 */
  var $fielddata = array();
/**
 * @var string show numbers in front of for elements LEVEL1 yes
 */
  var $numbers = LEVEL0;
/**
 * @var string module cache level
 */
  var $cachelevel = TPL_CACHE_ALL;
/**
 * @var integer cache expiry time in minutes
 */
  var $cachetime = 43200; //cache time in minutes
/**
 * @var string module indentifier for cache filename
 */
  var $tplfile = "form2";
/**
 * @var string template to use
 */
  var $template = "";


    /**
     * Class constructor
    */

  function form2 () {
        global $db, $language;

        $this->siteroot = COOKIE_URL;
        $this->language = $language;
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->vars = $_POST;
        $this->poll = $this->vars["poll"];

        if (is_object($db)) $this->dbc = $db->con;
        else {
            $db = new DB;
            $this->dbc = $db->connect();
        }

        $this->userid = $GLOBALS["user_data"][0];
        $this->username = $GLOBALS["user_data"][2];
        $this->user_fullname = $GLOBALS["user_data"][1];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        if ($this->module_param["form2"] != "" && $this->form == false) $this->form = $this->module_param["form2"];

        $this->fielddata = array(
            "1" => array(
                "type" => "textinput",
                "sql" => "varchar(50) NOT NULL",
                "size" => 50,
                "max" => 50
            ),
            "2" => array(
                "type" => "textinput",
                "sql" => "varchar(10) NOT NULL",
                "size" => 10,
                "max" => 10
            ),
            "3" => array(
                "type" => "textinput",
                "sql" => "varchar(4) NOT NULL",
                "size" => 4,
                "max" => 4
            ),
            "4" => array(
                "type" => "textfield",
                "sql" => "text NOT NULL",
                "rows" => 10,
                "cols" => 100,
                "class" => "i300",
            ),
            "5" => array(
                "type" => "checkbox",
                "sql" => "smallint(5) NOT NULL",
                "list" => array()
            ),
            "6" => array(
                "type" => "checkboxm",
                "sql" => "varchar(255) NOT NULL",
                "list" => array()
            ),
            "7" => array(
                "type" => "select",
                "sql" => "smallint(5) NOT NULL",
                "list" => array()
            ),
            "8" => array(
                "type" => "select2",
                "sql" => "smallint(5) NOT NULL",
                "size" => 5,
                "list" => array()
            )
        );

        require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
  }

    /**
     * Main module display function. show the form
     * @return string
    */

  function show () {
    global $txtf;

    $structure = $this->vars['structure'];
    $content = $this->vars['content'];

    foreach (array('write', 'dodb', 'dostat') as $v) {
        if (array_key_exists($v, $this->vars)) $$v = $this->vars[$v];
    }

    // if form not selected, return empty string.
    if (!$this->form) return "";

    // check access
    if ($GLOBALS["pagedata"]["login"] == 1 && $this->checkAccess() == false) return "";

    $sq = new sql;

    $txt = new Text($this->language, "module_form2");

    // #################################

    // get form action for `FORM_ID`
    $action = $GLOBALS['database']->fetch_first_value('SELECT `action` FROM '
       . ' `module_form2` WHERE `id` = ?', $this->form);

    // if no action associated with this form, return false.
    if (false === $action) return '';

    // get form action.
    $action_obj =& FormAction::driver($action, $this->form, $GLOBALS['database']
        , $txt->getTranslator());


    // ####

    // instantiate template class
    $tpl = new template;
    $tpl->tplfile = $this->tplfile . "_" . $this->form . "_";
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    if ($this->template == "") $this->template = "module_form2_form.html";

    $template = SITE_PATH . DIRECTORY_SEPARATOR
        . $GLOBALS["templates_".$this->language][$this->tmpl][1]
        . DIRECTORY_SEPARATOR . $this->template;

    $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=form2&form=".$this->form);
    $tpl->setTemplateFile($template);



    // on form submit try to process data and insert messages if needed...
    if ($write == "true") {

        // try to process data.
        $action_obj->submitForm($_POST);

        // if form data not valid, insert error messages.
        if (!$action_obj->isValid()) {

            // if field error exists, create fieldset of errors
            $field_errors = $action_obj->invalidFields();

            if (is_array($field_errors) and count($field_errors)) {
                $info_text = '';
                $tpl->addDataItem("INFO.TITLE", $txt->display("error"));
                $tpl->addDataItem("INFO.TYPE", 'error');

                foreach ($field_errors as $field => $err_msg){
                    $info_text .= $err_msg . '<br/>';
                }

                $tpl->addDataItem("INFO.INFO", $info_text);

            }

            if ($action_obj->isEmptyForm()) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("error"));
            }

        } else {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("ok"));
        }
    }


    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "form2";
        if ($GLOBALS["modera_debug"] == true) {
            return "<!-- module form2 cached -->\n" . $tpl->parse();
        }
        else {
            return $tpl->parse();
        }
    }

    // get form data
    $sq->query($this->dbc, "SELECT * FROM `module_form2` WHERE `id` = '"
        .addslashes($this->form)."' AND `active` = 1"
    );

    // if form not exists or not active, return parsed template.
    if ($sq->numrows == 0) {
        $tpl->addDataItem("MESSAGE", "<font color=\"red\">".$txt->display("notavailable")."</font>");
        return $tpl->parse();
    }
    else {
        $data = $sq->nextrow();
        $sq->free();

        $tpl->addDataItem("FORMTITLE", $data["title"]);
    }

    // insert all form fields into form.
    if (!$write || ($write && isset($action_obj) && !$action_obj->isValid())) {

        $tpl->addDataItem("FORM.SELF", processURL($_SERVER["PHP_SELF"],
            $_SERVER["QUERY_STRING"], "#formmodule", array("form", "write", "formmodule")));

        $tpl->addDataItem("FORM.BUTTON", $txt->display("add"));

        $sq->query($this->dbc,
            "SELECT * FROM `module_form2_fields` "
            . "WHERE `form` = '" . addslashes($this->form) . "' "
            . "ORDER BY `prio` ASC, `id` ASC;" );

        if ($sq->numrows == 0) {
            return $tpl->parse();
        }
        else {
            $nr = 1;
            while ($data = $sq->nextrow()) {
                if ($data["required"] == 1) $required = "*";
                else { $required = ""; }

                if ($this->numbers == LEVEL1 || $this->numbers == LEVEL2) {
                    $tpl->addDataItem("FORM.FIELD.TITLE", $nr . ". " . $data["name"]);
                } else {
                    $tpl->addDataItem("FORM.FIELD.TITLE", $data["name"]);
                    if ($required) {
                        $tpl->addDataItem("FORM.FIELD.REQUIRED", "required");
                    }
                }

                // if field error exists, add to this field class = 'error'
                if (!is_null($action_obj->fieldError('field'.$nr.'_1'))){
                    $tpl->addDataItem("FORM.FIELD.CLASS",'form2_error');
                }

                $tpl->addDataItem("FORM.FIELD.DESC", $data["descr"]);
                $tpl->addDataItem("FORM.FIELD.QUESTION1",
                    $this->makeField($nr, $data["type"], $data["fields1"], $data["options"], true, array(), 0, $txt));

                $nr++;
            }
        }
        $sq->free();
    }

    return $tpl->parse();
  }

// ##########################################################

    /**
     * Generate fields for chosen form field with given parameters
     * @param integer number of field
     * @param string type of field
     * @param array field data main
     * @param array choises for select/multiple selects
     * @param boolean true always here
     * @param array statistics, always empty in this moduel
     * @param integer 0
     * @param object text object reference
     * @access private
     * @return string html format form field
    */

    function makeField($nr, $type, $fields1, $choises, $isfield = true, $statistics = array(), $all = 0, $txt) {

        $additional_txt = false;
        $additional_ar = array();

        if ($choises != "") {
            $ar = split(";;", $choises);
            for ($t = 0; $t < sizeof($ar); $t++) {
                $this->fielddata[$type]["list"][($t+1)] = $ar[$t];
            }
        }

        if (is_array($this->fielddata[$type]["list"]) && $isfield == true) $fields1 = 1;
        else if ($isfield == true) { $fields1 = 1; }

        $result = "";

        // Parse the fields according to the needed number
        for ($c = 0; $c < $fields1; $c++) {
            if ($type == 5 && $c == 0) $value = 1;
            if ($type == 6 && $c == 0 && is_array($this->fielddata[$type]["list"])) $value = $this->fielddata[$type]["list"];

            if ($this->vars["field".$nr."_".($c+1)] != "") $value = $this->vars["field".$nr."_".($c+1)];

            // Should we display the form element
            if ($isfield == true) {
                $f = new AdminFields("field".$nr."_".($c+1), $this->fielddata[$type]);
                $disp_val = $f->display($value);
                if ($this->fielddata[$type]["class"]) {
                    $disp_val = str_replace("class=\"", "class=\"" . $this->fielddata[$type]["class"] . " ", $disp_val);
                }
                if ($additional_txt == true) {
                    $result .= $numbers . $disp_val . "&nbsp;" . $additional_ar[($c+1)]  . "<br />";
                }
                else {
                    $result .= $numbers . $disp_val . "<br />";
                }
            }
        }

        $this->fielddata[$type]["list"] = array();

        return $result;
    }

// ##########################

    /**
     * Parse DB resultset array to single level array
     * @access private
     * @return array
    */

    function parseArray($data) {
        $nr = 1;
        while(list($k, $v) = each($data)) {
            if (!is_float($nr/2)) {
                $return[] = array($k, $v);
            }
        $nr++;
        }
        return $return;
    }

    /**
     * Set the template to show
     * @param string template filename
     */

      function setTemplate ($template) {
        if (ereg("\.\.", $template)) trigger_error("Module form2: Template path is invalid !", E_USER_ERROR);
        $this->template = $template;
      }

    /**
     * Set the form to show
     * @param integer form ID
     */

      function setForm ($form) {
        if ($form && $form > 0 && $form < 1000000) {
            $this->form = $form;
        }
      }

 // ########################################

    /**
     * Check does the active user have access to the page/form
     * @access private
     * @return boolean
     */

      function checkAccess () {
        if ($this->userid && $GLOBALS["user_show"] == true) return true;
        else { return false; }
      }

// ##########################

    /**
     * Get parameters from page data
     * @access private
     */


    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Provide addtional parameters to page admin
     * @access private
     */

    function moduleOptions() {
        $sq = new sql;
        $txt = new Text($this->language, "module_form2");
        $sq->query($this->dbc, "SELECT id, title FROM module_form2 WHERE language = '".$this->language."' AND active = 1 ORDER BY id DESC");
        while ($data = $sq->nextrow()) {
            $list[$data["id"]] = $data["title"];
        }
        $sq->free();
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

}
