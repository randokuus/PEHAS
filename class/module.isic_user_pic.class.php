<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");

class isic_user_pic {
    /**
     * @var array merged array with _GET and _POST data
     */
    var $vars = array();
    /**
     * Current language code
     *
     * @var string
     * @access protected
     */
    var $language;
    /**
     * Active template set
     *
     * @var int
     * @access private
     */
    var $tmpl;
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;
    /**
     * @var boolean does this module provide additional parameters to admin page admin
     */
    var $content_module = false;
    /**
     * @var array additional parameters set at the page admin for this template
     */
    var $module_param = array();
    /**
     * @var integer active user id
     */
    var $userid = false;
    /**
     * @var integer user group ID
     */
    var $usergroup = false;
    /**
     * @var array user groups
     */
    var $usergroups = false;
    /**
     * @var user type (1 - can view all cards from the school his/her usergroup belongs to, 2 - only his/her own cards)
     */
    var $user_type = false;

    /**
     * @var user code (personal number (estonian id-code for example))
     */
    var $user_code = false;
    /**
     * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
     */
    var $user_access = 0;
    /**
     * Users that are allowed to access the same cards as current user
     *
     * @var array
     * @access protected
     */
    var $allowed_users = array();
    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */
    var $cachelevel = TPL_CACHE_NOTHING;
    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */
    var $cachetime = 1440;
    /**
     * @var string temp-folder for uploaded pictures
     */
    var $folder = "user_tmp/";
    /**
     * @var string real folder for isic pictures
     */
    var $folder_real = "user/";
    /**
     * @var string picture size
     */
    var $pic_size = '307x372';
    var $pic_size_x = '307';
    var $pic_size_y = '372';
    /**
     * @var string picture thumb size
     */
    var $pic_size_thumb = '83x100';
    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_user";

    var $isicDbUsers = false;
   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function isic_user_pic() {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        // assigning common methods class
        $this->isic_common = IsicCommon::getInstance();

        //$this->allowed_users = $this->createAllowedUsers();
        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->allowed_groups = $this->isic_common->allowed_groups;
        $this->isicDbUsers = IsicDB::factory('Users');
    }

    /**
     * Main module display function
     *
     * @return string html isic_user_pic content
    */

