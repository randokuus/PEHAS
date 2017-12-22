<?php
/**
 * Module for bunch upload.
 *
 * @version $Revision: 918 $
 * @author Priit Pold <priit.pold@modera.net>
 */

// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

$ATT = array_merge( $_GET , $_POST );

require_once('admin_header.php');

require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/HtmlTags.php");
require_once(SITE_PATH . "/class/DirScan.php");
require_once(SITE_PATH . "/class/Filter.php");
require_once(SITE_PATH . "/class/Filenames.php");
require_once(SITE_PATH . '/class/FileBrowser.php');
//
// define main case options
//
DEFINE(BUNCH_TRANSFER_PROCESS,'process');
DEFINE(BUNCH_TRANSFER_FORM,'form');

//
// INIT TEXT OBJECT FOR THIS PAGE
//
$translator =& ModeraTranslator::instance( $language2, 'admin_files');
$translator2 =& ModeraTranslator::instance( $language2, 'module_bunch_transfer');

// init systemlog object
$log = &SystemLog::instance($database);

/**
 * extra function for clearing ../ ./ from path
 *
 * @param string $path - path string to clear
 * @return string - cleared path string from ../ ./
 */
function cleanPath($path) {
   $result = array();
   // $pathA = preg_split('/[\/\\\]/', $path);
   $pathA = explode(DIRECTORY_SEPARATOR, $path);
   if (!$pathA[0]) $result[] = '';
   foreach ($pathA AS $key => $dir) {
       if ($dir == '..') {
           if (end($result) == '..') {
               $result[] = '..';
           } elseif (!array_pop($result)) {
               $result[] = '..';
           }
       } elseif ($dir && $dir != '.') {
           $result[] = $dir;
       }
   }
   if (!end($pathA))
       $result[] = '';
   return implode(DIRECTORY_SEPARATOR, $result);
}

function str2js( $string )
{
    return addslashes($string);
}

//
// VALIDATE INPUT DATA
//
Filter::setDefault($ATT,'do',VAR_2_STR);
Filter::setDefault($ATT,'bunch_dir',VAR_2_STR);
Filter::setDefault($ATT,'dest_dir',VAR_2_STR);
Filter::setDefault($ATT,'if_file_exists',VAR_2_STR);
Filter::setDefault($ATT,'size_big',VAR_2_STR,'640x480');
Filter::setDefault($ATT,'size_thumb',VAR_2_STR,'120x100');
Filter::setDefault($ATT,'size_only',VAR_2_BOOL,false);
Filter::setDefault($ATT,'edit_descr',VAR_2_BOOL,false);
Filter::setDefault($ATT,'process_subfolders',VAR_2_BOOL,false);
Filter::setDefault($ATT,'size_x',VAR_2_STR);
Filter::setDefault($ATT,'size_y',VAR_2_STR);

$ATT['size_x'] = Filter::toInt($ATT['size_x'],false);
$ATT['size_y'] = Filter::toInt($ATT['size_y'],false);
if( $ATT['size_x'] <= 0 ) $ATT['size_x'] = '';
if( $ATT['size_y'] <= 0 ) $ATT['size_y'] = '';

$errors = array(); // Array for storing local errors.

$source_root = $GLOBALS["directory"]["bunch_upload"];
$dest_root   = SITE_PATH.DIRECTORY_SEPARATOR.$GLOBALS["directory"]["upload"];

$source_path = cleanPath($source_root.DIRECTORY_SEPARATOR.$ATT['bunch_dir']);
$dest_path   = cleanPath($dest_root.DIRECTORY_SEPARATOR.$ATT['dest_dir']);

while ($source_path[strlen($source_path)-1]==DIRECTORY_SEPARATOR) {
    $source_path = substr($source_path,0,-1);
}
while ($dest_path[strlen($dest_path)-1]==DIRECTORY_SEPARATOR) {
    $dest_path = substr($dest_path,0,-1);
}


if (!$source_path || strpos($source_path,$source_root)===false) {
    $source_path     = $source_root;
}
if (!file_exists($source_path)) {
    $errors['bunch_dir'] = $translator2->tr('err_bunch_dir_not_exists');
}
if (!$dest_path || strpos($dest_path,$dest_root)===false) {
    $errors['dest_dir'] = $translator2->tr('err_dest_dir_not_exists');
    $dest_path = $dest_root;
}

