<?php
/*
    last modified 23.03.06 (martin)
    version 1.2

    <TPL_OBJECT:filemanager>
      <TPL_OBJECT_OUTPUT:show()>
    </TPL_OBJECT:filemanager>

    <TPL_OBJECT:filemanager>
      <TPL_OBJECT_OUTPUT:folders()>
    </TPL_OBJECT:filemanager>

    upload/ -> protected folder (all users)
    upload/myfolder/1 -> user's private folder
    upload/myfolder/2
*/

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");

class filemanager {

var $sid = false;
var $status = false;
var $user = false;
var $siteroot = false; // site name for the session cookie
var $dbc = false;
var $language = false;
var $tmpl = false;
var $content_module = true;
var $module_param = array();
var $userid = false;
var $username = false;
var $groupid = false;
var $groupids = false;
var $folder = false;
var $maxresults = 50;
var $start_folder = false;
var $start_folder1 = false;
var $start_url = false;
var $mode = "list";
var $myfolder = "myfolder";
var $protected = array();
var $cachelevel = TPL_CACHE_NOTHING;
var $cachetime = 1440; //cache time in minutes
var $project = false;
var $task = false;
var $messages = false;
var $icon_max = 3; // how many cells in one row in icon-mode
var $default_file_r = "1";
var $default_file_w = "0";
var $default_file_d = "0";
var $allowed_symbols = "_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    function filemanager() {
        global $db, $language;

        $this->siteroot = $GLOBALS["cookie_url"];
        $this->language = $language;
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->vars = array_merge($_GET, $_POST);

        if (is_object($db)) $this->dbc = $db->con;
        else {
            $db = new DB;
            $this->dbc = $db->connect();
        }

        $this->userid = $GLOBALS["user_data"][0];
        $this->username = $GLOBALS["user_data"][2];
        $this->protected = Array("contacts",$this->myfolder);
        $this->groupid = $GLOBALS["user_data"][4];
        $this->groups = $GLOBALS["user_data"][5];
        $this->messages = array();

        if ($this->content_module == true) {
            $this->getParameters();
        }
    }

// ########################################

    // Main function to call

    function show() {
        $faction = @$this->vars["faction"];
        $file_id = @$this->vars["file_id"];
        $folder = @$this->vars["folder"];

        if ($this->checkAccess() == false) return "";

        if (!$this->userid) {
            trigger_error("Module 'filemanager' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }
        if ($faction == "add") {
            $result = $this->addFile(false,$faction);
        }
        else if ($faction == "prepare_upload") {
            $result = $this->prepareUpload();
        }
        else if ($faction == "finish_upload") {
            $result = $this->finishUpload();
        }
        else if ($faction == "savefile") {
            $result = $this->saveFile();
        }
        else if ($file_id && $faction == "modify") {
            $result = $this->addFile($file_id,$faction);
        }
        else if ($file_id && $faction == "delete") {
            $result = $this->deleteFile($file_id, $folder, true);
        }
        else if ($faction == "diradd") {
            $result = $this->addFolder($faction);
        }
        else if ($faction == "dirmod") {
            $result = $this->addFolder($faction);
        }
        else if ($faction == "dirdel") {
            $result = $this->deleteFolder($faction);
        }
//        else if (!$folder) {
//            $result = $this->lastAdded(10, "module_filemanager_last_added.html");
//      }
        else {
            $result = $this->showFilelist();
        }
        return $result;
    }

// ########################################

    // Show list of files

    function showFilelist() {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $folder = @$this->vars["folder"];

        $prj = new projects;
        $this->project = $prj->project;
        $this->task = $prj->task;

        //project check
        //if (!$this->project) return ""; //doJump("");
        if ($this->project) {
            if ( is_array($prj->project_people[$this->project])) {
                if (!in_array($this->userid, $prj->project_people[$this->project])) doJump("");
            }
            else{
                doJump("");
            }
            /* = $this->folder*/ $folder = "/projects" . $this->project . "/";
            if ($this->task) {
                $folder .= "tasks" . $this->task . "/";
            }
            if ($folder == "myfolder") $folder = "";
        }

        if (!$folder) {
            $folder = $this->module_param["filemanager"];
        }

        if ($folder != "myfolder") {
            if (substr($folder, 0, 1) != "/") {
                 $folder = "/" . $folder;
            }
            if (substr($folder, -1) != "/") {
                $folder .= "/";
            }
            $folder = str_replace("//", "/", $folder);
        }

        if (ereg("\.", $folder)) {
            doJump("");
        }
        // Check access to my folder
        if ($folder != "myfolder" && eregi($this->myfolder, $folder) && $folder != $this->myfolder . "/" . $this->username) {
            redirect(processQuery($_SERVER["QUERY_STRING"], "", array("folder","errmess")));
        }

        if (!$start) {
            $start = 0;
        }
        if ($this->vars["mode"] != "") {
            $this->mode = $this->vars["mode"];
        }

        $general_url = processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "project=".$this->project . "&task=".$this->task . "&folder=" . $folder, array("project", "task", "folder", "mode", "file_id", "faction", "errmess"));
        if (substr($general_url, -1) == "#") $general_url = substr($general_url, 0, -1) . "?";

        $types = array(0 => "all", 1 => "all", 2 => "pic", 3 => "nopic", 4 => "doc", 5 => "pdf", 6 => "xls", 7 => "zip", 8 => "dwg", 9 => "cad", 10 => "eml", 11 => "txt", 12 => "avi", 13 => "ppt");
        if (!isset($this->vars["filter_type"])) $this->vars["filter_type"] = 1;

        $sq = new sql;

        $txt = new Text($this->language, "module_filemanager");

        // Check folders
        $this->checkFolder($this->myfolder);
        $this->checkFolder($this->myfolder . "/" . $this->username);
        if ($this->project) {
            $this->checkFolder("projects" . $this->project);
            if ($this->task) {
                $this->checkFolder("projects" . $this->project . "/tasks" . $this->task);
            }
        }
    
        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_filemanager_".$this->mode.".html";
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=filemanager&mode=".$this->mode."&folder=".$folder);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "filemanager";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module filemanager cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // showing error messages if neccessary
        if ($this->vars["errmess"] == "dirdelerr") $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("dirdel_error"));
        if ($this->vars["errmess"] == "dirdelperm") $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("dirdel_error_permissions"));
        if ($this->vars["errmess"] == "dirdelok") $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("dirdel_ok"));
        if ($this->vars["errmess"] == "foldwriperm") $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("write_error_folder"));

        // Prepare folders
        $begin_folder = $this->module_param["filemanager"];
        $this->start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;
        $this->start_folder1 = $GLOBALS["directory"]["upload"] . "/" . $this->folder;
        $this->start_url = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;

        // if there was copy, move or delete checked, then doing these file operations first
        if ($this->vars["copy_checked"]) {
            $this->copyFileChecked($this->vars["folder_checked"], $this->vars["check_file"], false);
        }
        if ($this->vars["move_checked"]) {
            $this->copyFileChecked($this->vars["folder_checked"], $this->vars["check_file"], true);
        }
        if ($this->vars["delete_checked"]) {
            $this->deleteFileChecked($this->vars["check_file"]);
        }

        // file descriptions from database
        if ($folder) {
            if ($this->project) {
                $fld = "folder LIKE '";
            } else {
                $fld = "folder = '";
            }
            /*
            if (substr($folder, -1) == "/") {
                $fld .= addslashes(substr($folder, 0, -1));
            } else {
                $fld .= addslashes($folder);
            }
            */
            $fld .= addslashes($folder);
            if ($this->project) {
                $fld .= '%';
            }
            $fld .= "'";
        }

        if ($folder == "myfolder") {
            $fld = "folder = '".$this->myfolder . "/" . $this->username."'";
            $myfolder = true;
        }

        // 22/01/2006, Martin: new filter option: checking if all_folers checkbox was in
        // if so then ignoring folder filter and searching from all folders
        if ($this->vars["filter_all_folders"]) {
            $fld = "";
        }

        // creating filter for query
        $qfilter = "";
        if (!$fld) {
            $qfilter = " 1 = 1";
        }
        if ($this->vars["filter_type_id"]) {
            $qfilter .= " AND files.type_id = '" . $this->vars["filter_type_id"] . "' ";
        }
        if ($this->vars["filter_category"]) {
            $qfilter .= " AND files.cat_list LIKE '%," . $this->vars["filter_category"] . ",%' ";
        }
        if ($this->vars["filter_text"]) {
            $qfilter .= " AND files.text LIKE '%" . $this->vars["filter_text"] . "%' ";
        }
        if ($this->vars["filter_keywords"]) {
            $qfilter .= " AND files.keywords LIKE '%" . $this->vars["filter_keywords"] . "%' ";
        }
        if ($begin_folder && !$this->project) {
            $qfilter .= " AND files.folder LIKE '" . addslashes($begin_folder) . "%'";
        }

        $files = array();
        $sql = "SELECT files.id, CONCAT(files.name, \".\", files.type) as file, files.text, files.folder, files.permissions, files.owner, files.add_date, files.lastmod, IF(module_user_users.user, CONCAT(module_user_users.name_first, ' ', module_user_users.name_last), '') AS owner_name FROM files LEFT JOIN module_user_users ON files.owner = CONCAT('99', module_user_users.user) WHERE $fld $qfilter ORDER BY file DESC";
        //echo "<!-- $sql -->\n";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $perm = $this->decodePermissions($data["permissions"]);

            if ($this->checkFilter($data["file"], $this->vars["filter_file"]) &&
                $this->checkType($data["file"], $types[$this->vars["filter_type"]]) &&
                ($this->folderAllowed($data["folder"]) && $this->hasPermissions($perm, "r") || ($data["owner"] == "99" . $this->userid)) || $this->project) {
                $desc[$data["id"]] = array(
                    $data["text"],
                    $data["folder"],
                    $this->hasPermissions($perm, "r"),
                    $this->hasPermissions($perm, "w"),
                    $this->hasPermissions($perm, "d"),
                    $data["add_date"],
                    $data["owner"],
                    $data["owner_name"],
                    $data["lastmod"]
                 );
                $files[$data["id"]] = $data["file"];
            }
        }

        // #######################

        // Get file list
