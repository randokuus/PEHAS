<?php defined("MODERA_KEY")|| die(); ?><?php 
$obj = new user;
$ov_e858ba9b5c6ee3dc61c3c655578f12de = $obj->show();

$obj = new isic_user;
$ov_5a90cb868bf47642a7f66e55ececc6a9 = $obj->showSchoolLogo();

$obj = new clock;
$ov_8a434d223dc3d93844199666dfee1dd8 = $obj->show();

$obj = new xslprocess;
$ov_08bcc4a9222d191cea4b7dd41b056b1c = $obj->menu('tmpl/menu_top.xsl');

$obj = new xslprocess;
$ov_4df18a8164f5b84bae946a8fa4e54504 = $obj->menu('tmpl/menu_sub.xsl');

$obj = new xslprocess;
$ov_4c7272eec081078d9c2c0d6bfa971d0a = $obj->menu('tmpl/menu_sub_sub.xsl');

$obj = new isic;
$ov_e8c1228a22e060f4657d0fa8f65e545f = $obj->show();

$obj = new projects;
$ov_0e57529f807208b2f321a6a32d152fa8 = $obj->calendar();

$obj = new links;
$ov_ea2a39b68d85c96633743aec858526dc = $obj->show();

$obj = new isic_school;
$ov_e5ac4034b8b93fbf786cb3b349b5fd87 = $obj->showSupportAndFeedbackInfo();
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $data["PAGETITLE"]; ?></title>
    <META name="robots" content="index,follow">
    <META NAME="rating" CONTENT= "General">
    <META NAME="revisit" CONTENT="1 day">
    <META NAME="revisit-after" CONTENT= "1 day">
    <META NAME="audience" CONTENT= "all">
    <META NAME="Keywords" content="content management, cms, intranet, extranet, wysiwyg, dynamic, modera.net">
    <META NAME="Description" content="modera.net demo site">
    <script language="javascript" type="text/javascript" src="js/overlib_mini.js"></script>	
    <script language="javascript" type="text/javascript" src="img/scripts.js"></script>
    <script language="javascript" type="text/javascript" src="img/nav.js"></script>
    <script language="javascript" type="text/javascript" src="img/panel.js"></script>
    <script language="javascript" type="text/javascript" src="js/swfobject.js"></script>
    <script type="text/javascript" src="js/ext321/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="js/ext321/ext-all.js"></script>
    <link rel="stylesheet" type="text/css" href="js/ext321/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="img/templates/global_css/base.css" />
    <link rel="stylesheet" type="text/css" href="img/templates/<?php echo $data["SKIN"]; ?>/css/base.css" />
    <link rel="stylesheet" type="text/css" href="js/cropper/cropper.css" media="screen" />
	<script src="js/cropper/lib/prototype.js" type="text/javascript"></script>	
 	<script src="js/cropper/lib/scriptaculous.js?load=builder,dragdrop" type="text/javascript"></script>
	<script src="js/cropper/cropper.js" type="text/javascript"></script>    
<?php echo $data["AJAX_JS"]; ?>
</head>

<body>

<!-- #container start -->
<div id="container">

  <!-- #header start -->
  <div id="header">
  <table width="100%" class="frame">
  <tr>
    <td id="logo" valign="middle">
      <div><a href="<?php echo $data["SELF"]; ?>"><img src="<?php echo $data["LOGO"]; ?>" border=0 alt=""/></a></div>
    </td>
    <td valign="middle" width="50%">
      <?php echo $ov_e858ba9b5c6ee3dc61c3c655578f12de; ?>
    </td>
    <td>
        <?php echo $ov_5a90cb868bf47642a7f66e55ececc6a9; ?>        
    </td>
    <td valign="middle" align="right" width="50%">
      <div id="time">
        <?php echo $ov_8a434d223dc3d93844199666dfee1dd8; ?>
        <div id="timeSettings">
          <p><?php echo $data["DATE_DAY"]; ?>, <?php echo $data["DATE_DATE"]; ?><!--<br /><a href="#">Localisation settings</a>--></p>
         </div>
      </div>
    </td>
  </tr>
  </table>
  </div>
  <!-- #header end -->

  <img src="img/gradient.png" width="100%" height="15" alg="" />

  <!-- #content start -->
  <div id="content">
  <table width="100%" class="frame">
  <tr>
    <td width="100%" valign="top" id="contentColumn">
    <div id="contentColumnSpan">

      <!-- #nav start -->
      <div id="nav">
                <?php echo $ov_08bcc4a9222d191cea4b7dd41b056b1c; ?>
        <div class="clearer"></div>
      </div>
      <!-- #nav end -->

      <?php echo $ov_4df18a8164f5b84bae946a8fa4e54504; ?>

      <?php echo $ov_4c7272eec081078d9c2c0d6bfa971d0a; ?>

      <div class="clearer"></div>

<!-- col 1 start -->
<!-- col 1 end -->
<!-- col 2 start -->
<div style="float:left; width:100%;">
<?php echo $data["TEXT"]; ?>
<br>
  <div class="block">
    <div class="blockHeaderSimple">
      <h2><?php echo $data["TITLE"]; ?></h2>
      <div class="clearer"></div>
    </div>
    <?php echo $ov_e8c1228a22e060f4657d0fa8f65e545f; ?>
  </div>
  <div class="clearer"></div>
</div>
<!-- col 2 end -->
      <div class="clearer"></div>

      </div>
    </td>
    <td valign="top" id="globalColumn">
    <div id="globalColumnSpan">

      <div class="columnHeader" style="display: block;">
        <p id="editColumn"><!--<a href="#"><strong>Edit</strong></a> this column-->&nbsp;</p>
        <p id="hideColumn"><a id="hideColumnLink" href="#" onClick="show_hide_panel('<?php echo $data["SKIN"]; ?>');"><?php echo $this->getTranslate("output|hide"); ?></a></p>
      </div>

      <span id="rightColumn" style="display: none;">

      <!-- #calendar start -->
      <div id="calendar" style="display: block;">
        <?php echo $ov_0e57529f807208b2f321a6a32d152fa8; ?>
      </div>
      <!-- #calendar end -->

      <!-- #shortcuts start -->
      <div id="shortcuts" style="display: block;">
        <?php echo $ov_ea2a39b68d85c96633743aec858526dc; ?>
      </div>
      <!-- #shortcuts end -->

      <!-- #search start -->
      <form id="search" action="<?php echo $data["SELF"]; ?>">
      <div class="search" style="display: block;">
        <h2><?php echo $this->getTranslate("output|search_topic"); ?></h2>
        <input type="text" id="search-string" name="search_query" />
        <input type="submit" class="submit" name="submit" value="<?php echo $this->getTranslate("output|go"); ?>" />
      </div>
      </form>
      <!-- #search end -->

      </span>

    </div>
    </td>

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

