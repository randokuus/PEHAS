<?php

require_once('../class/config.php');

if (!$_GET['f'] || strpos(!$_GET['f'], '..') !== false) {
    echo "Missing filename or hacking attempt!\n";
    exit();
}

$path = SITE_PATH . $_GET['f'];

if (!file_exists($path) || !is_readable($path)) {
    echo 'Could not find or read file: ' . $path . "\n";
    exit();
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($path));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));
ob_clean();
flush();
readfile($path);
exit;
