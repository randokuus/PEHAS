<?php
/*
last modified 13.11.07 (martin)
version x.x

    <TPL_OBJECT:projects>
      <TPL_OBJECT_OUTPUT:show()>
    </TPL_OBJECT:projects>

*/

class projects {

    var $tmpl = false;
    var $debug = false;
    var $language = false;
    var $siteroot = false;
    var $vars = array();
    var $dbc = false;
    var $status = false;
    var $user = false;
    var $content_module = true;
    var $module_param = array();
    var $userid = false;
    var $usergroup = false;
    var $project = false;
    var $maxresults = 50;
    var $maxresults_front = 10;
    var $cachelevel = TPL_CACHE_NOTHING;
    var $cachetime = 43200; //cache time in minutes
    var $tplfile = "projects";
    var $project_people = array();
    var $project_people_team = array();
    var $project_data = array();
    var $project_tasks = array();
    var $info_name = "Modera Extranet";
    var $info_email = "support@modera.net";
    var $max_status = 9;
    var $max_priority = 3;

    function projects() {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $GLOBALS["language"];
        $this->debug = $GLOBALS["modera_debug"];
        if (!is_object($GLOBALS["db"])) { $db = new DB; $this->dbc = $db->connect(); }
        else { $this->dbc = $GLOBALS["db"]->con; }

        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        if ($GLOBALS["site_settings"]["name"]) $this->info_name = $GLOBALS["site_settings"]["name"];
        if ($GLOBALS["site_settings"]["admin_email"]) $this->info_email = $GLOBALS["site_settings"]["admin_email"];

        $this->getProject();
        if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->vars["task"] != "") $this->task = $this->vars["task"];