/*
        if ($folder == "myfolder") {
            $opendir = $this->start_folder . $this->myfolder . "/" . $this->username . "/";
        } else {
            $opendir = $this->start_folder . addslashes($folder);
        }
*/
//        asort($files);
        reset($files);

        $item_count = 0;
        // Parse the files
        while (list($file_id, $file_name) = each($files)) {
            $obj = $this->getName($file_name);
            $text = $desc[$file_id][0];
            $type = $this->getTyp($file_name);
            $id = $file_id;
            $opendir = $this->start_folder . $desc[$file_id][1];
            // only showing filest that really exist
            if (file_exists($opendir . $file_name)) {
                $date = date ("d.m.Y", @filemtime($opendir . $file_name));
                $file_folder = $desc[$file_id][1];

                $ar = array();
                $ar = $this->doFile($id, $obj, $text, $type, $date, $file_folder, $this->mode);

                $f_name = substr($ar[1], 0, strrpos($ar[1], '.'));
                $f_type = substr($ar[1], strrpos($ar[1], '.') + 1);
                if (strlen($f_name) > 16) $ar[1] = substr($f_name,0,16)."_.".$f_type;

                if ($style == "even") {
                    $style = "";
                } else {
                    $style = "even";
                }

                if ($this->mode == "list" || $this->mode == "icon" && ($item_count % $this->icon_max == 0)) {
                    $tpl->addDataItem("ROWS.DUMMY", " ");
                }

                $tpl->addDataItem("ROWS.DATA.STYLE", $style);
    //            $tpl->addDataItem("DATA.ICON", $ar[0]);
                $tpl->addDataItem("ROWS.DATA.ICON", str_replace("//", "/", str_replace("://", ":///", $ar[0])));

    //          $tpl->addDataItem("DATA.URL", $ar[5]);
                $tpl->addDataItem("ROWS.DATA.URL", str_replace("//", "/", str_replace("://", ":///", $ar[5])));
                if ($desc[$file_id][4] || $myfolder || $desc[$file_id][6] == "99" . $this->userid) {
                    $tpl->addDataItem("ROWS.DATA.DELETE.URL_DELETE", $ar[6]);
                }
                if ($desc[$file_id][3] || $myfolder || $desc[$file_id][6] == "99" . $this->userid) {
                    $tpl->addDataItem("ROWS.DATA.MODIFY.URL_MODIFY", $ar[7]);
                }
                $tpl->addDataItem("ROWS.DATA.NAME", $ar[1] . ($desc[$file_id][7] ? (" / " . $desc[$file_id][7]) : ""));
                $tpl->addDataItem("ROWS.DATA.TEXT", $ar[2]);
                $tpl->addDataItem("ROWS.DATA.SIZE", $ar[3]);
                $tpl->addDataItem("ROWS.DATA.LASTMOD", date("d.m.Y", strtotime($desc[$file_id][8])));
                $tpl->addDataItem("ROWS.DATA.ADD_DATE", date("d.m.Y", strtotime($desc[$file_id][5])));
                $tpl->addDataItem("ROWS.DATA.ID", $id);
                $item_count++;
            }
        }

        // in icon mode we'll have to finish the row with empty data if needed
        if ($this->mode == "icon" && $item_count && ($item_count % $this->icon_max != 0)) {
            while ($item_count % $this->icon_max != 0) {
                $tpl->addDataItem("ROWS.DATA.URL", "");
                $tpl->addDataItem("ROWS.DATA.NAME", "");
                $tpl->addDataItem("ROWS.DATA.ICON", "");
                $item_count++;
            }
        }

        // ####
        // Folder tree select

        $fields = array(
            "folder_checked" => array("select",0,0,$this->vars["folder"])
        );

        $list = array();
        $list[""] = "-";
        // 14/03/2006, Martin: checking if private folder should be shown
        if ($this->module_param["filemanager2"]) {
            $list["myfolder"] = $txt->display("myfolder");
        }
        // 18/03/2006, Martin: also in folder-tree selectboxes check if tree was restricted from admin-environment
        if ($this->module_param["filemanager1"]) {
            $parent_name = substr($this->module_param["filemanager"], strrpos($this->module_param["filemanager"], "/"));
            if (substr($parent_name, 0, 1) == "/") {
                $parent_name = substr($parent_name, 1);
            }
            $list[$this->module_param["filemanager"]] = $parent_name;
            $list = $this->parseFolder($this->module_param["filemanager"], $list);
        } else {
            $list = $this->parseFolder("", $list);
        }
        $fields["folder_checked"][4] = $list;

        while (list($key, $val) = each($fields)) {
            if (sizeof($val) > 0) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val["5"];

                $f = new AdminFields("$key",$fdata);
                $field_data = $f->display($val[3]);
                $tpl->addDataItem("FIELD_$key", $field_data);
                unset($fdata);
            }
        }

        // ####
        // Filter fields
        $fdata["type"] = "select";
        $list = array();
        for ($u = 1; $u < 14; $u++) {
            $list[$u] = $txt->display("type".$u);
        }
        $fdata["list"] = $list;
        $f = new AdminFields("filter_type",$fdata);
        $field_data = $f->display($this->vars["filter_type"]);
        $tpl->addDataItem("FIELD_filter_type", $field_data);

        $fdata["type"] = "textinput";
        $fdata["size"] = "20";
        $fdata["class"] = "i120";
        $f = new AdminFields("filter_file",$fdata);
        $field_data = $f->display($this->vars["filter_file"]);
        $tpl->addDataItem("FIELD_filter_file", $field_data);

        // projects
        if ($folder == "myfolder" && !$this->project) {
            $tpl->addDataItem("FIELD_filter_project", "-");
        }
        else {
            $project_list = array("" => "---");
            while (list($key, $val) = each($prj->project_people)) {
                if (in_array($this->userid, $val)) {
                    //if (!$this->project) { $this->project = $key; $this->vars["project"] = $key; }
                    $project_list[$key] = $prj->project_data[$key];
                }
            }
            reset($prj->project_people);

            $fdata=array();
            $fdata["type"] = "select";
            $fdata["java"] = "onChange=\"this.form.submit()\"";
            $fdata["list"] = $project_list;
            $f = new AdminFields("project",$fdata);
            $field_data = $f->display($this->vars["project"]);
            $tpl->addDataItem("FIELD_filter_project", $field_data);

            // tasks of a project
            $task_list = array("" => "---");
            while (list($key, $val) = each($prj->project_tasks)) {
                $task_list[$key] = $val;
            }
            reset($prj->project_tasks);

            $fdata=array();
            $fdata["type"] = "select";
            $fdata["java"] = "onChange=\"this.form.submit()\"";
            $fdata["list"] = $task_list;
            $f = new AdminFields("task",$fdata);
            $field_data = $f->display($this->vars["task"]);
            $tpl->addDataItem("FIELD_filter_task", $field_data);
        }

        $fdata["type"] = "textinput";
        $fdata["size"] = "20";
        $fdata["class"] = "i120";
        $f = new AdminFields("filter_text",$fdata);
        $field_data = $f->display($this->vars["filter_text"]);
        $tpl->addDataItem("FIELD_filter_text", $field_data);

        $fdata["type"] = "select";
        $list = array();
        $list[] = "-";
        $sql = "SELECT files_cat.*, files_cat_group.title AS group_title FROM files_cat, files_cat_group WHERE files_cat.group_id = files_cat_group.id ORDER BY files_cat_group.title, files_cat.title";
        $sq->query($this->dbc, $sql);
        while ($cat_data = $sq->nextrow()) {
            $list[$cat_data["id"]] = $cat_data["group_title"] . ": " . $cat_data["title"];
        }
        $fdata["list"] = $list;
        $f = new AdminFields("filter_category",$fdata);
        $field_data = $f->display($this->vars["filter_category"]);
        $tpl->addDataItem("FIELD_filter_category", $field_data);

        $fdata["type"] = "select";
        $list = array();
        $list[] = "-";
        $sql = "SELECT * FROM files_type ORDER BY files_type.title";
        $sq->query($this->dbc, $sql);
        while ($type_data = $sq->nextrow()) {
            $list[$type_data["id"]] = $type_data["title"];
        }
        $fdata["list"] = $list;
        $f = new AdminFields("filter_type_id", $fdata);
        $field_data = $f->display($this->vars["filter_type_id"]);
        $tpl->addDataItem("FIELD_filter_type_id", $field_data);

        $fdata["type"] = "textinput";
        $fdata["size"] = "20";
        $fdata["class"] = "i120";
        $f = new AdminFields("filter_keywords",$fdata);
        $field_data = $f->display($this->vars["filter_keywords"]);
        $tpl->addDataItem("FIELD_filter_keywords", $field_data);

        $fdata["type"] = "checkbox";
        $f = new AdminFields("filter_all_folders",$fdata);
        $field_data = $f->display($this->vars["filter_all_folders"]);
        $tpl->addDataItem("FIELD_filter_all_folders", $field_data);

        // ####

        if ($this->project) {
            if ($this->task) {
                $tpl->addDataItem("FOLDER",  $prj->project_tasks[$this->task] . " (" . $prj->project_data[$this->project] . ")");
            } else {
                $tpl->addDataItem("FOLDER", $prj->project_data[$this->project]);
            }
        } else if ($folder == "myfolder") {
            $tpl->addDataItem("FOLDER", $txt->display("myfolder"));
        }
        else if ($folder != "") {
            $tpl->addDataItem("FOLDER", $this->folder . $folder);
        }
        else {
            $tpl->addDataItem("FOLDER", $txt->display("folder0"));
        }

        // reading permission data of a folder we are currently in
        $folder_folder = $this->folder . $folder;
        if (substr($folder_folder, -1) == "/") {
            $folder_folder = substr($folder_folder, 0, -1);
        }

        $folder_name = substr($folder_folder, strrpos($folder_folder, "/"));
        $folder_folder = substr($folder_folder, 0, strrpos($folder_folder, "/")) . "/";
        if (substr($folder_name, 0, 1) == "/") {
            $folder_name = substr($folder_name, 1);
        }

        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $folder_folder . "' AND folders.name = '" . $folder_name . "'";
        $sq->query($this->dbc, $sql);
        if ($row_data = $sq->nextrow()) {
            $foldperm = $this->decodePermissions($row_data["permissions"]);
        }

        $tpl->addDataItem("COPY_CHECKED.BUTTON_TITLE", $txt->display("button_copy_checked"));
        if ($this->hasPermissions($foldperm, "d") || $myfolder) {
            $tpl->addDataItem("MOVE_CHECKED.BUTTON_TITLE", $txt->display("button_move_checked"));
            $tpl->addDataItem("DELETE_CHECKED.BUTTON_TITLE", $txt->display("button_delete_checked"));
        }

        $tpl->addDataItem("SELF", processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "mode=".$this->mode, array("mode", "filter_file", "filter_type", "file_id", "faction", "errmess")));
        $tpl->addDataItem("URL_GENERAL", $general_url);
        if ($this->hasPermissions($foldperm, "w") || $myfolder || $this->vars["project"]) {
            $tpl->addDataItem("FILEADD.URL_ADD", $general_url . "&faction=add");
        }

        if ($folder != "myfolder") {
            if (!$folder) {
//                if ($foldperm[$this->groupid]["w"]) {
                    $tpl->addDataItem("DIRADD.URL_DIRADD", $general_url . "&faction=diradd");
//                }
            } else {
                if ($this->hasPermissions($foldperm, "w") || $myfolder) {
                    $tpl->addDataItem("DIRADD.URL_DIRADD", $general_url . "&faction=diradd");
                    $tpl->addDataItem("DIRMOD.URL_DIRMOD", $general_url . "&faction=dirmod");
                }
                // checking if there are files inside current folder
                // if there were files or folders then not showing del button
                if ($this->isFolderEmpty($folder) && $this->hasPermissions($foldperm, "d")) {
                    $tpl->addDataItem("DIRDEL.URL_DIRDEL", $general_url . "&faction=dirdel");
                }
            }
        }

        // ### All messages
        for ($ii = 0; $ii < sizeof($this->messages); $ii++) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->messages[$ii]);
        }

        // ####
        return $tpl->parse();

    }

// ########################################

    function folderTree() {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $folder = @$this->vars["folder"];

        $start_folder = $this->module_param["filemanager"];

        if (!$folder && $this->module_param["filemanager"]) {
            $folder = $this->module_param["filemanager"] . "/";
        }

        if (ereg("\.", $folder)) doJump("");

        $general_url = processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("project","task","folder","file_id","faction","errmess"));
        if (substr($general_url, -1) == "#") $general_url = substr($general_url, 0, -1) . "?";

        $txt = new Text($this->language, "module_filemanager");

        $this->start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;

//          $folders = $this->getFolders("", array(), "");
        $folders_tree = $this->getFoldersJSON("/", "", $general_url, $folder);
        // instantiate template class
        $tpl = new template;
        //$this->cachelevel = TPL_CACHE_ALL;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_filemanager_menu.html";
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=filemanager&folders=".serialize($folders)."&folder=".$folder);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "filemanager";
            return "<!-- module filemanager cached -->\n" . $tpl->parse();
        }

        $tpl->addDataItem("FOLDERS_TREE", $folders_tree);
