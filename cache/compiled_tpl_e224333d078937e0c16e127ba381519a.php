<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<LINK REL="SHORTCUT ICON" HREF="pic/havicon.ico">   

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $data["TITLE"]; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<frameset rows="60,*" cols="*" framespacing="0" frameborder="no" border="0">
	<frame src="top_index.php?<?php echo $data["TOPPARAM"]; ?>" id="top" name="top" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
	<frame src="<?php echo $data["BOTPARAM"]; ?>" id="main" name="main" marginwidth="0" marginheight="0" frameborder="0" scrolling="yes" />
</frameset>

<noframes><body>
</body></noframes>
</html>