        // check do we have access to this page, fill arrays with project member data
        $this->initialiseProjects();
    }

    // ########################################

    function initialiseProjects() {
        $sq = new sql;

        // get people on projects
        $sq->query($this->dbc, "SELECT id, team, client, name FROM module_projects_main ORDER BY name");
        while ($data = $sq->nextrow()) {
            $team = array();
            $client = array();
            if ($data["team"] != "") {
                $team = split(",", $data["team"]);
            }
            else { $team = array(); }
            if ($data["client"] != "") {
                $client = split(",", $data["client"]);
            }
            $this->project_people[$data["id"]] = array_merge($team, array_diff($client, $team)); // all the names are shown only once
            $this->project_people_team[$data["id"]] = $team;
            $this->project_data[$data["id"]] = $data["name"];

        }
        $sq->free();

        if ($this->project) {
            $sql = "SELECT id, task FROM module_projects_task WHERE project = '" . $this->project . "' ORDER BY task";
            $sq->query($this->dbc, $sql);
            while ($data = $sq->nextrow()) {
                $this->project_tasks[$data["id"]] = $data["task"];
            }
        }
    }

    // ########################################

    function checkAccess($pagedata) {

        $module_pagedata = array();
        $ar = split(";", $pagedata);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $module_pagedata[$a[0]] = $a[1];
        }

        if ($module_pagedata["projects"] != "") {
            if (in_array($this->userid, $this->project_people[$module_pagedata["projects"]])) {
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

    // ########################################

    // Main function to call

    function show() {
        $add_task = $this->vars["add_task"];
        $task_id = $this->vars["task_id"];
        $history = $this->vars["history"];        

        // general project access
        if ($this->checkAccess($GLOBALS["pagedata"]["module"]) == false) {
            trigger_error("Module projects: Access denied !", E_USER_ERROR);
        }

        if ($add_task == "true") {
            $result = $this->addTask();
        }
        else if ($task_id && !$add_task && !$history) {
            if ($this->usergroup == 1) {
                $result = $this->showTaskEmployee($task_id);
            }
            else {
                $result = $this->showTask($task_id);
            }
        }
        else if ($task_id && $history && !$add_task) {
            $result = $this->showHistory($task_id);
        }
        else {
            $result = $this->showTasklist();
        }
        return $result;
    }

    // ########################################

    // Show list of tasks

    function showTasklist() {
        $content = $this->vars["content"];
        $start = $this->vars["start"];
        //global $year, $month, $day;

        $year = $this->vars["year"];
        $month = $this->vars["month"];
        $day = $this->vars["day"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);
        //if (!$this->project) doJump("");
        //if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->project) {
            if (is_array($this->project_people[$this->project])) {
                if (!in_array($this->userid, $this->project_people[$this->project])) doJump("");
            }
            else {
                doJump("");
            }
            $projectcheck = "project = " . $this->project . " ";
        }

        if (!$start) { $start = 0; }

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_tasklist.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=tasklist");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $ff_fields = array(
            "type","priority","status"
        );

        for ($f = 0; $f < sizeof($ff_fields); $f++) {
            if ($this->vars["filter_".$ff_fields[$f]] != "" && $this->vars["filter_".$ff_fields[$f]] != "0") {
                $condition .= $ff_fields[$f] . " = '" . addslashes($this->vars["filter_".$ff_fields[$f]]) . "' AND ";
                $url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($this->vars["filter_".$ff_fields[$f]]);
            }
        }

        if ($this->vars["filter_date1"] != "" && $this->vars["filter_date2"] != "") {
            $condition .= "date1 >= '" . addslashes($this->vars["filter_date1"]) . "' AND " . "date2 <= '" . addslashes($this->vars["filter_date2"]) . "' AND ";
            $url_filter .= "&filter_date1=" . urlencode($this->vars["filter_date1"]) . "&filter_date2=" . urlencode($this->vars["filter_date2"]);
        }
        else if ($this->vars["filter_date1"] != "" && $this->vars["filter_date2"] == "") {
            $condition .= "date1 >= '" . addslashes($this->vars["filter_date1"]) . "' AND ";
            $url_filter .= "&filter_date1=" . urlencode($this->vars["filter_date1"]);
        }
        if ($this->vars["filter_lastmod"] != "") {
            $condition .= "lastmod LIKE '" . addslashes($this->vars["filter_lastmod"]) . "%' AND ";
            $url_filter .= "&filter_lastmod=" . urlencode($this->vars["filter_lastmod"]);
        }

        // CURRENT DAY FILTER
        if ($year && $day && $month) {
            if ($year < 1990 || $year > 2030) $year = date("Y");
            if ($month < 10) $month1 = "0" . $month;
            else { $month1 = $month; }
            if ($day < 10) $day1 = "0" . $day;
            else { $day1 = $day; }
            $dtocheck = addslashes($year) . "-" . addslashes($month1) . "-" . addslashes($day1);
            $condition .= "(date1 LIKE '$dtocheck%' OR date2 LIKE '$dtocheck%' OR date3 LIKE '$dtocheck%' OR lastmod LIKE '$dtocheck%') AND";
            $url_filter .= "&year=" . addslashes($year) . "&month=" . addslashes($month) . "&day=" . addslashes($day);
        }

        // Project filter
        if (!$this->project) {
            while(list($key, $val) = each($this->project_people)) {
                if (in_array($this->userid, $val)) {
                    $cond[] = "project = '$key'";
                }
            }
            if (is_array($cond)) {
                $condition .= "(" . join(" OR ", $cond) .  ")";
            }
            else {
                // no projects found, nothing is returned

                //$condition .= "(project = 99899)";
                //doJump("");
                return "";
            }
        }
        reset($this->project_people);

        if ($this->vars["where_member"] == 1) {
            if ($condition) {
                $condition .= " AND ";
            }
            $condition .= " (team = '".$this->userid."' OR team LIKE '".$this->userid.",%' OR team LIKE '%,".$this->userid.",%' OR team LIKE '%,".$this->userid."')";
            $url_filter .= "&where_member=1";
            if ($this->project) {
                $condition .= " AND ";
            }
        }

        if (!$this->project && !$condition) $condition = "1 = 1";

        // ####

        $oo_order = array(
            "task","date1","date2","type","status","priority"
        );
        if ($this->vars["sort"] != "" && in_array($this->vars["sort"], $oo_order)) {
            $order_by = $this->vars["sort"];
            if ($this->vars["sort_order"] == "asc") {
                $sort_order = "asc";
                $sort_order1 = "desc";
            }
            else if ($this->vars["sort_order"] == "desc") {
                $sort_order = "desc";
                $sort_order1 = "asc";
            }
            else {
                $sort_order = "asc";
                $sort_order1 = "desc";
            }
        }

        if (!$order_by) {
            $order_by = "date1";
            $sort_order = "desc";
            $sort_order1 = "asc";
        }

        // ####
        // DATA

        $number = 1+$start;
        //$sql = "SELECT * FROM module_projects_task WHERE project = " . $this->project . " AND $condition (team LIKE '".$this->userid.",%' OR team LIKE '%,".$this->userid.",%' OR team LIKE '%,".$this->userid."' OR team = '' OR team = 0) ORDER BY $order_by LIMIT $start,50";
        $sql = "SELECT *, DATE_FORMAT(date1, '%d.%m.%y') as date1x, DATE_FORMAT(date2, '%d.%m.%y') as date2x FROM module_projects_task WHERE $condition $projectcheck ORDER BY $order_by $sort_order LIMIT $start," . $this->maxresults;
//        echo "<!-- " . $sql . "-->";
        $sq->query($this->dbc, $sql);
        if ($sq->numrows == 0) {
            $tpl->addDataItem("RESULTS", $txt->display("search_noresults"));
        }
        else {
            $results = $sq->numrows;
            while ($data = $sq->nextrow()) {

                    if ($style == "even") {
                        $style = "";
                    } else {
                        $style = "even";
                    }

                    switch ($data["priority"]) {
                        case 1:
                            $style2 = "lowpriority";
                        break;
                        case 2:
                            $style2 = "";
                        break;
                        case 3:
                            $style2 = "red";
                        break;
                        default:
                            $style2 = "";
                        break;
                    }

                    /*
                    if ($data["status"] != 2) {
                        $tpl->addDataItem("TASKS.STYLE", $style);
                    }
                    else {
                        if ($style) {
                            $tpl->addDataItem("TASKS.STYLE", $style . " finished");
                        }
                        else {
                            $tpl->addDataItem("TASKS.STYLE", "finished");
                        }
                    }
                    */

                    $tpl->addDataItem("TASKS.STYLE", $style . " " . $style2);
                    $tpl->addDataItem("TASKS.NR", $number);
                    $tpl->addDataItem("TASKS.DATA_TASK", $data["task"]);
                    $tpl->addDataItem("TASKS.DATA_DATE1", $data["date1x"]);
                    $tpl->addDataItem("TASKS.DATA_DATE2", $data["date2x"]);
                    $tpl->addDataItem("TASKS.DATA_TYPE", $txt->display("type".$data["type"]));
                    $tpl->addDataItem("TASKS.DATA_STATUS", $txt->display("status".$data["status"]));
                    $tpl->addDataItem("TASKS.DATA_PRIORITY0", $data["priority"]);
                    $tpl->addDataItem("TASKS.DATA_PRIORITY", $txt->display("priority".$data["priority"]));
                    $tpl->addDataItem("TASKS.URL_DETAIL", $_SERVER['PHP_SELF'] . "?content=$content&project=" . $data["project"] . "&task_id=" . $data["id"] . $url_filter . "&sort=$order_by&sort_order=" . $sort_order);
                    $number++;

            }
            $sq->free();
        }
        // ####

        // page listing
        $sql = "SELECT count(id) as totalus FROM module_projects_task WHERE $condition $projectcheck";
        $sq->query($this->dbc, $sql);
        $data = $sq->nextrow();
        $total = $data["totalus"];
        $sq->free();

        $disp = ereg_replace("{NR}", "$total", $txt->display("search_results"));
        if ($results >= $this->maxresults) $end = $start+$this->maxresults;
        else { $end = $start+$results; }
        if ($end == 0) $start0 = 0;
        else { $start0 = $start+1; }
        $disp = ereg_replace("{DISP}", $start0 . "-$end", $disp);
        $tpl->addDataItem("RESULTS", $disp);

        $tpl->addDataItem("PAGES", resultPages($start, $total, $_SERVER['PHP_SELF'] . "?content=$content&task_id=" . $data["id"] . $url_filter . "&sort=$order_by&sort_order=" . $sort_order, $this->maxresults, $txt->display("text_prev"), $txt->display("text_next")));

        // ####
        // filter form

            for ($u = 0; $u < $this->max_status; $u++) {
                if ($this->vars["filter_status"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_status .= "<option value=\"$u\" $sel>" . $txt->display("status".$u) . "</option>";
            }
            for ($u = 0; $u < 4; $u++) {
                if ($this->vars["filter_priority"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_priority .= "<option value=\"$u\" $sel>" . $txt->display("priority".$u) . "</option>";
            }
            for ($u = 0; $u < 3; $u++) {
                if ($this->vars["filter_type"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_type .= "<option value=\"$u\" $sel>" . $txt->display("type".$u) . "</option>";
            }
            if ($this->project && $this->vars["project"] == "") {
                $f_project = "<b>".$this->project_data[$this->project]."</b>";
            }
            else {
                $f_project .= "<option value=\"\">" . $txt->display("status0") . "</option>";
                while (list($key, $val) = each($this->project_people)) {
                    if (in_array($this->userid, $val)) {
                        if ($this->vars["project"] == $key) $sel = "selected";
                        else { $sel = ""; }
                        $f_project .= "<option value=\"$key\" $sel>" . $this->project_data[$key] . "</option>";
                    }
                }
                $f_project = "<select name=\"project\">". $f_project . "</select>";
            }

            if ($this->vars["where_member"] == "1") $tpl->addDataItem("wheremember", "checked");
            else { $tpl->addDataItem("wheremember", " "); }
            $tpl->addDataItem("select_status", $f_status);
            $tpl->addDataItem("select_priority", $f_priority);
            $tpl->addDataItem("select_type", $f_type);
            $tpl->addDataItem("select_project", $f_project);

        // ####
        // get url to files section

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 120 AND language = '" . addslashes($this->language) . "' AND visible = 1 LIMIT 1");
        if ($sq->numrows != 0) {
             $data = $sq->nextrow();
             $files_url .= "?content=" . $data["content"];
        }
        else {  $files_url = "#";   }
        $sq->free();

        // ####

        $tpl->addDataItem("url_files", $files_url . "&project=" . $this->project);
        $tpl->addDataItem("url_general", $_SERVER['PHP_SELF'] . "?content=$content" . $url_filter . "&project=".$this->project . "&sort_order=" . $sort_order1);
        $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content=$content");

        // ####
        return $tpl->parse();

    }

    // ########################################

    // Show list of tasks (frontpage)

    function showTasklistFront() {
        $content = $this->vars["content"];
        $start = $this->vars["start"];
        //global $year, $month, $day;

        $year = $this->vars["year"];
        $month = $this->vars["month"];
        $day = $this->vars["day"];

        if (!$start) { $start = 0; }

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_tasklist_front.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=tasklistfront");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // get url to projects section

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 200 AND language = '" . addslashes($this->language) . "' AND visible = 1 LIMIT 1");
        if ($sq->numrows != 0) {
             $data = $sq->nextrow();
             $general_url = $_SERVER['PHP_SELF'] . "?content=" . $data["content"];
        } else {
            $general_url = "#MODULE_PROJECTS_NOT_DEFINED_IN_CONTENT_TABLE";
        }
        $sq->free();

        // ####
        // DATA

        $condition = " (module_projects_task.team = '".$this->userid."' OR module_projects_task.team LIKE '".$this->userid.",%' OR module_projects_task.team LIKE '%,".$this->userid.",%' OR module_projects_task.team LIKE '%,".$this->userid."')";

        $sql = "SELECT module_projects_main.name AS project_name, module_projects_task.*, DATE_FORMAT(module_projects_task.date1, '%d.%m.%y') AS date1x, DATE_FORMAT(module_projects_task.date2, '%d.%m.%y') AS date2x FROM module_projects_task, module_projects_main WHERE module_projects_main.id = module_projects_task.project AND $condition ORDER BY module_projects_task.lastmod DESC LIMIT $start," . $this->maxresults_front;
//        echo "<!-- " . $sql . "-->";
        $sq->query($this->dbc, $sql);
        if ($sq->numrows == 0) {
            $tpl->addDataItem("RESULTS", $txt->display("search_noresults"));
        } else {
            $results = $sq->numrows;
            while ($data = $sq->nextrow()) {
                if ($style == "even") {
                    $style = "";
                } else {
                    $style = "even";
                }

                switch ($data["priority"]) {
                    case 1:
                        $style2 = "lowpriority";
                    break;
                    case 2:
                        $style2 = "";
                    break;
                    case 3:
                        $style2 = "red";
                    break;
                    default:
                        $style2 = "";
                    break;
                }

                $tpl->addDataItem("TASKS.STYLE", $style . " " . $style2);
                $tpl->addDataItem("TASKS.DATA_PROJECT", $data["project_name"]);
                $tpl->addDataItem("TASKS.DATA_TASK", $data["task"]);
                $tpl->addDataItem("TASKS.DATA_DATE1", $data["date1x"]);
                $tpl->addDataItem("TASKS.DATA_DATE2", $data["date2x"]);
                $tpl->addDataItem("TASKS.DATA_TYPE", $txt->display("type".$data["type"]));
                $tpl->addDataItem("TASKS.DATA_STATUS", $txt->display("status".$data["status"]));
                $tpl->addDataItem("TASKS.DATA_PRIORITY0", $data["priority"]);
                $tpl->addDataItem("TASKS.DATA_PRIORITY", $txt->display("priority".$data["priority"]));
                $tpl->addDataItem("TASKS.URL_DETAIL", $general_url . "&project=" . $data["project"] . "&task_id=" . $data["id"]);
            }
            $sq->free();
        }

        $tpl->addDataItem("URL", $general_url);

        return $tpl->parse();
    }


    // ########################################

    // Project list
    function showProjectListFront() {
        return "";
    }

    // ########################################

    // Task details

    function showTask($task_id) {
        $content = $this->vars["content"];
        $write = $this->vars["write"];
        $start = $this->vars["start"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);
        //if (!$this->project) doJump("");
        //if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->project) {
            if (is_array($this->project_people[$this->project])) {
                if (!in_array($this->userid, $this->project_people[$this->project])) doJump("");
            }
            else {
                doJump("");
            }
        }
        $this->checkTask($this->project, $task_id);

        if (!$start) { $start = 0; }

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            if (!$this->vars["solution"] || !$this->vars["status"]) {
                $error = true;
            }
            else {

                $this->vars["status"] = 8;

                $sql = "INSERT INTO module_projects_task_arc
                            (task_id,lastmod,lastuser,status,solution) VALUES
                            ('".addslashes($this->vars["task_id"])."',
                            now(), ".$this->userid.",
                            '".addslashes($this->vars["status"])."',
                            '".addslashes($this->vars["solution"])."')";
                $sq->query($this->dbc, $sql);
                $sql = "UPDATE module_projects_task SET
                            lastuser = ".$this->userid.",
                            lastmod = now() WHERE id = '".addslashes($this->vars["task_id"])."'";
                $sq->query($this->dbc, $sql);

                // SEND EMAIL TO TASK OWNER
                $sq->query($this->dbc, "SELECT module_projects_task.task, module_user_users.name, module_user_users.email FROM module_projects_task LEFT JOIN module_user_users ON module_projects_task.owner = module_user_users.user WHERE module_projects_task.id = '".addslashes($this->vars["task_id"])."'");
                $u_data = $sq->nextrow();
                $sq->free();
                if ($u_data["email"]) {
                    $email_content = ereg_replace("{TASK}", $u_data["task"], $txt->display("task_email"));
                    $email_content = ereg_replace("{STATUS}", $txt->display("status".$this->vars["status"]), $email_content);
                    $email_content = ereg_replace("{USER}", $GLOBALS["user_data"][1], $email_content);
                    $email_content = ereg_replace("{INFO}", ereg_replace("\n", "<br/>", $this->vars["solution"]), $email_content);

                    $location = $this->getProjectUrl(true);
                    if ($location && $location != "#") {
                        $email_content = ereg_replace("{URL}", SITE_URL . "/" . $location."&project=".$this->project."&task_id=".$task_id, $email_content);
                    }
                    else {
                        $email_content = ereg_replace("{URL}", "#", $email_content);
                    }

                    //sendEmailTo("DO_NOT_REPLY@modera.ee", $u_data["email"], "MODERA EXTRANET", $email_content);
                    $this->projectEmail(array($u_data["email"]), $this->info_email, $this->info_name . " - " . $this->project_data[$this->project], $email_content);
                }

                doJump("content=$content&nocache=true&project=" . $this->project);
            }
        }
        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_taskdetail.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=taskdetail&task_id=".$task_id);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("adderror"));

        $users = $this->getUsers();

        $sql = "SELECT module_projects_task.*, module_projects_main.name FROM module_projects_task LEFT JOIN module_projects_main ON module_projects_task.project = module_projects_main.id WHERE module_projects_task.project = " . $this->project . " AND module_projects_task.id = '".addslashes($task_id)."'";
        $sq->query($this->dbc, $sql);
        $data = $sq->nextrow();

        if ($data["date3"] == "0000-00-00 00:00:00") $data["date3"] = "-";

        $tpl->addDataItem("PROJECT", $data["name"]);
        $tpl->addDataItem("TASK", $data["task"]);
        $tpl->addDataItem("DATE1", $data["date1"]);
        $tpl->addDataItem("DATE2", $data["date2"]);
        $tpl->addDataItem("TYPE", $txt->display("type".$data["type"]));
        $tpl->addDataItem("STATUS", $txt->display("status".$data["status"]));
        $tpl->addDataItem("PRIORITY", $txt->display("priority".$data["priority"]));
        $tpl->addDataItem("INFO", nl2br($data["info"]));

        $tpl->addDataItem("TEAM", "");
        $tpl->addDataItem("HOURS", $data["hours"]);
        $tpl->addDataItem("SOLUTION", nl2br($data["solution"]));
        $tpl->addDataItem("DATE3", $data["date3"]);
        $tpl->addDataItem("OWNER", $users[$data["owner"]][0]);
        $tpl->addDataItem("LASTMOD", $data["lastmod"] . ", " . $users[$data["lastuser"]][0]);
        //$tpl->addDataItem("BACK_URL", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "", array("task_id","history","write")));

        //$tpl->addDataItem("HISTORY", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "history=true", array("history")));

        // ####
        // filter form

            if ($this->vars["status"] == "") { $this->vars["status"] = $data["status"]; }

            for ($u = 8; $u < 9; $u++) {
                if ($this->vars["status"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_status .= "<option value=\"$u\" $sel>" . $txt->display("status".$u) . "</option>";
            }

            $tpl->addDataItem("select_status", $f_status);

        // ####

        $sq->free();

        $proj_v = "<input type=hidden name=\"project\" value=\"".$this->project."\">";
        $tpl->addDataItem("HIDDEN", "<input type=hidden name=\"write\" value=\"true\">\n<input type=hidden name=\"task_id\" value=\"".$task_id."\">$proj_v");
        $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content=$content");
        $tpl->addDataItem("HISTORY", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "history=true", array("history")));

        $tpl->addDataItem("BACK_URL", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "", array("task_id","history","write")));

        // ####
        // get url to files section

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 120 AND language = '" . addslashes($this->language) . "' AND visible = 1 LIMIT 1");
        if ($sq->numrows != 0) {
             $data = $sq->nextrow();
             $files_url .= "?content=" . $data["content"];
        }
        else {  $files_url = "#";   }
        $sq->free();

        // ####

        $tpl->addDataItem("url_files", $files_url . "&project=".$this->project . "&task=" . $task_id);

        // ####
        return $tpl->parse();

    }

    // ########################################

    // Task details for employee

    function showTaskEmployee($task_id) {
        $content = $this->vars["content"];
        $start = $this->vars["start"];
        $write = $this->vars["write"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);
        //if (!$this->project) doJump("");
        //if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->project) {
            if (is_array($this->project_people[$this->project])) {
                if (!in_array($this->userid, $this->project_people[$this->project])) doJump("");
            }
            else {
                doJump("");
            }
        }
        $this->checkTask($this->project, $task_id);

        if (!$start) { $start = 0; }

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            if (!$this->vars["solution"] || !$this->vars["status"]) {
                $error = true;
            }
            else {

                if (is_array($this->vars["team"])) {
                    $team = addslashes(join(",", $this->vars["team"]));
                }
                else {
                    $team = "";
                    $this->vars["team"] = array();
                }

                $sq->query($this->dbc, "SELECT team FROM module_projects_task WHERE id = '".addslashes($this->vars["task_id"])."'");
                if ($team != $sq->column(0, "team")) {
                    $send_to_team = true;
                }
                else {
                    $send_to_team = false;
                }

                $sql = "INSERT INTO module_projects_task_arc
                            (task_id,lastmod,lastuser,status,solution,date3) VALUES
                            ('".addslashes($this->vars["task_id"])."',
                            now(), ".$this->userid.",
                            '".addslashes($this->vars["status"])."',
                            '".addslashes($this->vars["solution"])."',
                            '".addslashes($this->vars["date3"])."')";
                $sq->query($this->dbc, $sql);
                $sql = "UPDATE module_projects_task SET
                            status = '".addslashes($this->vars["status"])."',
                            solution = '".addslashes($this->vars["solution"])."',
                            date3 = '".addslashes($this->vars["date3"])."',
                            hours = '".addslashes($this->vars["hours"])."',
                            team = '".$team."',
                            lastuser = ".$this->userid.",
                            lastmod = now() WHERE id = '".addslashes($this->vars["task_id"])."'";
                $sq->query($this->dbc, $sql);

                // SEND EMAIL TO TASK OWNER
                $sq->query($this->dbc, "SELECT module_projects_task.task, module_user_users.name, module_user_users.email FROM module_projects_task LEFT JOIN module_user_users ON module_projects_task.owner = module_user_users.user WHERE module_projects_task.id = '".addslashes($this->vars["task_id"])."'");
                $u_data = $sq->nextrow();
                $sq->free();
                if ($u_data["email"]) {
                    $email_content = ereg_replace("{TASK}", $u_data["task"], $txt->display("task_email"));
                    $email_content = ereg_replace("{STATUS}", $txt->display("status".$this->vars["status"]), $email_content);
                    $email_content = ereg_replace("{USER}", $GLOBALS["user_data"][1], $email_content);
                    $email_content = ereg_replace("{INFO}", ereg_replace("\n", "<br/>", $this->vars["solution"]), $email_content);

                    $location = $this->getProjectUrl(true);
                    if ($location && $location != "#") {
                        $email_content = ereg_replace("{URL}", SITE_URL . "/" . $location."&project=".$this->project."&task_id=".$task_id, $email_content);
                    }
                    else {
                        $email_content = ereg_replace("{URL}", "#", $email_content);
                    }

                    //sendEmailTo("DO_NOT_REPLY@modera.ee", $u_data["email"], "MODERA EXTRANET", $email_content);
                    $this->projectEmail(array($u_data["email"]), $this->info_email, $this->info_name . " - " . $this->project_data[$this->project], $email_content);
                }
                // send emails to team
                if ($send_to_team == true) {
                    $users = $this->getUsers();

                    $email_content = ereg_replace("{TASK}", $u_data["task"], $txt->display("task_email_team"));
                    $email_content = ereg_replace("{USER}", $GLOBALS["user_data"][1], $email_content);
                    $email_content = ereg_replace("{INFO}", ereg_replace("\n", "<br/>", $this->vars["info"]), $email_content);
                    $email_content = ereg_replace("{PROJECT}", $this->project_data[$this->project], $email_content);

                    $location = $this->getProjectUrl(true);
                    if ($location && $location != "#") {
                        $email_content = ereg_replace("{URL}", SITE_URL . "/" . $location."&project=".$this->project."&task_id=".$task_id_in, $email_content);
                    }
                    else {
                        $email_content = ereg_replace("{URL}", "#", $email_content);
                    }

                    $email_rcps = array();

                    for ($c = 0; $c < sizeof($this->vars["team"]); $c++) {
                        if ($users[$this->vars["team"][$c]][1] && ereg("@", $users[$this->vars["team"][$c]][1])) {
                            //$email_content .= $users[$this->project_people[$this->project][$c]][0] . ", " . $users[$this->project_people[$this->project][$c]][1] . "\n";
                            $email_rcps[] = $users[$this->vars["team"][$c]][1];
                        }
                    }
                    reset($this->vars["team"]);

                    if (sizeof($email_rcps) > 0) {
                        //sendEmailTo("DO_NOT_REPLY@modera.ee", join(", ", $email_rcps), "MODERA EXTRANET", $email_content);
                        $this->projectEmail($email_rcps, $this->info_email, $this->info_name . " - " . $this->project_data[$this->project], $email_content);
                    }
                    // END

                }

                doJump("content=$content&nocache=true&project=" . $this->project);
            }
        }
        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_taskdetail_employee.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=taskdetailemp&task_id=".$task_id);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("adderror"));

        $users = $this->getUsers();

        $sql = "SELECT * FROM module_projects_task WHERE project = " . $this->project . " AND id = '".addslashes($task_id)."'";
        $sq->query($this->dbc, $sql);
        $data = $sq->nextrow();
        if ($data["date3"] == "0000-00-00 00:00:00") $data["date3"] = "";

        $tpl->addDataItem("TASK", $data["task"]);
        $tpl->addDataItem("DATE1", $data["date1"]);
        $tpl->addDataItem("DATE2", $data["date2"]);
        $tpl->addDataItem("TYPE", $txt->display("type".$data["type"]));
        $tpl->addDataItem("PRIORITY", $txt->display("priority".$data["priority"]));
        $tpl->addDataItem("INFO", nl2br($data["info"]));

        $tpl->addDataItem("HOURS", $data["hours"]);

        //$tpl->addDataItem("TEAM", "");
        if ($this->vars["solution"] != "") {
            $tpl->addDataItem("SOLUTION", $this->vars["solution"]);
        }
        else {
            $tpl->addDataItem("SOLUTION", $data["solution"]);
        }
        if ($this->vars["date3"] != "") {
            $tpl->addDataItem("DATE3", $this->vars["date3"]);
        }
        else {
            $tpl->addDataItem("DATE3", $data["date3"]);
        }
        $tpl->addDataItem("OWNER", $users[$data["owner"]][0]);
        $tpl->addDataItem("LASTMOD", $data["lastmod"] . ", " . $users[$data["lastuser"]][0]);

        // ####
        // filter form

            //team
            if ($this->vars["team"] == "") { $this->vars["team"] = $data["team"]; }

            $f_team = "";
            $teamm = $this->project_people_team[$this->project];
            $teamm_in = split(",", $this->vars["team"]);

            for ($t = 0; $t < sizeof($teamm); $t++) {
                if (in_array($teamm[$t], $teamm_in)) $sel = "selected";
                else { $sel = ""; }
                $f_team .= "<option value=\"".$teamm[$t]."\" $sel>" . $users[$teamm[$t]][0] . "</option>";

            }
            $tpl->addDataItem("select_team", $f_team);

            // ####

            if ($this->vars["status"] == "") { $this->vars["status"] = $data["status"]; }

            for ($u = 1; $u < $this->max_status; $u++) {
                if ($this->vars["status"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_status .= "<option value=\"$u\" $sel>" . $txt->display("status".$u) . "</option>";
            }

            $tpl->addDataItem("select_status", $f_status);

        // ####
        $sq->free();

        $proj_v = "<input type=hidden name=\"project\" value=\"".$this->project."\">";
        $tpl->addDataItem("HIDDEN", "<input type=hidden name=\"write\" value=\"true\">\n<input type=hidden name=\"task_id\" value=\"".$task_id."\">$proj_v");
        $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content=$content");
        $tpl->addDataItem("HISTORY", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "history=true", array("history")));

        $tpl->addDataItem("BACK_URL", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "", array("task_id","history","write")));

        // ####
        // get url to files section

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 120 AND language = '" . addslashes($this->language) . "' AND visible = 1 LIMIT 1");
        if ($sq->numrows != 0) {
             $data = $sq->nextrow();
             $files_url = "?content=" . $data["content"];
        }
        else {  $files_url = "#";   }
        $sq->free();

        // ####

        $tpl->addDataItem("url_files", $files_url . "&project=".$this->project . "&task=" . $task_id);

        // ####
        return $tpl->parse();

    }

    // ########################################

    // Task details for employee

    function showHistory($task_id) {
        $content = $this->vars["content"];
        $start = $this->vars["start"];
        $write = $this->vars["write"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);
        //if (!$this->project) doJump("");
        //if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->project) {
            if (is_array($this->project_people[$this->project])) {
                if (!in_array($this->userid, $this->project_people[$this->project])) doJump("");
            }
            else {
                doJump("");
            }
        }
        $this->checkTask($this->project, $task_id);

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_taskhistory.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=taskhistory&task_id=".$task_id);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $users = $this->getUsers();

        $sql = "SELECT module_projects_task_arc.*, module_projects_task.task FROM module_projects_task_arc LEFT JOIN module_projects_task ON module_projects_task_arc.task_id = module_projects_task.id WHERE module_projects_task_arc.task_id = '".addslashes($task_id)."' ORDER BY module_projects_task_arc.lastmod DESC";
        $sq->query($this->dbc, $sql);

        $nr = 1;
        while ($data = $sq->nextrow()) {
            if ($nr == 1) $tpl->addDataItem("TASK", $data["task"]);

            if ($data["date3"] == "0000-00-00 00:00:00") $data["date3"] = "-";

            $tpl->addDataItem("TASKS.SOLUTION", nl2br($data["solution"]));
            $tpl->addDataItem("TASKS.STATUS", $txt->display("status".$data["status"]));
            $tpl->addDataItem("TASKS.TXTREADY", $txt->display("ready"));
            $tpl->addDataItem("TASKS.DATE3", $data["date3"]);
            $tpl->addDataItem("TASKS.LASTMOD", $data["lastmod"] . ", " . $users[$data["lastuser"]][0]);
        $nr++;
        }
        // ####
        $sq->free();

        $tpl->addDataItem("BACK_URL", processUrl($_SERVER['PHP_SELF'], $_SERVER["QUERY_STRING"], "", array("history","write")));

        // ####
        return $tpl->parse();

    }


    // ########################################

    function getUsers() {
        $sq = new sql;
        $sq->query($this->dbc, "SELECT user, name, email, phone, ggroup FROM module_user_users");
        while ($data = $sq->nextrow()) {
            $users[$data["user"]] = array($data["name"], $data["email"], $data["phone"], $data["ggroup"]);
        }
        $sq->free();
        return $users;
    }

    // ########################################

    /*function getCurrentUserProjects() {
        $sq = new sql;
        $sq->query($this->dbc, "SELECT user, name FROM module_user_users");
        while ($data = $sq->nextrow()) {
            $users[$data["user"]] = $data["name"];
        }
        $sq->free();
        return $users;
    }*/

    // ########################################

    // Add new task

    function addTask() {
        $content = $this->vars["content"];
        $start = $this->vars["start"];
        $write = $this->vars["write"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);

        if (!$start) { $start = 0; }

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            if (!$this->vars["info"] || !$this->vars["task"] || !$this->vars["date1"] || !$this->vars["date2"] || !$this->vars["project"] && !is_array($this->vars["project_list"])) {
                $error = true;
            }
            else {

                // adding simlar tasks for every selected project
                for ($i = 0; $i < sizeof($this->vars["project_list"]); $i++) {
                    $project_id = $this->vars["project_list"][$i];

                    if (is_array($this->project_people[$project_id])) {
                        if (!in_array($this->userid, $this->project_people[$project_id])) {
                            continue;
                        }
                        if (!$this->project) {
                            $this->project = $project_id;
                        }
                    } else {
                        continue;
                    }

                    if (is_array($this->vars["team"])) {
                        $team = addslashes(join(",", $this->vars["team"]));
                    } else {
                        $team = "";
                        $this->vars["team"] = array();
                    }

                    $sql = "INSERT INTO module_projects_task
                                (project, task, date1, date2, info, type, status, team, priority, owner, lastmod, lastuser) VALUES
                                (".addslashes($project_id).", '".addslashes($this->vars["task"])."', '".addslashes($this->vars["date1"])."',
                                '".addslashes($this->vars["date2"])."', '".addslashes($this->vars["info"])."',
                                '".addslashes($this->vars["type"])."', '1',
                                '".$team."',
                                '".addslashes($this->vars["priority"])."', ".$this->userid.", now(), ".$this->userid.")";
                    $sq->query($this->dbc, $sql);
                    $task_id_in = $sq->insertID();

                    // SEND EMAIL TO PROJECT MEMBERS
                    $users = $this->getUsers();

                    $email_content = ereg_replace("{TASK}", $this->vars["task"], $txt->display("taskadd_email"));
                    $email_content = ereg_replace("{USER}", $GLOBALS["user_data"][1], $email_content);
                    $email_content = ereg_replace("{INFO}", ereg_replace("\n", "<br/>", $this->vars["info"]), $email_content);
                    $email_content = ereg_replace("{PROJECT}", $this->project_data[$project_id], $email_content);

                    $location = $this->getProjectUrl(true);
                    if ($location && $location != "#") {
                        $email_content = ereg_replace("{URL}", SITE_URL . "/" . $location."&project=".$project_id."&task_id=".$task_id_in, $email_content);
                    }
                    else {
                        $email_content = ereg_replace("{URL}", "#", $email_content);
                    }

                    $email_rcps = array();

                    for ($c = 0; $c < sizeof($this->project_people[$project_id]); $c++) {
                        if ($users[$this->project_people[$project_id][$c]][1] && ereg("@", $users[$this->project_people[$project_id][$c]][1])) {
                            //$email_content .= $users[$this->project_people[$this->project][$c]][0] . ", " . $users[$this->project_people[$this->project][$c]][1] . "\n";
                            $email_rcps[] = $users[$this->project_people[$project_id][$c]][1];
                        }
                    }
                    reset($this->project_people[$project_id]);

                    if (sizeof($email_rcps) > 0) {
                        //sendEmailTo("DO_NOT_REPLY@modera.ee", join(", ", $email_rcps), "MODERA EXTRANET", $email_content);
                        $this->projectEmail($email_rcps, $this->info_email, $this->info_name . " - " . $this->project_data[$project_id], $email_content);
                    }
                    // END

                    // SEND EMAILS TO TEAM
                    //$users = $this->getUsers();

                    $email_content = ereg_replace("{TASK}", $this->vars["task"], $txt->display("task_email_team"));
                    $email_content = ereg_replace("{USER}", $GLOBALS["user_data"][1], $email_content);
                    $email_content = ereg_replace("{INFO}", ereg_replace("\n", "<br/>", $this->vars["info"]), $email_content);
                    $email_content = ereg_replace("{PROJECT}", $this->project_data[$project_id], $email_content);

                    $location = $this->getProjectUrl(true);
                    if ($location && $location != "#") {
                        $email_content = ereg_replace("{URL}", SITE_URL . "/" . $location."&project=".$project_id."&task_id=".$task_id_in, $email_content);
                    }
                    else {
                        $email_content = ereg_replace("{URL}", "#", $email_content);
                    }

                    $email_rcps = array();

                    for ($c = 0; $c < sizeof($this->vars["team"]); $c++) {
                        if ($users[$this->vars["team"][$c]][1] && ereg("@", $users[$this->vars["team"][$c]][1])) {
                            //$email_content .= $users[$this->project_people[$this->project][$c]][0] . ", " . $users[$this->project_people[$this->project][$c]][1] . "\n";
                            $email_rcps[] = $users[$this->vars["team"][$c]][1];
                        }
                    }
                    reset($this->vars["team"]);

                    if (sizeof($email_rcps) > 0) {
                        //sendEmailTo("DO_NOT_REPLY@modera.ee", join(", ", $email_rcps), "MODERA EXTRANET", $email_content);
                        $this->projectEmail($email_rcps, $this->info_email, $this->info_name . " - " . $this->project_data[$project_id], $email_content);
                    }
                    // END
                } // for each project_list

                doJump("content=$content&nocache=true&project=" . $this->project);
            }
        }
        // ###################################

        if (!$_POST["date1"]) $_POST["date1"] = date("Y-m-d H:i:00");
        if (!$_POST["date2"]) $_POST["date2"] = date("Y-m-d H:i:00");

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if ($this->usergroup == 1) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_taskadd_employee.html";
        }
        else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_taskadd.html";
        }
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=taskadd");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            return "<!-- module projects cached -->\n" . $tpl->parse();
        }

        // #################################

        if ($error == true) $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("adderror"));

        // ####
        // form

            $users = $this->getUsers();

            //team
            $f_team = "";
            $teamm = $this->project_people_team[$this->project];
            $teamm_in = split(",", $this->vars["team"]);

            for ($t = 0; $t < sizeof($teamm); $t++) {
                if (in_array($teamm[$t], $teamm_in)) $sel = "selected";
                else { $sel = ""; }
                $f_team .= "<option value=\"".$teamm[$t]."\" $sel>" . $users[$teamm[$t]][0] . "</option>";

            }
            $tpl->addDataItem("select_team", $f_team);

            // ####

            for ($u = 1; $u < 4; $u++) {
                if ($this->vars["priority"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_priority .= "<option value=\"$u\" $sel>" . $txt->display("priority".$u) . "</option>";
            }
            for ($u = 1; $u < 3; $u++) {
                if ($this->vars["type"] == $u) $sel = "selected";
                else { $sel = ""; }
                $f_type .= "<option value=\"$u\" $sel>" . $txt->display("type".$u) . "</option>";
            }

            if (!is_array($this->vars["project_list"])) {
                $this->vars["project_list"] = array($this->project);
            }

            if ($this->project && $this->vars["project"] == "") {
                $f_project = "<b>".$this->project_data[$this->project]."</b>";
                $f_project .= "<input type=\"hidden\" name=\"project\" value=\"".$this->project."\">";
            }
            else {
                while (list($key, $val) = each($this->project_people)) {
                    if (in_array($this->userid, $val)) {
                        if (in_array($key, $this->vars["project_list"])) {
                            $sel = "selected";
                        } else {
                            $sel = "";
                        }
                        $f_project .= "<option value=\"$key\" $sel>" . $this->project_data[$key] . "</option>";
                    }
                }
                $f_project = "<select name=\"project_list[]\" size=\"5\" class=\"formInput\" \"multiple\">". $f_project . "</select>";
                $f_project .= "<input type=\"hidden\" name=\"project\" value=\"".$this->project."\">";
            }

            $tpl->addDataItem("select_status", $f_status);
            $tpl->addDataItem("select_priority", $f_priority);
            $tpl->addDataItem("select_type", $f_type);
            $tpl->addDataItem("select_project", $f_project);

            $tpl->addDataItem("HIDDEN", "<input type=hidden name=\"add_task\" value=\"true\">\n<input type=hidden name=\"write\" value=\"true\">");

            $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content=$content");


        // ####
        return $tpl->parse();
    }


// ########################################

    function projectMembers() {
        $content = $this->vars["content"];

        //$this->getProject($GLOBALS["pagedata"]["module"]);
        //if ($this->vars["project"] != "" && !$this->project) $this->project = $this->vars["project"];
        if ($this->project) {
            if (is_array($this->project_people[$this->project])) {
                if (!in_array($this->userid, $this->project_people[$this->project])) return "";
            }
            else {
                return "";
            }
        }
        else {
            return "";
        }

        $sq = new sql;

        //$txt = new Text($this->language, "module_projects");

        $users = $this->getUsers();

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_members.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=projectmembers");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        for ($c = 0; $c < sizeof($this->project_people[$this->project]); $c++) {
            $tpl->addDataItem("ROW.NAME", $users[$this->project_people[$this->project][$c]][0]);
            $tpl->addDataItem("ROW.EMAIL", $users[$this->project_people[$this->project][$c]][1]);
            $tpl->addDataItem("ROW.PHONE", $users[$this->project_people[$this->project][$c]][2]);
        }
        reset($this->project_people[$this->project]);

        $tpl->addDataItem("PROJECTID", $this->project);

        // ####
        return $tpl->parse();
    }

