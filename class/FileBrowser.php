<?php
/**
 * @version $Revision: 918 $
 */

require_once(SITE_PATH . '/class/JsonEncoder.php');
require_once(SITE_PATH . '/class/FileSystem.php');
require_once(SITE_PATH . '/class/Imagemagick.php');
/**
 * Class for handling Ext-based file browser's requests
 *
 * @author Gleb Sinkovskiy <gleb.sinkovskiy@modera.net>
 */
class FileBrowser {

    /**
     * Request variables
     *
     * @var array $vars
     * @access private
     */
    var $_vars;

    /**
     * Database access object
     *
     * @var Database
     * @access private
     */
    var $_db;

    /**
     * Log instance
     *
     * @var SystemLog
     * @access private
     */
    var $_log;


    var $_perm;

    /**
     * Constructor
     *
     * @return FileBrowser
     */
    var $_ses;
    function FileBrowser() {
        $this->_vars = $_REQUEST;
        if (!isset($GLOBALS["sq"])) {
            $GLOBALS["sq"] = new sql();
            $GLOBALS["sq"]->connect();
        }
        if (!isset($GLOBALS["database"])) {
            $GLOBALS["database"] = new Database($GLOBALS["sq"]);
        }
        $this->_db =& $GLOBALS["database"];
        $this->_log =& SystemLog::instance($this->_db);
        $this->_ses = new Session();

        $user = $this->_ses->returnUser();
        $group = $this->_ses->group;
        $this->_perm = new Rights($group, $user, "module", false);

    }





    /**
     * Get class instance
     * singelton
     *
     * @access public
     * @static
     * @return object class instance
     */
    function &getInstance() {
        static $holder = array();
        if (!isset($holder['inst'])) {
            $holder['inst'] = new FileBrowser();
        }
        return $holder['inst'];
    }

    /**
     * Check file type
     *
     * @access private
     * @param string $file filename
     * @return boolean
     */
    function _fileVisible($file) {
        $mode = $this->_vars['mode'];
        $folder = $this->_vars['folder'];
        $file_info = Filenames::pathinfo($file);
        if (false !== strpos($file, '_thumb.') ||
        (($file == "SITELOGO.gif" || $file == "SITELOGO.jpg") && $folder == "root/upload") ||
        $file_info['extension'] == 'php' ||
        substr($file,0,1) == ".") {
            return false;
        }
        else {
            if ($mode == "all") {
                return true;
            }
            else if ($mode == "pic") {
                if ($file_info['extention'] == "gif" || $file_info['extention'] == "jpg" || $file_info['extention'] == "png") {
                    return true;
                }
                else {
                    return false;
                }
            }
            else if ($mode == "nopic") {
                if ($file_info['extention'] != "gif" && $file_info['extention'] != "jpg" && $file_info['extention'] != "png") {
                    return true;
                }
                else {
                    return false;
                }
            }
            else {
                if ($file_info['extention'] == addslashes($mode)) {
                    return true;
                }
                else {
                    return false;
                }
            }
        }
    }


    /**
     * Actions for folders
     *
     * @access public
     * @param string $source Source folder path
     * @param string $destination Destination folder path
     * @param bool $copy is action copy, if false action = move
     * @return bool
     */
    function _actionFolder($source, $destination, $copy = true) {

        if (isset($this->_vars['overwritedata']) && is_array($this->_vars['overwritedata'])) {
            $overwrite = true;
        } else {
            $overwrite = false;
        }

        $folder_name = substr($source, strrpos($source, '/') + 1);
        $source = substr($source, strlen('root/upload')) . '/';
        $destination = substr($destination, strlen('root/upload')) . '/';
        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];

        if (is_dir($upload_dir . $destination . $folder_name)) {
            if (!$overwrite) {
                $_owerwrite = new stdClass;
                $_owerwrite->type  = 'folder';
                $_owerwrite->name  = $folder_name;
                $_owerwrite->icon  = 'pic/ico_folder-closed.gif';
                $_owerwrite->sicon = 'pic/icosmall_folder-closed.gif';
                return array('error' => '', 'overwrite' => array($_owerwrite));
            }
        }

        $res = $this->_proccessFolderAction($upload_dir . $source
                  , $upload_dir . $destination . $folder_name . '/', $copy);

        if (!empty($res['error'])) {
            return $res;
        }

