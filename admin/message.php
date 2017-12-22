<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

/*
	type - (error, message) - which type of error
	message - the text to display in accordance to admin_EN file for example
	back - URL to return to (not displayed when not present)
	target - target for back URL
*/
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");

// ##############################################################

$sql = new sql();
$sql->connect();
$database = new Database($sql);
load_site_settings($database);
unset($sql);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// init Text object for this page
$txt = new Text($language2, "message");

// ##############################################################

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/message.html");

	if ($type == "error") {
		$tpl->addDataItem("TITLE", $txt->display("head2"));
	}
	else {
		$tpl->addDataItem("TITLE", $txt->display("head1"));
	}
	if ($txt->display($message)) {
		$tpl->addDataItem("TITLE", $txt->display($message));
	}
	else {
		$tpl->addDataItem("TEXT", $txt->display("error"));
	}

	if (!$back) $back = "javascript:history.back()";

	if ($back) {
		$tpl->addDataItem("BACK", "<a href=\"".urldecode($back)."\" target=\"".$target."\">".$txt->display("back")."</a>");
	}

echo $tpl->parse();
