<?php
/**
 * Helper function for working with Modera cache/ directory
 *
 * @version $Revision: 572 $
 */

/**
 * Remove cached menu HTML and XML files
 *
 * @return int number of removed files
 */
function clearXSLPfiles()
{
    return clearCache('/^(?:xslp_sitemenu)|(?:xslp_menuxml)/');
}

/**
 * Clear cache files
 *
 * @param string $pattern only files from cache directory matching this perl regular
 *  expression will be removed, if not specified all files in cache directory
 *  will be removed
 * @return int number of removed files
 */
function clearCache($pattern = '')
{
    $cachedir = SITE_PATH . '/cache/';
    if (!is_readable($cachedir) || !is_writable($cachedir)) {
        trigger_error('Could not read or write files in cache directory', E_USER_WARNING);
        return 0;
    }

    $removed_files = 0;

    if ($dh = opendir($cachedir)) {
        while (false !== ($file = readdir($dh))) {
            // skip some patterns
            if (in_array($file, array('.', '..', '.htaccess', 'error.log'
                , 'uri-aliases.map')))
            {
                continue;
            }

            $filepath = $cachedir . $file;
            if (('' == $pattern || preg_match($pattern, $file)) && is_file($filepath)) {
                $removed_files += @unlink($filepath);
            }
        }
        closedir($dh);
    }

    return $removed_files;
}