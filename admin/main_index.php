<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/Database.php");

/**
 * Prepare request string
 *
 * First occurrence of | in $req is replaced with ?, all other occurrences
 * are replaced with &
 *
 * @param string $req
 * @return string
 */
function req_prepare($req)
{
    if (false !== $pos = strpos($req, '|')) {
        $req[$pos] = '?';
    }

    return str_replace('|', '&', $req);
}

$sql = new sql();
$sql->connect();
$database = new Database($sql);
load_site_settings($database);
unset($sql);

$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

if (!$_SERVER["QUERY_STRING"]) {
	redirect("admin/empty.html");
}
else {

	$el = split("\\/", $_SERVER["QUERY_STRING"]);
	list($left, $right) = $el;

	$left = req_prepare($left);
	$right = req_prepare($right);

    $tpl = new template;
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile("tmpl/main_index.html");

    $tpl->addDataItem('FRAMEBORDER', (0 === strpos('content_navi.php', $left)) ? 1 : 0);
	$tpl->addDataItem("LEFT", $left);
	$tpl->addDataItem("RIGHT", $right);

    echo $tpl->parse();
}