/*
        // 14/03/2006, Martin: checking if private folder should be shown
        if ($this->module_param["filemanager2"]) {
            // My private folder
            $tpl->addDataItem("M_LEVEL2.LINK.URL", $general_url . "&folder=myfolder");
            if ($folder == "myfolder") {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", "<b>".$txt->display("myfolder")."</b>");
            } else {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", $txt->display("myfolder"));
            }
        }

        // 29/01/2006, Martin: if parameter 1 is set, then it means that only showing selected folder with it's subfolders ...
        if ($this->module_param["filemanager1"] && $start_folder) {

            if (substr($start_folder, -1) == "/") {
                $start_folder = substr($start_folder, 0, -1);
            }
            $folder_list = explode("/", $start_folder);
            $top_folder = $folder_list[sizeof($folder_list) - 1];

            $tpl->addDataItem("M_LEVEL2.LINK.URL", $general_url . "&folder=" . urlencode($start_folder . "/"));
            if ($folder == $start_folder . "/") {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", "<b>".ereg_replace("_", " ", $top_folder)."</b>");
            }
            else {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", ereg_replace("_", " ", $top_folder));
            }
            $tpl->addDataItem("M_LEVEL2.LINK.PAGES.PAGE.PAGE", $this->folderTreeSub($general_url, $folder, $folders, $start_folder));
        } else { // ... otherwise showing whole tree
            // Top level folder
            $tpl->addDataItem("M_LEVEL2.LINK.URL", $general_url . "");
            if ($folder == "") {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", "<b>".$txt->display("folder0")."</b>");
            }
            else {
                $tpl->addDataItem("M_LEVEL2.LINK.LINK", $txt->display("folder0"));
            }

            // All other folders, 2 levels max
            for ($c = 0; $c < sizeof($folders["ROOT"]); $c++) {
                $tpl->addDataItem("M_LEVEL2.LINK.URL", $general_url . "&folder=" . urlencode($folders["ROOT"][$c][0]."/"));
                if ($folder == $folders["ROOT"][$c][0]."/") {
                    $tpl->addDataItem("M_LEVEL2.LINK.LINK", "<b>".ereg_replace("_", " ", $folders["ROOT"][$c][1])."</b>");
                }
                else {
                    $tpl->addDataItem("M_LEVEL2.LINK.LINK", ereg_replace("_", " ", $folders["ROOT"][$c][1]));
                }
                $tpl->addDataItem("M_LEVEL2.LINK.PAGES.PAGE.PAGE", $this->folderTreeSub($general_url, $folder, $folders, $folders["ROOT"][$c][0]));
            }
        }
*/
    // ####
    return $tpl->parse();

}

// ########################################

function folderTreeSub($general_url, $folder, $folders, $start, $level = 3) {

    $cur_folder = $folders[$start];
    for ($t = 0; $t < sizeof($cur_folder); $t++) {
        if ($t == 0) {
            $tmp = "";
        }
        $tmp .= "<div class=\"menu$level\"><a href=\"" . $general_url . "&folder=" . urlencode($cur_folder[$t][0] . "/") . "\">";
        if ($folder == $cur_folder[$t][0] . "/") {
            $tmp .= "<b>" . ereg_replace("_", " ", $cur_folder[$t][1]) . "</b>";
        } else {
            $tmp .= ereg_replace("_", " ", $cur_folder[$t][1]);
        }
        $tmp .= "</a></div>\n";

        $show_child = substr($folder, 0, min(strlen($folder), strlen($cur_folder[$t][0]))) == substr($cur_folder[$t][0], 0, min(strlen($folder), strlen($cur_folder[$t][0])));
        if ($show_child && substr_count($folder, "/") > 1) {
            $tmp .= $this->folderTreeSub($general_url, $folder, $folders, $cur_folder[$t][0], $level + 1);
        }
    }
    $tmp .= $tmp ? "" : "";
    return $tmp;
}

// ########################################

    // Delete multiple files at once
    function deleteFileChecked($files) {
        $sq = new sql;

        if (!sizeof($files)) {
            return;
        }

        while (list($key, $val) = each($files)) {
            $sql = "SELECT * FROM files WHERE files.id = '" . $key . "'";
            $sq->query($this->dbc, $sql);
            if ($data = $sq->nextrow()) {
                $this->deleteFile($data["name"] . "." . $data["type"], $data["folder"], false);
            }
        }
    }

    // Delete file

    function deleteFile($file_id, $folder, $redirect) {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $project_people = @$this->vars["project_people"];
        $project_data = @$this->vars["project_data"];
        $mode = @$this->vars["mode"];

        $prj = new projects;
        $this->project = $prj->project;
        $this->task = $prj->task;

        if (ereg("\.", $folder) || !$file_id || ereg("\.\.", $file_id)) {
            redirect(processQuery($_SERVER["QUERY_STRING"], "", array("file_id", "faction","errmess")));
        }

        if ($this->project) {
            if (is_array($prj->project_people[$this->project])) {
                if (!in_array($this->userid, $prj->project_people[$this->project])) doJump("");
            }
            else {
                doJump("");
            }
            $this->folder = "/projects" . $this->project . "/";
            if ($this->task) {
                $this->folder .= "tasks" . $this->task . "/";
            }
            if ($folder == "myfolder") $folder = "";
            $folder = "";
        }

        $txt = new Text($this->language, "module_filemanager");
        $sq = new sql;

        if ($folder == "myfolder") {
            $tmp_folder = $this->folder . $this->myfolder . "/" . $this->username;
        }
        else {
            $tmp_folder = $this->folder . $folder;
        }

        $filename = substr($file_id, 0, strrpos($file_id, '.'));
        $filetype = substr($file_id, strrpos($file_id, '.') + 1);
        $filetype = strtolower($filetype);

        $sql = "SELECT * FROM files WHERE type = '" . addslashes($filetype) . "' AND name = '" . addslashes($filename) . "' AND folder = '" . addslashes($tmp_folder) . "'";

        $sq->query($this->dbc, $sql);
        if ($data = $sq->nextrow()) {
            $perm = $this->decodePermissions($data["permissions"]);
            // check if delete-ing of this file is permitted to current user's group or this user is owner of the file
            if (($this->hasPermissions($perm, "d")) || ($data["owner"] == "99" . $this->userid)) {
                $sta = @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $tmp_folder . $file_id);
                if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $tmp_folder . $filename . "_thumb." . $filetype)) {
                    @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $tmp_folder . $filename . "_thumb." . $filetype);
                }

                if ($sta) {
                    $sql = "DELETE FROM files WHERE type = '" . addslashes($filetype) . "' AND name = '" . addslashes($filename) . "' AND folder = '" . addslashes($tmp_folder) . "'"; // AND owner LIKE '99%'";
                    $sq->query($this->dbc, $sql);
                }
            } else {
                // there should be some kind of error-message
                $this->messages[0] = $txt->display("delete_error") . " " . $tmp_folder . $filename . "." . $filetype;
            }
        }

        if ($redirect) {
            redirect(processQuery($_SERVER["QUERY_STRING"], "", array("file_id","faction","errmess")));
        }
    }

// ########################################
    // Delete folder
    function deleteFolder() {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $folder = @$this->vars["folder"];
        $project_people = @$this->vars["project_people"];
        $project_data = @$this->vars["project_data"];
        $mode = @$this->vars["mode"];

        $sq = new sql;

        if ($folder == "myfolder") {
            //mainfolder doesn't matter since myfolder is always in root
            $folder = $this->folder . $this->myfolder . "/" . $this->username . "/";
        }

        if (substr($folder, -1) == "/") {
            $folder = substr($folder, 0, -1);
        }

        $ffolder = substr($folder, 0, strrpos($folder, "/")) . "/";
        $fname = substr($folder, strrpos($folder, "/"));
        if (substr($fname, 0, 1) == "/") {
            $fname = substr($fname, 1);
        }
        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
        $sq->query($this->dbc, $sql);
        if ($fdata = $sq->nextrow()) {
            $fperm = $this->decodePermissions($fdata["permissions"]);
        }

        // checking if update of current folder is allowed
        if ($this->hasPermissions($fperm, "d") || ($fdata["owner"] == "99" . $this->userid)) {
            if ($this->isFolderEmpty($folder)) {
                rmdir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder);
                $sql = "DELETE FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
                $sq->query($this->dbc, $sql);
                redirect(processQuery($_SERVER["QUERY_STRING"], "errmess=dirdelok", array("file_id","faction","errmess","folder")));
            } else {
                redirect(processQuery($_SERVER["QUERY_STRING"], "errmess=dirdelerr", array("file_id","faction","errmess","folder")));
            }
        } else {
            redirect(processQuery($_SERVER["QUERY_STRING"], "errmess=dirdelperm", array("file_id","faction","errmess","folder")));
        }
    }
// ########################################

    // Copy multiple files
    function copyFileChecked($target, $files, $move) {
        $sq = new sql;
        $sq2 = new sql;

        $txt = new Text($this->language, "module_filemanager");

        if (!sizeof($files)) {
            return;
        }

        if ($target == "myfolder") {
            $target .= "/" . $this->username;
            $myfolder = true;
        }

        $folder = $target;
        if (substr($folder, -1) == "/") {
            $folder = substr($folder, 0, -1);
        }

        $ffolder = substr($folder, 0, strrpos($folder, "/")) . "/";
        $fname = substr($folder, strrpos($folder, "/"));
        if (substr($fname, 0, 1) == "/") {
            $fname = substr($fname, 1);
        }
        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
        $sq->query($this->dbc, $sql);
        if ($fdata = $sq->nextrow()) {
            $fperm = $this->decodePermissions($fdata["permissions"]);
        }

        // checking if update of current folder is allowed
        if (!($this->hasPermissions($fperm, "w") || ($fdata["owner"] == "99" . $this->userid) || $myfolder)) {
            // if reading is not permitted, then error-message
            $this->messages[] = $txt->display("write_error_folder") . $target;
            return;
        }

        while (list($key, $val) = each($files)) {
            $sql = "SELECT * FROM files WHERE files.id = '" . $key . "'";
            $sq->query($this->dbc, $sql);
            if ($data = $sq->nextrow()) {
                // cannot copy to same folder
                if ($data["folder"] != $target) {
                    $perm = $this->decodePermissions($data["permissions"]);
                    // check if reading of this file is permitted to current user's group or this user is owner of the file
                    if (($this->hasPermissions($perm, "r")) || ($data["owner"] == "99" . $this->userid)) {
                        // first copying file to target folder
                        if (copy(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "." . $data["type"], SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $target . "/" . $data["name"] . "." . $data["type"])) {
                            // checking if there was a thumnail ...
                            if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "_thumb." . $data["type"])) {
                                // copying thumbnail as well
                                copy(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "_thumb." . $data["type"], SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $target . "/" . $data["name"] . "_thumb." . $data["type"]);
                            }

                            // checking if there already is a file in this directory with same name and extension
                            $sql = "SELECT * FROM files WHERE type = '" . $data["type"] . "' AND name = '" . $data["name"] . "' AND folder = '" . $target . "'";
                            $sq2->query($this->dbc, $sql);
                            // if record exists then updating current record ...
                            if ($data2 = $sq2->nextrow()) {
                                $perm2 = $this->decodePermissions($data2["permissions"]);
                                // check if writing of this file is permitted to current user's group or this user is owner of the file
                                if ($this->hasPermissions($perm2, "w") || ($data2["owner"] == "99" . $this->userid)) {
                                    $sql = "UPDATE files SET cat_list = '" . $data["cat_list"] . "', text = '" . $data["text"] . "', keywords = '" . $data["keywords"] . "', owner = '99" . $this->userid . "', lastmod = NOW(), permissions = '" . $data["permissions"] . "', type_id = '" . $data["type_id"] . "' WHERE type = '" . $data["type"] . "' AND name = '" . $data["name"] . "' AND folder = '" . $target . "'";
                                    $sq2->query($this->dbc, $sql);
                                } else {
                                    // if not permitted, then error-message
                                    $this->messages[] = $txt->display("write_error") . " " . $data2["folder"] . "/" . $data2["name"] . "." . $data2["type"];
                                }
                            } else {
                                // ... otherwise creating a new record to files table
                                $sql = "INSERT INTO files (cat_list, type, name, folder, text, keywords, owner, add_date, lastmod, permissions, type_id) VALUES ('" . $data["cat_list"] . "', '" . $data["type"] . "', '" . $data["name"] . "', '" . $target . "', '" . $data["text"] . "', '" . $data["keywords"] . "', '99" . $this->userid . "', NOW(), NOW(), '" . $data["permissions"] . "', '" . $data["type_id"] . "')";
                                $sq2->query($this->dbc, $sql);
                            }
                            // if it was moving procedure instead of plain copy ...
                            if ($move) {
                                // ... then deleting file(s) and old record
                                // check if deleting of this file is permitted to current user's group or this user is owner of the file
                                if (($this->hasPermissions($perm, "d")) || ($data["owner"] == "99" . $this->userid)) {
                                    unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "." . $data["type"]);
                                    // checking if there was a thumbnail ...
                                    if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "_thumb." . $data["type"])) {
                                        // copying thumbnail as well
                                        unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $data["folder"] . "/" . $data["name"] . "_thumb." . $data["type"]);
                                    }
                                    $sql = "DELETE FROM files WHERE files.id = '" . $key . "'";
                                    $sq2->query($this->dbc, $sql);
                                } else {
                                    // if not permitted, then error-message
                                    $this->messages[] = $txt->display("delete_error") . " " . $data["folder"] . "/" . $data["name"] . "." . $data["type"];
                                }
                            }
                        }
                    } else {
                        // if reading is not permitted, then error-message
                        $this->messages[] = $txt->display("read_error") . " " . $data["folder"] . "/" . $data["name"] . "." . $data["type"];
                    }
                }
            }
        }
    }

