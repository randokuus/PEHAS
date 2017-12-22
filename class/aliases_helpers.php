<?php
/**
 * Helper functions for uri-aliases (nice urls)
 * @version $Id: aliases_helpers.php 493 2007-05-07 11:06:45Z alexandr.chertkov $
 */

/**
 * Check if uri alias is valid
 *
 * @param string $alias
 * @return bool
 */
function is_valid_uri_alias($alias) {
    if ('/' == $alias) return false;
    return preg_match('#^[0-9a-zA-Z/\-_:]*$#', $alias);
}

/**
 * Dispatch alias string
 *
 * Dispatch alias string that might contain several aliases separated by "|" (pipe) and
 * return array of extracted aliases.
 *
 * @param string $alias
 * @return array
 */
function dispatch_aliases($alias) {
    $alias = preg_replace('|/{2,}|', '/', $alias);
    $dispatched_aliases = array();
    foreach (explode('|', $alias) as $tmp_alias) {
        $tmp_alias = trim($tmp_alias);
        if (strlen($tmp_alias)) $dispatched_aliases[] = $tmp_alias;
    }

    return $dispatched_aliases;
}

/**
 * Recreates rewrite map file
 *
 * @param Database $database
 * @global string $GLOBALS['site_settings']['lang'] default site language
 */
function refresh_rewrite_map(&$database) {
    // extract site root
    $m = null;
    if (preg_match('%://[^/]+(/.+)$%', SITE_URL, $m)) {
        $site_root = "$m[1]/";
    } else {
        $site_root = '/';
    }

    // load available languages
    $res =& $database->query('SELECT `language` FROM `languages`');
    $languages = $res->fetch_all();

    $fp = fopen(SITE_PATH . '/cache/uri-aliases.map', 'w');

    // default aliases for switching languages
    $lang_res =& $database->query('SELECT `language`, `title` FROM `languages`');
    while (list($lang_code, $lang_title) = $lang_res->fetch_row()) {
        fwrite($fp, "$site_root$lang_code\t$site_root?language=$lang_code\n");
    }

    $res =& $database->query('SELECT `content`, `uri_alias`, `language` FROM `content`');
    while ($row = $res->fetch_assoc()) {
        foreach (dispatch_aliases($row['uri_alias']) as $alias) {
            if ('/' == substr($alias, 0, 1)) {
                $alias = substr($alias, 1);
            }

            $alias = $site_root . $alias;
            $link = "$site_root?content=$row[content]"
                . (strtolower($GLOBALS['site_settings']['lang'] != $row['language']
                    ? "&language=$row[language]" : ''));

            fwrite($fp, "$alias\t$link\n");
        }
    }

    fclose($fp);
}