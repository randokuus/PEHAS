<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<LINK REL="SHORTCUT ICON" HREF="pic/havicon.ico">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $data["TITLE"]; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="topindex_<?php echo $data["TYPE"]; ?>.css" type="text/css" media="all" />
</head>
<body id="login-frame">
	<div id="box">
		<div id="logo"><img src="pic/topframe-logo-<?php echo $data["TYPE"]; ?>.gif" alt="<?php echo $data["TYPE"]; ?>" /></div>
		<div id="login">
			<?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

			<ul class="messagelist error">
				<li><?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></li>
			</ul>
			<?php }} ?>

			<form action="<?php echo $this->getGlobals("PHP_SELF"); ?>" method="post">
			<input type="hidden" name="login" value="true">
			<input type="hidden" name="href" value="<?php echo $data["HREF"]; ?>">
			<table cellpadding="6" cellspacing="0" border="0">
			<tr>
				<td><label for="username"><?php echo $data["USERNAME"]; ?></label></td>
				<td><input type="text" size="40" name="username" id="username" value="<?php echo $this->getGlobals("username"); ?>" /></td>
			</tr>
			<tr>
				<td><label for="password"><?php echo $data["PASSWORD"]; ?></label></td>
				<td><input type="password" size="40" name="password" id="password" value="" /></td>
			</tr>
			<tr>
				<td><label for="password"><?php echo $data["LANGUAGE"]; ?></label></td>
				<td><?php echo $data["FIELD_LANGUAGE"]; ?></td>
			</tr>
			<tr>
				<td></td>
				<td><button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["BUTTON"]; ?></button></td>
			</tr>
			</table>
			</form>
            <script type="text/javascript">
            //<![CDATA[
            document.getElementById('username').select();
            document.getElementById('username').focus();
            //]]>
            </script>
		</div>
	</div>
</body>
</html>