        return array('error' => '');
    }

    /**
     * Copy folder
     *
     * @access public
     * @param string $source Source folder path
     * @param string $destination Destination folder path
     * @return bool
     */
    function copyFolder($source, $destination) {
        return $this->_actionFolder($source, $destination, true);
    }

    /**
     * Move folder
     *
     * @access public
     * @param string $source Source folder path
     * @param string $destination Destination folder path
     * @return bool
     */
    function moveFolder($source, $destination) {
        return $this->_actionFolder($source, $destination, false);
    }


    /**
     * Remove Folder wrapper
     *
     * @access private
     * @param string $destination folder path
     * @return array result array
     */
    function _rmdir($destination) {

        if ('/' == substr($destination, -1)) {
            $destination = substr($destination, 0, -1);
        }

        $folder_id = $this->_getFolderId($destination);

        if (!file_exists($destination) && !is_dir($destination)) {
            if ($folder_id) {
                $sql   = "DELETE FROM `folders` WHERE `id` = " . $folder_id;
                $this->_db->query($sql);
            }
            return array('error' => '');
        }

        if (!$this->_perm->Access(0, $folder_id, "d", "folderaccess")) {
            return array('error' => 'You don`t have permissions to delete this folder.'
                     . $destination);
        }

        $error = '';
        if (@rmdir($destination)) {
            $name = substr($destination
                , strlen(SITE_PATH . "/" . $GLOBALS["directory"]["upload"]));

            // save log about this action
            $this->_log->log('file_manager', 'Folder ' . $name
                                . ' deleted by ' . $GLOBALS['ses']->getUsername());

            if ($folder_id) {
                $sql   = "DELETE FROM `folders` WHERE `id` = " . $folder_id;
                $this->_db->query($sql);
            }
        } else {
            $error = 'File system error removing directory! ' . $destination;
        }

        return array('error' => $error);
    }

    /**
     * Create Folder wrapper
     *
     * @access private
     * @param string $destination folder path
     * @return array result array
     */
    function _mkdir($destination) {
        $error = '';
        if (!$this->_perm->Access(0, 0, "a", "folderaccess")) {
            return array('error' => 'Create folder action is denied!');
        }

        if ('/' == substr($destination, -1)) {
            $destination = substr($destination, 0, -1);
        }

        if (!is_dir($destination) && !file_exists($destination)) {
            $parent_dest = substr($destination, 0, strrpos($destination, '/'));
            if (!is_writable($parent_dest)) {
                return array('error' => 'Filesystem error! ' . $parent_dest . ' - is not writable.');
            }

            $name = substr($destination
                      , strlen(SITE_PATH . "/" . $GLOBALS["directory"]["upload"]));

            if (255 < strlen($name)) {
                return array('error' => 'Directory path too long. Max 255 chars.');
            }

            if (!@mkdir($destination, 0777)) {
                return array('error' => 'Filesystem error! Can not create directory ' . $destination);
            } else {
                // save log about this action
                $this->_log->log('file_manager', 'Folder '. $name
                    . ' created by ' . $GLOBALS['ses']->getUsername());

                $owner  = $this->_db->quote($GLOBALS['ses']->returnUser());
                $_name   = $this->_db->quote(substr($name, strrpos($name, '/') + 1));
                $_folder = $this->_db->quote(substr($name, 0, strrpos($name, '/') + 1));

                $sql   = "INSERT INTO `folders` (`name`, `folder`, `owner`, `removed`, `disabled`) "
                       . "VALUES ($_name, $_folder, $owner, 0, 0)";

                $res =& $this->_db->query($sql);
                if (false === $res) {
                    if (@rmdir($destination)) {
                        // save log about this action
                        $this->_log->log('file_manager', 'Folder '. $name
                              . ' created by ' . $GLOBALS['ses']->getUsername() .' while db error');
                    }
                    $error = 'Insert directory record filed by database. ' . $name;
                }
            }
        } elseif (is_dir($destination)) {
            $error = 'Directory allready exists. ' . $destination;
        }

        return array('error' => $error);
    }

    /**
     * Actions for folder with subfolders
     *
     * @access private
     * @param string $source Source path
     * @param string $destination Destination path
     * @param boolean show copy or move(false)
     * @return array - result
     */
    function _proccessFolderAction($source, $destination, $copy = true) {
        if ($source == $destination) {
            return array('error' => '');
        }

        if (substr($destination, -1) == '/') {
            $destination = substr($destination, 0, -1);
        }

        if (substr($source, -1) == '/') {
            $source = substr($source, 0, -1);
        }

        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];

        if (($res = $this->autoRenameIf($destination, false))
                        && !empty($res['error'])) {
            return $res;
        }

        if (!is_dir($destination) && !file_exists($destination)) {
            if (!$copy) {
                return $this->_rename($destination, $source);
            }
            $res = $this->_mkdir($destination);
            if (!empty($res['error'])){
                return $res;
            }
        }

        if (!$dir = @opendir($source)) {
            array('error' => 'Filesystem error opening directory ' . $source);
        }



        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($source . '/' . $file)) {
                    $res = $this->_proccessFolderAction($source . '/' . $file, $destination . '/' . $file, $copy);
                    if (!empty($res['error'])){
                        return $res;
                    }
                } else {
                    $file_sorce = substr($source, (strlen($upload_dir))) . '/' . $file ;
                    $file_dest  = substr($destination, (strlen($upload_dir))) . '/' . $file;
                    $res = $this->_proccessFileAction($file_sorce, $file_dest, true, $copy);
                    if (!empty($res['error'])){
                        return $res;
                    }
                }
            }
        }

        closedir($dir);
        if (!$copy) {
            $res = $this->_deleteContents($source, true);
            if (!empty($res['error'])){
                return $res;
            }
        }

        return array('error' => null);
    }

    /**
     * Copy folder with subfolders
     *
     * @access private
     * @param string $source Source path
     * @param string $destination Destination path
     * @return boolean
     */
    function _copyFolder($source, $destination) {
        $this->_actionFolder($source, $destination, true);
    }

    /**
     * Move folder with subfolders
     *
     * @access private
     * @param string $source Source path
     * @param string $destination Destination path
     * @return boolean
     */
    function _moveFolder($source, $destination) {
        $this->_actionFolder($source, $destination, false);
    }

    /**
     * Delete file or folder content
     *
     * @access private
     * @param string $dir Path to delete
     * @param boolean $remove_root If true - remove path root
     * @return boolean
     */
    function _deleteContents($dir, $remove_root, $recycle = true) {
        $svnpath = SITE_PATH. "/" . $GLOBALS["directory"]["upload"] .'/.svn';
        $dh = @opendir($dir);
        while ($file = @readdir($dh)){
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    if ($file != 'file.php' && $file != '.htaccess' && $file != 'SITELOGO.gif') {
                        $res = $this->_deleteFile(substr($fullpath, strlen(SITE_PATH
                                   . "/" . $GLOBALS["directory"]["upload"])), true, $recycle);
                        if (!empty($res['error'])) {
                            return $res;
                        }
                    }
                } else {
                    if ($svnpath == $fullpath) {
                        continue;
                    }
                    $res = $this->_deleteContents($fullpath, true, $recycle);
                    if (!empty($res['error'])) {
                        return $res;
                    }
                }
            }
        }
        closedir($dh);

        if ($remove_root == true && $svnpath != $dir) {
            $res = $this->_rmdir($dir);
            if (!empty($res['error'])) {
                return $res;
            }
        }
        return array('error' => '');
    }

    /**
     * Check is file an image
     *
     * @access private
     * @param string $file Filename to check
     * @return boolean
     */
    function _fileIsImage($file) {
        if (false === strpos(strtolower($file), '_thumb.')
             && (strtolower(substr($file, -3)) == "gif"
                || strtolower(substr($file, -3)) == "jpg"
                || strtolower(substr($file, -3)) == "png")) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Check is file an image thumbnail
     *
     * @access private
     * @param string $file Filename to check
     * @return boolean
     */
    function _fileIsThumb($file) {
        $ender = strtolower(substr($file, -10));
        if ($ender == "_thumb.gif" || $ender == "_thumb.jpg" || $ender == "_thumb.png") {
            return true;
        }
        return false;
    }

    /**
     * Generate thumbnails
     *
     * @access private
     * @param string $folder Folder name
     * @return boolean
     */
    function _generateThumbnails($folder){
        $opendir = addslashes($folder);
        if ($dir = @opendir($opendir)) {
            // files
            $im = new Imagemagick();
            while (($file = @readdir($dir)) !== false) {
                if (@!is_dir($opendir . $file) && $file != "." && $file != ".." && $this->_fileIsImage($file)) {
                    $file_thumb = $opendir . substr($file, 0, -4) . "_thumb." . substr($file, -3);
                    $im->thumbnail($opendir .$file, $file_thumb, '120x100');
                    if (file_exists($file_thumb)){
                        // save log about this action
                        $this->_log->log('file_manager', 'Thumbnail of file '
                            . substr($opendir . $file, strlen(SITE_PATH))
                            . ' created by ' . $GLOBALS['ses']->getUsername());
                    }
                }
            }
        }
    }

    /**
     * Processing request
     *
     * Process request params, call needed function and encode results
     * @access public
     */
    function process() {
        if (!isset($this->_vars['do'])) {
            return;
        }
        $result = false;

        switch ($this->_vars['do']) {
            case 'refresh':
                $result = $this->refresh($this->_vars['folder']);
                break;
            case 'users_store':
                $result = $this->getUsers();
                break;
            case 'change_description':
                $result = $this->changeDescription($this->_vars['value'],$this->_vars['dest']);
                break;
            case 'save_privs':
                $result = $this->saveProperties($this->_vars['file'], $this->_vars['folder'], $this->_vars['newowner'],  $this->_vars['type']);
                break;
            case 'save_panel_state':
                $this->saveViewState($this->_vars['state']);
                $result = true;
                break;
            case 'get_nodes':
                $result = $this->getFolders($this->_vars['node']);
                break;
            case 'get_files':
                $result = $this->getFiles($this->_vars['folder'], $this->_vars['showdisabled']);
                break;
            case 'move_files':
                $result = $this->moveFiles($this->_vars['files'], $this->_vars['source_path'], $this->_vars['destination_path']);
                break;
            case 'copy_files':
                $result = $this->copyFiles($this->_vars['files'], $this->_vars['source_path'], $this->_vars['destination_path']);
                break;
            case 'copy_folder':
                $result = $this->copyFolder($this->_vars['source'], $this->_vars['destination']);
                break;
            case 'move_folder':
                $result = $this->moveFolder($this->_vars['source'], $this->_vars['destination']);
                break;
            case 'delete':
                $result = $this->deleteFiles($this->_vars['filenames']);
                break;
            case 'add_folder':
                $result = $this->createFolder($this->_vars['parent_folder'], $this->_vars['name']);
                break;
            case 'empty_folder':
                $result = $this->moveFolderContentToRecycle($this->_vars['folder']);
                break;
            case 'delete_folder':
                $result = $this->deleteFolder($this->_vars['folder']);
                break;
            case 'create_thumbnails':
                $result = $this->createThumbnails($this->_vars['folder']);
                break;
            case 'rename_folder':
                $result = $this->renameFolder($this->_vars['value'],$this->_vars['dest']);
                break;
            case 'rename_file':
                $result = $this->renameFile($this->_vars['value'],$this->_vars['dest']);
                break;
            case 'is_admin':
                $result = $this->isUserAdminGroup();
                break;
            case 'folder_recycle':
                $result = $this->moveFolderToRecycle($this->_vars['source']);
                break;
            case 'empty_recycle':
                $result = $this->emptyRecycle();
                break;
            case 'restore_folder':
                $result = $this->restoreFolder($this->_vars['source'], $this->_vars['strict']);
                break;
            case 'restore_files':
                $result = $this->restoreFiles($this->_vars['files'], $this->_vars['strict']);
                break;

        }
        if ($result || is_array($result)) {
            echo JsonEncoder::encode($result);
            exit();
        }
    }

    /**
     * Get folders tree.
     *
     * @access public
     * @param string $root Current folder path
     * @return array tree of subfolders
     */
    function getFolders($root) {
        if ($root == "root") {
            $sql = 'SELECT COUNT(`id`) FROM `folders` WHERE `removed`=1';
            $res = $this->_db->fetch_first_value($sql);
            return array(
                array(
                    'text' => 'upload',
                    'id' => 'root/upload',
                    'cls' => 'folder',
                    'childs' => true,
                ),
                array(
                    'text' => 'Recycle bin',
                    'id' => 'root/Recycle',
                    'cls' => 'trash',
                    'disabled'=>1,
                    'childs' => $res,
                )
            );
        } elseif ($root == "root/Recycle") {
            $folder = '/';
            $sql = 'SELECT * FROM `folders` WHERE `removed`=1';
        } else {
            $folder = substr($root, strlen('root/upload')) . '/';
            $sql = 'SELECT * FROM `folders` WHERE `folder`='
                . $this->_db->quote($folder) . ' AND `removed`=0';
        }
        $verif = array();
        $res =& $this->_db->query($sql);
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $folders[] = array(
                             'text' => $row['name'],
                             'id' => 'root/upload' . $row['folder'] . $row['name'],
                             'cls' => ($row['owner'])?'folder':'folder-noowner',
                             'owner' => $row['owner'],
                             'disabled' => (int) $row['disabled'],
                             'removed' => (int) $row['removed'],
                             'childs' => false
                );

                $verif[] = '`folder`=' . $this->_db->quote($folder . $row['name'] . '/');
            }
            if (!empty($verif)) {
                $vsql = 'SELECT `folder` FROM `folders` WHERE ' . implode(' OR ', $verif);
                $vres =& $this->_db->query($vsql);
                while ($vrow = $vres->fetch_assoc()) {
                    foreach ($folders as $key=>$dir) {
                        if ($dir['id'] . '/' == 'root/upload' . $vrow['folder']) {
                            $folders[$key]['childs'] = true;
                            break;
                        }
                    }
                }
            }
        }


        return $folders;

    }


    /**
     * Get folders tree.
     *
     * @access static
     * @param string $root Current folder path
     * @return array tree of subfolders
     */
    function getStaticFolders($dir, $folder_list) {
        $inst =& FileBrowser::getInstance();
        return $inst->getFoldersList($dir, $folder_list);
    }

    /**
     * Get folders tree.
     *
     * @access public
     * @param string $root Current folder path
     * @return array tree of subfolders
     */
    function getFoldersList($dir, $folder_list) {
        $res =& $this->_db->query('SELECT `name` FROM `folders` WHERE `disabled`=0 AND `owner`>0 AND `folder`='
              . $this->_db->quote($dir . '/'));
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $file = $row['name'];
                if ($file != "." && $file != "..") {
                    if ($dir) $final = $dir . "/" . $file ;
                    else { $final = $file ; }

                    if ('/' == substr($final, 0,1)) {
                        $final = substr($final, 1);
                    }

                    $folder_list['/'.$final. '/'] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/")) . $file;
                    $folder_list = $this->getFoldersList('/' . $final, $folder_list);
                }
            }
        }

        return $folder_list;

    }

    /**
     * Get list of files in folder
     *
     * @access public
     * @param string $folder path
     * @param mixed $disabled if true removed files will be showed
     * @return array list of files
     */
    function getFiles($folder, $disabled = false) {

        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];
        $view_dir   = SITE_URL . "/" . $GLOBALS["directory"]["upload"];
        $masked = 0;

        if ($folder == "root/Recycle") {
            $sql_inj = ' `removed`=1';
            $this->saveFolderState($folder, $disabled);
            $this->saveDisabledState($disabled);
        } else {
            $folder = substr($folder, strlen('root/upload')) . "/";
            $sql_inj = ' `folder` = '  . $this->_db->quote($folder);
            $this->saveFolderState($folder, $disabled);
            $this->saveDisabledState($disabled);
            $opendir = $upload_dir . addslashes($folder) ;
            if ($folder != '/') {
                if (false == ($fp = $this->_getFolderProps($opendir))) {
                    return array('total'=>0, 'rows' => array());
                } else {
                    if ($fp['disabled'] == 0 ) {
                        if (!$disabled) {
                            $sql_inj .= ' AND `disabled` = 0';
                        } else {
                            $masked = 1;
                        }
                    } else {
                        $sql_inj .= ' AND `removed` = 0';
                    }
                }
            } else {
                if (!$disabled) {
                    $sql_inj .= ' AND `disabled` = 0';
                } else {
                    $masked = 1;
                }
            }

            $opendir .= '/';
        }


        $files = array();
        $files_r = array();

        if ($this->_vars['filter'] != "") {
            $images = array('gif','jpg','bmp','png');
            switch($this->_vars['filter']) {
                case "images":
                    $filter_sql = " AND (`type`='gif' OR `type`='jpg' OR `type`='bmp' OR `type`='png')";
                    break;
                case "notimages":
                    $filter_sql = " AND `type`!='gif' AND `type`!='jpg' AND `type`!='bmp' AND `type`!='png'";
                break;
                default:
                    $filter_sql = " AND CONCAT(`name`, \".\", `type`) LIKE " .$this->_db->quote('%' . strtolower($this->_vars['filter']) . '%');
            }
        }

        $count = intval($this->_vars['count']);
        $start = intval($this->_vars['start']);
        if ($start < 0) {
            $start = 0;
        }
        if ($count <= 0) {
            $count = 100;
        }
        if ($count > 0) {
            $sql_limit = ' LIMIT ' . $start  . ', ' . $count;
        }

        $sql = "SELECT `id` FROM `files` WHERE " . $sql_inj . $filter_sql;

        $res =& $this->_db->query($sql);

        $total = $res->num_rows();

        if($total == 0) {
            return array('total'=>0, 'rows' => array());
        }

        $sql = "SELECT `id`, `type`, `name`, CONCAT(name, \".\", type) as `file`, `text`, `folder`,`owner`, `removed`, `disabled`
                FROM `files` WHERE ". $sql_inj . $filter_sql ." ORDER BY file ASC" . $sql_limit;
        $res =& $this->_db->query($sql);
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $obj  = $row['name'];
                $type = $row['type'];
                $view_url = $view_dir . $row["folder"] . $row["file"];
                $thumb_url = '';
                if ($type == "gif" || $type == "jpg" || $type == "png" || $type == "tif") {
                    $content = 'image';
                    $icon = 'pic/icosmall_image.gif';
                    $thumbname = $row["folder"] . $obj . "_thumb." . $type;
                    if (file_exists($upload_dir . $thumbname)) {
                        $thumb_url = $icon_big = $view_dir . $thumbname;
                    } else {
                        $icon_big = "pic/ico_image.gif";
                    }
                } else {
                    $content = 'file';
                    if (file_exists(SITE_PATH . "/admin/pic/icosmall_" . $type . ".gif")) {
                        $icon = 'pic/icosmall_' . $type . '.gif';
                    } else {
                        $icon = 'pic/icosmall_other.gif';
                    }

                    if (file_exists(SITE_PATH . "/admin/pic/ico_" . $type . ".gif")) {
                        $icon_big = "pic/ico_" . $type . ".gif";
                    } else {
                        $icon_big = "pic/ico_other.gif";
                    }
                }


                $rows[] = array(
                    'owner'=> $row["owner"],
                    'icon' => $icon,
                    'disabled' => $row["disabled"],
                    'removed' => $row["removed"],
                    'icon_big' => $icon_big,
                    'view_url' => $view_url,
                    'thumb_url' => $thumb_url,
                    'filename' => $row["file"],
                    'size' => filesize($upload_dir .  $row["folder"] .  $row["file"]),
                    'last_modified' => date ("d.m.y H:i", filemtime($upload_dir .  $row["folder"] .  $row["file"])),
                    'description' => ($row["text"])?$row["text"]:'',
                    'id' => $row["id"],
                    'obj' => $obj,
                    'type' => $type,
                    'folder' => $row["folder"],
                    'content' => $content,
                    'masked' => ($masked && $row["disabled"]==1)?1:0,
                );
            }
        }
        return array('total'=>$total, 'rows' => $rows);
    }


    /**
     * Get file id from full path
     *
     * @access private
     * @param string $full_path
     * @return mixed id of file if it exists false on error
     */
    function _getFileId($full_path) {
        $src = Filenames::pathinfo($full_path);
        $src_folder = str_replace('\\', '/', $src['dirname']);
        if ($src_folder && $src_folder != '/') {
            $src_folder .= '/';
        }
        return $this->_db->fetch_first_value("SELECT `id` FROM `files`"
                      . " WHERE `name` = '" . addslashes($src['filename']) . "' "
                      . "AND `type` = '" . addslashes($src['extension']) . "' "
                      . "AND `folder` = '" . $src_folder . "'");
    }


    /**
     * Get file properties from full path
     *
     * @access private
     * @param string $full_path
     * @return mixed id of file if it exists false on error
     */
    function _getFileProps($full_path) {
        $src = Filenames::pathinfo($full_path);
        $src_folder = str_replace('\\', '/', $src['dirname']);
        if ($src_folder && $src_folder != '/') {
            $src_folder .= '/';
        }
        return $this->_db->fetch_first_row("SELECT * FROM `files`"
                      . " WHERE `name` = '" . addslashes($src['filename']) . "' "
                      . "AND `type` = '" . addslashes($src['extension']) . "' "
                      . "AND `folder` = '" . $src_folder . "'");

    }


    /**
     * Get Folder id from full path
     *
     * @access private
     * @param string $full_path
     * @return mixed id of folder if it exists false on error
     */
    function _getFolderId($full_path) {

        if ('/' == substr($full_path, -1)) {
            $full_path = substr($full_path, 0, -1);
        }
        $name = substr($full_path
                , strlen(SITE_PATH . "/" . $GLOBALS["directory"]["upload"]));

        $_name   = $this->_db->quote(substr($name, strrpos($name, '/') + 1));
        $_folder = $this->_db->quote(substr($name, 0, strrpos($name, '/') + 1));

        $query = "SELECT `id` FROM `folders` WHERE `name` = "
             . $_name . " AND `folder` = " . $_folder;
        return $this->_db->fetch_first_value($query);

    }

    /**
     * Get Folder properties from full path
     *
     * @access private
     * @param string $full_path
     * @return mixed id of folder if it exists false on error
     */
    function _getFolderProps($full_path) {

        if ('/' == substr($full_path, -1)) {
            $full_path = substr($full_path, 0, -1);
        }
        $name = substr($full_path
                , strlen(SITE_PATH . "/" . $GLOBALS["directory"]["upload"]));

        $_name   = $this->_db->quote(substr($name, strrpos($name, '/') + 1));
        $_folder = $this->_db->quote(substr($name, 0, strrpos($name, '/') + 1));

        $query = "SELECT * FROM `folders` WHERE `name` = "
               . $_name . " AND `folder` = " . $_folder;
        return $this->_db->fetch_first_row($query);


    }


    function autoRenameRecycled($dest, $file = true, $name = null) {
        $inst =& FileBrowser::getInstance();
        return $inst->autoRenameIf($dest, $file, $name);
    }


    /**
     * Auto rename folder/file if it exists and removed
     *
     * @access public
     * @param string $dest destination
     * @param bool $file if true than check file, folder otherwise
     * @return bool
     */
    function autoRenameIf($dest, $file = true, $name = null) {

        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];
        $dest = str_replace('\\', '/', $dest);
        $dest = str_replace('//', '/', $dest);
        if ($file) {
            $prop_method = '_getFileProps';
            $exists_func = 'is_file';
            $search_dest = substr($dest, strlen($upload_dir));
        } else {
            $prop_method = '_getFolderProps';
            $exists_func = 'is_dir';
            $search_dest = $dest;
        }

        if ($exists_func($dest)) {
            $fp = $this->{$prop_method}($search_dest);
            if ($fp) {
                if (1 == $fp['disabled'] && 0 == $fp['removed']) {
                    return array('error' => 'Posted wrong data');
                } elseif (1 == $fp['disabled'] && 1 == $fp['removed']) {
                    if ($name == null) {
                        $name = $fp['name'];
                    }

                    $i = 1;
                    do {
                        $new = $upload_dir . $fp['folder'] . $name . '_' . $i;

                        if ($file) {
                            $new .= '.' . $fp['type'];
                        }

                        if (!$exists_func($new)) {
                            $res = $this->_rename($new, $dest, false);
                            clearstatcache();
                            if (!empty($res['error'])) {
                                return $res;
                            }
                            break;
                        }
                        $i++;
                    } while (true);
                }
            }
        }
        return  array('error' => '');
    }

    /**
     *
     *
     * @access public
     * @param array $files List of files
     * @param string $source_path Source file folder
     * @param string $destination_path Destination file filder
     * @return bool
     */
    function _proccessFileAction($file, $destination_path,
                                   $overwrite = false, $copy = true, $check_perm = true) {

        if ($this->_fileIsThumb($file)) {
            return array('error' => '');
        }

        $id = $this->_getFileId($file);

        if (!$id) {
            return array('error' => 'File not found in database: ' . $file);
        }

        if ($check_perm && !$this->_perm->Access(0, $id, "m", "fileaccess")) {
            return array('error' => 'Access to the requested resource is denied !');
        }

        $src = Filenames::pathinfo($file);
        $src_folder = str_replace('\\', '/', $src['dirname']);
        if ($src_folder && $src_folder != '/') {
            $src_folder .= '/';
        }

        $file = $src['filename'] . '.' . $src['extension'];

        $dst = Filenames::pathinfo($destination_path . $file);
        $dst_folder = str_replace('\\', '/', $dst['dirname']);
        if ($dst_folder && $dst_folder != '/') {
            $dst_folder .= '/';
        }

        $source_file = SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
                       . "/" . $src_folder . $file;

        if (!is_file($source_file)) {
            return array('error' => 'File ' . $source_file . ' - not found in filesystem.');
        } elseif (is_file($source_file) && !is_readable($source_file)) {
            return array('error' => 'Access to file ' . $source_file . ' - denied by filesystem.');
        }

        $dest_file   = SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
                       .  $dst_folder . $file;

        if (($res = $this->autoRenameIf($dest_file))
                        && !empty($res['error'])) {
            return $res;
        }

        if (is_readable($dest_file) && !$overwrite) {

            $_owerwrite = new stdClass;
            $_owerwrite->type = 'file';
            $_owerwrite->name = $file;

            if (file_exists(SITE_PATH . "/admin/pic/ico_" . strtolower($src['extension']) . ".gif")) {
                $_owerwrite->icon = "pic/ico_".strtolower($src['extension']). ".gif";
                $_owerwrite->sicon = 'pic/icosmall_' . strtolower($src['extension']) . '.gif';
            } else {
                if ($this->_fileIsImage($dest_file)) {
                    $_owerwrite->icon = "pic/ico_image.gif";
                    $_owerwrite->sicon = 'pic/icosmall_image.gif';
                } else {
                    $_owerwrite->icon = "pic/ico_other.gif";
                    $_owerwrite->sicon = 'pic/icosmall_other.gif';
                }
            }

            $_owerwrite->dsize = filesize($dest_file);
            $_owerwrite->ddate = date ("d.m.y H:i", filemtime($dest_file));

            $_owerwrite->ssize = filesize($source_file);
            $_owerwrite->sdate = date ("d.m.y H:i", filemtime($source_file));

            return array('error' => '', 'overwrite' => $_owerwrite);
        } elseif (is_readable($dest_file) && $overwrite) {
            $res = $this->_deleteFile($dst_folder . $file, true, false);
            if (!empty($res['error'])) {
                return $res;
            }
        }

        if ($copy == true) {
            if (!@copy($source_file, $dest_file)) {
                return array('error' => 'Filesystem error copying file.');
            }
        } else {
            if (!@rename($source_file, $dest_file)) {
                return array('error' => 'Filesystem error moving file');
            }
        }

        if ($this->_fileIsImage($file)) {
            $source_thumb_file =  SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
             . "/" . $src_folder . $src['filename'] . '_thumb.' . $src['extension'];
            $destin_thumb_file =  SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
             . "/" . $dst_folder . $src['filename'] . '_thumb.' . $src['extension'];

            if ($copy == true) {
                @copy($source_thumb_file, $destin_thumb_file);
            } else {
                @rename($source_thumb_file, $destin_thumb_file);
            }
        }

        if ($copy == true) {
            $sql = "INSERT INTO `files` (`type`, `name`, `folder`, `text`, `owner`, `lastmod`) "
                . "SELECT `type`, `name`, '" . addslashes($dst_folder) . "' as `folder`, "
                . "`text`, `owner`, now() as `lastmod `FROM `files` "
                . "WHERE `name` = '" . addslashes($src['filename']) . "' "
                . "AND `type` = '" . addslashes($src['extension']) . "' "
                . "AND `folder` = '" . addslashes($src_folder) . "'";
            if ($this->_db->query($sql) === false) {
                return array('error' => 'Error updating database. SQL: [' . $sql . ']');
            }
        } else {
            $sql = "UPDATE `files` SET `folder` = '" . addslashes($dst_folder) . "' WHERE `id` = " . $id;
            if ($this->_db->query($sql) === false) {
                return array('error' => 'Error updating database. SQL: [' . $sql . ']');
            }
        }

    }

    /**
     * Delete file function
     *
     * @access private
     * @param string $destination_path Destination file filder
     * @param string $strict
     * @return array
     */
    function _deleteFile($file, $strict = false, $recycle = true) {
        $src = Filenames::pathinfo($file);
        $src_folder = str_replace('\\', '/', $src['dirname']);
        if ($src_folder && $src_folder != '/' && substr($src_folder, -1) !== '/') {
            $src_folder .= '/';
        }

        $file = $src_folder . $src['filename'] . '.' . $src['extension'];
        $full_path = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $file;

        if (false == ($fp = $this->_getFileProps($file))) {
            $id = false;
        } else {
            $id = $fp['id'];
        }

        if (!$id && $this->_fileIsThumb($file)) {
            if ($strict) {
                if (@unlink($full_path)) {
                    // save log about this action
                    $this->_log->log('file_manager', 'File '.substr($full_path, strlen(SITE_PATH))
                         . ' deleted by ' . $GLOBALS['ses']->getUsername());
                    return array('error' => '');
                } elseif (is_file($full_path)) {
                    return array('error' => 'Error deleting file from hard drive! ' . $file);
                }
            }

            return array('error' => '', 'errorcode'=>1);
        }

        if ($id) {
            if (!$this->_perm->Access(0, $id, "d", "fileaccess")) {
                return array('error' => 'Deleting ' . $file . ' is denied !');
            }

            if ($recycle) {
                if (!$fp['disabled']) {
                    $this->_db->query("UPDATE `files` SET `disabled`=1, `removed`=1 WHERE `id`=" . $id);
                }
                return array('error' => '');
            }

            if (is_file($full_path) && !is_readable($full_path)) {
                return array('error' => 'Access to file ' . $file . ' is denied from filesystem.');
            } elseif (is_readable($full_path)) {
                if(!@unlink($full_path)) {
                    return array('error' => 'Error deleting from hard drive! ' . $file);
                } else {
                    // save log about this action
                    $this->_log->log('file_manager', 'File ' . substr($full_path, strlen(SITE_PATH))
                         . ' deleted by ' . $GLOBALS['ses']->getUsername());
                }
            }

            $this->_db->query("DELETE FROM `files` WHERE `id`=" . $id);

            if ($this->_fileIsImage($file)) {
                $thumb_file = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"
                       . $src_folder . $src['filename'] . '_thumb.' . $src['extension'];

                if (is_file($thumb_file)) {
                    if(!is_readable($thumb_file)) {
                        return array('error' => 'Access to file ' . $thumb_file . ' is denied from filesystem.');
                    } else {
                        if(!@unlink($thumb_file)) {
                            return array('error' => 'Error deleting from hard drive! ' . $thumb_file);
                        }
                    }
                }
            }
        } else {
            if ($strict) {
                if (@unlink($full_path)) {
                    // save log about this action
                    $this->_log->log('file_manager', 'File '.substr($full_path, strlen(SITE_PATH))
                         . ' deleted by ' . $GLOBALS['ses']->getUsername());
                    return array('error' => '');
                } else {
                    return array('error' => 'Error deleting file from hard drive! ' . $file);
                }
            }
            return array('error' => 'File not found ' . $file, 'errorcode' => 1);
        }
        return array('error' => '');

    }


    /**
     * Apply action to many files
     *
     * @access public
     * @param array $files List of files
     * @param string $source_path Source file folder
     * @param string $destination_path Destination file filder
     * @return bool
     */
    function actionFiles($files, $source_path, $destination_path, $copy = true) {

        if (isset($this->_vars['overwritedata']) && is_array($this->_vars['overwritedata'])) {
            $overwrite      = true;
            $files  = $this->_vars['overwritedata'];
        } else {
            $overwrite      = false;
        }

        $to_overwrite   = array();
        $source_path = substr($source_path, strlen('root/upload')) . '/';
        $destination_path = substr($destination_path, strlen('root/upload')) . '/';
        foreach ($files as $file) {
            $res = $this->_proccessFileAction($source_path . $file, $destination_path, $overwrite, $copy);
            if (!empty($res['error'])) {
                return  $res;
            } else {
                if (!$overwrite && !empty($res['overwrite'])) {
                    $to_overwrite[] = $res['overwrite'];
                }
            }
        }
        return array('error' => '', 'overwrite' => $to_overwrite);
    }


    /**
     * Copy files
     *
     * @access public
     * @param array $files List of files
     * @param string $source_path Source file folder
     * @param string $destination_path Destination file filder
     * @return bool
     */
    function copyFiles($files, $source_path, $destination_path) {
        return $this->actionFiles($files, $source_path, $destination_path);
    }


    /**
     * Move files
     *
     * @access public
     * @param array $files List of files
     * @param string $source_path Source file folder
     * @param string $destination_path Destination file filder
     * @return bool
     */
    function moveFiles($files, $source_path, $destination_path) {
        return $this->actionFiles($files, $source_path, $destination_path, false);
    }



    /**
     * Delete files
     *
     * @access public
     * @param array $filenames Files to delete
     * @param bool $recycle
     * @return array
     */
    function deleteFiles($filenames) {
        foreach ($filenames as $filename) {
            $res = $this->_deleteFile($filename);
            if (!empty($res['error'])) {
                return $res;
            }
        }
        return array('error' => '');
    }

    /**
     * Create folder
     *
     * @param string $parent Parent folder name
     * @param string $name Folder name
     * @return array
     */
    function createFolder($parent, $name) {

        if (!$this->_perm->Access(0, 0, "a", "fileaccess")) {
            return array('error' => 'Creating folders is denied!');
        }

        $parent = substr($parent, strlen('root/upload'));
        $name = FileSystem::filterFolderName($name);
        if ($name == "") {
            return array('error' => 'Invalid name');
        }

        $destination = SITE_PATH . '/' . $GLOBALS['directory']['upload']
                     . $parent . '/' . $name;

        if (($res = $this->autoRenameIf($destination, false))
                        && !empty($res['error'])) {
            return $res;
        }
        return $this->_mkdir($destination);

    }


    /**
     * Empty folder
     *
     * @param string $folder Folder name
     * @return array
     */
    function emptyFolder($folder) {

        $folder = substr($folder, strlen('root/upload'));
        $res = $this->_deleteContents(SITE_PATH . "/"
                . $GLOBALS["directory"]["upload"] . $folder, false);
        if (!empty($res['error'])) {
            return $res;
        }

        return array('error' => '');
    }

    /**
     * Initializate SESSION if it's not started.
     *
     * @return null
     */
    function sessionInit()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['browser_state'])){
            $_SESSION['admin']['browser_state'] = array('view'=>0,'folder'=>'');
        }
    }

    /**
     * Save folder tree state in session
     *
     * @param string $folder Folder name
     * @return null
     */
    function saveFolderState($folder, $disabled)
    {
        $this->sessionInit();
        $_SESSION['admin']['browser_state']['folder'] = $folder;
        $_SESSION['admin']['browser_state']['showdisabled'] = $disabled;
    }

    /**
     * Get folder tree state from session
     *
     * @return string - folder path
     */
    function getFolderState()
    {
        $this->sessionInit();
        return $_SESSION['admin']['browser_state']['folder'];
    }


    /**
     * Save disabled state in session
     *
     * @param int $disabled
     * @return null
     */
    function saveDisabledState($disabled)
    {
        $this->sessionInit();
        $_SESSION['admin']['browser_state']['showdisabled'] = $disabled;
    }

    /**
     * Get disabled state from session
     *
     * @return int
     */
    function getDisabledState()
    {
        $this->sessionInit();
        return $_SESSION['admin']['browser_state']['showdisabled'];
    }


    /**
     * Save grid view state in session
     *
     * @param string $panel Panel index
     * @return null
     */
    function saveViewState($panel)
    {
        $this->sessionInit();
        $_SESSION['admin']['browser_state']['view'] = $panel;
    }

    /**
     * Get grid view state from session
     *
     * @return string Panel index
     */
    function getViewState()
    {
        $this->sessionInit();
        return $_SESSION['admin']['browser_state']['view'];
    }

    /**
     * Delete folder with contents
     *
     * @param string $folder Folder name
     * @return array
     */
    function deleteFolder($folder)
    {
        $folder = substr($folder, strlen('root/upload'));
        $res = $this->_deleteContents(SITE_PATH . "/"
                    . $GLOBALS["directory"]["upload"] . $folder, true);
        if (!empty($res['error'])) {
            return $res;
        }
        return array('error' => '');
    }


    /**
     * Delete folder with contents
     *
     * @return array
     */
    function emptyRecycle() {
        if (!$this->_perm->Access(0, 0, "d", "fileaccess")) {
            return array('error' => 'Action is denied !');
        }

        $sql = "SELECT `name`,  `type`,  `folder` FROM `files` WHERE `removed`=1";
        $res =& $this->_db->query($sql);
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $fn = $row['folder'] . $row['name'] . "." . $row['type'];
                if($_res = $this->_deleteFile($fn, true, false) && !empty($_res['error'])) {
                    return $_res;
                }
            }
        }

        $sql = "SELECT `name`,   `folder` FROM `folders` WHERE `removed`=1";
        $res =& $this->_db->query($sql);
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $fn = SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
                 . $row['folder'] . $row['name'];
                if($_res = $this->_deleteContents($fn, true, false) && !empty($_res['error'])) {
                    return $_res;
                }
            }
        }

        return array('error' => '');
    }

    /**
     * Create thumbnails
     *
     * @param string $folder Folder name
     * @return array
     */
    function createThumbnails($folder)
    {

        if (!$this->_perm->Access(0, 0, "m", "fileaccess")) {
            return array('error' => 'Action is denied!');
        }

        $folder = substr($folder, strlen('root/upload')) . '/';
        $this->_generateThumbnails(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $folder);
        return array('error' => '');
    }


    /**
     * Rename function wrapper
     *
     * @param string $newpath
     * @param string $path path
     * @param bool $check_perm
     */
    function _rename($new_path, $path, $check_perm = true) {
        $upload_len = strlen(SITE_PATH . "/upload");

        if (is_file($path)) {

            $dst = Filenames::pathinfo($new_path);

            if ($new_path == $path) {
                 return array('error' => '', 'value'=> $dst['filename']);
            }


            if (is_file($new_path)) {
                return array('error' => 'File with same name already exists. ' . $new_path);
            }

            $file_id   = $this->_getFileId(substr($path, $upload_len));

            if ($check_perm && !$this->_perm->Access(0, $file_id, "m", "fileaccess")) {
                return array('error' => 'Rename file action is denied!');
            }

            $src = Filenames::pathinfo($path);
            $src_folder = str_replace('\\', '/', $src['dirname']);

            if ($src['dirname'] != $dst['dirname']
             || $src['filename'] == $dst['filename']
             || $src['extension'] != $dst['extension']) {
                return array('error' => 'Error renaming file!');
            }

            if (!rename($path, $new_path)) {
                return array('error' => 'Error renaming file');
            } else {


                if ($this->_fileIsImage($new_path)) {
                    @rename($src_folder .'/'. $src['filename'] . '_thumb.' . $src['extension']
                            , $src_folder .'/'. $dst['filename'] . '_thumb.' . $src['extension']);
                }

                // save log about this action
                $this->_log->log('file_manager', 'File '. $path .' renamed to ' . $new_path
                        . ' by ' . $GLOBALS['ses']->getUsername());
                $sql = "UPDATE `files` SET `name` = " . $this->_db->quote($dst['filename'])
                     . " WHERE `folder` = " . $this->_db->quote(substr($src_folder, $upload_len).'/')
                     . " AND `type` = " . $this->_db->quote($src['extension'])
                     . " AND `name` = " . $this->_db->quote($src['filename']);
                if (!$this->_db->query($sql)) {
                    return array('error' => 'Error updating database['.$sql.']');
                }
            }

            return array('error' => '', 'obj'=>$dst['filename'], 'type'=>$src['extension'], 'folder'=>substr($src_folder, $upload_len).'/');
        }

        if (!is_dir($path)) {
            return array('error' => 'Directory not found on hard drive! ' . $path);
        }

        if ('/' == substr($path, -1)) {
            $path = substr($path, 0, -1);
        }

        if ('/' == substr($new_path, -1)) {
            $new_path = substr($new_path, 0, -1);
        }

        $folder_id   = $this->_getFolderId($path);
        if ($check_perm && !$this->_perm->Access(0, $folder_id, "m", "folderaccess")) {
            return array('error' => 'Rename directory action is denied!');
        }

        if (is_dir($new_path)) {
            return array('error' => 'Directory already exists. ' . $new_path);
        }

        if (!rename($path, $new_path)) {
            return array('error' => 'Error renaming folder');
        } else {
            $path = substr($path, $upload_len);
            $new_path = substr($new_path, $upload_len);
            $path_substr = strlen($path) + 1;
            // save log about this action
            $this->_log->log('file_manager', 'Folder '. $path .' renamed to ' . $new_path
                    . ' by ' . $GLOBALS['ses']->getUsername());
            $sql = "UPDATE `files` SET `folder` = CONCAT(" . $this->_db->quote($new_path)
                 . ", SUBSTRING(`folder`, " . $path_substr . ")) WHERE `folder` LIKE "
                  . $this->_db->quote($path . '/%');
            if (!$this->_db->query($sql)) {
                return array('error' => 'Error updating database['.$sql.']');
            }

            $_name   = $this->_db->quote(substr($new_path, strrpos($new_path, '/') + 1));
            $_folder   = $this->_db->quote(substr($new_path, 0, strrpos($new_path, '/') + 1));
            $_oldname   = $this->_db->quote(substr($path, strrpos($path, '/') + 1));
            $_oldfolder   = $this->_db->quote(substr($path, 0, strrpos($path, '/') + 1));
            $sql = "UPDATE `folders` SET `name`=$_name, `folder`=$_folder WHERE `name`=$_oldname AND `folder` ="
                     . $_oldfolder;
            if (!$this->_db->query($sql)) {
                return array('error' => 'Error updating database['.$sql.']');
            }

            $sql = "UPDATE `folders` SET `folder` = CONCAT(" . $this->_db->quote($new_path)
                 . ", SUBSTRING(`folder`, " . $path_substr . ")) WHERE `folder` LIKE "
                  . $this->_db->quote($path . '/%');


            if (!$this->_db->query($sql)) {
                return array('error' => 'Error updating database['.$sql.']');
            } else {
                return array('error' => '');
            }
        }
    }


    /**
     * Rename current folder
     *
     * @param string $newValue new folder name
     * @param string $path path
     * @todo change folder path renaming method. Remove this str_replace.
     */
    function renameFolder($newValue, $path) {

        $path = str_replace('root/upload', '', $path);
        $newValue = FileSystem::filterFolderName($newValue);

        if ($newValue == "") {
            return array('error' => 'Invalid name');
        }

        $path = SITE_PATH . "/upload" . $path;
        $new_path = substr($path, 0, (strrpos($path, '/') + 1)) . $newValue;

        if (($res = $this->autoRenameIf($new_path, false))
                        && !empty($res['error'])) {
            return $res;
        }

        return $this->_rename($new_path, $path);

    }

    /**
     * Rename current file
     *
     * @param string $newValue new file name
     * @param string $path path
     */
    function renameFile($newValue, $path) {

        $newValue = preg_replace('/ /', '_', $newValue);
        $newValue = ereg_replace("[^[:space:]a-zA-Z0-9*_.-]", "", $newValue);
        if ($newValue == "") {
            return array('error' => 'Invalid name');
        }

        $dst = Filenames::pathinfo($path);

        $path = SITE_PATH . "/upload" . $path;
        $new_path = substr($path, 0, (strrpos($path, '/') + 1))
            . $newValue . '.' . $dst['extension'];

        if (($res = $this->autoRenameIf($new_path, true))
                        && !empty($res['error'])) {
            return $res;
        }

        if (($res = $this->_rename($new_path, $path))
                        && !empty($res['error'])) {
            return $res;
        }

        $type = $res['type'];
        $obj  = $res['obj'];
        $folder  = $res['folder'];
        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];
        $view_dir   = SITE_URL . "/" . $GLOBALS["directory"]["upload"];
        $res['view_url'] = $view_dir . $folder . $obj . "." . $type;
        if ($type == "gif" || $type == "jpg" || $type == "png" || $type == "tif") {
            $thumbname = $folder . $obj . "_thumb." . $type;
            if (file_exists($upload_dir . $thumbname)) {
                $res['thumb_url'] = $res['icon_big'] = $view_dir . $thumbname;
            }
        }
        return $res;

    }



    /**
     * Change Description
     *
     * @param string $newValue new description
     * @param string $path path
     */
    function changeDescription($newValue, $path) {

        $id = $this->_getFileId($path);

        $sql = array();
        if ($id) {
            if (!$this->_perm->Access(0, $id, "m", "fileaccess")) {
                return array('error' => 'Action is denied!');
            }

            $desc = substr(trim($newValue), 0, 255);
            $sql = "UPDATE `files` SET `text` = "
             . $this->_db->quote($desc) . " WHERE `id`=" . $id;

            if (!$this->_db->query($sql)) {
                return array('error'=>'Error updating database['.$sql.']');
            } else {
                return array('result'=>array(array('edited'=>true,
                                                             'value'=>$desc)));
            }
        } else {
            return array('error'=>'File not found in database');
        }

    }

    /**
     * Check is user in admin group
     * @return array
     */
    function isUserAdminGroup() {
        $user = $this->_ses->returnUser();
        if ($this->_ses->group == 1) {
            return array('error' => '');
        } else {
            return array('error' => 'You can\'t acces Recycle bin');
        }
    }

    /**
     *
     * @return array
     */
    function getUsers() {
        $users = array();
        $sql = "SELECT `user`, `username` FROM `adm_user`";
        $res =& $this->_db->query($sql);
        if (false !== $res) {
            while ($row = $res->fetch_assoc()) {
                $users[] = array('user'=>$row['user'], 'username'=>$row['username']);
            }
        }
        return array('results'=>$users);
    }


    /**
     * Validate if permission to modify is enabled
     *
     * @return bool
     */
    function hasPermAccess() {
        return $this->_perm->Access(0, 0, "m", "fileaccess");
    }

    /**
     * Update properties of folders or file
     *
     * @param string $file
     * @param string $folder
     * @param string $newowner
     * @param string $type
     * @return array
     */
    function saveProperties($file, $folder, $newowner, $type) {

        if (empty($newowner)) {
            return array('error'=>'Permission field is required!');
        }
        if ($type == 'folder') {
            $table = 'folders';
            $id = $this->_getFolderId(SITE_PATH . "/"
                       . $GLOBALS["directory"]["upload"] . substr($folder, strlen('root/upload')));
        } else{
            $table = 'files';
            $id = $this->_getFileId($folder . $file);
        }

        if ($id) {
            if (!$this->hasPermAccess()) {
                return array('error'=>'Action is denied !');
            }
            $sql = "UPDATE `" . $table . "` SET `owner`=" . $this->_db->quote($newowner)
                 . " WHERE `id`=" . $id;
            if (!$this->_db->query($sql)) {
                return array('error'=>'Error updating database['.$sql.']');
            } else {
                return array('success'=>true);
            }
        } else {
            return array('error'=>'Not found source!');
        }
    }



    function moveFolderToRecycle($source) {
        $folder = substr($source, strlen('root/upload'));
        if (substr($folder,-1) == '/') {
            $folder = substr($folder,0,strlen($folder)-1);
        }

        $fp = $this->_getFolderProps(SITE_PATH
                   . "/" . $GLOBALS["directory"]["upload"] . $folder);

        if (!$fp['id']) {
            return array('error' => 'Folder not found in database!');
        }

        if ($fp['removed']==1 || $fp['disabled'] == 1) {
            return array('error' => 'Folder already in Recycle bin!');
        }

        if (!$this->_perm->Access(0, $fp['id'], "d", "folderaccess")) {
            return array('error' => 'Delete directory action is denied!');
        }

        $sql = "UPDATE `folders` SET `disabled`=1 WHERE `disabled`=0 AND `removed`=0" .
                    " AND `folder` LIKE " . $this->_db->quote($folder . '/%');
        $this->_db->query($sql);

        $sql = "UPDATE `files` SET `disabled`=1 WHERE `disabled`=0 AND `folder` LIKE "
                  . $this->_db->quote($folder . '/%');
        $this->_db->query($sql);

        $sql = "UPDATE `folders` SET `disabled`=1, `removed`=1 WHERE `id`=" . $fp['id'];
        $this->_db->query($sql);

        return array('error' => '');
    }



    function moveFolderContentToRecycle($source) {
        $folder = substr($source, strlen('root/upload'));
        if (substr($folder,-1) != '/') {
            $folder .= '/';
        }

        $sql = 'SELECT `name` FROM `folders` WHERE `folder`='
                . $this->_db->quote($folder) . ' AND `disabled`=0';

        if (false !== ($res =& $this->_db->query($sql))) {
            while ($row = $res->fetch_assoc()) {
                $_res = $this->moveFolderToRecycle('root/upload' . $folder . $row['name']);
                if (!empty($_res['error'])) {
                    return $_res;
                }
            }
        }

        $sql = 'SELECT `name`, `type`, `folder` FROM `files` WHERE `folder`='
                . $this->_db->quote($folder) . ' AND `disabled`=0';

        if (false !== ($res =& $this->_db->query($sql))) {
            while ($row = $res->fetch_assoc()) {
                $_res = $this->_deleteFile($row['folder'] . $row['name'] . "." . $row['type']);
                if (!empty($_res['error'])) {
                    return $_res;
                }
            }
        }

        return array('error' => '');
    }


    function restoreFolder($destination, $strict) {
        $destination = substr($destination, strlen('root/upload'));

        if ('/' == substr($destination, -1)) {
            $destination = substr($destination, 0, -1);
        }

        $upload_dir  = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];
        $fp = $this->_getFolderProps($upload_dir . $destination);

        if ($fp) {
            $res =  $this->_restoreParents($destination, $strict);
            if (!empty($res['error']) || !empty($res['parents'])) {
                return $res;
            }
            $this->_restoreChilds($destination .'/');
            return array('error' => '');
        } else {
            return array('error' => 'Folder not found! ' . $upload_dir . $destination);
        }
    }


    function _restoreChilds($destination) {

        if ('/' != substr($destination, -1)) {
            $destination = $destination . '/';
        }

        $sql = 'SELECT `name` FROM `folders` WHERE `folder`='
                . $this->_db->quote($destination) . ' AND `removed`=0 AND `disabled`=1';

        if (false !== ($res =& $this->_db->query($sql))) {
            while ($row = $res->fetch_assoc()) {
                $this->_restoreChilds($destination . $row['name'] . '/');
            }
        }

        $sql = "UPDATE `files` SET `removed`=0, `disabled`=0 WHERE `removed`=0 " .
                    " AND `disabled`=1 AND `folder`=". $this->_db->quote($destination);
        $this->_db->query($sql);

        $sql = "UPDATE `folders` SET `removed`=0, `disabled`=0 WHERE `removed`=0 " .
                    " AND `disabled`=1 AND `folder`=". $this->_db->quote($destination);
        $this->_db->query($sql);
    }



    function _restoreParents($destination, $strict = false) {

        if ('/' == substr($destination, -1)) {
            $destination = substr($destination, 0, -1);
        }

        if ($destination == '' || $destination == '/') {
            return array('error' => '', 'parents' => 0);
        }

        $parent_dest = substr($destination, 0, strrpos($destination, '/')+1);
        $name = substr($destination, strrpos($destination, '/')+1);
        $upload_dir  = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];

        $fp = $this->_getFolderProps($upload_dir . $parent_dest);

        if ('/' == $parent_dest || $fp) {
            if ('/' == $parent_dest || $fp['disabled'] == 0) {
                $sql = "UPDATE `folders` SET `removed`=0, `disabled`=0 WHERE " .
                           " `name`=" . $this->_db->quote($name) . " AND `folder`="
                           . $this->_db->quote($parent_dest);
                $this->_db->query($sql);
                return array('error' => '', 'parents' => 0);
            } else {
                if ($fp['disabled'] == 1 && !$strict) {
                    return array('error' => '', 'parents' => 1);
                }

                $sql = "UPDATE `folders` SET `removed`=0, `disabled`=0 WHERE " .
                           " `name`=" . $this->_db->quote($name) . " AND `folder`="
                           . $this->_db->quote($parent_dest);
                $this->_db->query($sql);

                $sql = "UPDATE `folders` SET `removed`=1 WHERE `disabled`=1 AND `removed`=0 " .
                           " AND `name`!=" . $this->_db->quote($name) . " AND `folder`="
                           . $this->_db->quote($parent_dest);
                $this->_db->query($sql);

                $sql = "UPDATE `files` SET `removed`=1 WHERE `disabled`=1 AND `removed`=0 " .
                           " AND `folder`=" . $this->_db->quote($parent_dest);
                $this->_db->query($sql);
                return $this->_restoreParents($parent_dest, $strict);
            }
        } else {
            return array('error' => 'Folder not found! ' . $upload_dir . $parent_dest, 'parents' => 0);
        }

    }


    /**
     * Restore passed files
     *
     * @param array $files
     * @return array
     */
    function restoreFiles($files, $strict) {
        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];
        for($i = 0; $i < count($files); $i++) {
            $fp = $this->_getFileProps($files[$i]);
            if ($fp && $fp['disabled'] == 1) {
                $parent_folder = substr($files[$i], 0, strrpos($files[$i], '/') + 1);
                if ($parent_folder != '/') {
                    $fop = $this->_getFolderProps($upload_dir . $parent_folder);
                    if ($fop) {
                        if ($fop['disabled'] == 1) {
                            if (!$strict) {
                                return array('error' => '', 'parents' => 1);
                            } else {

                                $sql = "UPDATE `folders` SET `removed`=1 "
                                 . " WHERE `disabled`=1 AND `removed`=0 AND `folder`="
                                 . $this->_db->quote($parent_folder);
                                $this->_db->query($sql);

                                $sql = "UPDATE `files` SET `removed`=1 "
                                 . " WHERE `disabled`=1 AND `removed`=0 AND `folder`="
                                 . $this->_db->quote($parent_folder) . " AND `id`!=" . $fp['id'];
                                $this->_db->query($sql);

                                $res = $this->_restoreParents($parent_folder, true);
                                if (!empty($res['error'])) {
                                    return $res;
                                }
                            }
                        }
                    } else {
                        return array('error' => 'Parent folder not found! ' . $parent_folder);
                    }
                }
                $sql = "UPDATE `files` SET `removed`=0, `disabled`=0 WHERE `id`=" . $fp['id'];
                $this->_db->query($sql);
            }
        }
        return array('error' => '');
    }


    /**
     * Refresh folder
     *
     * @param string $folder Folder name
     * @return array
     */
    function refresh($folder) {
        if (!empty($folder)) {
            $folder = substr($folder, strlen('root/upload'));
        }
        return $this->_refreshStructure(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $folder);
    }

    /**
     * refreshStructure
     *
     * @access private
     * @param string $source Source path
     * @return array - result
     */
    function _refreshStructure($source = null, $disabled = null) {

        $upload_dir = SITE_PATH . "/" . $GLOBALS["directory"]["upload"];

        if (!$source || $upload_dir == $source) {
            $source = $upload_dir;
            $db_folder = '';
            $disabled = 0;
        } else {
            if ('/' == substr($source, -1)) {
                $source = substr($source, 0, -1);
            }
            $db_folder = substr($source, strlen(SITE_PATH . "/" . $GLOBALS["directory"]["upload"]));
        }

        if ($disabled === null) {

            $fp = $this->_getFolderProps($source);

            if (!$fp) {
               return array('error' => 'Folder not found ');
            }

            $disabled = $fp['disabled'];
        }


        if (!$dir = @opendir($source)){
            return;
        }
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                $link = $db_folder . '/' . $file;
                if (is_dir($source . '/' . $file)) {
                    if ($file == '.svn') {
                         continue;
                    }

                    //echo $source . '/' . $file ."\n";
                    if (!$this->_getFolderId($source . '/' . $file)) {
                        $sql   = "INSERT INTO `folders` (`name`, `folder`, `owner`, `removed`, `disabled`) "
                                 . "VALUES (".$this->_db->quote($file).", ".$this->_db->quote($db_folder . '/').", 0, 0, " . $disabled . ")";

                        if (!$this->_db->query($sql)) {
                            return array('error' => 'Error updating database['.$sql.']');
                        }
                    }
                    $res = $this->_refreshStructure($source . '/' . $file);
                    if (!empty($res['error'])) {
                        return $res;
                    }

                } else {
                    if ($file == 'file.php' || $file == '.htaccess'
                     || $file == "SITELOGO.gif" || $file == "SITELOGO.jpg") {
                         continue;
                     }
                    if (!$this->_getFileId($link) && !$this->_fileIsThumb($link)) {

                        $src = Filenames::pathinfo($link);

                        $sql = "INSERT INTO `files` (`type`, `name`, `folder`, `text`, `owner`, `lastmod`, `removed`, `disabled`) "
                            .  "VALUES ("
                            . $this->_db->quote($src['extension']) . ", "
                            . $this->_db->quote($src['filename']) . ", "
                            . $this->_db->quote($db_folder . '/') . ", '', 0, NOW(), 0, " . $disabled . ")";

                        if (!$this->_db->query($sql)) {
                            return array('error' => 'Error updating database['.$sql.']');
                        }
                    }
                }
            }
        }

        closedir($dir);
        return array('error' => '');
    }

}