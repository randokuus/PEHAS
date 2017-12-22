<?php
/*
IM controller class
*/
class imcontroller {

  var $tmpl = false;
  var $debug = false;
  var $language = false;
  var $siteroot = false;
  var $vars = array();
  var $dbc = false;
  var $content_module = false;
  var $module_param = array();
  var $userid = false;
  var $username = false;
  var $user_fullname = false;
  var $cachelevel = TPL_CACHE_ALL;
  var $cachetime = 43200; //cache time in minutes
  var $template = false;
  var $config = array();
  var $email;

/** Constructor
    */

  function imcontroller () {
    global $db, $debug;
    global $language;
    global $data_settings;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $data_settings["template"];
    $this->language = $language;
    $this->debug = $debug;
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];
    $this->username = $GLOBALS["user_data"][2];
    $this->user_fullname = $GLOBALS["user_data"][1];

    if ($this->content_module == true) {
        $this->getParameters();
    }

    if ($data_settings["admin_email"]) $this->email = $data_settings["admin_email"];

  }

  function getConfig() {
    $sq = new sql;
    $sq->query($this->dbc, "SELECT * FROM IM_config WHERE id = 1");
    $data = $sq->nextrow();
    $sq->free();
    $this->config = $data;
  }

  function configXML() {
  $this->getConfig();
  echo '<?xml version="1.0" encoding="UTF-8"?>';
  ?>
<c>
    <c name="im_lang">
        <c name="fileName"><?php echo $this->config["conf_lang"]?></c>
    </c>
    <c name="im_server">
        <c name="server_ip"><?php echo $this->config["conf_server"]?></c>
        <c name="server_port"><?php echo $this->config["conf_port1"]?></c>
        <c name="server_command_port"><?php echo $this->config["conf_port2"]?></c>
    </c>
    <c name="im_type">
        <c name="im_type"><?php if ($this->config["conf_type"] == 1) { echo "intranet"; } else { echo "extranet"; } ?></c>
    </c>
    <c name="window_title">
        <c name="title"><?php echo $this->config["conf_title"]?></c>
    </c>
    <c name="anonym_group_name">
        <c name="name"><?php echo $this->config["conf_anon"]?></c>
    </c>
    <c name="sound_file_path">
        <c name="name"><?php echo $this->config["conf_sound"]?></c>
    </c>
    <c name="anonumous_settings">
        <c name="owner_message_color">0x000000</c>
        <c name="buddy_message_color">0x000099</c>
        <c name="emoticons">true</c>
    </c>
    <c name="client_settings">
        <c name="owner_message_color">0x000099</c>
        <c name="buddy_message_color">0x000000</c>
        <c name="emoticons">true</c>
    </c>
    <c name="watcher_settings">
        <c name="owner_message_color">0x000099</c>
        <c name="buddy_message_color">0x000000</c>
        <c name="emoticons">true</c>
    </c>
    <c name="lunchIMButton">
        <c name="link">javascript:openIM('modera.php')</c>
        <c name="getData">user_data.php</c>
    </c>
</c>
<?php

  }

// ########################################

function showAnonymous() {
    global $data_settings;

    $sq = new sql;

    $txt = new Text($this->language, "module_imcontroller");

    if (sizeof($this->config) == 0) $this->getConfig();

    // do form
    if ($_POST["write"] == "true") {
        include_once(SITE_PATH . "/class/mail/htmlMimeMail.php");
        if ($_POST["name"] && $_POST["email"] && $_POST["message"] && validateEmail($_POST["email"])) {
            $e_text = $_POST["message"] . "<br /><br />\n\n";
            $e_text .= "\n<br /><b>Date</b>: ". date("d.m.Y H:i") . "<br />\n";
            $e_text .= "<b>Remote addr</b>: " . $_SERVER["REMOTE_ADDR"] . "<br />\n";
            if ($this->userid) {
                $e_text .= "<b>Logged in user</b>: " . $this->username . " (id ". $this->userid. ") - ". $this->user_fullname . "<br />\n";
            }
            $mail = new htmlMimeMail();
            $mail->setHtml($e_text, returnPlainText($e_text));
            if ($this->config["conf_email"]) {
                $send_to = $this->config["conf_email"];
            }
            else {
                $send_to = $this->email;
            }
            $mail->setFrom($_POST["name"] . " <".$_POST["email"].">");

            $mail->setSubject($txt->display("module_title"));
            $result = $mail->send(array($send_to));
            if ($result) { $ok = true; }
            else { $ok = false; }
        }
        else {
            $error = true;
        }
    }

    // instantiate template class
    $tpl = new template;

    $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_imcontroller_anonymous.html";

    $tpl->tplfile = $this->tplfile;
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=imcontroller&status=".$this->config["conf_status"]);
    $tpl->setTemplateFile($template);

    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "imcontroller";
        return "<!-- module imcontroller cached -->\n" . $tpl->parse();
    }

    // #################################

    if ($this->config["conf_status"] == 1) {
        $tpl->addDataItem("OPEN.MESSAGE", $txt->display("module_title"));
        $tpl->addDataItem("OPEN.BUTTON", $txt->display("open_im"));
    }
    else if ($this->config["conf_status"] == 2) {
        //$tpl->addDataItem("CLOSED.MESSAGE", $txt->display("im_closed"));
        if ($ok == true) {
            $tpl->addDataItem("CLOSED.MESSAGE.MESSAGE", $txt->display("form_ok"));
        }
        else {
            if ($error == true) {
                $tpl->addDataItem("CLOSED.MESSAGE.MESSAGE", $txt->display("form_error"));
            }
            $tpl->addDataItem("CLOSED.FORM.SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("write")));
        }
    }

    // ####

    return $tpl->parse();
}

// ########################################

    // #####################
    // functions for content management

    function getParameters() {
        global $pagedata;
        $ar = split(";", $pagedata["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    function moduleOptions() {

        // ####
        return array();
        // name, type, list
    }

}
