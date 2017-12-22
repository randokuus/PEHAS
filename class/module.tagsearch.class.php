<?php
/**
 * <TPL_OBJECT:tagsearch>
 *   <TPL_OBJECT_PARAM:setTemplate('module_tagsearch.html')>
 *   <TPL_OBJECT_OUTPUT:show()>
 * </TPL_OBJECT:tagsearch>
 *
 * @version $Revision: 270 $
 */

class TagSearch
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

    function TagSearch()
    {
        $this->cachetime = 0;
        $this->cachelevel = TPL_CACHE_ALL;
        $this->content_module = false;
        $this->tplfile = 'tagsearch';

        $this->db =& $GLOBALS['database'];
        $this->vars = array_merge($_GET, $_POST);
        $this->language = $GLOBALS["language"];
        $this->tmpl = $GLOBALS["site_settings"]["template"];

        $this->debug = $GLOBALS["modera_debug"];
    }

    function show()
    {
        $tag = (string)$this->vars['search_tag'];

        $translator =& ModeraTranslator::instance($this->language, 'output');

        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if (!$this->template) $this->setTemplate("module_tagsearch.html");
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=tagsearch&template=".$template
          . "&tag=".$tag);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "tagsearch";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module tagsearch cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #### SEARCH RESULTS
        #---- Add search page title and messages
        $tpl->addDataItem("TITLE", $translator->tr("tagsearch_topic") . htmlspecialchars($tag));

        $res =& $this->db->query('
          SELECT
              `c`.`title`
              , `c`.`structure`
              , `c`.`content`
          FROM
              `content` AS `c` INNER JOIN `tags` `t` USING(`content`)
          WHERE
              `t`.`tag` = ?
          GROUP BY
              `c`.`content`'
        , $tag);

        if (!$res->num_rows()) {
            // no pages found
            $tpl->addDataItem("SEARCH_RESULTS", $translator->tr("search_notfound"));

        } else {
            $tpl->addDataItem("SEARCH_RESULTS", $translator->ntr('tagsearch_results', $res->num_rows(),
              array($res->num_rows())));

            while ($row = $res->fetch_assoc()) {
                $tpl->addDataItem("SEARCH_STRUCTURE.URL", SITE_URL . "/?structure=$row[structure]&content=$row[content]");
                $tpl->addDataItem("SEARCH_STRUCTURE.TITLE", htmlspecialchars($row['title']));

                // select tags for this page
                foreach ($this->db->fetch_all('SELECT `tag` FROM `tags` WHERE `content` = ?', $row['content']) as $row) {
                    $tag = htmlspecialchars($row['tag']);
                    $tpl->addDataItem('SEARCH_STRUCTURE.TAG.URL', SITE_URL . "/?search_tag=$tag");
                    $tpl->addDataItem('SEARCH_STRUCTURE.TAG.TAG', $tag);
                }
            }
        }

        return $tpl->parse();
    }

    /**
     * Set template
     *
     * @param string $template path to template relative to
     */
    function setTemplate($template)
    {
        if (false !== strpos($template, '..')) {
            trigger_error("Module tagsearch: Template path is invalid!", E_USER_ERROR);
        } else {
            $this->template = $template;
        }
    }
}