// ########################################

    function projectMenu() {
        $content = $this->vars["content"];

        $sq = new sql;

//        $txt = new Text($this->language, "module_projects");

        $users = $this->getUsers();

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_menu.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=projectmenu");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################
        $general_url = $_SERVER['PHP_SELF'] . "?content=$content&project=";

        foreach ($this->project_data as $project_id => $project_name) {
            if (is_array($this->project_people[$project_id]) && in_array($this->userid, $this->project_people[$project_id])) {
                $project_class = "";
                if ($this->project == $project_id) {
                    $project_class = "on";
                }
                $tpl->addDataItem("PROJECTS.URL", $general_url . $project_id);
                $tpl->addDataItem("PROJECTS.NAME", $project_name);
                $tpl->addDataItem("PROJECTS.CLASS", $project_class);
            }
        }
            
        // ####
        return $tpl->parse();
    }

// ########################################

    function projectSummary() {
        $content = $this->vars["content"];

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        $users = $this->getUsers();

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_summary.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=projectsummary");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################
        if ($this->project) {
            $project_id = $this->project;
            $project_name = $this->project_data[$this->project];

            if (is_array($this->project_people[$project_id]) && in_array($this->userid, $this->project_people[$project_id])) {
                $tpl->addDataItem("PROJECT_NAME", $project_name);
                $total_qty = 0;
                $status_list = array();

                $sql = "SELECT COUNT(id) AS qty, status FROM module_projects_task WHERE project = '" . addslashes($project_id) . "' GROUP BY status ORDER BY status";
                $sq->query($this->dbc, $sql);
                while ($data = $sq->nextrow()) {
                    $status_list[$data["status"]] = $data["qty"];
                    $total_qty += $data["qty"];
                }

                for ($i = 1; $i < $this->max_status; $i++) {
                    $tmp_qty = 0;
                    if ($status_list[$i]) {
                        $tmp_qty = $status_list[$i];
                    }
                    $tpl->addDataItem("STATUSLIST.NAME", $txt->display("status" . $i));
                    $tpl->addDataItem("STATUSLIST.COUNT", $tmp_qty);
                    $tpl->addDataItem("STATUSLIST.PERCENT", $total_qty ? (round($tmp_qty / $total_qty * 100)) : 0);
                }

                return $tpl->parse();
            }
        }
    }