// ########################################

    // Add new file

    function addFile($file_id, $faction) {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $folder = @$this->vars["folder"];
        $write = @$this->vars["write"];

        $show_file_permissions = $this->module_param["filemanager4"]; // 0 - don't show file permissions, 1 - show file permissions

        $file_name = $file_name_orig = $_FILES["file"]["name"];
        $file_size = $_FILES["file"]["size"];
        $file = $_FILES["file"]["tmp_name"];

        if (ereg("\.", $folder) || ereg("\.\.", $file_id)) redirect(processQuery($_SERVER["QUERY_STRING"], "", array("file_id","faction","errmess")));

        $prj = new projects;
        $this->project = $prj->project;
        $this->task = $prj->task;

        if ($this->project) {
            /*$this->folder*/ $folder = "/projects" . $this->project . "/";
            if ($this->task) {
                $folder .= "tasks" . $this->task . "/";
            }
            if ($folder == "myfolder") $folder = "";
        }

        // Check access to my folder
        if ($folder != "myfolder" && eregi($this->myfolder, $folder) && $folder != $this->myfolder . "/" . $this->username && $folder != $this->myfolder . "/" . $this->username . "/") {
            redirect(processQuery($_SERVER["QUERY_STRING"], "", array("folder","errmess")));
        }

        $sq = new sql;

        $txt = new Text($this->language, "module_filemanager");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {

            // 13/11/2005, Martin:
            // if modify and no file_name, then from now on not considering it an error, because user may only want to change permissions,
            // description, category and so on without actually re-uploading a file
            if (/*!$this->vars["info"] ||*/ (!$file_name && ($faction != "modify"))) {
                $error = true;
            } else {
                if ($this->vars["project"]) {
                    if (is_array($prj->project_people[$this->project])) {
                        if (!in_array($this->userid, $prj->project_people[$this->project])) doJump("");
                    }
                    else {
                        doJump("");
                    }
                    if ($folder == "myfolder") $folder = "";
                }
                if ($folder == "myfolder") {
                    //phifolder siin ei ole mrav, myfolder alati rootus
                    $folder = $this->folder . $this->myfolder . "/" . $this->username . "/";
                    $myfolder = true;
                } else {
                    if (in_array($folder, $this->protected)) {
                        exit;
                    }
                    $old_folder = $folder;
                    $folder = $this->folder . $folder;
                }

                $sizes = array("400x300", "640x480", "800x600");
                if (!in_array($this->vars["picture_size"], $sizes)) $this->vars["picture_size"] = "";

                $permissions = $this->encodePermissions($this->vars["group_read"], $this->vars["group_write"], $this->vars["group_delete"]);
                $category = $this->encodeCategory($this->vars["category"]);

                // MODIFY FILE
                if ($faction == "modify" && $file_id) {
                    $fld = "folder = '" . $this->folder;
                    if ($folder) {
                        $fld .= addslashes($folder) . "'";
                    }
                    else {
                        if ($this->folder) { 
                            $fld .= $fld . "'";
                        }
                        else { 
                            $fld .= "'"; 
                        }
                    }
                    if ($folder == "myfolder") {
                        $fld = "folder = '".$this->myfolder . "/" . $this->username."'";
                    }
                    $sql = "SELECT *, CONCAT(name, \".\", type) as filefullname, DATE_FORMAT(lastmod, '%d.%m.%Y %H:%i') as date FROM files WHERE $fld AND CONCAT(name, \".\", type) = '".addslashes($file_id) . "'";

                    $sq->query($this->dbc, $sql);
                    $row_data = $sq->nextrow();
                    $file_name = $row_data["filefullname"];
                    $sq->free();

                    $perm = $this->decodePermissions($row_data["permissions"]);
                    $cats = $this->decodeCategory($row_data["cat_list"]);

                    // checking if update of current row was allowed
                    if (($this->hasPermissions($perm, "w")) || ($row_data["owner"] == "99" . $this->userid) || $myfolder || $this->vars["project"]) {
                        if ($file_size) {
                            $up_data = $this->uploadFile($folder, $file_name, $file_size, $file, $this->vars["info"], true, $this->vars["picture_size"], $category, $this->vars["keywords"], $permissions, $this->vars["type_id"]);
                        } else {
                            $sql = "UPDATE files SET cat_list = '" . $category . "', text = '" . $this->vars["info"] . "', keywords = '" . $this->vars["keywords"] . "', owner = '" . $row_data["owner"] . "', lastmod = NOW(), permissions = '" . $permissions . "', type_id = '" . $this->vars["type_id"] . "' WHERE id = '" . $row_data["id"] . "'";
                            $sq->query($this->dbc, $sql);
                        }
                    } else {
                        // some kind of error-message about not being allowed to write ...
                        $this->messages[] = $txt->display("write_error") . " " . $folder . $file_name;
                    }
                }
                // ADD FILE
                else {
                    $tfolder = $folder;
                    if (substr($tfolder, -1) == "/") {
                        $tfolder = substr($tfolder, 0, -1);
                    }

                    $ffolder = substr($tfolder, 0, strrpos($tfolder, "/")) . "/";
                    $fname = substr($tfolder, strrpos($tfolder, "/"));
                    if (substr($fname, 0, 1) == "/") {
                        $fname = substr($fname, 1);
                    }
                    $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
                    $sq->query($this->dbc, $sql);
                    if ($fdata = $sq->nextrow()) {
                        $fperm = $this->decodePermissions($fdata["permissions"]);
                    }

                    // checking if writing into this folder is allowed
                    if ($this->hasPermissions($fperm, "w") || ($fdata["owner"] == "99" . $this->userid) || $myfolder || $this->vars["project"]) {
                        $up_data = $this->uploadFile($folder, $file_name, $file_size, $file, $this->vars["info"], false, $this->vars["picture_size"], $category, $this->vars["keywords"], $permissions, $this->vars["type_id"]);
                    } else {
                        redirect(processQuery($_SERVER["QUERY_STRING"], "folder=".urlencode($folder) . "&errmess=foldwriperm", array("folder","file_id","faction","errmess")));
                    }
                }
                if ($up_data[1] != "") { $upped_file = $up_data[1]; }

                // check if there was a file_name and no file_size, which means that file was not completly uploaded
                if ($file_name_orig && !$file_size) {
                    $error = true;
                } else {
                    if ($old_folder && substr($old_folder,-1,1) != "/") $old_folder .= "/";
                    redirect(processQuery($_SERVER["QUERY_STRING"], "folder=" . urlencode($old_folder) . "&project=" . $this->project . "&task=" . $this->task . "&nocache=true", array("project", "task", "file_id", "faction", "errmess")));
                }
            }
        }
        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if ($faction == "add" && $this->multiUploadSupport()) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_filemanager_add.html";
        } else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_filemanager_mod.html";
        }

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=filemanager&faction=add&folder=".$folder);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "filemanager";
            return "<!-- module filemanager cached -->\n" . $tpl->parse();
        }

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error"));
        if ($faction == "modify" && $file_id) {
            $fld = "folder = '" . $this->folder;
            if ($folder) {
//                $fld .= addslashes(substr($folder,0,-1)) . "'";
                $fld .= addslashes($folder) . "'";
            } else {
                if ($this->folder) {
//                    $fld = substr($fld,0,-1) . "'";
                    $fld = $fld . "'";
                } else {
                    $fld .= "'";
                }
            }
            if ($folder == "myfolder") {
                $fld = "folder = '" . $this->myfolder . "/" . $this->username . "'";
            }
            $sql = "SELECT *, DATE_FORMAT(lastmod, '%d.%m.%Y %H:%i') as date FROM files WHERE $fld AND CONCAT(name, \".\", type) = '".addslashes($file_id) . "'";
            //echo $sql;
            $sq->query($this->dbc, $sql);
            $row_data = $sq->nextrow();
            $row_data["info"] = $row_data["text"];
            $row_data["picture_size"] = "640x480";
            $row_data["category"] = $row_data["cat_list"];
            $row_data["project"] = $this->project;
            $row_data["task"] = $this->task;

            if (substr($row_data["owner"],0,2) == "99") {
                $users = $this->getUsers();
                $tpl->addDataItem("LASTMOD", $row_data["date"] . ", " . $users[substr($row_data["owner"],2)]);
            }
            else {
                $tpl->addDataItem("LASTMOD", "-");
            }
        }
        else {
            $tpl->addDataItem("LASTMOD", "-");
        }

        // ####
        // form

        if (!isset($this->vars["picture_size"])) $this->vars["picture_size"] = "640x480";
        if (isset($this->vars["folder"]) && substr($this->vars["folder"], -1) == "/") $this->vars["folder"] = substr($this->vars["folder"], 0,-1);
        else if (isset($this->vars["folder"]) && substr($this->vars["folder"], -1) != "/") $this->vars["folder"] = $this->vars["folder"];

        $fields = array(
            "type_id" => array("select", 40,0,$this->vars["type_id"]),
            "keywords" => array("textinput", 40,0,$this->vars["keywords"]),
            "info" => array("textinput", 40,0,$this->vars["info"]),
            "file" => array("file",40,0,""),
            "project" => array("select",0,0,$this->project),
            "task" => array("select",0,0,$this->task),
            "folder" => array("select",0,0,$this->vars["folder"]),
            "picture_size" => array("select",0,0,$this->vars["picture_size"])
        );

        $list = array();
        $list[0] = "-";
        $sql = "SELECT * FROM files_type ORDER BY files_type.title";
        $sq->query($this->dbc, $sql);
        while ($type_data = $sq->nextrow()) {
            $list[$type_data["id"]] = $type_data["title"];
        }
        $fields["type_id"][4] = $list;

        $list = array("400x300" => "400x300", "640x480" => "640x480", "800x600" => "800x600", "nosize" => $txt->display("picture_size_no"));
        $fields["picture_size"][4] = $list;

        // projects
        $project_list = array("" => "---");
        while (list($key, $val) = each($prj->project_people)) {
            if (in_array($this->userid, $val)) {
                //if (!$this->project) { $this->project = $key; $this->vars["project"] = $key; }
                $project_list[$key] = $prj->project_data[$key];
            }
        }
        reset($prj->project_people);

        $fields["project"][4] = $project_list;
        $fields["project"][3] = $this->project;
        $fields["project"][5] = "onChange=\"document.vorm.folder.disabled=true\"";

        // tasks
        $task_list = array("" => "---");
        while (list($key, $val) = each($prj->project_tasks)) {
            $task_list[$key] = $val;
        }

        $fields["task"][4] = $task_list;
        $fields["task"][3] = $this->task;
        $fields["task"][5] = "onChange=\"document.vorm.folder.disabled=true\"";

        while (list($key, $val) = each($fields)) {
            if (sizeof($val) > 0) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val["5"];
                if ($faction == "modify" && $error != true) $val[3] = $row_data[$key];
                $f = new AdminFields("$key",$fdata);
                $field_data = $f->display($val[3]);
                if ($key == "folder") {
                    if ($folder == "myfolder") {
                        $field_data = $txt->display("myfolder");
                        $field_data .= "<input type=\"hidden\" name=\"folder\" value=\"myfolder\">";
                    } else {
                        $field_data = $hid_folder = $folder;
                        if ($hid_folder) {
                            /*
                            if ($hid_folder && substr($hid_folder, -1) == "/") {
                                $hid_folder = substr($hid_folder, 0, -1);
                            }
                            */
                            $field_data .= "<input type=\"hidden\" name=\"folder\" value=\"" . $hid_folder . "\">";
                        }
                    }
                }
                $tpl->addDataItem("FIELD_$key", $field_data);
                unset($fdata);
            }
        }

        $cats_array = $this->decodeCategory($row_data["cat_list"]);

        $sql = "SELECT files_cat.*, files_cat_group.title AS group_title FROM files_cat, files_cat_group WHERE files_cat.group_id = files_cat_group.id ORDER BY files_cat_group.title, files_cat.title";
        $group_title = false;
        $sq->query($this->dbc, $sql);
        while ($cdata = $sq->nextrow()) {
            if ($group_title != $cdata["group_title"]) {
                $tpl->addDataItem("CATEGORIES.TITLE", $cdata["group_title"]);
                $group_title = $cdata["group_title"];
            }
            $tpl->addDataItem("CATEGORIES.CAT.ID", $cdata["id"]);
            $tpl->addDataItem("CATEGORIES.CAT.NAME", $cdata["title"]);

            if (in_array($cdata["id"], $cats_array)) {
                $tpl->addDataItem("CATEGORIES.CAT.CHECKED", "checked");
            }
        }

        $perm_array = $this->decodePermissions($row_data["permissions"]);

        // finding permissions of a current folder
        $tfolder = $folder;
        if (substr($tfolder, -1) == "/") {
            $tfolder = substr($tfolder, 0, -1);
        }

        $ffolder = substr($tfolder, 0, strrpos($tfolder, "/")) . "/";
        $fname = substr($tfolder, strrpos($tfolder, "/"));
        if (substr($fname, 0, 1) == "/") {
            $fname = substr($fname, 1);
        }

        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
        $sq->query($this->dbc, $sql);
        if ($folder_data = $sq->nextrow()) {
            $fperm_array = $this->decodePermissions($folder_data["permissions"]);
        }

        $sql = "SELECT * FROM module_user_groups ORDER BY module_user_groups.name";
        $sq->query($this->dbc, $sql);
        while ($gdata = $sq->nextrow()) {
            if ($show_file_permissions) {
                $tpl->addDataItem("PERM.PERM.ID", $gdata["id"]);
                $tpl->addDataItem("PERM.PERM.NAME", $gdata["name"]);
            } else {
                $tpl->addDataItem("PERM_HIDDEN.ID", $gdata["id"]);
            }

            // 14/03/2006, Martin: in case of adding new file, assigning permissions by the folder
            if ($perm_array[$gdata["id"]]["r"] || ($faction == "add" && $fperm_array[$gdata["id"]]["r"])) {
                if ($show_file_permissions) {
                    $tpl->addDataItem("PERM.PERM.READ_CHECKED", "checked");
                } else {
                    $tpl->addDataItem("PERM_HIDDEN.READ_CHECKED", $this->default_file_r);
                }
            }
            if ($perm_array[$gdata["id"]]["w"] || ($faction == "add" && $fperm_array[$gdata["id"]]["w"])) {
                if ($show_file_permissions) {
                    $tpl->addDataItem("PERM.PERM.WRITE_CHECKED", "checked");
                } else {
                    $tpl->addDataItem("PERM_HIDDEN.WRITE_CHECKED", $this->default_file_w);
                }
            }
            if ($perm_array[$gdata["id"]]["d"] || ($faction == "add" && $fperm_array[$gdata["id"]]["d"])) {
                if ($show_file_permissions) {
                    $tpl->addDataItem("PERM.PERM.DELETE_CHECKED", "checked");
                } else {
                    $tpl->addDataItem("PERM_HIDDEN.DELETE_CHECKED", $this->default_file_d);
                }
            }
        }

        $tpl->addDataItem("BUTTON", $txt->display("button_save"));
