<?php

define("SITE_PATH", "/var/www/minukool.ee/khs"); // no trailing slash - /var/www/www.mysite.com/site

require_once (SITE_PATH . "/class/id_card.class.php");
$id = new id_card();
echo $id->id_card_valid();

print_r($_SERVER);