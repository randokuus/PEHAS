<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require(SITE_PATH . "/tools/archive.php");
require(SITE_PATH . "/class/scp.class.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$isic = new isic();
$pic_names = array();
if ($result = $isic->exportCards($pic_names)) {

    $fname = $isic->getFilename();
    $out_fname = $fname . '.out';
    echo "Filename: " . $out_fname . "\n";
    echo "Saved: " . $isic->saveFile($out_fname, ISIC_PATH, $result);

    for ($i = 0; $i < sizeof($pic_names); $i++) {
        $pic_names[$i] = SITE_PATH . $pic_names[$i];
    }
    print_r($pic_names);

    // creating tar-archive
    $tar_fname = ISIC_PATH . $fname . ".tar";
    echo "\nCreating tar archive: $tar_fname\n";
    $tar = new tar_file($tar_fname);
    $tar->set_options(array('basedir' => ISIC_PATH, 'overwrite' => 1, 'storepaths' => 0));
    $tar->add_files(array(ISIC_PATH . $out_fname));
    $tar->add_files($pic_names);
    $tar->create_archive();
    //FileSystem::rmr(ISIC_PATH . $out_fname);

    /*
    $zip_fname = ISIC_PATH . $fname . ".zip";
    echo "Creating zip of pictures: $zip_fname\n";
    $zip = new zip_file($zip_fname);
    $zip->set_options(array('basedir' => ISIC_PATH, 'overwrite' => 1, 'storepaths' => 0));
    $zip->add_files($pic_names);
    $zip->create_archive();
    */

    echo "Data transfer to Trueb: \n";

    $scp = new scp('', HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);

    echo $tar_fname . "\n";
    if (!$scp->upload($tar_fname, TARGET_PATH)) {
        echo $scp->getErrors();
    }
/*
    echo $zip_fname . "\n";
    if (!$scp->upload($zip_fname, TARGET_PATH)) {
        echo $scp->getErrors();
    }
*/
//    FileSystem::rmr(ISIC_PATH . $out_fname);
//    FileSystem::rmr(ISIC_PATH . $tar_fname);
//    FileSystem::rmr(ISIC_PATH . $zip_fname);

} else {
    echo "There were no records to export this time ...";
}

exit();