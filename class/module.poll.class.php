<?php
/*
last modified 09.07.04 (siim)
version 1.5

 example use inside template
    <TPL_OBJECT:poll>
      <TPL_OBJECT_OUTPUT:show()>
    </TPL_OBJECT:poll>
*/

class poll {

  var $tmpl = false;
  var $max_width = 150;
  var $debug = false;
  var $language = false;
  var $siteroot = false;
  var $vars = array();
  var $dbc = false;
  var $content_module = false;
  var $module_param = array();
  var $userid = false;
  var $cachelevel = TPL_CACHE_NOTHING;
  var $cachetime = 1440; //cache time in minutes

/** Constructor
    */

  function poll () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    $this->debug = $GLOBALS["modera_debug"];
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

// ########################################

    /** Main object display function
        */

  function show () {
    global $txtf;

    $poll = @$this->vars["poll"];
    $vote = @$this->vars["vote"];

    $sq = new sql;

    $txt = new Text($this->language, "module_poll");

    // instantiate template class
    $tpl = new template;
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    if (!$poll) {
        $poll_list = array();
        $sql = "SELECT module_poll.id, IF(module_poll_user.id, 1, 0) AS answered FROM module_poll LEFT JOIN module_poll_user ON module_poll.id = module_poll_user.poll_id WHERE module_poll.language = '" . $this->language . "' AND module_poll.active = 1 AND module_poll.start_time <= now() AND module_poll.end_time >=now()";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $poll_list[$data["answered"]][] = $data["id"];
        }
        if (is_array($poll_list[0]) && sizeof($poll_list[0])) {
            $select = $this->randomint(sizeof($poll_list[0])); // unanswered polls
            $poll = $poll_list[0][$select];
        } else {
            $select = $this->randomint(sizeof($poll_list[1])); // answered polls
            $poll = $poll_list[1][$select];
        }
    }

    // first check if this user has already answered for this poll
    $sql = "SELECT id FROM module_poll_user WHERE poll_id = '" . addslashes($poll) . "' AND user_id = '" . $this->userid . "'";
    $sq->query($this->dbc, $sql);
    if ($sq->numrows) {
        $vote_done = true;
    }

    if ($poll && ($vote || $vote_done)) {
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_poll_results.html";
    }
    else {
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_poll_question.html";
    }

