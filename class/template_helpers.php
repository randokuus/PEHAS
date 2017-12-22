<?php
/**
 * @version $Revision: 644 $
 */

/**
 * Helper function to work with templates.
 *
 * @author Priit Põld <priit.pold@modera.net>
 * @static
 */
class templateHelpers{

    /**
     * Find templates, which contains $module.
     * If it would find templates,
     *  then it will return array with templates numbers
     *
     * @static
     * @param string $language
     * @param string $module
     * @return array
     */
    function findTemplatesWithModule($language, $module){
        static $templates;

        if (is_null($templates)) {
            $templates = array();
        }

        $language = strtoupper($language);

        if (isset($templates[$language][$module])) {
            return $templates[$language][$module];
        }

        $_tmpls = $GLOBALS["templates_$language"][1][2];

        if (!isset($_tmpls) || !count($_tmpls)) {
            $templates[$language][$module] = array();
            return $templates[$language][$module];
        }

        $tpl_dir = $GLOBALS["templates_$language"][1][1];

        foreach ($_tmpls as $tmpl_nr => $tmpl_name) {
            $modules = templateHelpers::scanTemplateForModules($tmpl_nr, $language);
            if (!is_array($modules) || !count($modules)) {
                continue;
            }
            foreach ($modules as $_module) {
                $templates[$language][$_module][] = $tmpl_nr;
            }
        }

        return $templates[$language][$module];
    }

    /**
     * Scan content for module existance in it.
     * If content contains some module, it will return array of module names
     *
     * @static
     * @param string $content
     * @return array
     */
    function scanContentForModules($content){
        // fetch all object declarations from passed content
        preg_match_all("/<([\\/]?TPL_OBJECT[^>]*):([^>]*)>/i", $content, $tags, PREG_SET_ORDER);

        $modules = array();
        foreach ($tags as $tag) {
            if ("TPL_OBJECT" == strtoupper($tag[1])) {
                $title = $tag[2];
                $o = new $title;
                if ($o->content_module) {
                    $modules[] = $title;
                }
            }
        }
        return $modules;
    }
    /**
     * Scan template for module existance in it.
     * Template number is used as template identifier.
     *
     * @static
     * @param int $tpl_nr
     * @param string $language
     * @return array
     */
    function scanTemplateForModules($tpl_nr, $language){
        static $modules = array();

        if (isset($modules[$language][$tpl_nr])) {
            return $modules[$language][$tpl_nr];
        }

        $tpl_file = SITE_PATH . "/" . $GLOBALS["templates_$language"][1][1]
            . "/" . $GLOBALS["templates_$language"][1][2][$tpl_nr] . ".html";

        if (!file_exists($tpl_file) || !($fp = fopen($tpl_file, "r"))) {
            return false;
        }
        $content = "";
        while ($_data = fread($fp, 2048)) {
            $content .= $_data;
        }
        fclose($fp);

        $modules[$language][$tpl_nr] = templateHelpers::scanContentForModules($content);
        return $modules[$language][$tpl_nr];
    }
}