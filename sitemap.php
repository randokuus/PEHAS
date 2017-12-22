<?php
/**
 * Sitemap builder script
 *
 * Sitemaps are an easy way for webmasters to inform search engines about pages
 * on their sites that are available for crawling. In its simplest form, a Sitemap
 * is an XML file that lists URLs for a site along with additional metadata about
 * each URL (when it was last updated, how often it usually changes, and how
 * important it is, relative to other URLs in the site) so that search engines
 * can more intelligently crawl the site.
 *
 * @link https://www.google.com/webmasters/tools/docs/en/protocol.html
 * @link http://www.sitemaps.org/index.html
 * @author Priit Pold <priit.pold@modera.net>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @version $Revision: 347 $
 */

require(dirname(__FILE__) . '/class/config.php');
require(SITE_PATH . '/class/common.php');
require(SITE_PATH . '/class/'.DB_TYPE.'.class.php');
require(SITE_PATH . '/class/Database.php');
require(SITE_PATH . '/class/templatef.class.php');

/**
 * Generate sitemap index file
 *
 * @link https://www.google.com/webmasters/tools/docs/en/protocol.html#sitemapFileRequirements
 * @param Database $db
 * @return string
 */
function get_sitemap_index(&$db)
{
    $sql = '
        SELECT
            DISTINCT(`languages`.`language`)
        FROM
            `languages` INNER JOIN `content` USING(`language`)
        WHERE
            `redirect` = 0 AND `visible` = 1 AND `login` != 1
    ';

    $sitemap = '';
    foreach ($db->fetch_first_col($sql) as $language) {
        // construct url
        $loc = SITE_URL . '/sitemap.php?get=sitemap&language=' . $language;
        $loc = htmlentities($loc);

        $sitemap .= "\n\t<sitemap>";
        $sitemap .= "\n\t\t<loc>$loc</loc>";
        $sitemap .= "\n\t</sitemap>";
    }

    return $sitemap;
}

/**
 * Genera sitemap file for specified language
 *
 * @link https://www.google.com/webmasters/tools/docs/en/protocol.html#sitemapXMLFormat
 * @param Database $db
 * @param string $language code of language for which sitemap will be generated
 * @return string
 */
function get_sitemap(&$db, $language)
{
    $sql = '
        SELECT
            `content`, `structure`
        FROM
            `content`
        WHERE
            `language` = ?
            AND `visible` = 1
            AND `redirect` = 0
            AND `login` = 0
    ';

    $structure = $db->fetch_all($sql, strtoupper($language));
    $sitemap = '';
    foreach ($structure as $row) {
        // construct url
        $loc = SITE_URL . "/?language=$language&structure=$row[structure]"
            . "&content=$row[content]";
        $loc = htmlentities($loc);

        $sitemap .= "\n\t<url>";
        $sitemap .= "\n\t\t<loc>$loc</loc>";
        $sitemap .= "\n\t</url>";
    }

    return $sitemap;
}

//
// General initialization
//

if ($modera_debug) {
    error_reporting(E_ALL ^ E_NOTICE);
}
$old_error_handler = set_error_handler("userErrorHandler");

if (!MODERA_ENABLE_SITEMAP) {
    trigger_error("Sitemap is disabled!", E_USER_ERROR);
}

$ATT = array_merge($_POST, $_GET);

$sql = new sql();
$sql->connect();
$database = new Database($sql);

// check if site is active
$site_active = $database->fetch_first_value('SELECT `active` FROM `settings` LIMIT 1');
if (!$site_active) {
    redirect("error.php?error=999");
}

//
// For caching xml output we will use template class.
//

$tpl = new template();
$tpl->tplfile = 'sitemap';
$tpl->setCacheLevel(TPL_CACHE_ALL);
$tpl->setCacheTtl(720);

switch($ATT['get']) {
    //
    // Sitemap protocol
    //
    case 'sitemap':

        $template_file = 'sitemap';
        $instance = $ATT['language'];

        if ($tpl->isCached($template_file, $instance)) {
            $sitemap = $tpl->getCachedPage($template_file, $instance);

        } else {
            $sitemap = "<?xml version='1.0' encoding='UTF-8'?>\n"
                . '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
                . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
                . ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"'
                . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                . get_sitemap($database, $ATT['language']) . "\n"
                . '</urlset>';

            $tpl->saveCachedPage($sitemap, $template_file, $instance);
        }

        header('Content-Type: text/xml');
        echo $sitemap;
        exit();

    //
    // Sitemap index protocol
    //
    case 'sitemapindex':

        $template_file = 'sitemapindex';
        $instance = 'instance';

        if ($tpl->isCached($template_file, $instance)) {
            $sitemap_index = $tpl->getCachedPage($template_file, $instance);

        } else {
            $sitemap_index = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
                . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
                . ' http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"'
                . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                . get_sitemap_index($database) . "\n"
                . '</sitemapindex>';

            $tpl->saveCachedPage($sitemap_index, $template_file, $instance);
        }

        header('Content-Type: text/xml');
        echo $sitemap_index;
        exit();
}

$url_components = parse_url(SITE_URL . '/sitemap.php');
$script_host  = htmlspecialchars($url_components['host']);
$script_path  = htmlspecialchars($url_components['path']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>SITE_URL sitemap</title>
    <style type="text/css">
        div.definition { margin-left: 1em; }
        span.value { color: darkblue; font-weight: bold; }
        span.param { color: darkgreen; font-weight: bold; }
        #content{ font-size: small; margin-left: 2em; }
        #content pre{
            background-color: #f5f5f5;
            font-family: Courier New, Courier;
            margin-right: 2em;
            padding: 5px;
            border: 1px #f0e0e5 solid;
        }
    </style>
</head>

<body>
<div id="content">
<h3 class="sub_heading">Sitemap Index HTTP GET</h3>
<div class="definition">
<p>Bellow is HTTP GET request and response examples for Sitemap Index.</p>

<pre>GET <?php echo $script_path; ?>?get=sitemapindex HTTP/1.1
Host: <?php echo $script_host; ?></pre>

<pre>HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8
Content-Length: <span class="value">length</span>

&lt;?xml version=&quot;1.0&quot;?&gt;

<span class="value">xml</span></pre>
</div>

<h3 class="sub_heading">Sitemap HTTP GET</h3>
<div class="definition">
<p>Bellow is HTTP GET request and response examples for Sitemap.</p>

<pre>GET <?php echo $script_path; ?>?get=sitemap&amp;<span class="param">language</span>=<span class="value">string</span> HTTP/1.1
Host: <?php echo $script_host; ?>
</pre>

<pre>HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8
Content-Length: <span class="value">length</span>

&lt;?xml version=&quot;1.0&quot;?&gt;

<span class="value">xml</span>
</pre>
</div></div>
</body>
</html>