/*
        if ($faction == "add") {
            $tpl->addDataItem("BUTTON", $txt->display("button_add"));
        } else {
            $tpl->addDataItem("BUTTON", $txt->display("button_mod"));
        }
*/
        $hidden = "<input type=\"hidden\" name=\"max_size\" value=\"" . $this->return_bytes(ini_get("upload_max_filesize")) . "\">\n";
        $hidden .= "<input type=\"hidden\" name=\"form_id\" value=\"" . rand(0, 99999999) . "\">\n";
        $hidden .= "<input type=hidden name=\"faction\" value=\"$faction\">\n";
        $tpl->addDataItem("HIDDEN", "<input type=hidden name=\"write\" value=\"true\">\n<input type=hidden name=\"file_id\" value=\"".$file_id."\">" . $hidden);
        $tpl->addDataItem("SELF", processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "mode=".$this->mode, array("folder","project","mode","filter_file","filter_type","file_id","faction","errmess")));

        // ####
        return $tpl->parse();
    }


    function log_faction($data) {
        if ($fp_log = fopen(SITE_PATH . "/sessionlog.txt", "a+")) {
            fwrite($fp_log, date("d.m.Y H:i:s") . ": " . $data . "\n");
            fclose($fp_log);
        }
    }


// ########################################
    // prepearing session variables for multi upload
    function prepareUpload() {
        unset($_POST["faction"]);
        $_SESSION["upload"][$_POST['form_id']] = $_POST;
        exit();
    }

// ########################################
    // saving multiuploaded files into cache temporarily
    function saveFile() {
        $sq = new sql;
        if (isset($_FILES['Filedata'])) {
            $_POST = $_SESSION["upload"][$_GET['form_id']];
            $_POST["text"] = $_REQUEST["text"] = $_POST["description"][$_GET['file']];
            $new_name = SITE_PATH . "/cache/upl_" . session_id() . "_" . $_GET["file"];
            move_uploaded_file($_FILES['Filedata']['tmp_name'], $new_name);
            $_FILES['Filedata']['tmp_name'] = $new_name;
            foreach ($_POST as $k => $v) $$k = $v;

            $file_name = $_FILES['Filedata']["name"];
            $file_size = $_FILES['Filedata']["size"];
            $filedata  = $_FILES['Filedata']["tmp_name"];
            $file_text = $_POST['text'];
            $pic_size  = $_POST['picture_size'];
            $project   = $_POST['project'];
            $task      = $_POST['task'];
            $folder    = $_POST['folder'];

            $permissions = $this->encodePermissions($group_read, $group_write, $group_delete);
            $category = $this->encodeCategory($category);

            $tfolder = $folder;
            if (substr($tfolder, -1) == "/") {
                $tfolder = substr($tfolder, 0, -1);
            }

            $ffolder = substr($tfolder, 0, strrpos($tfolder, "/")) . "/";
            $fname = substr($tfolder, strrpos($tfolder, "/"));
            if (substr($fname, 0, 1) == "/") {
                $fname = substr($fname, 1);
            }
            $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
            $sq->query($this->dbc, $sql);
            if ($fdata = $sq->nextrow()) {
                $fperm = $this->decodePermissions($fdata["permissions"]);
            }

            // checking if writing into this folder is allowed
            if ($this->hasPermissions($fperm, "w") || ($fdata["owner"] == "99" . $this->userid) || $myfolder || $project) {
                $up_data = $this->uploadFile($folder, $file_name, $file_size, $filedata, $file_text, false, $pic_size, $category, $keywords, $permissions, $type_id);
            } else {
                redirect(processQuery($_SERVER["QUERY_STRING"], "folder=".urlencode($folder) . "&errmess=foldwriperm", array("folder","file_id","faction","errmess")));
            }
        }
    }

// ########################################
    // finishing uploading process
    function finishUpload() {
        $form_id = $_GET['form_id'] ? $_GET['form_id'] : $_POST['form_id'];
        if (isset($_SESSION["upload"][$form_id]['error'])) {
        } else {
            $folder = $_SESSION["upload"][$form_id]['folder'];
            $project = $_SESSION["upload"][$form_id]['project'];
            $task = $_SESSION["upload"][$form_id]['task'];
            unset($_SESSION["upload"][$form_id]);
            redirect(processQuery($_SERVER["QUERY_STRING"], "&folder=" . $folder . "&project=" . $project . "&task=" . $task, array("file_id","faction")));
//            exit;
        }
    }


// ########################################
    // Add folder


    function addFolder($faction) {
        $structure = @$this->vars["structure"];
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $folder = @$this->vars["folder"];
        $write = @$this->vars["write"];

        $recursive_permissions = $this->module_param["filemanager3"]; // 0 - no recursive permission assigning, 1 - premissions are set recusively

        // Check access to my folder
        if ($folder != "myfolder" && eregi($this->myfolder, $folder) && $folder != $this->myfolder . "/" . $this->username && $folder != $this->myfolder . "/" . $this->username . "/") {
            redirect(processQuery($_SERVER["QUERY_STRING"], "", array("folder","errmess")));
        }

        $sq = new sql;

        $txt = new Text($this->language, "module_filemanager");

        // 23/03/2006, Martin: replacing spaces with underscores
        $this->vars["folder_name"] = str_replace(" ", "_", $this->vars["folder_name"]);

        if ($faction == "dirmod" && !$this->vars["folder_name"]) {
            $cur_folder = $folder;
            if (substr($cur_folder, -1) == "/") {
                $cur_folder = substr($cur_folder, 0, -1);
            }
            $this->vars["folder"] = $folder = substr($cur_folder, 0, strrpos($cur_folder, "/")) . "/";
            $folder_name_old = substr($cur_folder, strrpos($cur_folder, "/"));
            if (substr($folder_name_old, 0, 1) == "/") {
                $folder_name_old = substr($folder_name_old, 1);
            }
            if (!$this->vars["folder_name"]) {
                $this->vars["folder_name"] = $folder_name_old;
            }
        }

        $error_name = false;
        if (!$this->nameAllowed($this->vars["folder_name"])) {
            $error_name = true;
        }

        // ###################################
        // WRITE TO DB
        if ($write == "true" && !$error_name) {

            if (!$this->vars["folder_name"]) {
                $error = true;
            } else {
                if ($folder == "myfolder") {
                    //phifolder siin ei ole mrav, myfolder alati rootus
                    $folder = $this->folder . $this->myfolder . "/" . $this->username . "/";
                    $myfolder = true;
                } else {
                    if (in_array($folder, $this->protected)) {
                        exit;
                    }
                    $old_folder = $folder;
                    $folder = $this->folder . $folder;
                }

                if ($folder == "/") {
//                    $folder = "";
                }

                $ffolder = $folder;
                if ($ffolder) {
//                    $ffolder = substr($folder, 0, -1);
                }

                $permissions = $this->encodePermissions($this->vars["group_read"], $this->vars["group_write"], $this->vars["group_delete"]);

                // MODIFY FILE
                if ($faction == "dirmod" && $this->vars["folder_name"]) {
                    // reading permission data of a folder we are about to change
                    $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $this->vars["folder_name_old"] . "'";
                    $sq->query($this->dbc, $sql);
                    if ($row_data = $sq->nextrow()) {
                        $fperm = $this->decodePermissions($row_data["permissions"]);
                    }

                    // checking if update of current folder is allowed
                    if ($this->hasPermissions($fperm, "w") || ($row_data["owner"] == "99" . $this->userid) || $myfolder) {
                        if (!is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $this->vars["folder_name"])) {
                            rename(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $this->vars["folder_name_old"], SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $this->vars["folder_name"]);
                        }
                        // changing folder-table
                        $sql = "UPDATE folders SET name = '" . $this->vars["folder_name"] . "', permissions = '$permissions', lastmod = NOW() WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $this->vars["folder_name_old"] . "'";
                        $sq->query($this->dbc, $sql);
                        // and then all subfolders
                        $sql = "UPDATE folders SET folder = REPLACE(folder, '" . $folder . $this->vars["folder_name_old"] . "', '" . $folder . $this->vars["folder_name"] . "')";
                        if ($recursive_permissions) {
                            $sql .= ", permissions = '$permissions'";
                        }
                        $sql .= " WHERE folder LIKE '" . $folder . $this->vars["folder_name_old"] . "/%' OR folder = '" . $folder . $this->vars["folder_name_old"] . "'";
                        $sq->query($this->dbc, $sql);

                        // after renaming folder, changig all records in files table as well
                        // first exact match
                        $sql = "UPDATE files SET folder = '" . $folder . $this->vars["folder_name"] . "'";
                        if ($recursive_permissions) {
                            $sql .= ", permissions = '$permissions'";
                        }
                        $sql .= " WHERE folder = '" . $folder . $this->vars["folder_name_old"] . "'";
                        $sq->query($this->dbc, $sql);
                        // and then all subfolders
                        $sql = "UPDATE files SET folder = REPLACE(folder, '" . $folder . $this->vars["folder_name_old"] . "', '" . $folder . $this->vars["folder_name"] . "')";
                        if ($recursive_permissions) {
                            $sql .= ", permissions = '$permissions'";
                        }
                        $sql .= " WHERE folder LIKE '" . $folder . $this->vars["folder_name_old"] . "/%'";
                        $sq->query($this->dbc, $sql);
                        redirect(processQuery($_SERVER["QUERY_STRING"], "folder=" . urlencode($folder . $this->vars["folder_name"] . "/") . "&project=" . $this->project . "&task=" . $this->task . "&nocache=true", array("file_id","faction","errmess","folder")));
                    } else {
                        $error_permissions_mod = true;
                    }
                } else { // ADD FILE
                    if (is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $this->vars["folder_name"])) {
                        $error_exists = true;
                    } else {
                        // first check if there are permissions to write into current folder
//                        echo "<!-- F: $ffolder -->\n";
                        if (substr($ffolder, -1) == "/") {
                            $tfolder = substr($tfolder, 0, -1);
                        }
                        $parent_folder = substr($tfolder, 0, strrpos($tfolder, "/"));
                        $parent_name = substr($tfolder, strrpos($tfolder, "/"));
                        if (substr($parent_name, 0, 1) == "/") {
                            $parent_name = substr($parent_name, 1);
                        }
                        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $parent_folder . "' AND folders.name = '" . $parent_name . "'";
                        $sq->query($this->dbc, $sql);
                        if ($row_data = $sq->nextrow()) {
                            $fperm = $this->decodePermissions($row_data["permissions"]);
                        }

                        // checking if update of current folder is allowed
                        // 16/05/2006, Martin: if parent folder is empty then letting to add new folder without any restrictions
                        if ($this->hasPermissions($fperm, "w") || ($row_data["owner"] == "99" . $this->userid) || (!$parent_folder) || $myfolder) {
                            $sql = "INSERT INTO folders (name, folder, owner, lastmod, permissions) VALUES ('" . $this->vars["folder_name"] . "', '$ffolder', '99" . $this->userid . "', NOW(), '$permissions')";
                            $sq->query($this->dbc, $sql);

                            mkdir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $this->vars["folder_name"], 0777);
                            redirect(processQuery($_SERVER["QUERY_STRING"], "folder=" . urlencode($ffolder) . "&project=" . $this->project . "&task=" . $this->task . "&nocache=true", array("file_id","faction","errmess")));
                        } else {
                            $error_permissions_add = true;
                        }
                    }
                }
            }
        }
        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_filemanager_diradd.html";
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=filemanager&faction=diradd&folder=".$folder);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "filemanager";
            return "<!-- module filemanager cached -->\n" . $tpl->parse();
        }

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error"));
        if ($error_exists == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("folder_exists_error"));
        if ($permissions_changed == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("folder_permissions_changed"));
        if ($error_permissions_mod == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("folder_permissions_mod_error"));
        if ($error_permissions_add == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("folder_permissions_add_error"));
        if ($error_name == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("folder_name_error") . $this->allowed_symbols);

        // ####
        // form
