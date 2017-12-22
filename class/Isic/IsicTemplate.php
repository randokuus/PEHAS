<?php

class IsicTemplate {
    private $cached = false;
    /**
     * @var Template
     */
    private $tpl;
    private $template = false;
    private $module = '';
    private $language = '';
    private $tmpl = false;

    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */

    private $cachelevel = TPL_CACHE_NOTHING;

    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */

    private $cachetime = 1440;

    public function __construct($module = '', $cachelevel = TPL_CACHE_NOTHING, $cachetime = 1440) {
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->module = $module;
        $this->cachelevel = $cachelevel;
        $this->cachetime = $cachetime;
        $this->usecache = checkParameters();
    }

    public function initTemplateInstance($templateName, $instanceParameters) {
        $this->tpl = new template;
        $this->tpl->setCacheLevel($this->cachelevel);
        $this->tpl->setCacheTtl($this->cachetime);

        $this->template = $GLOBALS['templates_' . $this->language][$this->tmpl][1] . '/' . $templateName;

        $this->tpl->setInstance($_SERVER["PHP_SELF"] . '?language=' . $this->language . '&module=' . $this->module . $instanceParameters);
        $this->tpl->setTemplateFile($this->template);
        $this->initCached();
        return $this->tpl;
    }

    private function initCached() {
        $this->cached = false;
        if ($this->tpl->isCached($this->template) == true && $this->usecache == true) {
            $GLOBALS['caching'][] = $this->module;
            if ($GLOBALS['modera_debug'] == true) {
                $this->cached = "<!-- module {$this->module} cached -->\n";
            }
            $this->cached .= $this->tpl->parse();
        }
    }

    public function getCached() {
        return $this->cached;
    }

    public function getTemplateInstance() {
        return $this->tpl;
    }

    public function getModuleTemplateId($module) {
        return array_search($module, $GLOBALS['templates_EN'][1][2]);
    }
}
