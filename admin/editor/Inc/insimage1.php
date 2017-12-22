<?php
include("../../../class/config.php");
require(SITE_PATH . "/class/common.php");
// backward compatibility

$insert_into = '';
if ($_GET['insert_into']) {
    $insert_into = "insert_into=" . $_GET['insert_into'];
}
if ($_GET["pic"]) {
    redirect("/admin/select_media.php?pic=" . $_GET["pic"] . "&{$insert_into}");
} else {
    redirect("/admin/select_media.php?$insert_into");
}
exit();
?>