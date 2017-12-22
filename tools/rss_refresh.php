<?php
ini_set("memory_limit","20M");
set_time_limit(0);
require_once(dirname(__FILE__) . "/../class/config.php");
require_once(SITE_PATH . "/class/common.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/module.rss.class.php");

$rss = new rss();
$result = $rss->refreshRssObjects();
print_r($result);
exit();