//
// PREDEFINE SYSTEM VARIABLES
//
$settings['fail_on_error'] = false;
$main_content = '';         // main content
$size_thumb = array();      // sizes for thumbnails
$size_picture = array();    // sizes for big pictures
$if_file_exists = array(
    'rename'    => $translator2->tr('rename'),
    'replace'   => $translator2->tr('replace'),
    'skip'      => $translator2->tr('skip')
);
$message_failed = $translator2->tr('msg_failed');
$message_success= $translator2->tr('msg_success');

if (!array_key_exists($ATT['if_file_exists'],$if_file_exists)) {
    $ATT['if_file_exists'] = '';
}

//
// GET SIZES FROM DB
// IF size_thumb/size_pictures IS EMPTY, THEN FILL IT WITH DEFAULT VALUES
//
$size_db_res = $database->query( "SELECT size, name FROM files_imagesizes ORDER BY id ASC" );
while ( $data = $size_db_res->fetch_assoc() ) {
    $size_thumb[$data["size"]] = $data["name"];
    $size_picture[$data["size"]] = $data["name"];
}

if (sizeof($size_thumb) == 0){
    $size_thumb = array(
        "80x80"=>"80x80","100x80"=>"100x80",
        "120x100"=>"120x100","140x105"=>"140x105",
        "200x150"=>"200x150"
    );
}
if (sizeof($size_picture) == 0){
    $size_picture = array(
        "400x300" => "400x300", "640x480" => "640x480",
        "800x600" => "800x600"
    );
}
$size_picture['nosize'] = $translator->tr('size_no');

if( !array_key_exists($ATT['size_big'],$size_picture) ) $ATT['size_big']='';
if( !array_key_exists($ATT['size_thumb'],$size_thumb) ) $ATT['size_thumb']='';

