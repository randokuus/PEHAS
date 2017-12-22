<?php

$directories = array(
    'appl_tmp',
    'user_tmp',
//    'user_wrong_size',
//    'isic_tmp'
);

$path = '/var/www/minukool.ee/khs/upload/';

foreach ($directories as $dir) {
    $dir = $path . $dir . '/';
    echo $dir . ": ";

    $count = 0;
    $it = new DirectoryIterator($dir);

    /** @var FileSystemIterator $fileinfo  */
    $fileinfo = null;
    foreach ($it as $fileinfo) {
        if ($fileinfo->isFile()) {
            $count++;
            unlink($fileinfo->getPathname());
            echo $fileinfo->getPathname() . "\n";
        }
//        if ($count > 10) {
//            break;
//        }
    }
    echo 'DELETED: ' . $count . "\n";
}