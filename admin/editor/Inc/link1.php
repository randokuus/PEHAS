<?
include("../../../class/config.php");
require(SITE_PATH . "/class/common.php");
// backward compatibility

$add = "";

if ($_GET["pic"]) $add = "?pic=".$_GET["pic"];

redirect("/admin/select_hyperlink.php".$add);
exit
?>