// ########################################

    function showProjectSummaryFront() {
        $content = $this->vars["content"];

        $sq = new sql;

        $txt = new Text($this->language, "module_projects");

        $users = $this->getUsers();

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_projects_summary_front.html";
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=projects&page=projectsummaryfront");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "projects";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module projects cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $global_url = $this->getProjectUrl(false);

        foreach ($this->project_data as $project_id => $project_name) {
            if (is_array($this->project_people[$project_id]) && in_array($this->userid, $this->project_people[$project_id])) {
                $tpl->addDataItem("PROJECT.URL", $global_url . "&project=" . $project_id);
                $tpl->addDataItem("PROJECT.NAME", $project_name);
                $total_qty = 0;
                $prio_list = array();
                // all tasks that are not closed yet
                $sql = "SELECT COUNT(id) AS qty, priority FROM module_projects_task WHERE project = '" . addslashes($project_id) . "' AND status <> 2 GROUP BY priority ORDER BY priority";
                $sq->query($this->dbc, $sql);
                while ($data = $sq->nextrow()) {
                    $prio_list[$data["priority"]] = $data["qty"];
                    $total_qty += $data["qty"];
                }

                for ($i = 1; $i <= $this->max_priority; $i++) {
                    $tmp_qty = 0;
                    if ($prio_list[$i]) {
                        $tmp_qty = $prio_list[$i];
                    }
                    $tpl->addDataItem("PROJECT.STATUS.COLOR", $i);
                    $tpl->addDataItem("PROJECT.STATUS.PERCENT", $total_qty ? (round($tmp_qty / $total_qty * 100)) : 0);
                }
            }
        }
        return $tpl->parse();
    }

