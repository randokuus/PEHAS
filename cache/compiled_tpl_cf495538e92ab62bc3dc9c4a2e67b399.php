<?php defined("MODERA_KEY")|| die(); ?><?php 
$obj = new user;
$ov_142f6748feab6eb0bdbce47c7f2b1989 = $obj->login();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo $data["PAGETITLE"]; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="img/style_main.css" media="all" />
    <!--[if IE 7]>
        <link rel="stylesheet" type="text/css" href="img/style_ie7.css" media="all" />
    <![endif]-->
    <!--[if lte IE 6]>
        <link rel="stylesheet" type="text/css" href="img/style_ie6.css" media="all" />
    <![endif]-->
    <!--<link rel="icon" type="image/vnd.microsoft.icon" href="img/favicon.ico" />-->
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jqueryui.combobox.js"></script>
    <script type="text/javascript" src="js/jqueryui.widgets.js"></script>
    <script type="text/javascript" src="js/scripts_isic.js"></script>
    <script type="text/javascript">
    </script>
</head>
<body onload="switch_login_type();">
    <?php echo $ov_142f6748feab6eb0bdbce47c7f2b1989; ?>
</body>
</html>