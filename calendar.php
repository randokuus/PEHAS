<?php
/**
 * Modera.net calendar popup
 * @access public
 */

// ##############################################################
error_reporting(0);
include("class/config.php");
require("class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require("class/".DB_TYPE.".class.php");
require("class/language.class.php");
require("class/text.class.php");
require("class/templatef.class.php");
require("class/Database.php");

// ##############################################################

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data_settings = $data = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, "");
$language = $lan->lan();

$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$type = $_GET["type"];
$field = $_GET["field"];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Modera.net</title>
<link href="img/style.css" rel="stylesheet" type="text/css" />

<SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
<!--
    function ChooseDate() {
        var finalres = "";
        var year = parseInt(document.forms['datevorm'].elements['year'].value);
        var month = parseInt(document.forms['datevorm'].elements['month'].value);
        var day = parseInt(document.forms['datevorm'].elements['day'].value);
        var field = document.forms['datevorm'].elements['field'].value;
        var dype = parseInt(document.forms['datevorm'].elements['type'].value);

        var hour = document.forms['datevorm'].elements['hour'].options [document.forms['datevorm'].elements['hour'].selectedIndex].value;
        var minute = document.forms['datevorm'].elements['minute'].options [document.forms['datevorm'].elements['minute'].selectedIndex].value;

        if (month < 10) month = "0" + month;
        if (day < 10) day = "0" + day;

        if (dype == 1) {
            finalres = year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00";
        }
        else if (dype == 2) {
            finalres = year + "-" + month + "-" + day;
        } else if (dype == 3) {
            finalres = day + "." + month + "." + year;
        }

//        alert (dype);
//        alert (finalres);
      //window.opener.document.vorm.elements[field].value = final;

        if (window.opener && !window.opener.closed) {

<?php
    if ($type == 2) {
?>
            window.opener.document.forms['vorm'].elements[field + '_d'].value = day;
            window.opener.document.forms['vorm'].elements[field + '_m'].value = month;
            window.opener.document.forms['vorm'].elements[field + '_y'].value = year;
            window.opener.document.forms['vorm'].elements[field + '_hh'].value = hour;
            window.opener.document.forms['vorm'].elements[field + '_mm'].value = minute;
<?php
    } else {
?>
            window.opener.document.forms['vorm'].elements[field].value = finalres;
<?php
    }
?>
            window.close();
        }

    }
// -->
</SCRIPT>

</head>

<body>

<?
$cal = new calendar;
if ($type) {
    $cal->parameters($_SERVER["PHP_SELF"] . "?type=" . $type, array(), $field);
} else {
    $cal->parameters($_SERVER["PHP_SELF"], array(), $field);
}
$cal->setTemplate("module_calendar_popup.html");
echo $cal->show_events_cal();

?>
</body>
</html>