// ####################################################
    /** Show project calendar
        */

    function calendar() {
        $cal = new calendar;
        $sq = new sql;

        $location = $this->getProjectUrl(false);

        if ($this->vars["year"]) $year = $this->vars["year"];
        else { $year = date("Y"); }
        if ($this->vars["month"]) $month = "0".$this->vars["month"];
        else { $month = date("m"); }

        $dtocheck = addslashes($year) . "-" . addslashes($month);
        if ($this->project) $project_check = " project = '".addslashes($this->project)."' AND ";

        $date_events = array();
        $sql = "SELECT project, id,date1,date2,date3, lastmod as date4 FROM module_projects_task WHERE $project_check (date1 LIKE '$dtocheck%' OR date2 LIKE '$dtocheck%' OR date3 LIKE '$dtocheck%' OR lastmod LIKE '$dtocheck%')";
        //echo $sql;
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            for ($c = 1; $c < 5; $c++) {
                $datex = $data["date".$c];
                if (ereg($dtocheck, $datex)) {
                    $by = substr($datex, 0, 4);
                    $bm = substr($datex, 5,2);
                    $bd = substr($datex, 8,2);
                    if (substr($bm, 0, 1) == "0") $bm = substr($bm, -1);
                    if (substr($bd, 0, 1) == "0") $bd = substr($bd, -1);
                    $date_events[$by][$bm][$bd][] = $data["id"];
                }
            }
        }
        // ########

        // url to link to + array with event data
        $cal->parameters($location, $date_events);

        return $cal->show_events_cal();
    }

    // ########################################

    function projectEmail($send_to, $sender, $subject, $text) {

        include_once(SITE_PATH . "/class/mail/htmlMimeMail.php");

        if (!$sender) $sender = $this->info_email;
        if (!$subject) $subject = $this->info_name;
        if (!$text) return false;

        if (!is_array($send_to)) return false;
        /*
        echo "<!-- \n";
        print_r($sender);
        echo "\n";
        print_r($send_to);
        echo "-->\n";
        */

        $mail = new htmlMimeMail();
        $mail->setHtml($text, returnPlainText($text));
        $mail->setFrom("<".$sender.">");
        $mail->setSubject($subject);
        $result = $mail->send($send_to);
        if ($result) { return true; }
        else { return false; }

    }

    // ########################################

    function doNothing() {
        return "";
    }

    // return project url to link to
    function getProjectUrl($short = false) {
        $sq = new sql;

//        if (!$this->project) {
            $sql = "SELECT content, structure, first FROM content WHERE template = 200 AND language = '" . addslashes($this->language) . "' AND (module LIKE '%projects=%') LIMIT 1";            
//        }
//        else {
//            $sql = "SELECT content, structure, first FROM content WHERE template = 200 AND language = '" . addslashes($this->language) . "' AND module LIKE '%projects=".$this->project."%' LIMIT 1";
//        }

        $sq->query($this->dbc, $sql);
        if ($sq->numrows != 0) { $data = $sq->nextrow(); }
        $sq->free();
        if ($short == true) {
            $location = "?content=" . $data["content"];
        }
        else {
            $location = $_SERVER['PHP_SELF'] . "?content=" . $data["content"];
        }
        return $location;
    }

    // get current project from page settings
    function getProject() {

        $module_pagedata = array();
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $module_pagedata[$a[0]] = $a[1];
        }

        if ($module_pagedata["projects"] != "") {
            $this->project = $module_pagedata["projects"];
        }
    }

    // check access to desired project
    function checkTask($project, $task_id) {
        $sq = new sql;
        $sql = "SELECT id FROM module_projects_task WHERE id = '".addslashes($task_id)."' AND project = '".addslashes($project)."'";
        $sq->query($this->dbc, $sql);
        if ($sq->numrows == 0) {
            doJump(""); //return false;
        }
        else {
            return true;
        }
    }

    function makeClickableLinks($str)  {
        // Exclude matched inside anchor tags
        $not_anchor = '(?<!"|href=|href\s=\s|href=\s|href\s=)';
        // Match he protocol with ://, e.g. http://
        $protocol = '(http|ftp|https):\/\/';
        $domain = '[\w]+(.[\w]+)';
        $subdir = '([\w\-\.;,@?^=%&:\/~\+#]*[\w\-\@?^=%&\/~\+#])?';
        $test = '/' . $not_anchor . $protocol . $domain . $subdir . '/i';
        // Match and replace where there is a protocol and no anchor
        $ret = preg_replace($test, "<a href='$0' title='$0'>$0</a>", $str);
        // Now match things beginning with www.
        $not_anchor = '(?<!"|href=|href\s=\s|href=\s|href\s=)';
        $not_http = '(?<!:\/\/)';
        $domain = 'www(.[\w]+)';
        $subdir = '([\w\-\.;,@?^=%&:\/~\+#]*[\w\-\@?^=%&\/~\+#])?';
        $test = '/' . $not_anchor . $not_http . $domain . $subdir . '/is';
        return preg_replace($test, "<a href='http://$0' title='http://$0'>$0</a>", $ret);
    }


// ########################################
    // #####################
    // functions for content management

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    function moduleOptions() {
        $sq = new sql;
        $txt = new Text($this->language, "module_projects");
        //$sq->query($this->dbc, "SELECT user, name FROM module_user_users ORDER BY name ASC");
        $sq->query($this->dbc, "SELECT id, name FROM module_projects_main ORDER BY begin DESC");
        $list[""] = " - - - ";
        while ($data = $sq->nextrow()) {
            $list[$data["id"]] = $data["name"];
        }
        $sq->free();
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }
}