    $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=poll&poll=$poll");
    $tpl->setTemplateFile($template);

    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "poll";
        if ($GLOBALS["modera_debug"] == true) {
            return "<!-- module poll cached -->\n" . $tpl->parse();
        }
        else {
            return $tpl->parse();
        }
    }

    // #################################

        $sql = "SELECT content, structure FROM content WHERE template = 50 AND language = '" . addslashes($this->language) . "' LIMIT 1";
        $sq->query($this->dbc, $sql);
        if ($data = $sq->nextrow()) {
            $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"];
            if ($data["content"]) {
                $general_url .= "&content=" . $data["content"];
            }
        }

    // ####


    if ($poll && ($vote || $vote_done)) {

        $sq->query($this->dbc, "SELECT id, title FROM module_poll WHERE language = '".$this->language."' AND active = 1 AND id = '" .addslashes($poll) . "'");
        $data = $sq->nextrow();
        if ($sq->numrows > 0) {
            $tpl->addDataItem("QUESTION", $data["title"]);
        }
        $sq->free();

        if (!$vote_done) {
            $sq->query($this->dbc, "UPDATE module_poll_questions SET score = score + 1 WHERE poll = '" . addslashes($poll) . "' AND id = '" . addslashes($vote) . "'");
            // inserting voting result to module_poll_user
            $sql = "INSERT INTO module_poll_user (poll_id, user_id, question_id, entrydate) VALUES ('" . addslashes($poll) . "', '" . $this->userid . "', '" . addslashes($vote) . "', NOW())";
            $sq->query($this->dbc, $sql);
        }

        $sq->query($this->dbc, "SELECT sum(score) as total from module_poll_questions where poll = '" . addslashes($poll) . "'");
        $total = $sq->column(0, "total");

        $sq->query($this->dbc, "SELECT score, question FROM module_poll_questions WHERE poll = '".addslashes($poll)."' ORDER BY prio ASC, id ASC");
        while ($data = $sq->nextrow()) {
            $tpl->addDataItem("OPTION.SIZE", (($data["score"]*100)/$total)*$this->max_width/100);
            $tpl->addDataItem("OPTION.OPTION", $data["question"]);
            $tpl->addDataItem("OPTION.SCORE", $data["score"] . " " . $txt->display("votes"));
            $tpl->addDataItem("OPTION.PERCENT", sprintf("%d", (($data["score"]*100)/$total)));
        }
        $sq->free();

        $tpl->addDataItem("TOTAL", ereg_replace("{NR}", "$total", $txt->display("total")));
        $tpl->addDataItem("URL_ARCHIVE", $general_url);
    }

    // #### ####

    else {

        $sq->query($this->dbc, "SELECT id, title FROM module_poll WHERE id = '" . addslashes($poll) . "'");
        if ($pdata = $sq->nextrow()) {
//            $tpl->addDataItem("FORM.SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("poll","vote")));
            $tpl->addDataItem("FORM.SELF", $general_url);
            $tpl->addDataItem("FORM.URL_RESULTS", $general_url . "&poll=" . $data["id"]);
            $tpl->addDataItem("FORM.QUESTION", $pdata["title"]);
            $tpl->addDataItem("FORM.POLL", $poll);
            $tpl->addDataItem("FORM.BUTTON", $txt->display("submit"));
            $sq->free();

            $sq->query($this->dbc, "SELECT id, question FROM module_poll_questions WHERE poll = '".addslashes($poll)."' ORDER BY prio ASC, id ASC");
            while ($data = $sq->nextrow()) {
                $tpl->addDataItem("FORM.OPTION.OPTION", $data["question"]);
                $tpl->addDataItem("FORM.OPTION.VOTE", $data["id"]);
            }
            $sq->free();
        }
    }

    // ####

    return $tpl->parse();

  }

    /*
    ** Archive
    */

    function showArchive() {
        global $txtf;

        $poll = @$this->vars["poll"];
        $vote = @$this->vars["vote"];

        $sq = new sql;

        $txt = new Text($this->language, "module_poll");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if (!$poll) {
            $sq->query($this->dbc, "SELECT id FROM module_poll WHERE language = '".$this->language."' AND active = 1 AND start_time <= now() ORDER BY start_time DESC, end_time DESC, id DESC LIMIT 1");
            if ($sq->numrows > 0) {
                $poll = $sq->column($select, "id");
            }
        } else {
            $sq->query($this->dbc, "SELECT id FROM module_poll WHERE language = '".$this->language."' AND active = 1 AND start_time <= now() AND id = '" . addslashes($poll) . "' LIMIT 1");
            if ($sq->numrows > 0) {
                $tpoll = $sq->column($select, "id");
                // first check if this user has already answered for this poll
                $sql = "SELECT id FROM module_poll_user WHERE poll_id = '" . addslashes($tpoll) . "' AND user_id = '" . $this->userid . "'";
                $sq->query($this->dbc, $sql);
                if ($sq->numrows) {
                    $vote_done = true;
                }

                if ($vote && !$vote_done) {
                    $sq->query($this->dbc, "UPDATE module_poll_questions SET score = score + 1 WHERE poll = '" . addslashes($tpoll) . "' AND id = '" . addslashes($vote) . "'");
                    // inserting voting result to module_poll_user
                    $sql = "INSERT INTO module_poll_user (poll_id, user_id, question_id, entrydate) VALUES ('" . addslashes($tpoll) . "', '" . $this->userid . "', '" . addslashes($vote) . "', NOW())";
                    $sq->query($this->dbc, $sql);
                }
            }
        }

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_poll_archive.html";

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=poll&poll=$poll&archive=1");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "poll";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module poll cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $sql = "SELECT content, structure FROM content WHERE template = 50 AND language = '" . addslashes($this->language) . "' LIMIT 1";
        $sq->query($this->dbc, $sql);
        if ($data = $sq->nextrow()) {
            $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"];
            if ($data["content"]) {
                $general_url .= "&content=" . $data["content"];
            }
        }

        // ####

        if ($poll) {
            $sq->query($this->dbc, "SELECT id, title FROM module_poll WHERE language = '".$this->language."' AND active = 1 AND start_time <= now() AND id = '" .addslashes($poll) . "'");
            $data = $sq->nextrow();
            if ($sq->numrows > 0) {
                $tpl->addDataItem("QUESTION", $data["title"]);
            }
            $sq->free();

            $sq->query($this->dbc, "SELECT sum(score) as total from module_poll_questions where poll = '" . addslashes($poll) . "'");
            $total = $sq->column(0, "total");

            $sq->query($this->dbc, "SELECT score, question FROM module_poll_questions WHERE poll = '".addslashes($poll)."' ORDER BY prio ASC, id ASC");
            while ($data = $sq->nextrow()) {
                $tpl->addDataItem("OPTION.SIZE", $total ? ((($data["score"]*100)/$total)*$this->max_width/100) : 0);
                $tpl->addDataItem("OPTION.OPTION", $data["question"]);
                $tpl->addDataItem("OPTION.SCORE", $data["score"] . " " . $txt->display("votes"));
                $tpl->addDataItem("OPTION.PERCENT", sprintf("%d", $total ? (($data["score"]*100)/$total) : 0));
            }
            $sq->free();

            $tpl->addDataItem("TOTAL", ereg_replace("{NR}", "$total", $txt->display("total")));
        }

        // List of all of the polls
        $sql = "SELECT module_poll.*, SUM(module_poll_questions.score) AS total FROM module_poll, module_poll_questions WHERE module_poll.id = module_poll_questions.poll AND language = '".$this->language."' AND active = 1 AND start_time <= now() GROUP BY module_poll.id ORDER BY start_time DESC, end_time DESC, id DESC";
//        echo "<!-- $sql -->\n";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $tpl->addDataItem("POLLS.QUESTION", $data["title"]);
            $tpl->addDataItem("POLLS.ANSWERS", $data["total"]);
            $tpl->addDataItem("POLLS.URL", $general_url . "&poll=" . $data["id"]);
        }

        // ####

        return $tpl->parse();
    }

// ########################################

    /** Select random number
        *   @max the max number to get
        */

    function randomint($max = 100) {
        static $startseed = 0;
        if (!$startseed) {
            $startseed = (double)microtime()*getrandmax();
            srand($startseed);
        }
        if ($max) {
            return (rand()%$max);
        }
        return 0;
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
        $list = array();
        $list[0] = "---";
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

}