/*
        if (isset($this->vars["folder"]) && substr($this->vars["folder"], -1) == "/") {
            $this->vars["folder"] = substr($this->vars["folder"], 0,-1);
        } else if (isset($this->vars["folder"]) && substr($this->vars["folder"], -1) != "/") {
            $this->vars["folder"] = $this->vars["folder"];
        }
*/
        $fields = array(
            "folder_name" => array("textinput", 40, 0, $this->vars["folder_name"]),
            "folder" => array("select", 0, 0, $this->vars["folder"]),
        );

        if ($ffolder) {
            $folder = $ffolder;
        }

        while (list($key, $val) = each($fields)) {
            if (sizeof($val) > 0) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val["5"];
                if ($faction == "dirmod" && $error != true && $key != "folder_name") {
                    $val[3] = $row_data[$key];
                }
                $f = new AdminFields("$key", $fdata);
                $field_data = $f->display($val[3]);
                if ($key == "folder") {
                    if ($folder == "myfolder") {
                        $field_data = $txt->display("myfolder");
                        $field_data .= "<input type=\"hidden\" name=\"folder\" value=\"myfolder\">";
                    } else {
                        $field_data = $folder;
                        if ($folder) {
                            $field_data .= "<input type=\"hidden\" name=\"folder\" value=\"" . $folder . "\">";
                        }
                    }
                }
                $tpl->addDataItem("FIELD_$key", $field_data);
                unset($fdata);
            }
        }

        $sql = "SELECT *, DATE_FORMAT(lastmod, '%d.%m.%Y %H:%i') as date FROM folders WHERE folders.name = '" . $this->vars["folder_name"] . "' AND folders.folder = '" . $folder . "'";
        $sq->query($this->dbc, $sql);
        if ($row_data = $sq->nextrow()) {
            $perm_array = $this->decodePermissions($row_data["permissions"]);
            if (substr($row_data["owner"], 0, 2) == "99") {
                $users = $this->getUsers();
                $tpl->addDataItem("LASTMOD", $row_data["date"] . ", " . $users[substr($row_data["owner"], 2)]);
            } else {
                $tpl->addDataItem("LASTMOD", "-");
            }
        } else {
            $tpl->addDataItem("LASTMOD", "-");
        }

        // finding permissions of a current folder
        $tfolder = $folder;
        if (substr($tfolder, -1) == "/") {
            $tfolder = substr($tfolder, 0, -1);
        }

        $ffolder = substr($tfolder, 0, strrpos($tfolder, "/"));
        $fname = substr($tfolder, strrpos($tfolder, "/"));
        if (substr($fname, 0, 1) == "/") {
            $fname = substr($fname, 1);
        }

        $sql = "SELECT * FROM folders WHERE folders.folder = '" . $ffolder . "' AND folders.name = '" . $fname . "'";
        $sq->query($this->dbc, $sql);
        if ($folder_data = $sq->nextrow()) {
            $fperm_array = $this->decodePermissions($folder_data["permissions"]);
        }

        $sql = "SELECT * FROM module_user_groups ORDER BY module_user_groups.name";
        $sq->query($this->dbc, $sql);
        while ($gdata = $sq->nextrow()) {
            $tpl->addDataItem("PERM.ID", $gdata["id"]);
            $tpl->addDataItem("PERM.NAME", $gdata["name"]);

            if ($perm_array[$gdata["id"]]["r"] || ($faction == "diradd" && $fperm_array[$gdata["id"]]["r"])) {
                $tpl->addDataItem("PERM.READ_CHECKED", "checked");
            }
            if ($perm_array[$gdata["id"]]["w"] || ($faction == "diradd" && $fperm_array[$gdata["id"]]["w"])) {
                $tpl->addDataItem("PERM.WRITE_CHECKED", "checked");
            }
            if ($perm_array[$gdata["id"]]["d"] || ($faction == "diradd" && $fperm_array[$gdata["id"]]["d"])) {
                $tpl->addDataItem("PERM.DELETE_CHECKED", "checked");
            }
        }

        if ($faction == "diradd") {
            $tpl->addDataItem("BUTTON", $txt->display("button_diradd"));
        } else {
            $tpl->addDataItem("BUTTON", $txt->display("button_dirmod"));
        }

        $tpl->addDataItem("HIDDEN", "<input type=hidden name=\"faction\" value=\"$faction\">\n<input type=hidden name=\"write\" value=\"true\">\n<input type=hidden name=\"folder_name_old\" value=\"" . $this->vars["folder_name"] . "\">\n");
        $tpl->addDataItem("SELF", processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "mode=" . $this->mode, array("folder", "project", "task", "mode", "filter_file", "filter_type", "file_id", "faction", "errmess")));

        // ####
        return $tpl->parse();
    }

// ########################################
    // Get file information

    function doFile ($id, $obj, $text, $type, $date, $folder, $list) {

        if ($list == "list") {
            if ($type == "gif" || $type == "jpg" || $type == "png" || $type == "tif") {
                $icon = "<img src=\"img/files/icosmall_image.gif\" align=absmiddle border=0 alt=\"$text\">";
                $url = "javascript:openPicture('" . $this->start_url . $folder . $obj . "." . $type . "');";
            }
            else {
//              $url = "javascript:openFile('" . $this->start_url . $folder . $obj . "." . $type . "');";
                $url = $this->start_url . $folder . $obj . "." . $type;
                if (file_exists(SITE_PATH . "/img/files/icosmall_" . strtolower($type) . ".gif")) {
                    $icon = "<img src=\"img/files/icosmall_".strtolower($type). ".gif\" align=absmiddle border=0 alt=\"$text\">";
                }
                else {
                    $icon = "<img src=\"img/files/icosmall_other.gif\" align=absmiddle border=0 alt=\"$text\">";
                }
            }
        }
        else if ($list == "icon") {
            if ($type == "gif" || $type == "jpg" || $type == "png") {
                // THUMB Exists
                if (file_exists($this->start_folder . $folder . $obj . "_thumb." . $type)) {
                    $icon = "<img src=\"".$this->start_url . $folder . $obj . "_thumb." . $type."\" align=absmiddle border=0 alt=\"$text\">";
                }
                else {
                    $icon = "<img src=\"img/files/ico_image.gif\" alt=\"$text\" border=0>";
                }
                $url = "javascript:openPicture('" . $this->start_url . $folder . $obj . "." . $type . "');";
            }
            else {
//              $url = "javascript:openFile('" . $this->start_url  . $folder . $obj . "." . $type . "');";
                $url = $this->start_url  . $folder . $obj . "." . $type;
                if (file_exists(SITE_PATH . "/img/files/ico_" . strtolower($type) . ".gif")) {
                    $icon = "<img src=\"img/files/ico_".strtolower($type). ".gif\" alt=\"$text\" border=0>";
                }
                else {
                    $icon = "<img src=\"img/files/ico_other.gif\" alt=\"$text\" border=0>";
                }
            }
        }
        if (file_exists($this->start_folder  . $folder . $obj . "." . $type)) {
            $size = $this->getSize($this->start_folder  . $folder . $obj . "." . $type);
        }
        else {
            $size = "? kb";
        }

        if ($text == "") $text = "&nbsp;";

        $del_url = processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "faction=delete&folder=".urlencode($folder)."&project=".$this->project."&task=".$this->task."&file_id=".urlencode($obj . "." . $type), array("project","task","folder","faction","file","errmess"));
        $mod_url = processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "faction=modify&folder=".urlencode($folder)."&project=".$this->project."&task=".$this->task."&file_id=".urlencode($obj . "." . $type), array("project","task","folder","faction","file","errmess"));

        return array($icon, "$obj.$type", $text, $size, $date, $url, $del_url, $mod_url);
    }

// ########################################

    function doFolder($fold, $fold_name) {
        $folder = @$this->vars["folder"];

        if ($fold_name == "..")  {
            $icon =  "<img src=\"img/files/fold_up.gif\" align=absmiddle border=0 alt=\"$fold\">";
        }
        else {
            $icon = "<img src=\"img/files/closefold.gif\" align=absmiddle border=0 alt=\"$fold\">";
        }
        if ($folder && $fold && $fold_name != "..") $fold = $folder . $fold;
        if ($fold != "" && substr($fold, 0, -1) != "/") $fold .= "/";
        $url = processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "folder=" . urlencode($fold), array("folder","errmess"));

        return array($icon, $fold_name, $fold_name, $url);
    }

// ########################################
    // Checks if folder is empty

    function isFolderEmpty($dir) {
        $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
            while ($file = @readdir($dh)){
                if ($file != "." && $file != "..") {
                    return false;
                }
            }
        return true;
    }

// ########################################
    // Get hierarchial folder list

    function parseFolder($dir, $folder_list) {
        $sq = new sql;

        $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $dir);
            while ($file=@readdir($dh)){
                if ($file != "." && $file != ".." && !eregi("^projects", $file) && !eregi("^tasks", $file) && !in_array($file, $this->protected) && is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $dir . $file)) {
                    if ($dir) {
                        $final = $dir . $file . "/";
                    } else {
                        $final = $file . "/";
                    }

                    // check if all folders are in database table ...
                    $sql = "SELECT * FROM folders WHERE folders.name = '$file' AND folders.folder = '$dir'";
                    $sq->query($this->dbc, $sql);
                    if ($data = $sq->nextrow()) {
                        $perm = $this->decodePermissions($data["permissions"]);
                        if ($this->hasPermissions($perm, "r") || ($data["owner"] == "99" . $this->userid) || !$this->userid) {
                            $folder_list[$final] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/") - 2) . $file;
                            $folder_list = $this->parseFolder($final, $folder_list);
                        }
                    }
                }
            }
        return $folder_list;
    }