    function show() {
        if ($this->checkAccess() == false) return "";

        $faction = @$this->vars["faction"];

        if (!$this->userid) {
            trigger_error("Module 'isic_user_pics' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == 2) {
            return $this->isic_common->showErrorMessage("error_picture_import_not_allowed", $this->translation_module_default);
        }
        if ($faction == "prepare_upload") {
            $result = $this->prepareUpload();
        }
        else if ($faction == "finish_upload") {
            $result = $this->finishUpload();
        }
        else if ($faction == "save_pics") {
            $result = $this->finishUpload();
        }
        else if ($faction == "savefile") {
            $result = $this->saveFile();
        } else {
            $result = $this->addFile();
        }
        return $result;
    }


    /**
     * Displays add form for the upload
     *
     * @return string html addform for pictures
    */

    function addFile() {
        $content = @$this->vars["content"];

        $txt = new Text($this->language, "module_isic_user_pic");

        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if ($this->multiUploadSupport()) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_user_pic_madd.html";
        } else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_user_pic_sadd.html";
        }

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic_user_pic&faction=add");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic_user_pic";
            return "<!-- module isic_user_pic cached -->\n" . $tpl->parse();
        }

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error"));

        // ####
        // form

        $fields = array(
            "file" => array("file",40,0,""),
        );

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

        $tpl->addDataItem("BUTTON", $txt->display("button_save"));
        $hidden = "<input type=\"hidden\" name=\"max_size\" value=\"" . $this->return_bytes(ini_get("upload_max_filesize")) . "\">\n";
        $hidden .= "<input type=\"hidden\" name=\"form_id\" value=\"" . rand(0, 99999999) . "\">\n";
        $hidden .= "<input type=hidden name=\"faction\" value=\"\">\n";
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("faction")));

        // ####
        return $tpl->parse();
    }

    /**
     * Simple debug-method
    */

    function log_faction($data) {
        if ($fp_log = fopen(SITE_PATH . "/isic_user_pic.txt", "a+")) {
            fwrite($fp_log, date("d.m.Y H:i:s") . ": " . $data . "\n");
            fclose($fp_log);
        }
    }

    /**
     * Preparing upload and setting post-variables in session variable
    */

    function prepareUpload() {
        //$this->log_faction("prepareupload");
        unset($_POST["faction"]);
        $_SESSION["upload"][$_POST['form_id']] = $_POST;
        exit();
    }

    /**
     * Saves uploaded files one-by-one
    */

    function saveFile() {
        //$this->log_faction("savefile");
        if (isset($_FILES['Filedata'])) {
            $_POST = $_SESSION["upload"][$_GET['form_id']];
            $new_name = SITE_PATH . "/cache/upl_" . session_id() . "_" . $_GET["file"];
            move_uploaded_file($_FILES['Filedata']['tmp_name'], $new_name);
            $_FILES['Filedata']['tmp_name'] = $new_name;
            foreach ($_POST as $k => $v) {
                $$k = $v;
            }

            $file_name = $_FILES['Filedata']["name"];
            $file_size = $_FILES['Filedata']["size"];
            $filedata  = $_FILES['Filedata']["tmp_name"];

            if ($up_data = $this->uploadFile($this->folder, $file_name, $file_size, $filedata, $this->pic_size, $this->pic_size_thumb)) {
                $_SESSION["upload"][$_GET['form_id']]["files"][] = $up_data;
            }
        }
        exit();
    }

    /**
     * Displays uploaded pictures with according persons and also saves new images to right folder
     *
     * @return string html form with pictures and persons
    */

    function finishUpload() {
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];

        $form_id = $_GET['form_id'] ? $_GET['form_id'] : $_POST['form_id'];
        if (isset($_SESSION["upload"][$form_id]['error'])) {
            // do nothing for now
        } else {
            $files_list = $_SESSION["upload"][$form_id]["files"];
//            unset($_SESSION["upload"][$form_id]);
        }

        $txt = new Text($this->language, "module_isic_user_pic");

        if ($write) {
            $pic_list = $this->vars["save_pic"];
            $pics_saved = 0;
            if (is_array($pic_list)) {
                $pic_save = 0;

                foreach ($pic_list as $pic_name => $val) {
                    $user_code = $pic_name;
                    $person_data = $this->isicDbUsers->getRecordByCode($user_code);

                    if ($person_data) {
                        $src_path = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;
                        $tar_path = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder_real;
                        $src_file = $src_path . $pic_name . ".jpg";
                        $src_file_thumb = $src_path . $pic_name . "_thumb.jpg";

                        if (is_file($src_file) && is_readable($src_file)) {
                            $pics_saved++;
                            $tar_fname = 'USER' . str_pad($person_data["user"], 10, '0', STR_PAD_LEFT) . ".jpg";
                            $tar_fname_thumb = 'USER' . str_pad($person_data["user"], 10, '0', STR_PAD_LEFT) . "_thumb.jpg";
                            $cp_status = @copy($src_file, $tar_path . $tar_fname);
                            $cp_status = @copy($src_file_thumb, $tar_path . $tar_fname_thumb);
                            $picName = "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder_real . $tar_fname;
                            $this->isicDbUsers->updateRecord($person_data['user'], array('pic' => $picName));
                        }
                    }
                }
                $success = true;
            } else {
                $error = true;
            }

            // deleting all the tmp-files
            for ($i = 0; $i < sizeof($files_list); $i++) {
                $fpath = pathinfo($files_list[$i]);
                $file_name = $fpath["basename"];
                $file_name_thumb = str_replace(".jpg", "_thumb.jpg", $fpath["basename"]);

                $src_path = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;
                @unlink($src_path . $file_name);
                @unlink($src_path . $file_name_thumb);
            }
        }

        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if ($write) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_user_pic_success.html";
        } else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_user_pic_list.html";
        }

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic_user_pic&faction=finish_upload");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic_user_pic";
            return "<!-- module isic_user_piccached -->\n" . $tpl->parse();
        }

        if ($error == true) {
            $tpl->addDataItem("ERROR.MESSAGE", $txt->display("save_error"));
        }

        if ($success == true) {
            $tpl->addDataItem("SUCCESS.MESSAGE", str_replace("<PICS_SAVED>", $pics_saved, $txt->display("data_saved")));
        }

        // ####
        // form

        if ($write) {
            // do nothing
        } else {
            if (is_array($files_list)) {
                $src_path = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folder;

                for ($i = 0; $i < sizeof($files_list); $i++) {
                    $fpath = pathinfo($files_list[$i]);
                    $fname = explode('.', $fpath["basename"]);
                    $user_code = $fname[0];
                    $person_data = false;

                    if ($user_code) {
                        $person_data = $this->isicDbUsers->getRecordByCode($user_code);
                    }

                    $tpl->addDataItem("ROW.ROW", $i + 1);
                    $tpl->addDataItem("ROW.PERSON_PIC", "<img src=\"" . $files_list[$i] . "\">");

                    if (is_array($person_data)) {
                        $pic_size = getimagesize($src_path . $fpath["basename"]);
                        if (($pic_size[0] + 1) >= $this->pic_size_x && ($pic_size[1] + 1) >= $this->pic_size_y) {
                            $fdata["type"] = "checkbox";
                            $f = new AdminFields("save_pic[" . $user_code . "]", $fdata);
                            $field_data = $f->display("1");
                        } else {
                            $field_data = $txt->display("pic_size_minimum") . ": " . $this->pic_size;
                        }

                        $tpl->addDataItem("ROW.PERSON_PIC_OLD", $person_data["pic"] ? "<img src=\"" . SITE_URL . $person_data["pic"] . "\">" : "-");
                        $tpl->addDataItem("ROW.PERSON_NAME", $person_data["name_first"] . ' ' . $person_data["name_last"]);
                        $tpl->addDataItem("ROW.USER_CODE", $person_data["user_code"]);
                        $tpl->addDataItem("ROW.SAVE_PIC", $field_data);
                    } else {
                        $tpl->addDataItem("ROW.PERSON_PIC_OLD", "-");
                        $tpl->addDataItem("ROW.PERSON_NAME", $txt->display("unknown_person"));
                        $tpl->addDataItem("ROW.USER_CODE", "-");
                        $tpl->addDataItem("ROW.SAVE_PIC", "-");
                    }
                }
            }

            $tpl->addDataItem("BUTTON", $txt->display("button_save"));
            $hidden .= "<input type=hidden name=\"faction\" value=\"finish_upload\">\n";
            $hidden .= "<input type=hidden name=\"write\" value=\"true\">\n";
            $hidden .= "<input type=hidden name=\"form_id\" value=\"" . $form_id . "\">\n";
            $tpl->addDataItem("HIDDEN", $hidden);
            $tpl->addDataItem("SELF", processURL($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("faction")));
        }

        // ####
        return $tpl->parse();
    }

    /**
     * Resizing file and copying it to specified folder
     *
     * @param string $folder folder to save uploaded image
     * @param string $filedata_name temp-name of the uploaded file
     * @param string $filedata_size size of the file
     * @param string $filedata original name of the uploaded file
     * @param string $image_size image size for imagemagick
     * @return string/boolean the path of the uploaded file of false if something went wrong
    */

    function uploadFile($folder, $filedata_name, $filedata_size, $filedata, $image_size, $thumb_size) {
        $filedata_name = preg_replace('/ /', '_', $filedata_name);
        $filedata_name = ereg_replace("[^[:space:]a-zA-Z0-9*_.-]", "", $filedata_name);

        $filetype = strtolower(substr($filedata_name, strrpos($filedata_name, '.') + 1));
        $filetype = substr($filetype,0,3);
        $filename = substr($filedata_name, 0, strrpos($filedata_name, '.'));

        if ($filedata_name && $filedata_size) {
            $up = $filename . "." . $filetype;
            $up = preg_replace('/ /', '%20', $up);
            $up_tn = $filename . "_thumb." . $filetype;
            $up_tn = preg_replace('/ /', '%20', $up_tn);

            if ($filetype == "jpg") {
                $pilta = $filedata . ".jpg";
                $pilta_thumb = $filedata . "_thumb.jpg";

                // trying to convert image into jpg no matter what was the extension of the file
                $command = IMAGE_CONVERT . " $filedata $pilta";
                exec($command, $_dummy, $return_val);

                if (!$return_val) {
                    $picsize = getimagesize($pilta);
                    if (is_array($picsize) && (image_type_to_mime_type($picsize[2]) == "image/jpeg")) {
                        if ($image_size) {
                            // http://www.imagemagick.org/Usage/resize/#fill
                            $command = IMAGE_CONVERT . " -resize \" {$image_size}^\" -gravity center -extent {$image_size} {$pilta} {$pilta}";
                            $stat = @system($command, $kala2);
                            $stat = @system(IMAGE_CONVERT . " -geometry \"" . $thumb_size . ">\" $pilta $pilta_thumb", $kala2);
                        }

                        $cp_status = @copy($pilta, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up);
                        $cp_status = @copy($pilta_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up_tn);

                        if ($cp_status) {
                            return SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $up;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if browser supports multi-upload
     *
     * @return boolean true if supports, false otherwise
    */

    function multiUploadSupport() {
        return true;
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!$_SERVER["HTTPS"] || $_SERVER["HTTPS"] && stristr($agent, "firefox") === false) {
            return true;
        }
        return false;
    }

    /**
     * Calculates given string into bytes
     *
     * @param string $val size as string
     * @return int size in bytes
    */

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

    /**
     * Returns module parameters array
     *
     * @return array module parameters
    */

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Creates array of module parameter values for content admin
     *
     * @return array module parameters
    */

    function moduleOptions() {
        $sq = new sql;
        return array();
        // name, type, list
    }
}
