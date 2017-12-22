<?php
    /**
    * Modera.net iforum module.
    *
    * @package iforum
    * @version $Revision: 44 $
    * @author inz
    */

    require_once(SITE_PATH.'/admin/module_iforum_common.php');

    class iforum {

        /**
        * @var integer main template group
        * @var string 2 character currently active language code
        * @var array merged array with GET and POST data.
        * @var integer database connection parameter
        * @var boolean is the module of type content module eg. does it provide additional parameters to admin->contet->page admin (parameters will be called from this module, when a template is chosen that contains a reference to this module)
        * @var array module parameters, accessed from currently active site page (DB table content, field module)
        * @var integer logged in user's id
        * @var string general cache level for this module TPL_CACHE_ALL, TPL_CACHE_NOTHING. you can set individual cache for each function or returned template if you like
        * @var integer cache expiration time in minutes
        * @var string template file part of name (ALWAYS name of the module or name of the module + something extra). template generated will be always tpl_iforum_****. The end part is determined by the setInstance function, which you can control for uniqunesess of the cache
        * @var string template filename to show for the module or a single function or whatever
        * @var string date format. parameters similar to PHP date() function
        * @var array acceptable GET parameters. Convinient to use in processUrl();
            * @var integer how many topics/posts to show per page
        * @var integer how many topics to show on latest topics page
        * @var integer wrap words in post subjects/bodies if longer than this limit
        * @var integer threads with postcount bigger than this have property "Hot"
        */

        var $tmpl = "";
        var $language = "";
        var $vars = array();
        var $dbc = false;
        var $content_module = true;
        var $module_param = array();
        var $userid = false;
        var $cachelevel = TPL_CACHE_ALL;
        var $cachetime = 720; //cache time in minutes
        var $tplfile = "iforum";
        var $template = "";
        var $date_format = "d.m.Y H:i:s";
        var $acceptable_parameters=array('tid','fid','pid','moduleaction','page','addpost','quote','reply','fsearch');
        var $per_page = 40;
        var $latest_topics_count = 5;
        var $wrap_at = 50;
        var $hotlevel = 50;
        var $top_thread_count = 10;
        var $last_thread_count = 5;

        /**
        * Class constructor. Initiated with each class initialization.
        */

        function iforum () {
            $this->query_string = str_replace("&&", "&", str_replace("&amp;", "&", $_SERVER["QUERY_STRING"]));
            $this->vars = array_merge($_GET, $_POST);
            $this->tmpl = $GLOBALS["site_settings"]["template"];
            $this->language = $GLOBALS["language"];
            if (!is_object($GLOBALS["db"])) {
                $db = new DB;
                $this->dbc = $db->connect();
            }else {
                $this->dbc = $GLOBALS["db"]->con;
            }

            $this->userid = $GLOBALS["user_data"][0];
            $this->groupid = $GLOBALS["user_data"][4];
            $this->groups = $GLOBALS["user_data"][5];

            if (!is_numeric($this->vars['page']) || $this->vars['page'] < 1) $this->vars['page']=1;
            if ($this->content_module == true) {
                $this->getParameters();
            }
            if (!is_numeric($this->vars['per_page']) || $this->vars['per_page'] < 1) $this->vars['per_page']=20;

            if (is_numeric($this->vars['tid']))
            {
                unset($this->vars['fid']);
                $sq = new sql;
                $sq->query($this->dbc,"SELECT f.name as fname,f.id as fid, f.user_group as fgrp, t.name as tname, t.id as tid FROM module_iforum_forums f inner join module_iforum_sections sec on (f.section_id = sec.id) inner join module_iforum_threads t on (f.id=t.forum_id) where  sec.language = '".addslashes($this->language)."' and t.id = ".$this->vars['tid']);
                if ($this->allowedGroups($sq->column(0,'fgrp'))) {
                    $this->module_param['fid']=$sq->column(0,'fid');
                    $this->module_param['fname']=$sq->column(0,'fname');
                    $this->module_param['tid']=$sq->column(0,'tid');
                    $this->module_param['tname']=$sq->column(0,'tname');
                }
                $sq->free();
            } elseif (is_numeric($this->vars['fid'])) {
                unset($this->vars['tid']);
                $sq = new sql;
                $sq->query($this->dbc,"SELECT f.name as fname,f.id as fid, f.user_group as fgrp FROM module_iforum_forums f inner join module_iforum_sections sec on (f.section_id = sec.id) where sec.language = '".addslashes($this->language)."' and f.id = ".$this->vars['fid']);
                if ($this->allowedGroups($sq->column(0,'fgrp'))) {
                    $this->module_param['fid']=$sq->column(0,'fid');
                    $this->module_param['fname']=$sq->column(0,'fname');
                }
                $sq->free();
                unset($this->module_param['tid'], $this->module_param['tname']);
            } else
            {
                unset($this->vars['tid'], $this->vars['fid'], $this->module_param['fid'], $this->module_param['fname'], $this->module_param['tid'], $this->module_param['tname']);
            }


        }
        /**
        * Main display. In a module, that returns output, a function named show() that either directly or by calling other functions does
        * returning should exist.
        * @access public
        * @return string HTML content
        */

        function show () {
            if ($this->checkAccess() == false) return "";
            $tpl = new template;

            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters(); // this function checks for common parameters such as _POST found or nocache=true to determine cache usage
            $tpl2init='';
            if ($this->module_param['fid'])$this->vars['moduleaction']='forum';
            if ($this->module_param['tid']) $this->vars['moduleaction']='thread';
            if (isset($this->vars['addpost'])) $this->vars['moduleaction']='post';
            if ($this->vars['fsearch']) $this->vars['moduleaction']='search';
            switch ($this->vars['moduleaction']) {
                case 'post': if (!$this->userid) $this->vars['moduleaction']='notloggedin';
                case 'search':
                case 'index':
                case 'forum':
                case 'thread':

                    $tpl2init=$this->vars['moduleaction'];
                    break;
                default:
                    $this->vars['moduleaction']='index';
                    $tpl2init='index';
            }
            $tpl->tplfile = $this->tplfile.'_'.$tpl2init;
            if ($this->vars['moduleaction'] == 'thread') $tpl->tplfile .= '_'.$this->module_param['tid'];
            $this->template = 'module_iforum_'.$tpl2init.'.html';
            switch ($this->vars['moduleaction']){
                case 'post':
                    $cachekey=$_SERVER["PHP_SELF"]."?language=".$this->language."&module=".get_class($this)."&moduleaction=".serialize($this->vars['moduleaction']).'&fid='.$this->module_param['fid'].'&tid='.$this->module_param['tid'].'&quote='.$this->vars['quote'].'&reply='.$this->vars['reply'];
                    break;
                case 'index':
                    $cachekey=$_SERVER["PHP_SELF"]."?language=".$this->language."&module=".get_class($this)."&moduleaction=".serialize($this->vars['moduleaction']);
                    break;
                case 'forum':
                    $cachekey=$_SERVER["PHP_SELF"]."?language=".$this->language."&module=".get_class($this)."&moduleaction=".serialize($this->vars['moduleaction']).'&page='.$this->vars['page'].'&fid='.$this->module_param['fid'];
                    break;
                case 'thread':
                    $sq = new sql;
                    $sq->query($this->dbc,"update module_iforum_threads set views = views + 1 where id = ".$this->module_param['tid']);
                    clearCacheFiles('tpl_iforum_forum','');
                    clearCacheFiles('tpl_iforum_latest','');
                    $cachekey=$_SERVER["PHP_SELF"]."?language=".$this->language."&module=".get_class($this)."&moduleaction=".serialize($this->vars['moduleaction']).'&tid='.$this->module_param['tid'].'&page='.$this->vars['page'];
                    break;
                default:
                    $usecache = false;

            }

            $tpl->setInstance($cachekey);

            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
            $tpl->setTemplateFile($template);

            // CACHE FOUND for the template, return it
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "iforum";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module iforum cached -->\n" . $tpl->parse();
                }
                else {
                    return $tpl->parse();
                }
            }
            $tpl->addDataItem("TITLE",  htmlspecialchars($GLOBALS["pagedata"]['title']));
            $tpl->addDataItem("NAV_INDEX_TEXT",  htmlspecialchars($this->module_param['iforum']));
            $tpl->addDataItem("NAV_INDEX_URL",  processUrl('', $this->query_string, "", $this->acceptable_parameters));

            $func=$tpl2init.'View';
            if ($tpl2init && method_exists($this, $func)) $this->$func($tpl);

            return $tpl->parse();

        }

        /**
        * Generates posting view
        * @access private
        */

        function postView(&$tpl){
            if (!$this->userid) exit;
            $txt = new Text($this->language, "module_iforum");
            if ($this->vars['lamer_sensor'])
            {
                $error=false;
                if (!$error && strlen($this->vars['fsubject']) < 1) $error=$txt->display('subject_too_short');
                if (!$error && strlen($this->vars['fcontent']) < 3) $error=$txt->display('message_too_short');
                if (!$error && isset($_SESSION['iforum']['posts'][$this->vars['lamer_sensor']]) && $_SESSION['iforum']['posts'][$this->vars['lamer_sensor']] == md5($this->vars['fsubject'].$this->vars['fcontent'])) $error=$txt->display('form_has_been_submitted_before');
                if (!$error)
                {
                    if ($this->vars['preview'])
                    {
                        $tpl->addDataItem("SHOW_PREVIEW.USER_NAME",  htmlspecialchars($GLOBALS["user_data"][1]));
                        $tpl->addDataItem("SHOW_PREVIEW.POST_TIME",  htmlspecialchars(date($this->date_format)));
                        $tpl->addDataItem("SHOW_PREVIEW.NAME",  htmlspecialchars(wordwrap($this->vars['fsubject'],$this->wrap_at, "\n", 1)));
                        $tpl->addDataItem("SHOW_PREVIEW.CONTENT", formatPost(htmlspecialchars($this->vars['fcontent']),$this->wrap_at));
                    }else{
                        $sq = new sql;
                        if (!$this->module_param['tid'] && $this->module_param['fid']){
                            $sq->query($this->dbc,"insert into module_iforum_threads (forum_id, user, name) values (".intval($this->module_param['fid']).", ".intval($this->userid).", '".addslashes($this->vars['fsubject'])."')");
                            $this->module_param['tid']=$sq->insertID();
                        }
                        if ($this->module_param['tid']){
                            $sq->query($this->dbc,"insert into module_iforum_posts (thread_id, user, content, subject, stamp) values (".intval($this->module_param['tid']).", ".intval($this->userid).", '".addslashes($this->vars['fcontent'])."', '".addslashes($this->vars['fsubject'])."', now() )");
                            $insertid=$sq->insertID();
                        }
                        $posts=calculateThreadStats($this->module_param['tid']);
                        $_SESSION['iforum']['posts'][$this->vars['lamer_sensor']]= md5($this->vars['fsubject'].$this->vars['fcontent']);
                        clearCacheFiles('tpl_iforum_thread_'.$this->module_param['tid'],'');
                        clearCacheFiles('tpl_iforum_latest','');

                        $redirecttopage=floor( $posts / $this->per_page);
                        if($posts % $this->per_page>0) $redirecttopage++;
                        redirect(str_replace("&amp;", "&", (processUrl('', $this->query_string, "tid=".$this->module_param['tid'].'&page='.$redirecttopage.'#p'.$insertid, $this->acceptable_parameters))));

                        // we have an insert request


                    }
                }
                $tpl->addDataItem("FSUBJECT",  htmlspecialchars($this->vars['fsubject']));
                $tpl->addDataItem("FCONTENT",  htmlspecialchars($this->vars['fcontent']));
            }else{
                if ($this->vars['quote'] || $this->vars['reply'] )
                {
                    if ($this->vars['reply']) $tempid=$this->vars['reply'];
                    if ($this->vars['quote']) $tempid=$this->vars['quote'];
                    $sq = new sql;
                    $sq->query($this->dbc,"select subject, content from module_iforum_posts where  id=".intval($tempid)." and thread_id=".intval($this->module_param['tid']));
                    if ($this->vars['quote']) $tpl->addDataItem("FCONTENT",  '[quote]'.htmlspecialchars($sq->column(0,'content')).'[/quote]');
                    $temp_subject=$sq->column(0,'subject');
                    $temp_re=$txt->display('re');
                    $pos = strpos($temp_subject, $temp_re);
                    if (!($pos !== false && $pos == 0)) $temp_subject=$temp_re.$temp_subject;
                    $tpl->addDataItem("FSUBJECT",  htmlspecialchars($temp_subject));
                }
            }



            $tpl->addDataItem("TITLE",  htmlspecialchars(strtoupper($this->module_param['fname'])));
            $tpl->addDataItem("LAMER_SENSOR",  htmlspecialchars(microtime()));
            $tpl->addDataItem("USER_NAME",  htmlspecialchars($GLOBALS["user_data"][1]));
            $tpl->addDataItem("NAV2.URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
            $tpl->addDataItem("NAV2.NAME",  htmlspecialchars($this->module_param['fname']));
            if ($this->module_param['tid']){
                $tpl->addDataItem("NAV3.URL",  processUrl('', $this->query_string, "tid=".$this->module_param['tid'], $this->acceptable_parameters));
                $tpl->addDataItem("NAV3.NAME",  htmlspecialchars(wordwrap($this->module_param['tname'],$this->wrap_at, "\n", 1)));
                $tpl->addDataItem("POST_WHAT",  htmlspecialchars($txt->display('post_new_comment')));
                $tpl->addDataItem("TARGET",  processUrl('', $this->query_string, "addpost=1&tid=".$this->module_param['tid'], $this->acceptable_parameters));
            }else{
                $tpl->addDataItem("TARGET",  processUrl('', $this->query_string, "addpost=1&fid=".$this->module_param['fid'], $this->acceptable_parameters));
                $tpl->addDataItem("POST_WHAT", htmlspecialchars( $txt->display('post_new_topic')));
            }
            if ($error) $tpl->addDataItem("ERROR.MESSAGE", htmlspecialchars( $error));
            global $iforum_smileys;
            iforumLoadSmilies();
            $tmptest=array();
            if (is_array($iforum_smileys)) foreach($iforum_smileys as $smilecode => $smileurl){
                if ($tmptest[$smileurl]) continue;
                $tmptest[$smileurl]=1;
                $tpl->addDataItem("SMILIES.URL", htmlspecialchars( $smileurl));
                $tpl->addDataItem("SMILIES.DESC", htmlspecialchars( $smilecode));

            }


// show original
                if ($this->vars['quote'] || $this->vars['reply'] )
                {
                    if ($this->vars['reply']) $tempid=$this->vars['reply'];
                    if ($this->vars['quote']) $tempid=$this->vars['quote'];
                    $tpl->addDataItem("HIDDENREPLY",$this->vars['reply']);
                    $tpl->addDataItem("HIDDENQUOTE",$this->vars['quote']);
                    $sq = new sql;
                    $sq->query($this->dbc,"select subject, content, UNIX_TIMESTAMP(stamp) as stamp, CONCAT(ua.name_first, ' ', ua.name_last) as username from module_iforum_posts left outer join module_user_users  ua on (module_iforum_posts.user = ua.user) where  id=".intval($tempid)." and thread_id=".intval($this->module_param['tid']));
                        $tpl->addDataItem("SHOW_ORIGINAL.USER_NAME",  htmlspecialchars($sq->column(0,'username')));
                        $tpl->addDataItem("SHOW_ORIGINAL.POST_TIME",  htmlspecialchars(date($this->date_format, $sq->column(0,'stamp'))));
                        $tpl->addDataItem("SHOW_ORIGINAL.NAME",  htmlspecialchars(wordwrap($sq->column(0,'subject'),$this->wrap_at, "\n", 1)));
                        $tpl->addDataItem("SHOW_ORIGINAL.CONTENT", formatPost(htmlspecialchars($sq->column(0,'content')),$this->wrap_at));
                }

// eof show original

        }


        /**
        * Generates forum view
        * @access private
        */

        function forumView(&$tpl){
            if (!$this->module_param['fid']) return false;
            $sq = new sql;
            $tpl->addDataItem("SEARCH_KEYWORD",  htmlspecialchars($this->vars['fsearch']));
            $tpl->addDataItem("SEARCH_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
            $tpl->addDataItem("TITLE",  htmlspecialchars(strtoupper($this->module_param['fname'])));
            $tpl->addDataItem("NAV2.URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
            $tpl->addDataItem("NEW_TOPIC_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'].'&addpost=1', $this->acceptable_parameters));
            $tpl->addDataItem("NAV2.NAME",  htmlspecialchars($this->module_param['fname']));
            $sq->query($this->dbc,"select count(*) as threadsum from module_iforum_threads where forum_id = ".$this->module_param['fid']);
            $totalrows=$sq->column(0,'threadsum');
            $sq->free();
            $threadpages=floor( $totalrows / $this->per_page);
            if($totalrows%$this->per_page>0) $threadpages++;
            if ($threadpages > 1) for ($temp_th_counter=1;$temp_th_counter<=$threadpages;$temp_th_counter++)
            {
                if ($temp_th_counter == $this->vars['page'])
                {
                    $activepage='ACTIVE_';
                }
                else
                {
                    $activepage='INACTIVE_';
                }
                $tpl->addDataItem('PAGINATOR.PAGE.DUMB',1);
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.URL', processUrl('', $this->query_string, "fid=".$this->module_param['fid'].'&page='.$temp_th_counter, $this->acceptable_parameters));
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.NAME',htmlspecialchars($temp_th_counter));


            }
            $sq->query($this->dbc,"SELECT CONCAT(ua.name_first, ' ', ua.name_last) as author_name, CONCAT(ul.name_first, ' ', ul.name_last) as last_post_author_name, UNIX_TIMESTAMP(t.last_post) as last_post_time, t.name, t.views, t.posts,t.id, DATE_ADD(t.last_post, interval 1 DAY) > now() as posted_today
            FROM
            module_iforum_threads t
            left outer join module_user_users  ua on (t.user = ua.user)
            left outer join module_user_users  ul on (t.last_post_user = ul.user)
            where t.forum_id = ".$this->module_param['fid']."
            ORDER BY t.last_post DESC
            LIMIT ".($this->vars['page']-1)*intval($this->per_page).",".intval($this->per_page)."
            ");

            while ($data = $sq->nextrow()) {


                if ($data["id"]){
                    if ($data['posts'] > $this->hotlevel) {
                        $icon='hot';
                    }elseif ($data['posted_today']) {
                        $icon = 'new';
                    }else {
                        $icon='old';
                    }
                    $tpl->addDataItem("THREAD.ICON",  $icon);

                    $tpl->addDataItem("THREAD.NAME", htmlspecialchars(wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                    $tpl->addDataItem("THREAD.VIEWS",  htmlspecialchars($data["views"]));
                    $tpl->addDataItem("THREAD.REPLIES",  ($data["posts"] - 1)>0?($data["posts"] - 1):0);
                    $tpl->addDataItem("THREAD.AUTHOR_NAME",  htmlspecialchars($data["author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_AUTHOR_NAME",  htmlspecialchars($data["last_post_author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_TIME",  htmlspecialchars(strlen($data["last_post_time"])?date($this->date_format, $data["last_post_time"]):''));
                    $tpl->addDataItem("THREAD.URL", processUrl('', $this->query_string, "tid=".$data["id"], $this->acceptable_parameters));
                    $subpages=floor( $data["posts"] / $this->per_page);
                    if($data["posts"]%$this->per_page>0) $subpages++;
                    if ($subpages > 1) for ($tempcounter=1;$tempcounter<=$subpages;$tempcounter++)
                    {
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.URL", processUrl('', $this->query_string, "tid=".$data["id"].'&page='.$tempcounter, $this->acceptable_parameters));
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.NAME",htmlspecialchars($tempcounter));
                    }
                }

            }
            $sq->free();


        }

        /**
        * Generates search view
        * @access private
        */

        function searchView(&$tpl){
            $tpl->addDataItem("SEARCH_KEYWORD",  htmlspecialchars($this->vars['fsearch']));
            $error=false;
            $txt = new Text($this->language, "module_iforum");
            $sql_addon='';
            if ($this->module_param['fid']){
                $tpl->addDataItem("SEARCHTEXT",  htmlspecialchars($txt->display('search_this_forum')));
                $tpl->addDataItem("TITLE",  htmlspecialchars(strtoupper($this->module_param['fname'])));
                $tpl->addDataItem("THISFORUM_B.NEW_TOPIC_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'].'&addpost=1', $this->acceptable_parameters));
                $tpl->addDataItem("THISFORUM_A.NEW_TOPIC_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'].'&addpost=1', $this->acceptable_parameters));
                $tpl->addDataItem("THISFORUMSTYLE.DUMB", 1);
                $tpl->addDataItem("NAV2.URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
                $tpl->addDataItem("NAV2.NAME",  htmlspecialchars($this->module_param['fname']));
                $tpl->addDataItem("SEARCH_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
                $sql_addon=" and t.forum_id=".intval($this->module_param['fid']);
            }else{
                $tpl->addDataItem("SEARCH_URL",  processUrl('', $this->query_string, '' , $this->acceptable_parameters));
                $tpl->addDataItem("SEARCHTEXT",  htmlspecialchars($txt->display('search_the_forums')));
                $tpl->addDataItem("CONDITIONALBREAK", '<br />');
            }

            if (strlen($this->vars['fsearch']) < 4) $error=$txt->display('keyword_too_short');
            if ($error){
                $tpl->addDataItem('SEARCHERROR.TEXT',htmlspecialchars($error));
                return true;
            }
            $sq=new sql;
            $sq->query($this->dbc,"select count(distinct t.id) as threadsum from module_iforum_threads t inner join module_iforum_posts p on (t.id=p.thread_id) where 1=1 $sql_addon and match (subject,content) against ('".addslashes($this->vars['fsearch'])."%')");
            $totalrows=$sq->column(0,'threadsum');
            $sq->free();
            if ($totalrows < 1) $error=$txt->display('bad_search');
            if ($error) {
                $tpl->addDataItem('SEARCHERROR.TEXT',htmlspecialchars($error));
                return true;
            }


            $threadpages=floor( $totalrows / $this->per_page);
            if($totalrows%$this->per_page>0) $threadpages++;
            if ($threadpages > 1) for ($temp_th_counter=1;$temp_th_counter<=$threadpages;$temp_th_counter++)
            {
                if ($temp_th_counter == $this->vars['page'])
                {
                    $activepage='ACTIVE_';
                }
                else
                {
                    $activepage='INACTIVE_';
                }
                $tpl->addDataItem('PAGINATOR.PAGE.DUMB',1);
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.URL', processUrl('', $this->query_string, "fsearch=".$this->vars['fsearch'].'&page='.$temp_th_counter.($this->module_param['fid']?'&fid='.$this->module_param['fid']:''), $this->acceptable_parameters));
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.NAME',htmlspecialchars($temp_th_counter));


            }
            $sq->query($this->dbc,"SELECT CONCAT(ua.name_first, ' ', ua.name_last) as author_name, CONCAT(ul.name_first, ' ', ul.name_last) as last_post_author_name, UNIX_TIMESTAMP(t.last_post) as last_post_time, t.name, t.views, t.posts,t.id
            FROM
            module_iforum_threads t
            inner join module_iforum_posts p on (t.id=p.thread_id)
            left outer join module_user_users  ua on (t.user = ua.user)
            left outer join module_user_users  ul on (t.last_post_user = ul.user)
            where 1=1 $sql_addon and match (subject,content) against ('".addslashes($this->vars['fsearch'])."%')
            group by t.id
            ORDER BY t.last_post DESC
            LIMIT ".($this->vars['page']-1)*intval($this->per_page).",".intval($this->per_page)."
            ");

            while ($data = $sq->nextrow()) {


                if ($data["id"]){
                    $tpl->addDataItem("THREAD.NAME",  htmlspecialchars(wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                    $tpl->addDataItem("THREAD.VIEWS",  htmlspecialchars($data["views"]));
                    $tpl->addDataItem("THREAD.REPLIES",  ($data["posts"] - 1)>0?($data["posts"] - 1):0);
                    $tpl->addDataItem("THREAD.AUTHOR_NAME",  htmlspecialchars($data["author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_AUTHOR_NAME",  htmlspecialchars($data["last_post_author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_TIME",  htmlspecialchars(strlen($data["last_post_time"])?date($this->date_format, $data["last_post_time"]):''));
                    $tpl->addDataItem("THREAD.URL", processUrl('', $this->query_string, "tid=".$data["id"], $this->acceptable_parameters));
                    $subpages=floor( $data["posts"] / $this->per_page);
                    if($data["posts"]%$this->per_page>0) $subpages++;
                    if ($subpages > 1) for ($tempcounter=1;$tempcounter<=$subpages;$tempcounter++)
                    {
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.URL", processUrl('', $this->query_string, "tid=".$data["id"].'&page='.$tempcounter, $this->acceptable_parameters));
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.NAME",htmlspecialchars($tempcounter));
                    }
                }

            }
            $sq->free();


        }

        /**
        * Generates thread view
        * @access private
        */

        function threadView(&$tpl) {
            $tpl->addDataItem("NEW_TOPIC_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'].'&addpost=1', $this->acceptable_parameters));
            if (!$this->module_param['tid']) return false;
            $sq = new sql;
            $tpl->addDataItem("TITLE",  htmlspecialchars(strtoupper($this->module_param['fname'])));
            $tpl->addDataItem("SEARCH_KEYWORD",  htmlspecialchars($this->vars['fsearch']));
            $tpl->addDataItem("SEARCH_URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
            $tpl->addDataItem("NAV2.URL",  processUrl('', $this->query_string, "fid=".$this->module_param['fid'], $this->acceptable_parameters));
            $tpl->addDataItem("NAV2.NAME",  htmlspecialchars($this->module_param['fname']));
            $tpl->addDataItem("NAV3.URL",  processUrl('', $this->query_string, "tid=".$this->module_param['tid'], $this->acceptable_parameters));
            $tpl->addDataItem("NAV3.NAME",  htmlspecialchars(wordwrap($this->module_param['tname'],$this->wrap_at, "\n", 1)));
            $sq->query($this->dbc,"select count(*) as postsum from module_iforum_posts where thread_id = ".$this->module_param['tid']);
            $totalrows=$sq->column(0,'postsum');
            $sq->free();
            $postpages=floor( $totalrows / $this->per_page);
            if($totalrows%$this->per_page>0) $postpages++;
            if ($postpages > 1) for ($temp_p_counter=1;$temp_p_counter<=$postpages;$temp_p_counter++)
            {
                if ($temp_p_counter == $this->vars['page'])
                {
                    $activepage='ACTIVE_';
                }
                else
                {
                    $activepage='INACTIVE_';
                }
                $tpl->addDataItem('PAGINATOR.PAGE.DUMB',1);
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.URL', processUrl('', $this->query_string, "tid=".$this->module_param['tid'].'&page='.$temp_p_counter, $this->acceptable_parameters));
                $tpl->addDataItem('PAGINATOR.PAGE.'.$activepage.'PAGE.NAME',htmlspecialchars($temp_p_counter));


            }
            $sq->query($this->dbc,"SELECT CONCAT(u.name_first, ' ', u.name_last) as author_name,  UNIX_TIMESTAMP(p.stamp) as post_time, p.subject as name, p.content, p.id
            FROM
            module_iforum_posts p
            left outer join module_user_users  u on (p.user = u.user)
            where p.thread_id = ".$this->module_param['tid']."
            ORDER BY p.stamp aSC
            LIMIT ".($this->vars['page']-1)*intval($this->per_page).",".intval($this->per_page)."
            ");

            while ($data = $sq->nextrow()) {
                if ($data["id"]){
                    if ($style == 'ODD')
                    {
                        $style = 'EVEN';
                    }
                    else
                    {
                        $style = 'ODD';
                    }
                    $tpl->addDataItem('POST.DUMB',1);
                    $tpl->addDataItem('POST.'.$style.'_POST.NAME',  htmlspecialchars(wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                    $tpl->addDataItem('POST.'.$style.'_POST.ID',  htmlspecialchars($data["id"]));
                    $tpl->addDataItem('POST.'.$style.'_POST.CONTENT',  formatPost(htmlspecialchars($data["content"]),$this->wrap_at));
                    $tpl->addDataItem('POST.'.$style.'_POST.AUTHOR_NAME',  htmlspecialchars($data["author_name"]));
                    $tpl->addDataItem('POST.'.$style.'_POST.REPLY_URL', processUrl('', $this->query_string, "tid=".$this->module_param['tid'].'&addpost=1', $this->acceptable_parameters));
                    $tpl->addDataItem('POST.'.$style.'_POST.POST_TIME',  htmlspecialchars(strlen($data["post_time"])?date($this->date_format, $data["post_time"]):''));
                }

            }
            $sq->free();
        }

        /**
        * Generates list of top 10 threads
        * @access private
        */

        function topthreadView() {
            $sq = new sql;

            // instantiate template class
            $tpl = new template;
            //$this->cachelevel = TPL_CACHE_ALL;
            $this->cachelevel = TPL_CACHE_NOTHING;
            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters();

            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_iforum_top_threads.html";
            $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=iforum&type=topthread");
            $tpl->setTemplateFile($template);

            // PAGE CACHED
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "contacts";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module iforum cached -->\n" . $tpl->parse();
                }
                else {
                    return $tpl->parse();
                }
            }

            $sq->query($this->dbc,"SELECT * FROM module_iforum_threads WHERE 1 ORDER BY module_iforum_threads.posts DESC LIMIT " . $this->top_thread_count);

            while ($data = $sq->nextrow()) {
                $tpl->addDataItem('THREAD.NAME',  htmlspecialchars(wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                $tpl->addDataItem("THREAD.URL", processUrl('', $this->query_string, "tid=" . $data["id"], $this->acceptable_parameters));
            }
            $sq->free();
            return $tpl->parse();
        }

        /**
        * Generates list of last 5 threads
        * @access private
        */

        function lastthreadView() {
            $sq = new sql;

            // instantiate template class
            $tpl = new template;
            //$this->cachelevel = TPL_CACHE_ALL;
            $this->cachelevel = TPL_CACHE_NOTHING;
            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters();

            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_iforum_last_threads.html";
            $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=iforum&type=topthread");
            $tpl->setTemplateFile($template);

            // PAGE CACHED
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "contacts";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module iforum cached -->\n" . $tpl->parse();
                }
                else {
                    return $tpl->parse();
                }
            }

            $sq->query($this->dbc,"SELECT * FROM module_iforum_threads WHERE 1 ORDER BY module_iforum_threads.last_post DESC LIMIT " . $this->last_thread_count);

            while ($data = $sq->nextrow()) {
                $tpl->addDataItem('THREAD.NAME',  htmlspecialchars(wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                $tpl->addDataItem("THREAD.URL", processUrl('', $this->query_string, "tid=" . $data["id"], $this->acceptable_parameters));
            }
            $sq->free();
            return $tpl->parse();
        }

        /**
        * Generates forum index view
        * @access private
        */

        function indexView(&$tpl){
            $sq = new sql;
            $tpl->addDataItem("SEARCH_KEYWORD",  htmlspecialchars($this->vars['fsearch']));
            $tpl->addDataItem("SEARCH_URL",  processUrl('', $this->query_string, '' , $this->acceptable_parameters));
            $tpl->addDataItem("NAV_INDEX_TEXT",  htmlspecialchars($this->module_param['iforum']));
            $tpl->addDataItem("NAV_INDEX_URL",  processUrl('', $this->query_string, "", $this->acceptable_parameters));

            $tmp_names=array();
            $sq->query($this->dbc,"
            SELECT f.name as forum_name, f.topics as topics, f.posts as posts, f.description as forum_description, f.id as forum_id, f.user_group as forum_group, sec.name as section_name,sec.id as section_id, CONCAT(u.name_first, ' ', u.name_last) as user_name, UNIX_TIMESTAMP(f.last_post) as last_post_time
            FROM
            module_iforum_forums f
            right join module_iforum_sections sec on (f.section_id = sec.id)
            left outer join module_user_users  u on (f.last_post_user = u.user)
            where  sec.language = '".$this->language."'
            ORDER BY sec.prio ASC, f.prio ASC, f.name
            ");
            while ($data = $sq->nextrow()) {

                if (!$tmp_names[$data["section_id"]])
                {
                    $tmp_names[$data["section_id"]]=1;
                    $tpl->addDataItem("SECTION.NAME",  htmlspecialchars($data["section_name"]));
                }
                if ($data["forum_id"] && $this->allowedGroups($data["forum_group"])){
                    $tpl->addDataItem("SECTION.FORUM.NAME",  htmlspecialchars($data["forum_name"]));
                    $tpl->addDataItem("SECTION.FORUM.ID",  htmlspecialchars($data["forum_id"]));
                    $tpl->addDataItem("SECTION.FORUM.URL",  processUrl('', $this->query_string, "fid=".$data["forum_id"], $this->acceptable_parameters));
                    $tpl->addDataItem("SECTION.FORUM.DESCRIPTION", htmlspecialchars( $data["forum_description"]));
                    $tpl->addDataItem("SECTION.FORUM.POSTS",  htmlspecialchars($data["posts"]));
                    $tpl->addDataItem("SECTION.FORUM.TOPICS",  htmlspecialchars($data["topics"]));
                    $tpl->addDataItem("SECTION.FORUM.LAST_POST_USER",  htmlspecialchars($data["user_name"]));
                    $tpl->addDataItem("SECTION.FORUM.LAST_POST_TIME",  htmlspecialchars(strlen($data["last_post_time"])?date($this->date_format, $data["last_post_time"]):''));

                }
            }
            $sq->free();
        }


        /**
        * Generates forum navigator box
        * @access public
        * @return string HTML content
        */

        function menu () {
            $sq = new sql;
            $tpl = new template;
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_iforum_shortmenu.html";
        $message = wordwrap($message,$wrap_at, "\n", 1);
            $tpl->tplfile = $this->tplfile.'_menu';
            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters();
            $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&structure=".$this->vars['structure']."&content=".$this->vars['content']."&module=iforum&mode=iforum_forum_select&fid=".$this->module_param['fid']);
            $tpl->setTemplateFile($template);

            // PAGE CACHED
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "iforum";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module iforum cached -->\n" . $tpl->parse();
                }
                else {
                    return $tpl->parse();
                }
            }

            // #################################

            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 701 AND language = '" . addslashes($this->language) . "' LIMIT 1");
            if ($sq->numrows != 0) {
                $data = $sq->nextrow();
                $general_url = $_SERVER["PHP_SELF"] . "?";
                if ($data["structure"])  $general_url .= "&structure=" . $data["structure"];
                if ($data["content"]) $general_url .= "&content=" . $data["content"];
            }
            else {  $general_url = "#"; }
            $sq->free();
            $tpl->addDataItem("INDEX_URL", htmlspecialchars($general_url .  "&moduleaction=index"));
            $tpl->addDataItem("INDEX_TEXT",  htmlspecialchars($this->module_param['iforum']));

            $sq->query($this->dbc,"SELECT f.name as forum_name, f.id as forum_id
            FROM
            module_iforum_forums f
            inner join module_iforum_sections sec on (f.section_id = sec.id)
            where  sec.language = '".$this->language."'
            ORDER BY sec.prio ASC, f.prio ASC, f.name
            ");
            while ($data = $sq->nextrow()) {
                if ($data['forum_id'] == $this->module_param['fid'])
                {
                    $active='ACTIVE';
                }
                else
                {
                    $active='INACTIVE';
                }

                $tpl->addDataItem('FORUMSELECTOR.FORUM.DUMB',1);
                $tpl->addDataItem('FORUMSELECTOR.FORUM.'.$active.'.NAME',htmlspecialchars($data["forum_name"]));
                $tpl->addDataItem('FORUMSELECTOR.FORUM.'.$active.'.URL',htmlspecialchars($general_url .  "&fid=".$data['forum_id']));
            }
            $sq->free();
            return $tpl->parse();

        }

        /**
        * Generates forum last topics table
        * @access public
        * @return string HTML content
        */
        function showLatestTopics () {
            $sq = new sql;
            $tpl = new template;
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_iforum_latest_topics.html";
            $tpl->tplfile = $this->tplfile.'_latest';
            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters();
            $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=iforum&mode=iforum_forum_latesttopics");
            $tpl->setTemplateFile($template);

            // PAGE CACHED
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "iforum";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module iforum cached -->\n" . $tpl->parse();
                }
                else {
                    return $tpl->parse();
                }
            }

            // #################################

            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 701 AND language = '" . addslashes($this->language) . "' LIMIT 1");
            if ($sq->numrows != 0) { $data = $sq->nextrow();
            $general_url = $_SERVER["PHP_SELF"] . "?";
            if ($data["structure"]) $general_url .= "&structure=" . $data["structure"];
            if ($data["content"]) $general_url .= "&content=" . $data["content"];
            }
            else {  $general_url = "#"; }
            $sq->free();


            $sq->query($this->dbc,"SELECT CONCAT(ua.name_first, ' ', ua.name_last) as author_name, CONCAT(ul.name_first, ' ', ul.name_last) as last_post_author_name, UNIX_TIMESTAMP(t.last_post) as last_post_time, t.name, t.views, t.posts,t.id, DATE_ADD(t.last_post, interval 1 DAY) > now() as posted_today
            FROM
            module_iforum_threads t
            left outer join module_user_users  ua on (t.user = ua.user)
            left outer join module_user_users  ul on (t.last_post_user = ul.user)
            ORDER BY t.last_post DESC
            LIMIT ".intval($this->latest_topics_count)."
            ");

            while ($data = $sq->nextrow()) {


                if ($data["id"]){
                    if ($data['posts'] > $this->hotlevel) {
                        $icon='hot';
                    }elseif ($data['posted_today']) {
                        $icon = 'new';
                    }else {
                        $icon='old';
                    }
                    $tpl->addDataItem("THREAD.ICON",  $icon);
                    $tpl->addDataItem("THREAD.NAME", htmlspecialchars( wordwrap($data["name"],$this->wrap_at, "\n", 1)));
                    $tpl->addDataItem("THREAD.VIEWS",  htmlspecialchars($data["views"]));
                    $tpl->addDataItem("THREAD.REPLIES",  ($data["posts"] - 1)>0?($data["posts"] - 1):0);
                    $tpl->addDataItem("THREAD.AUTHOR_NAME", htmlspecialchars( $data["author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_AUTHOR_NAME", htmlspecialchars( $data["last_post_author_name"]));
                    $tpl->addDataItem("THREAD.LAST_POST_TIME", htmlspecialchars( strlen($data["last_post_time"])?date($this->date_format, $data["last_post_time"]):''));
                    $tpl->addDataItem("THREAD.URL", htmlspecialchars($general_url. "&tid=".$data["id"]));
                    $subpages=floor( $data["posts"] / $this->per_page);
                    if($data["posts"]%$this->per_page>0) $subpages++;
                    if ($subpages > 1) for ($tempcounter=1;$tempcounter<=$subpages;$tempcounter++)
                    {
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.URL", htmlspecialchars($general_url. "&tid=".$data["id"].'&page='.$tempcounter));
                        $tpl->addDataItem("THREAD.PAGINATOR.PAGE.NAME",htmlspecialchars($tempcounter));
                    }
                }

            }
            $sq->free();
            return $tpl->parse();

        }




        /**
        * Set template module helper function
        * @param string template file name
        */

        function setTemplate ($template) {
            if (ereg("\.\.", $template)) trigger_error("Module iforum: Template path is invalid !", E_USER_ERROR);
            $this->template = $template;
        }

        /**
        * Set date format to use when displaying
        * @param string date format with parameters similar to PHP date() command
        */

        function setDateFormat($format) {
            $this->date_format = $format;
        }
        /**
        * Set wrap threshold to use when displaying
        * @param int number of characters
        */
        function setWrap($wrap=50) {
            if (is_numeric($wrap) && $wrap > 0) $this->wrap_at = $wrap;
        }

        /**
        * Set hotlevel threshold to use when displaying
        * @param int number of posts to trigger hot status
        */

        function setHotLevel ($level=50) {
            if (is_numeric($level) && $level > 0) $this->hotlevel=$level;
        }
        /**
        * Set number of topics displayed in latest topics page
        * @param int number of topics
        */

        function setLatestTopicsCount ($count=5) {
            if (is_numeric($count) && $count > 0) $this->latest_topics_count=$count;
        }

        /**
        * Check if given list of groups are allowed to current user
        * @param string list of groups
        */

        function allowedGroups ($glist) {
            if ($glist) {
                $groups = explode(",", $glist);
            }

            if (is_array($groups) && sizeof($groups)) {
                if (is_array($this->groups) && sizeof($this->groups)) {
                    $g_is = array_intersect($this->groups, $groups);
                    if (sizeof($g_is)) {
                        return true;
                    }
                }
                return false;
            }
            return true;
        }

    // global site search interface
    function global_site_search($search, $beg_date = "", $end_date = "") {
        $sq = new sql;
        $txt = new Text($this->language, "module_iforum");

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 701 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        if ($data = $sq->nextrow()) {
            $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"] . "&content=" . $data["content"];
        } else {
            return false; // no content-record found, will not continue
        }

        // creating array for search result
        $result = array(
            "title" => $txt->display("module_title"), // module title
            "fields" => array("name", "views", "posts", "author_name", "last_post_author_name", "last_post_time"), // array of fields with according titles
            "values" => array() // array of values will be stored here
        );

        if ($this->userid) {
            if ($beg_date) {
                $bd = date("d", strtotime($beg_date));
                $bm = date("m", strtotime($beg_date));
                $by = date("Y");
                $beg_date = date("Y-m-d", mktime(0, 0, 0, $bm, $bd, $by));

                $date_filter .= " AND date_sub(concat(year(now()), '-', month(module_contacts.birthdate), '-', dayofmonth(module_contacts.birthdate)) , interval 0 day) >= '" . $beg_date . "'";
            }
            if ($end_date) {
                $ed = date("d", strtotime($end_date));
                $em = date("m", strtotime($end_date));
                $ey = date("Y");
                $end_date = date("Y-m-d", mktime(0, 0, 0, $em, $ed, $ey));

                $date_filter .= " AND date_sub(concat(year(now()), '-', month(module_contacts.birthdate), '-', dayofmonth(module_contacts.birthdate)) , interval 0 day) <= '" . $end_date . "'";
            }

            $sql = "SELECT CONCAT(ua.name_first, ' ', ua.name_last) as author_name, CONCAT(ul.name_first, ' ', ul.name_last) as last_post_author_name, UNIX_TIMESTAMP(t.last_post) as last_post_time, t.name, t.views, t.posts,t.id, f.user_group AS forum_group
            FROM
            module_iforum_threads t
            inner join module_iforum_posts p on (t.id=p.thread_id)
            inner join module_iforum_forums f on (t.forum_id = f.id)
            left outer join module_user_users  ua on (t.user = ua.user)
            left outer join module_user_users  ul on (t.last_post_user = ul.user)
            where 1=1 and (LOWER(subject) like LOWER('%" . addslashes($search) . "%') OR LOWER(content) like LOWER('%" . addslashes($search) . "%'))
            group by t.id
            ORDER BY t.last_post DESC";

//            echo "<!-- $sql -->\n";
            $sq->query($this->dbc, $sql);
            $row = 0;
            while ($data = $sq->nextrow()) {
                if ($this->allowedGroups($data["forum_group"])) {
                    $result["values"][$row]["url"] = $general_url . "&tid=" . $data["id"];
                    $data["last_post_time"] = date("d.m.Y H:i:s");
                    foreach ($result["fields"] as $key) {
                        $result["values"][$row][$key] = $data[$key];
                    }
                    $row++;
                }
            }

            $sq->free();
        }

        return $result;
    }

        // ##############################################################

        /**
        * check does the current user have access, should we return anything or not
        * @access private
        * @return boolean  true - ok, display, false - not ok
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
        * Retrieve module specific additional parameters from current page -> DB table content, field module
        * @access private
        */

        function getParameters() {
            $ar = split(";", $GLOBALS["pagedata"]["module"]);
            for ($c = 0; $c < sizeof($ar); $c++) {
                $a = split("=", $ar[$c]);
                $this->module_param[$a[0]] = $a[1];
            }
        }

        /**
        * Return an array with additional fields to the admin->content admin
        * for example in this case, we want the user to define a group for module demo1
        * @access private
        * @return array (name, type, list, name2, type2, list2, name3, type3, list3, name4, type4, list4)
        */

        function moduleOptions() {
            $txt = new Text($this->language, "module_iforum");
            return array($txt->display("option1"), "textinput", array());
        }

    }
