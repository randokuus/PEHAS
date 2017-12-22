<?php
/**
 * TagCloud module
 *
 * <pre>
 * <TPL_OBJECT:tagcloud>
 *      <TPL_OBJECT_PARAM:setTemplate('module_tagcloud.html')>
 *      <TPL_OBJECT_PARAM:setFontSizeBounds(10, 32)>
 *      <TPL_OBJECT_OUTPUT:show()>
 * </TPL_OBJECT:demo1>
 * </pre>
 *
 * @package modera_net
 */

class TagCloud
{
    var $tmpl;
    var $debug;
    var $language;
    var $vars;
    var $content_module;
    var $cachelevel;
    var $cachetime; //cache time in minutes
    var $tplfile;
    var $template;
    var $db;
    var $maxfontsize;
    var $minfontsize;

    function TagCloud()
    {
        $this->setFontSizeBounds(10, 32);
        $this->cachetime = 30;
        $this->cachelevel = TPL_CACHE_ALL;
        $this->content_module = false;
        $this->tplfile = 'tagcloud';

        $this->db =& $GLOBALS['database'];
        $this->vars = array_merge($_GET, $_POST);
        $this->language = $GLOBALS["language"];
        $this->tmpl = $GLOBALS["site_settings"]["template"];

        $this->debug = $GLOBALS["modera_debug"];
    }

    /**
     * Method used to get module output
     *
     * @return string
     */
    function show()
    {
        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters(); // this function checks for common parameters such as _POST found or nocache=true to determine cache usage

        // template not defined, use default
        if (!strlen($this->template)) {
            $this->template = "module_tagcloud.html";
        }

        // set instance controls the uniqueness of the cache file. if you want to enable/disable cache based on the existence
        // of certain variables, arrays etc. place them in here. the parameter to setInstance is in no way restricted.
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=" . $this->tplfile);

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        $tpl->setTemplateFile($template);

        if ($usecache && $tpl->isCached($template)) {
            // CACHE FOUND for the template, return it

            $GLOBALS["caching"][] = "tagcloud";
            if ($GLOBALS["modera_debug"]) {
                return "<!-- module tagcloud cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }

        } else {
            // CACHE NOT FOUND

            // select biggest number
            $maxweight = $this->db->fetch_first_value('SELECT COUNT(*) FROM `tags` WHERE `language` = ?'
                . ' GROUP BY `tag` ORDER BY 1 DESC LIMIT 1', $this->language);

            // number of different tag weights available in system
            $res =& $this->db->query('SELECT DISTINCT COUNT(*) FROM `tags` WHERE `language` = ?'
                . ' GROUP BY `tag`', $this->language);

            // if there is only one weight all tokens have, set maximum weight bigger so all tokens
            // will be drawn with smaller font size instead of biggest one
            if (1 == $res->num_rows()) {
                $maxweight += 1;
            }

            // select tags with ranking ordered alphabeticaly
            $res =& $this->db->query('SELECT `tag`, COUNT(*) AS `weight` FROM `tags` WHERE `language`=?'
                . ' GROUP BY `tag` ORDER BY `tag`', $this->language);

            while ($row = $res->fetch_assoc()) {
                $tag = htmlspecialchars($row['tag']);
                $tpl->addDataItem('TAG.URL', SITE_URL . "/?search_tag=$tag");
                $tpl->addDataItem('TAG.FONTSIZE', $this->font_size_by_weight($row['weight'], $maxweight));
                $tpl->addDataItem('TAG.TAGTEXT', $tag);
            }

            return $tpl->parse();
        }
    }

    /**
     * Calculate font size for tag
     *
     * Font size grows exponentionaly
     *
     * @param int $weight weight of tag
     * @param int $maxweight maximum weight
     * @return int
     */
    function font_size_by_weight($weight, $maxweight)
    {
        return $this->minfontsize + round((pow($weight, 2) / pow($maxweight, 2) *
            ($this->maxfontsize - $this->minfontsize)));
    }

    /**
     * Set font size bounds
     *
     * @param int $minsize
     * @param int $maxsize
     */
    function setFontSizeBounds($minsize, $maxsize)
    {
        $this->minfontsize = (int)$minsize;
        $this->maxfontsize = (int)$maxsize;
    }

    /**
     * Set template
     *
     * @param string $template path to template relative to
     */
    function setTemplate($template)
    {
        if (false !== strpos($template, '..')) {
            trigger_error("Module tagcloud: Template path is invalid !", E_USER_ERROR);
        } else {
            $this->template = $template;
        }
    }

    /**
     * check does the current user have access, should we return anything or not
     * @access private
     * @return boolean  true - ok, display, false - not ok
    */
    function checkAccess()
    {
        return true;
    }
}