//
// MAIN ACTION GOES HERE
//
switch ( $ATT['do'] ){
    //
    // Transformation. Validates data and if all is correct, do transform.
    //
    case BUNCH_TRANSFER_PROCESS:
        require_once(SITE_PATH . "/class/FileUploader.php");

        // validate data. if needed redirect to previos page and show errors.

        if ($ATT['size_big']=='nosize') $ATT['size_big'] = null;
        if ($ATT['size_only']) $ATT['size_thumb'] = null;
        if ($ATT['size_x'] || $ATT['size_y']) {
            $ATT['size_big'] = $ATT['size_x'].'x'.$ATT['size_y'];
        }
        //files_bunch_transfer_list.html
        $images_ext = array('gif','jpg','jpeg','tiff','png');
        $file_icons = array();

        // Database fields for `files` table.
        $fields = array('type','name','folder','owner','lastmod');

        // requiered elements for save.
        $required_data = array( 'type','name','folder','text','owner','lastmod' );

        // load directory files.
        $dir_scan = DirScan::method('oneDimension',$source_path);
        $dir_scan->setOptions(array('with_subfolders'=>false,'with_files'=>true,'max_depth'=>1,));
        if ($ATT['process_subfolders']) {
            $dir_scan->setOptions(array(
                'with_subfolders'=>true,
                'forbidden_dir'=>array('.svn'),
                'max_depth'=>0,
            ));
        }
        $files = $dir_scan->getDirStructure();

        // proceed with every file. checking, if it's image, then processUploadImage otherwise processUploadFile.
        // print out status of files...

        $tpl_fldr_choose = new Template();
        $tpl_fldr_choose->setTemplateFile( 'tmpl/files_bunch_transfer_list.html' );
        $tpl_fldr_choose->addDataItem('TITLE',$translator2->tr('bunch_form_title'));
        $tpl_fldr_choose->addDataItem('JS_AI_LABEL',$translator2->tr('js_please_wait'));
        $tpl_fldr_choose->addDataItem('HEADERS.NAME','&nbsp;');
        $tpl_fldr_choose->addDataItem('HEADERS.NAME',$translator2->tr('file_name'));
        $tpl_fldr_choose->addDataItem('HEADERS.NAME',$translator2->tr('status'));
        $tpl_fldr_choose->addDataItem('HEADERS.NAME',$translator2->tr('message'));

        echo $tpl_fldr_choose->parse();
        flush();ob_end_flush();
        echo "<script type='text/javascript'>ai.Start();</script>";

        $i = 1;
        $fu = new FileUploader();

        //
        // Process all folders.
        //
        foreach ($files as $dir_files){

            // if dir has no files, skip this folder.
            if (!is_array($dir_files['files'])) continue;

            $dest_sub_dir= '';
            $sub_pos = strpos($dir_files['full_path'],$source_path);
            if ($sub_pos !== false) {
                $dest_sub_dir = substr($dir_files['full_path'],strlen($source_path) );
                if ($dest_sub_dir===false) {
                    $dest_sub_dir = '';
                }
            }
            // check, if fulle destination path exists or not.
            // if not exists, try to create it with 0755 rights
            if (!file_exists($dest_path . $dest_sub_dir)) {
                if( !mkdir($dest_path . $dest_sub_dir, 0755)) {
                    trigger_error('Cant create directory:' . $ATT['dest_dir'] . $dest_sub_dir, E_USER_ERROR);
                } else {
                    $log->log('bunch_transfer', 'Folder ' . substr($dest_path . $dest_sub_dir, strlen(SITE_PATH))
                        . ' created by ' . $GLOBALS['ses']->getUsername());
                }
            }
            echo "<script type='text/javascript'>bunch_add_new_dir_tr('"
                . str2js($ATT['dest_dir'] . $dest_sub_dir) . "');</script>\n";
            flush();ob_end_flush();

            //
            // Process all files in current folder.
            //
            foreach ($dir_files['files'] as $file) {

                // destination file name must be without spaces.
                // remove spaces from file name
                $src_file = $source_path . $dest_sub_dir . DIRECTORY_SEPARATOR . $file;

                $src_info = Filenames::pathinfo( $src_file );
                $src_info['extension'] = strtolower($src_info['extension']);

                $src_info['filename'] = preg_replace('/ /', '_', $src_info['filename']);
                $src_info['filename'] = ereg_replace("[^[:space:]a-zA-Z0-9*_.-]", "", $src_info['filename']);

                $dest_file   = Filenames::constructPath($src_info['filename']
                    , $src_info['extension'], $dest_path . $dest_sub_dir);

                if (!isset($src_info['extension'])) $src_info['extension'] = '';

                // try to identify, which file type icon to show in list.
                $icon = '';
                // set icon for this file
                if (!$src_info['extension']) {
                    $file_icons[$src_info['extension']] = '';

                } elseif (isset($file_icons[$src_info['extension']])) {
                    // just skip it...
                } elseif (in_array($src_info['extension'], $images_ext)) {
                    $file_icons[$src_info['extension']] = "/admin/pic/icosmall_image.gif";

                } elseif (file_exists(SITE_PATH."/admin/pic/icosmall_" . $src_info['extension'] . ".gif")) {
                    $file_icons[$src_info['extension']] = "/admin/pic/icosmall_" . $src_info['extension'] . ".gif";

                }
                $icon = $file_icons[$src_info['extension']];

                /**
                 * Main process/transformation.
                 * image: FileUploader::processUploadedImage
                 * file : FileUploader::processUploadedFile
                 **/
                if (in_array($src_info['extension'], $images_ext)) {
                    $file_trans_res = $fu->processUploadedImage( $src_file , $dest_file
                        ,$ATT['size_big'], $ATT['size_thumb'], $ATT['if_file_exists'], $settings['fail_on_error'] );

                } else { // if it's not an image, then use FileUploader::processUploadedFile
                    $file_trans_res = $fu->processUploadedFile( $src_file , $dest_file
                        ,$ATT['if_file_exists'] , $settings['fail_on_error'] );

                }

                $file_renamed = false;// flag, shows if file was renamed

                if ($file_trans_res === false) { // ON MOVE/COPY FAILED.
                    $row_class = 'msg_failed';
                    $row_status = $message_failed;

                } else { // ON MOVE/COPY DONE
                    // save log about this action
                    $log->log('bunch_transfer', 'File ' . substr($file_trans_res, strlen(SITE_PATH))
                        . ' uploaded by ' . $GLOBALS['ses']->getUsername());

                    $row_class = 'msg_ok';
                    $row_status = $message_success;

                    $dest_info = Filenames::pathinfo($file_trans_res);

                    // Check, if file was renamed or not.
                    if ($file != $dest_info['basename']) {
                        $file_renamed = true;
                    }

                    $add_new_row = false; // does we need to add new row or not

                    // on 'replace' if file exists, we need to update 'lastmod' time in db
                    if ($ATT['if_file_exists'] == 'replace' AND file_exists($dest_file)) {
                        // get this file entry from DB
                        $old_data = $database->query(
                            'SELECT * FROM `files` WHERE type=? AND name=? AND folder=? AND owner=?'
                            , $src_info['extension'], $dest_info['filename'], $ATT['dest_dir'] . $dest_sub_dir, $user
                        );
                        $old_data = $old_data->fetch_assoc();

                        if ($old_data['id']) { // if info exists, then update data
                            $up_res = $database->query(
                                "UPDATE ?f SET ?f = ? WHERE ?f= ?",
                                'files', 'lastmod', date("Y-m-d H:i:s"), 'id', $old_data['id']
                            );

                        } else { // if doesnt exists, then we need to add this file
                            $add_new_row = true;

                        }
                    }
                    // if rename, insert new file into Database.
                    if ($ATT['if_file_exists'] == 'rename' || $add_new_row) {
                        $data   = array($src_info['extension'], $dest_info['filename']
                            , $ATT['dest_dir'] . $dest_sub_dir, $user, date("Y-m-d H:i:s"));
                        $db_res = $database->query('INSERT INTO `files` (?@f) VALUES(?@)', $fields, $data);
                    }

                }
                // get last error from FileUploader. if it's empty, then set our own.
                $row_message = $fu->getLastError();
                if (!strlen($row_message)) $row_message = $translator2->tr('transfer_success');
                if ($file_renamed) $row_message = $translator2->tr('file_renamed_to') . $dest_info['basename'];

                // echo JS string for adding new row into table result.
                $js_string = "<script type='text/javascript'>bunch_add_new_line('%s','%s','%s','%s','%s');</script>\n";
                echo sprintf($js_string,$icon,addslashes($file),$row_status,addslashes($row_message),$row_class);

                flush();ob_end_flush();
            }
        }

        // echos last part of info.
        $js_string = "<script type='text/javascript'>ai.Stop();</script>\n";
        $js_string.= "<div class='buttonbar'><button onclick='javascript:history.go(-1);'>%s</button></div>\n";
        $js_string.= "</body></html>";
        echo sprintf($js_string,addslashes($translator2->tr('btn_back_to_form')));

        flush();ob_end_flush();
        break;

    //
    // CASE SHOW ACTION FORM. User choose options for transfering.
    // - check, if it's deal with img/tmpl or bunch_upload files... make right desision.
    //
    case BUNCH_TRANSFER_FORM:
    default:
        $main_tpl = new Template();
        $main_tpl->setCacheLevel( TPL_CACHE_NOTHING );
        $main_tpl->setTemplateFile('tmpl/admin_main.html');
        $main_tpl->addDataItem( "TITLE", $translator2->tr('bunch_form_title') );

        // set BUNCH DIR OPTIONS
        $dir_scan = DirScan::method('oneDimension',$GLOBALS["directory"]["bunch_upload"]);
        $dir_scan->setOptions(array('with_subfolders'=>true,'forbidden_dir'=>array('.svn'),'count_files'=>true));
        $bunch_dir_struct = $dir_scan->getDirStructure();

        $bunch_dir_options = '';
        foreach ($bunch_dir_struct as $dir){
            $dir['full_path'] = str_replace(array($source_root.DIRECTORY_SEPARATOR,$source_root),'',$dir['full_path']);
            if (!strlen($dir['dir'])) {
                if (0===strpos($GLOBALS['directory']['bunch_upload'],SITE_PATH)) {
                    $dir['dir'] = substr($GLOBALS['directory']['bunch_upload'],strlen(SITE_PATH));
                } else {
                    $dir['dir'] = '-';
                }
            }
            $opt_content = str_repeat('&nbsp;&nbsp;&nbsp;',$dir['depth']).$dir['dir'];
            $opt_content = sprintf( $opt_content." (%d)",isset($dir['files_count'])?$dir['files_count']:0 );
            $opt_attrs = array( 'value'=>$dir['full_path']);

            if ($ATT['bunch_dir']==$dir['full_path']) {
                $opt_attrs['selected'] = 'selected';
            }
            $bunch_dir_options .= HtmlTags::createTag('option',$opt_content,$opt_attrs);
        }
        unset($bunch_dir_struct);

        // set DESTINATION DIR OPTIONS
        $dir_scan->setRootPath(SITE_PATH.DIRECTORY_SEPARATOR.$GLOBALS["directory"]["upload"]);
        $dir_scan->setDefaultOptions();
        $dir_scan->setOptions(array('with_subfolders'=>true,'forbidden_dir'=>array('.svn')));
        $dest_folders = $dir_scan->getDirStructure();

        $dest_options = '';
        foreach ($dest_folders as $dir) {
            $dir['full_path'] = str_replace(array($dest_root.DIRECTORY_SEPARATOR,$dest_root),'',$dir['full_path']);
            if (!strlen($dir['dir'])) {
                $dir['dir'] = $GLOBALS["directory"]["upload"];
            }
            $opt_content = str_repeat('&nbsp;&nbsp;&nbsp;',$dir['depth']).$dir['dir'];
            $opt_attrs = array( 'value'=>$dir['full_path']);

            if ($ATT['dest_dir']==$dir['full_path']) {
                $opt_attrs['selected'] = 'selected';
            }
            $dest_options .= HtmlTags::createTag('option',$opt_content,$opt_attrs);

        }
        unset($dest_folders);

        $tpl_fldr_choose = new Template();

        $tmpl_elements = array(
        // Translations
            'SUMBIT_TXT'            => $translator2->tr('sumbit_txt'),
            'LEGEND'                => $translator2->tr('bunch_form_legend'),
            'BUNCH_DIR_LABEL'       => $translator2->tr('bunch_dir'),
            'PROCESS_SUBFOLDERS'    => $translator2->tr('process_subfolders'),
            'DEST_FOLDER_LABEL'     => $translator->tr('folder'),
            'IF_FILE_EXISTS_LABEL'  => $translator2->tr('if_file_exists_act'),
            'IMAGE_SIZE_HEAD'       => $translator->tr('size_head'),
            'BIG_IMG_SIZE_LABEL'    => $translator->tr('size_big'),
            'THUMBNAIL_SIZE_LABEL'  => $translator->tr('size_thumb'),
            'SIZE_ONLY_LABEL'       => $translator->tr('size_only'),
            'SIZE_CUST_X_LABEL'     => $translator->tr('size_custom_x'),
            'SIZE_CUST_Y_LABEL'     => $translator->tr('size_custom_y'),
            'DESCR_LABEL'           => $translator->tr('my_token'),
            'HIDDEN_FIELDS.VALUE'   => $translator->tr('my_token'),

        // Elements
            'BUNCH_DIR_OPTIONS'     => $bunch_dir_options,
            'DEST_FOLDER_OPTIONS'   => $dest_options,
            'IF_FILE_EXISTS_OPTIONS'=> HtmlTags::createOptions($if_file_exists,$ATT['if_file_exists']),
            'THUMBNAIL_SIZE_OPTIONS'=> HtmlTags::createOptions($size_thumb,$ATT['size_thumb']),
            'BIG_IMG_SIZE_OPTIONS'  => HtmlTags::createOptions($size_picture,$ATT['size_big']),
            'DESCR_EDIT'            => HtmlTags::array2attrs(array('title'=>'Edit descriptions')),
            'SIZE_CUST_X_VALUE'     => $ATT['size_x'],
            'SIZE_CUST_Y_VALUE'     => $ATT['size_y'],

            'SIZE_CUST_X_ID'        => 'size_x',
            'SIZE_CUST_Y_ID'        => 'size_y',
            'SIZE_ONLY_ID'          => 'size_only',
            'HIDDEN_FIELDS.NAME'    => 'do',
            'HIDDEN_FIELDS.VALUE'   => BUNCH_TRANSFER_PROCESS,
        );

        $tpl_fldr_choose->setTemplateFile('tmpl/files_bunch_transfer_form.html');
        foreach ($tmpl_elements as $tmpl_key=>$tmpl_value) {
            $tpl_fldr_choose->addDataItem($tmpl_key,$tmpl_value);
        }

        $main_content = $tpl_fldr_choose->parse();
        $main_tpl->addDataItem("CONTENT", $main_content);
        echo $main_tpl->parse();
       break;
}