// ########################################
    // Get hierarchial folder list in json form

    function getFoldersJSON($dir, $parent, $general_url, $active_folder) {
        $sq = new sql;
        $folder_string = "";

        $tmpperm = $this->getAllPermissions();
        $allperm = $this->encodePermissions($tmpperm[0], $tmpperm[1], $tmpperm[2]);
        $cnt = 0;

        if (!$parent) $parent = "ROOT";
        $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
        while ($file=@readdir($dh)){
            if ($file != "." && $file != ".." && !eregi("^projects", $file) && !eregi("^tasks", $file) && !in_array($file, $this->protected) && is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir."/".$file)) {
                $cnt++;
                if ($dir) {
                    $final = $dir . $file . "/";
                } else {
                    $final = $file . "/";
                }

                if ($folder_string) {
                    $folder_string .= ",\n";
                }
                $folder_string .= "{\"text\" : \"" . $file . "\", \"id\" : " . $cnt . ", \"leaf\" : false, \"cls\" : \"folder\", \"href\" : \"" . $this->escape_url($general_url . "&folder=" . $final) . "\"";

                if (!strncmp($final . "/", $active_folder, strlen($final))) {
                    $folder_string .= ", \"expanded\" : true";
                }

                // check if all folders are in database table ...
                $sql = "SELECT * FROM folders WHERE folders.name = '$file' AND folders.folder = '$dir'";
                $sq->query($this->dbc, $sql);
                if ($data = $sq->nextrow()) {
                    $perm = $this->decodePermissions($data["permissions"]);
                    if ($perm[$this->groupid]["r"] || ($data["owner"] == "99" . $this->userid)) {
                        $folder_children = $this->getFoldersJSON($final, $final, $general_url, $active_folder);
//                        if ($folder_children) {
                            $folder_string .= ", \"children\" : [\n";
                            $folder_string .= $folder_children;
                            $folder_string .= "]\n";
//                        }
                    }
                } else {
                    // ... if not then create record into folders table
                    $sql = "INSERT INTO folders (name, folder, owner, lastmod, permissions) VALUES ('$file', '$dir', 0, NOW(), '$allperm')";
                    $sq->query($this->dbc, $sql);
                    $folder_children = $this->getFoldersJSON($final, $final, $general_url, $active_folder);
                    if ($folder_children) {
                        $folder_string .= ", \"children\" : [\n";
                        $folder_string .= $folder_children;
                        $folder_string .= "]\n";
                    }
                }
                $folder_string .= "}";
            }
        }
        return $folder_string;
    }

    function escape_url($url) {
        return str_replace("/", "\/", $url);
    }

// ########################################
    // Get hierarchial folder list

    function getFolders($dir, $folder_list, $parent) {
        $sq = new sql;

        $tmpperm = $this->getAllPermissions();
        $allperm = $this->encodePermissions($tmpperm[0], $tmpperm[1], $tmpperm[2]);

        if (!$parent) $parent = "ROOT";
        $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
            while ($file=@readdir($dh)){
                if ($file != "." && $file != ".." && !eregi("^projects", $file) && !eregi("^tasks", $file) && !in_array($file, $this->protected) && is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir."/".$file)) {
                    if ($dir) $final = $dir."/".$file;
                    else { $final = $file; }
                    $folder_list[$parent][] = array($final, $file);

                    // check if all folders are in database table ...
                    $sql = "SELECT * FROM folders WHERE folders.name = '$file' AND folders.folder = '$dir'";
                    $sq->query($this->dbc, $sql);
                    if ($data = $sq->nextrow()) {
                        $perm = $this->decodePermissions($data["permissions"]);
                        if ($this->hasPermissions($perm, "r") || ($data["owner"] == "99" . $this->userid)) {
                            $folder_list = $this->getFolders($final, $folder_list, $final);
                        }
                    } else {
                        // ... if not then create record into folders table
                        $sql = "INSERT INTO folders (name, folder, owner, lastmod, permissions) VALUES ('$file', '$dir', 0, NOW(), '$allperm')";
                        $sq->query($this->dbc, $sql);
                        $folder_list = $this->getFolders($final, $folder_list, $final);
                    }
                }
            }
        return $folder_list;
    }

// ########################################
    // File size

    function getSize($file){
        $s=filesize($file);
        if($s>1024){
            $s=round($s/1024);
            return "$s KB";
        }
        if($s>1024*1024){
            $s=round($s/(1024*1024));
            return "$s MB";
        }
        return "$s B";
    }

    function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val{strlen($val)-1});
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

// ########################################
    // File name

    function getName($file) {
        return substr($file,0,strrpos($file, '.'));
    }

// ########################################
    // File type

    function getTyp($file) {
        return strtolower(substr($file,(strrpos($file, '.')+1),4));
    }

// ########################################
    // Check file name against filter

    function checkFilter($file, $filter) {
        if ($filter != "") {
            if (eregi($filter, $file)) { return true; }
            else { return false; }
        }
        else { return true; }
    }

// ########################################
    // Check type

    function checkType($file, $mode) {
        if (ereg("^\.", $file) || ereg("\.php", $file)) {
            return false;
        }
        else {
            if ($mode == "all") {
                return true;
            }
            else if ($mode == "pic") {
                if ($this->getTyp($file) == "gif" || $this->getTyp($file) == "jpg" || $this->getTyp($file) == "png") {
                    return true;
                }
                else {
                    return false;
                }
            }
            else if ($mode == "nopic") {
                if ($this->getTyp($file) != "gif" && $this->getTyp($file) != "jpg" && $this->getTyp($file) != "png") {
                    return true;
                }
                else {
                    return false;
                }
            }
            else {
                if ($this->getTyp($file) == addslashes($mode)) {
                    return true;
                }
                else {
                    return false;
                }
            }
        }
    }

// ########################################
    // check if giver folder is allowed
    function folderAllowed($folder) {
        // check if it's one of myfolder's then wether it's current user's myfolder
        if (substr($folder, 0, strlen($this->myfolder)) == $this->myfolder) {
            if ($folder == $this->myfolder . "/" . $this->username) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

// ########################################

// ########################################
    // check if given name of a folder of file is allowed
    function nameAllowed($name) {
        $name = trim($name);
        $allowed = array();

        for ($i = 0; $i < strlen($this->allowed_symbols); $i++) {
            $allowed[$i] = substr($this->allowed_symbols, $i, 1);
        }
        if (!$name) {
            return true;
        }
        // checking if all of the symbols are allowed
        for ($i = 0; $i < strlen($name); $i++) {
            if (!in_array(substr($name, $i, 1), $allowed)) {
                return false;
            }
        }
        return true;
    }

// ########################################

    function uploadFile($folder, $filedata_name, $filedata_size, $filedata, $desc, $overwrite, $image_size, $category, $keywords, $permissions, $type_id) {

        $sq = new sql;

        if ($folder && substr($folder, -1) != "/") {
            $folder .= "/";
        }

        $filedata_name = preg_replace('/ /', '_', $filedata_name);
        $filedata_name = ereg_replace("[^[:space:]a-zA-Z0-9*_.-]", "", $filedata_name);

        $filetype = substr($filedata_name, strrpos($filedata_name, '.') + 1);
        $filetype = substr($filetype,0,3);
        $filename = substr($filedata_name, 0, strrpos($filedata_name, '.'));

        if ($overwrite == true && $filedata_name && $filedata_size) {
            $sta = @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filedata_name);
            if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filename . "_thumb." . $filetype)) {
                @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filename . "_thumb." . $filetype);
            }
            if ($sta) {
                $sql = "DELETE FROM files WHERE type = '".addslashes($filetype)."' AND name = '".addslashes($filename)."' AND folder = '" . addslashes($folder) . "'"; // AND owner LIKE '99%'";
                $sq->query($this->dbc, $sql);
            }
        }

        if ($filedata_name && $filedata_size) {

            if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filedata_name)) {
                $filename = substr($filedata_name, 0, strrpos($filedata_name, '.'));
                $filetype = substr($filedata_name, strrpos($filedata_name, '.') + 1);
                $filetype = strtolower($filetype);
                $filetype = substr($filetype,0,3);
                if ($filetype == "php") exit;

                $check = $filename . $extra . "." . $filetype;
                while (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $check)) {
                    $extra++;
                    $check = $filename . $extra . "." . $filetype;
                }
                $up = $check;
                $filename = $filename . $extra;
            }
            else {
                $filename = substr($filedata_name, 0, strrpos($filedata_name, '.'));
                $filetype = substr($filedata_name, strrpos($filedata_name, '.') + 1);
                $filetype = strtolower($filetype);
                $filetype = substr($filetype,0,3);
                if ($filetype == "php") exit;
                $up = $filename . "." . $filetype;
            }

            $up = preg_replace('/ /', '%20', $up);

            if ($filetype == "gif" || $filetype == "jpg" || $filetype == "png") {
                $pilta=$filedata;
                $pilta_thumb=$pilta."_thumb";

                $stat1 = @system(IMAGE_CONVERT . " -geometry 120x100 $pilta $pilta_thumb", $kala);
                if ($image_size) {
                    $stat2 = @system(IMAGE_CONVERT . " -geometry \"".$image_size.">\" $pilta $pilta", $kala2);
                }

                $cp_status1 = @copy($pilta, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up);
                $cp_status2 = @copy($pilta_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filename . "_thumb." . $filetype);

                if (!$cp_status1 || !$cp_status2) {
                    trigger_error("Module 'filemanager' file upload/copy failed. Check file/folder permissions", E_USER_ERROR);
                    exit;
                }

            }
            else {
                $cp_status1 = @copy($filedata, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up);
                if (!$cp_status1) {
                    trigger_error("Module 'filemanager' file upload/copy failed. Check file/folder permissions", E_USER_ERROR);
                    exit;
                }
            }

            $sql = "INSERT INTO files (type, name, folder, text, owner, add_date, lastmod, cat_list, keywords, permissions, type_id) VALUES ('".addslashes($filetype)."', '".addslashes($filename)."', '".addslashes($folder)."', '".addslashes($desc)."',99".$this->userid.", NOW(), now(), '" . addslashes($category) . "', '" . addslashes($keywords) . "', '" . $permissions . "', '" . addslashes($type_id) . "')";
            $sq->query($this->dbc, $sql);

            return array(SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up, SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $filename . "_thumb." . $filetype);
        }
        else {
            return array();
        }
    }

// ########################################
    // Create permission string
    function encodePermissions($read, $write, $delete) {
        $perm = array();
        $perm_list = "";
        if (is_array($read)) {
            while (list($key, $val) = each($read)) {
                if ($val) {
                    $perm[$key]["r"] = 1;
                }
            }
        }
        if (is_array($write)) {
            while (list($key, $val) = each($write)) {
                if ($val) {
                    $perm[$key]["w"] = 1;
                }
            }
        }
        if (is_array($delete)) {
            while (list($key, $val) = each($delete)) {
                if ($val) {
                    $perm[$key]["d"] = 1;
                }
            }
        }
        // creating encoded list from permission array
        while (list($key, $val) = each($perm)) {
            if ($perm_list) {
                $perm_list .= ";";
            }
            $perm_list .= $key;
            $perm_list .= ":" . ($val["r"] ? "1" : "0");
            $perm_list .= "," . ($val["w"] ? "1" : "0");
            $perm_list .= "," . ($val["d"] ? "1" : "0");
        }
        return $perm_list;
    }

// ########################################
    // decodes permission strin into array
    function decodePermissions($perm_str) {
        $tmp_perm = explode(";", $perm_str);
        $perm = array();

        for ($i = 0; $i < sizeof($tmp_perm); $i++) {
            $tmp_gp = explode(":", $tmp_perm[$i]);
            $tmp_p = explode(",", $tmp_gp[1]);
            $perm[$tmp_gp[0]]["r"] = $tmp_p[0];
            $perm[$tmp_gp[0]]["w"] = $tmp_p[1];
            $perm[$tmp_gp[0]]["d"] = $tmp_p[2];
        }

        return $perm;
    }


