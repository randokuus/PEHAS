<?php
set_time_limit(0);
require_once(dirname(__FILE__) . "/../class/config.php");
require_once(SITE_PATH . "/class/common.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/module.news.class.php");

$news = new news();
$news->refresh_news_objects();
exit();