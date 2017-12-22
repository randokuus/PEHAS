<?php defined("MODERA_KEY")|| die(); ?><?php 
$obj = new isic_school;
$ov_1077c42504ed9029d61267dc562efdc5 = $obj->showLogo();

$obj = new user;
$ov_e858ba9b5c6ee3dc61c3c655578f12de = $obj->show();

$obj = new xslprocess;
$ov_08bcc4a9222d191cea4b7dd41b056b1c = $obj->menu('tmpl/menu_top.xsl');

$obj = new xslprocess;
$ov_4df18a8164f5b84bae946a8fa4e54504 = $obj->menu('tmpl/menu_sub.xsl');

$obj = new xslprocess;
$ov_4c7272eec081078d9c2c0d6bfa971d0a = $obj->menu('tmpl/menu_sub_sub.xsl');

$obj = new isic_application_pic;
$ov_48093a7e85b986e2a75574422d843177 = $obj->show();

$obj = new isic_school;
$ov_e5ac4034b8b93fbf786cb3b349b5fd87 = $obj->showSupportAndFeedbackInfo();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
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
    <link rel="icon" type="image/vnd.microsoft.icon" href="img/favicon.ico" />
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jqueryui.combobox.js"></script>
    <script type="text/javascript" src="js/jqueryui.widgets.js"></script>
    <script type="text/javascript" src="js/scripts_isic.js"></script>
    <link rel="stylesheet" type="text/css" href="js/jquery.Jcrop.css" media="screen" />
    <script src="js/jquery.Jcrop.js" type="text/javascript"></script>    
    <?php echo $data["AJAX_JS"]; ?>
</head>
<body>

<div class="mainWrap">
    <!--header-->
    <div class="header">
        <a href="<?php echo $data["SELF"]; ?>" class="logo"><img src="<?php echo $data["LOGO"]; ?>" border="0" alt="" width="158" height="41" /></a>
        <div class="logo1">
        <?php echo $ov_1077c42504ed9029d61267dc562efdc5; ?>        
        </div>
		<?php echo $ov_e858ba9b5c6ee3dc61c3c655578f12de; ?>
    </div>
    <!--/header-->

    <!--menuWrap-->
    <div class="menuWrap">
        <!--search-->
        <div class="search">
            <form action="<?php echo $data["SELF"]; ?>" method="GET" name="">
                <table>
                    <tr>
                        <td>
                            <div class="wtext"><div><input type="text" name="search_query" onblur="if (this.value=='') this.value='<?php echo $this->getTranslate("output|search_info"); ?>';" onfocus="if (this.value=='<?php echo $this->getTranslate("output|search_info"); ?>') this.value='';" value="<?php echo $this->getTranslate("output|search_info"); ?>" /></div></div>
                        </td>
                        <th>
                            <div class="wbutt"><div><button type="submit" name="submit"><?php echo $this->getTranslate("output|go"); ?></button></div></div>

                        </th>
                    </tr>
                </table>
            </form>
        </div>
        <!--/search-->

        <!--menu-->
        <?php echo $ov_08bcc4a9222d191cea4b7dd41b056b1c; ?>
        <!--/menu-->
        
        <!--menu1-->
		<?php echo $ov_4df18a8164f5b84bae946a8fa4e54504; ?>
        <!--/menu1-->

        <!--menu2-->
		<?php echo $ov_4c7272eec081078d9c2c0d6bfa971d0a; ?>
        <!--/menu2-->
    </div>
    <!--/menuWrap-->
    
    <!--wrappper-->
    <div class="wrapper">

    <?php echo $ov_48093a7e85b986e2a75574422d843177; ?>
    </div>
    <!--/wrappper-->

    <!--footer-->
    <div class="footer">
        <?php echo $ov_e5ac4034b8b93fbf786cb3b349b5fd87; ?>        
        <a href="#" title="Up" class="ico iup">Up</a>
    </div>
    <!--/footer-->
</div>

</body>
</html>