// ########################################
    // returns array of all permissions
    function getAllPermissions() {
        $sq = new sql;

        $sql = "SELECT id FROM module_user_groups";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $read[$data["id"]] = 1;
            $write[$data["id"]] = 1;
            $delete[$data["id"]] = 1;
        }

        return array($read, $write, $delete);
    }

// ########################################
    // checks if permission is granted
    function hasPermissions($perm, $type) {
        for ($i = 0; $i < sizeof($this->groups); $i++) {
            if ($perm[$this->groups[$i]][$type]) {
                return true;
            }
        }
        return false;
    }

// ########################################
    // Create category-list string
    function encodeCategory($category) {
        $cat = array();
        $cat_list = "";
        if (is_array($category)) {
            while (list($key, $val) = each($category)) {
                $cat[] = $key;
            }
        }
        // creating encoded list from category array
        while (list($key, $val) = each($cat)) {
            $cat_list .= "," . $val;
        }
        if ($cat_list) {
            $cat_list .= ",";
        }
        return $cat_list;
    }

// ########################################
    // Get all users
    function decodeCategory($cat_str) {
        $tmp_cat = explode(",", $cat_str);
        $cat = array();

        for ($i = 0; $i < sizeof($tmp_cat); $i++) {
            if ($tmp_cat[$i]) {
                $cat[] = $tmp_cat[$i];
            }
        }

        return $cat;
    }

// ########################################
    // Get all users

    function getUsers() {
        $sq = new sql;
        $sq->query($this->dbc, "SELECT user, name FROM module_user_users");
        while ($data = $sq->nextrow()) {
            $users[$data["user"]] = $data["name_first"] . ' ' . $data["name_last"];
        }
        $sq->free();
        return $users;
    }

// ########################################
    // Check if the needed folder exists, create it if not

    function checkFolder($folder) {
        if (!file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder) && !is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder)) {
            mkdir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder, 0777);
        }
    }

// ########################################
    // #####################
    // last n added files
    function lastAdded($qty = 10, $tmpl = "") {
        $sq = new sql;
        $txt = new Text($this->language, "module_filemanager");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . ($tmpl ? $tmpl : "module_filemanager_last_added_menu.html");
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=filemanager&mode=last&folder=".$folder);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "filemanager";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module filemanager cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        if ($this->userid) {
            $begin_folder = $this->module_param["filemanager"];
            $this->start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;
            $this->start_url = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;

            $files = array();
            $sql = "SELECT files.id, CONCAT(files.name, \".\", files.type) as file, files.text, files.folder, files.permissions, files.owner, files.add_date, files.lastmod, IF(module_user_users.user, CONCAT(module_user_users.name_first, ' ', module_user_users.name_last), '') AS owner_name FROM files LEFT JOIN module_user_users ON files.owner = CONCAT('99', module_user_users.user) WHERE (LOWER(files.name) LIKE LOWER('%" . $search . "%') OR LOWER(files.text) LIKE LOWER('%" . $search . "%')) AND files.folder LIKE '" . addslashes($begin_folder) . "%' ORDER BY files.add_date DESC LIMIT $qty";

            $sq->query($this->dbc, $sql);
            while ($data = $sq->nextrow()) {
                $perm = $this->decodePermissions($data["permissions"]);

                if ($this->folderAllowed($data["folder"]) && $perm[$this->groupid]["r"] || ($data["owner"] == "99" . $this->userid)) {
                    $desc[$data["id"]] = array(
                        $data["text"],
                        $data["folder"] . "/",
                        $perm[$this->groupid]["r"],
                        $perm[$this->groupid]["w"],
                        $perm[$this->groupid]["d"],
                        $data["add_date"],
                        $data["owner"],
                        $data["owner_name"],
                        $data["lastmod"]
                    );
                    $files[$data["id"]] = $data["file"];
                }
            }

            $sq->free();
            // #######################
//            asort($files);
            reset($files);

            // Parse the files
            while (list($file_id, $file_name) = each($files)) {
                $obj = $this->getName($file_name);
                $text = $desc[$file_id][0];
                $type = $this->getTyp($file_name);
                $id = $file_id;
                $opendir = $this->start_folder . $desc[$file_id][1];
                // only showing filest that really exist
                if (file_exists($opendir . $file_name)) {
                    $date = date ("d.m.Y", @filemtime($opendir . $file_name));
                    $file_folder = $desc[$file_id][1];

                    $ar = array();
                    $ar = $this->doFile($id, $obj, $text, $type, $date, $file_folder, $this->mode);

                    $f_name = substr($ar[1], 0, strrpos($ar[1], '.'));
                    $f_type = substr($ar[1], strrpos($ar[1], '.') + 1);
                    if (strlen($f_name) > 16) $ar[1] = substr($f_name,0,16)."_.".$f_type;

                    if ($style == "even") {
                        $style = "";
                    } else {
                        $style = "even";
                    }

                    $tpl->addDataItem("LAST_ADDED.STYLE", $style);
                    $tpl->addDataItem("LAST_ADDED.ICON", str_replace("//", "/", str_replace("://", ":///", $ar[0])));
                    $tpl->addDataItem("LAST_ADDED.URL", str_replace("//", "/", str_replace("://", ":///", $ar[5])));
                    if ($desc[$file_id][4] || $myfolder || $desc[$file_id][6] == "99" . $this->userid) {
                        $tpl->addDataItem("LAST_ADDED.DELETE.URL_DELETE", $ar[6]);
                    }
                    if ($desc[$file_id][3] || $myfolder || $desc[$file_id][6] == "99" . $this->userid) {
                        $tpl->addDataItem("LAST_ADDED.MODIFY.URL_MODIFY", $ar[7]);
                    }
                    $tpl->addDataItem("LAST_ADDED.NAME_OWNER", $ar[1] . ($desc[$file_id][7] ? (" / " . $desc[$file_id][7]) : ""));
                    $tpl->addDataItem("LAST_ADDED.NAME", $ar[1]);
                    $tpl->addDataItem("LAST_ADDED.TEXT", $ar[2]);
                    $tpl->addDataItem("LAST_ADDED.SIZE", $ar[3]);
                    $tpl->addDataItem("LAST_ADDED.LASTMOD", date("d.m.Y", strtotime($desc[$file_id][8])));
                    $tpl->addDataItem("LAST_ADDED.ADD_DATE", date("d.m.Y", strtotime($desc[$file_id][5])));
                    $tpl->addDataItem("LAST_ADDED.ID", $id);
                }
            }
        }

        // ####
        return $tpl->parse();
    }

// ########################################
    // Checks if browser supports multi upload in https mode
    function multiUploadSupport() {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!$_SERVER["HTTPS"] || $_SERVER["HTTPS"] && stristr($agent, "firefox") === false) {
            return true;
        }
        return false;
    }

// ########################################
    // #####################
    // global site search interface
    function global_site_search($search) {
        $sq = new sql;
        $txt = new Text($this->language, "module_filemanager");

        // creating array for search result
        $result = array(
            "title" => $txt->display("module_title"), // module title
            "fields" => array("name", "text", "add_date", "lastmod", "style", "size", "lastmod", "icon"), // array of fields with according titles
            "values" => array() // array of values will be stored here
        );

        if ($this->userid) {
            $begin_folder = $this->module_param["filemanager"];
            $this->start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;
            $this->start_url = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;

            $files = array();
            $sql = "SELECT files.id, CONCAT(files.name, \".\", files.type) as file, files.text, files.folder, files.permissions, files.owner, files.add_date, files.lastmod, IF(module_user_users.user, CONCAT(module_user_users.name_first, ' ', module_user_users.name_last), '') AS owner_name FROM files LEFT JOIN module_user_users ON files.owner = CONCAT('99', module_user_users.user) WHERE (LOWER(files.name) LIKE LOWER('%" . $search . "%') OR LOWER(files.text) LIKE LOWER('%" . $search . "%')) AND files.folder LIKE '" . addslashes($begin_folder) . "%' ORDER BY file ASC";
            $sq->query($this->dbc, $sql);
            while ($data = $sq->nextrow()) {
                $perm = $this->decodePermissions($data["permissions"]);

                if ($this->folderAllowed($data["folder"]) && $this->hasPermissions($perm, "r") || ($data["owner"] == "99" . $this->userid)) {
                    $desc[$data["id"]] = array(
                        $data["text"],
                        $data["folder"] . "/",
                        $this->hasPermissions($perm, "r"),
                        $this->hasPermissions($perm, "w"),
                        $this->hasPermissions($perm, "d"),
                        $data["add_date"],
                        $data["owner"],
                        $data["owner_name"],
                        $data["lastmod"]
                     );
                    $files[$data["id"]] = $data["file"];
                }
            }

            $sq->free();

            // #######################
            asort($files);
            reset($files);

            $row = 0;

            // Parse the files
            while (list($file_id, $file_name) = each($files)) {
                $obj = $this->getName($file_name);
                $text = $desc[$file_id][0];
                $type = $this->getTyp($file_name);
                $id = $file_id;
                $opendir = $this->start_folder . $desc[$file_id][1];
                // only showing filest that really exist
                if (file_exists($opendir . $file_name)) {
                    $date = date ("d.m.Y", @filemtime($opendir . $file_name));
                    $file_folder = $desc[$file_id][1];

                    $ar = array();
                    $ar = $this->doFile($id, $obj, $text, $type, $date, $file_folder, $this->mode);

                    $f_name = substr($ar[1], 0, strrpos($ar[1], '.'));
                    $f_type = substr($ar[1], strrpos($ar[1], '.') + 1);
                    if (strlen($f_name) > 16) $ar[1] = substr($f_name,0,16)."_.".$f_type;

                    if ($style == "even") {
                        $style = "";
                    } else {
                        $style = "even";
                    }

                    $result["values"][$row]["url"] = str_replace("//", "/", str_replace("://", ":///", $ar[5]));
                    $result["values"][$row]["style"] = $style;
                    $result["values"][$row]["icon"] = str_replace("//", "/", str_replace("://", ":///", $ar[0]));
                    $result["values"][$row]["name"] = $ar[1] . ($desc[$file_id][7] ? (" / " . $desc[$file_id][7]) : "");
                    $result["values"][$row]["text"] = $ar[2];
                    $result["values"][$row]["size"] = $ar[3];
                    $result["values"][$row]["lastmod"] = date("d.m.Y", strtotime($desc[$file_id][8]));
                    $result["values"][$row]["add_date"] = date("d.m.Y", strtotime($desc[$file_id][5]));
                    $row++;
                }
            }
        }

        return $result;
    }

// ########################################

    /**
     * Check does the active user have access to the page/form
     * @access private
     * @return boolean
     */

    function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

    // #####################
    // functions for content management

    function getParameters() {
        $sq = new sql;
        $module_param = $GLOBALS["pagedata"]["module"];
        if (strpos($module_param, "filemanager") === false) {
            $sql = "SELECT module FROM content WHERE template = 120 AND language = '" . addslashes($this->language) . "' LIMIT 1";
            $sq->query($this->dbc, $sql);
            if ($data = $sq->nextrow()) {
                $module_param = $data["module"];
            }
        }
        $ar = split(";", $module_param);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    function moduleOptions() {
        $sq = new sql;
        $txt = new Text($this->language, "module_filemanager");
        $list[""] = $txt->display("folder");
        $list = $this->parseFolder("/", $list);
        $list2 = array("0" => $txt->display("show_parent"), "1" => $txt->display("show_children"));
        $list3 = array("0" => $txt->display("hide_myfolder"), "1" => $txt->display("show_myfolder"));
        $list4 = array("0" => $txt->display("no_recursive_permissions"), "1" => $txt->display("recursive_permissions"));
        $list5 = array("0" => $txt->display("hide_file_permissions"), "1" => $txt->display("show_file_permissions"));
        return array($txt->display("start_folder"), "select", $list, $txt->display("folder_tree"), "select", $list2, $txt->display("private_folder"), "select", $list3, $txt->display("recursive_permissions"), "select", $list4, $txt->display("file_permissions"), "select", $list5);
        // name, type, list
    }
}
