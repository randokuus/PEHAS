<?php

/**
 * This script is needed for testing if server environment is suitable for
 * running Modera or not.
 *
 * @version $Revision: 713 $
 */

//
// catch image request
//

if (isset($_GET['img'])) {
    display_image($_GET['img']);
}

//
// process requested tab
//

if (isset($_GET['tab']) && $_GET['tab'] > 0) {
    $active_tab = intval($_GET['tab']);
} else {
    $active_tab = 0;
}

if (!defined('PHP_SAPI')) {
    define('PHP_SAPI', php_sapi_name());
}

//
// include config.php and common.php files
//

ob_start();
$included = include_once('class/config.php');
$included &= include_once('class/common.php');
ob_end_clean();

if (!$included) {
    exit('Could not include class/config.php or class/common.php files');
}

//
// perform testing
//

$tabs = array(
    'Server components' => array(

        '<p>This program checks the necessary server components'
            . ' needed by Modera.net to run.</p><p><strong>NB!</strong> This script'
            . ' should be executed from the same folder where modera is installed.</p>',

        'Server environment' => array(
            array(
                $s = $r = ETester::phpVersion(PHP_VERSION),
                $r ? 'PHP version: ' . PHP_VERSION
                    : 'Your PHP version is older than required 4.1.2',
            ),
            array(
                $s = $r = ETester::serverApi(PHP_SAPI),
                $r ? 'Server api: ' . PHP_SAPI
                    : 'Your server api is not supported',
            ),
            // array(
            //     $s = $r = ETester::ioncubeAvailable(),
            //     $r ? 'ionCube Loaders are correctly installed'
            //         : 'ionCube Loaders are not installed. Modera.net requires'
            //             . ' ionCube loader to be available. Visit'
            //             . ' <a href="http://www.ioncube.com/loaders.php">http://www.ioncube.com/loaders.php</a>'
            //             . ' to download ionCube Loaders.',
            // ),
        ),

        'Installed PHP extensions' => array(
            array(
                $s &= $r = ETester::phpExtension('mysql'),
                $r ? 'MySQL extension'
                    : 'MySQL extension is not available. Check <a href="http://www.php.net/mysql">'
                        . 'http://www.php.net/mysql</a> for more information about this extension.',
            ),
            array(
                $s &= $r = ETester::phpExtension('xml'),
                $r ? 'XML Parser Functions'
                    : 'xml extension is not available. Check <a href="http://www.php.net/xml">'
                        . 'http://www.php.net/xml</a> for more information about this extension.',
            ),
            array(
                $s &= $r = ETester::phpExtension('pcre'),
                $r ? 'Regular Expression Functions (Perl-Compatible)'
                    : 'pcre extension is not available. Check <a href="http://www.php.net/pcre">'
                        . 'http://www.php.net/pcre</a> for more information about this extension.',
            ),
        ),

        'status' => $s,
    ),

    'Modera setup' => array(
        '<p>This program checks the necessary server components'
            . ' needed by Modera.net to run.</p><p><strong>NB!</strong> This script'
            . ' should be executed from the same folder where modera is installed.</p>',

        'Filesystem permissions' => array(
            array(
                $s = $r = ETester::dirIsWritable(SITE_PATH . '/upload'),
                $r ? '/upload/ - is writable' : '/upload/ - should be writable by web server',
            ),
            array(
                $s &= $r = ETester::dirIsWritable(SITE_PATH . '/img'),
                $r ? '/img/ - is writable' : '/img/ - should be writable by web server',
            ),
            array(
                $s &= $r = ETester::dirIsWritable(SITE_PATH . '/tmpl'),
                $r ? '/tmpl/ - is writable' : '/tmpl/ - should be writable by web server',
            ),
            array(
                $s &= $r = ETester::dirIsWritable(SITE_PATH . '/cache'),
                $r ? '/cache/ - is writable' : '/cache/ - should be writable by web server',
            ),
        ),

        'Configuration parameters' => array(
            array(
                $s &= $r = ETester::mysqlCredentials(DB_USER, DB_PASS, DB_HOST),
                $r ? 'Successfully connected to MySQL database with parameters specified in class/config.php'
                    : 'Could not connect to MySQL database with parameters specified in class/config.php',
            ),
            array(
                $s &= $r = ETester::spathIsValid(SITE_PATH),
                $r ? 'SITE_PATH is correct'
                    : sprintf("Incorrect SITE_PATH specified in config.php. <strong>'%s'"
                        . "</strong> found while <strong>'%s'</strong> expected.", SITE_PATH
                            , dirname($_SERVER["SCRIPT_FILENAME"])),
            ),
        ),

        'status' => $s,
    ),

    'Recommended componenets' => array(
        '<p>This program checks the necessary server components'
            . ' needed by Modera.net to run.</p><p><strong>NB!</strong> This script'
            . ' should be executed from the same folder where modera is installed.</p>',

        'GD library extensions' => array(
            array(
                $s = $r = ETester::phpExtension('gd'),
                $r ? 'GD library'
                    : 'GD extesion is not available. Some parts of Modera.net and'
                        . ' modules might use GD libary with freetype support.',
            ),
        ),

        'Image Convertor extensions' => array(
            array(
                $s &= $r = ETester::phpFunction('exec'),
                $r ? 'PHP function exec() is allowed'
                    : 'PHP function exec() is not allowed. It used to execute external programs like imagemagick.',
            ),
            array(
                $s &= (bool)$r = ETester::imVersion(IMAGE_CONVERT),
                $r ? 'Imagemagick "convert" utility is available. Version: ' . htmlspecialchars($r)
                    : 'Imagemagick "convert" is not available.',
            )
        ),

        'status' => $s,
    ),
);

//
// perform exceptional tests
//

if (version_compare(PHP_VERSION, '5', '>=')) {
    // xsl
    array_push($tabs['Server components']['Installed PHP extensions'], array(
        $tabs['Server components']['status'] &= $r = ETester::phpExtension('xsl'),
        $r ? 'XSL Functions'
            : 'xsl extension is not available. Check <a href="http://www.php.net/xsl">'
                . 'http://www.php.net/xsl</a> for more information about this extension.'
    ));
} else {
    // xslt
    array_push($tabs['Server components']['Installed PHP extensions'], array(
        $tabs['Server components']['status'] &= $r = ETester::phpExtension('xslt'),
        $r ? 'XSLT Functions'
            : 'xslt extension is not available. Check <a href="http://www.php.net/xslt">'
                . 'http://www.php.net/xslt</a> for more information about this extension.'
    ));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Modera.net installation tester</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body {
            padding: 10px 50px;
            font: normal 62.5% Verdana, Arial, Helvetica, sans-serif;
            color:black;
        }

        h1 { font-size: 2em; }

        .notice {
            background-color: #ffffaa;
            padding: 10px;
            margin-bottom: 2em;
            border: 1px dashed #ffbb77;
            float: right;
            width: 30em;
        }

        #container { font-size: 1.2em; }
        #navcontainer { margin-bottom: 20px; }

        #navlist {
            list-style-type: none;
            padding: 14px 5px 27px 5px;
            margin: 0;
            border-bottom: 1px solid #999;
            background: #efefef;
        }

        #navlist li {
            float: left;
            height: 26px;
            background-color: #ddd;
            margin: 0 5px;
            border-top: 1px solid #999;
            border-left: 1px solid #999;
            border-right: 1px solid #999;
        }

        #navlist li a {
            float: left;
            display: block;
            background-position: left center;
            background-repeat: no-repeat;
            text-decoration: none;
            margin-bottom: -2px;
            padding: 8px 10px 2px 30px;
            color: blue;
        }

        #navlist li a.passed { background-image: url('test_server.php?img=check_ok_gray'); }
        #navlist li a.failed { background-image: url('test_server.php?img=check_fail_gray'); }

        #navlist li.active a.passed, #navlist li a.passed:hover {
            background-color: #fff;
            background-image: url('test_server.php?img=check_ok');
            border-bottom: 5px solid #fff;
        }

        #navlist li.active a.failed, #navlist li a.failed:hover {
            background-color: #fff;
            background-image: url('test_server.php?img=check_fail');
        }

        label.group {
            font-size: 1.5em;
            font-weight: bold;
            display: block;
            padding-bottom: 0.5em;
        }

        ul.checklist {
            margin: 0 0 2em 0;
            padding: 0;
            list-style: none;
        }

        ul.checklist li {
            margin: 0;
            padding: 2px 0 0 40px;
            height: 22px;
            background-position: left center;
            background-repeat: no-repeat;
        }

        ul.checklist li.passed {
            background-image: url('test_server.php?img=check_ok');
            color: #339933;
        }

        ul.checklist li.failed {
            background-image: url('test_server.php?img=check_fail');
            color: #993333;
        }
    </style>

    <script type="text/javascript">
    <!--
        var cur_tab = 'tab<?php echo $active_tab ?>';

        function selectTab(num)
        {
            var id = 'tab' + num;

            if (!document.getElementById(cur_tab)) {
                cur_tab = 'tab0';
            }

            if (document.getElementById(id)) {
                document.getElementById(cur_tab).style.display = 'none';
                document.getElementById(cur_tab+'_menu').className = 'nactive';
                cur_tab = id;
                document.getElementById(cur_tab).style.display = 'block';
                document.getElementById(cur_tab+'_menu').className = 'active';
            }
        }

        function changeHref()
        {
            var navlist = document.getElementById('navlist');
            var tab_id = 0;
            for (var i = 0; i < navlist.childNodes.length; i++) {
                var li = navlist.childNodes[i];
                if (1 == li.nodeType && 'li' == li.tagName.toLowerCase()) {
                    for (var j = 0; j < li.childNodes.length; j++) {
                        var a = li.childNodes[j];
                        if (1 == a.nodeType && 'a' == a.tagName.toLowerCase()) {
                            a.href = "javascript:selectTab('" + tab_id + "');void(0);";
                        }
                    }
                    tab_id++;
                }
            }
        }
    -->
    </script>
</head>

<body onload="changeHref()">
<div id="container">
    <h1>Modera.net server test program</h1>

    <div id="navcontainer">
        <ul id="navlist">
            <?php $tab = 0; foreach ($tabs as $tab_name => $tab_data):
                // iteration variables initialization
                $style_att = $active_tab == $tab ? 'class="active"' : '';
                $status_cls = $tab_data['status'] ? 'passed' : 'failed';
            ?>
                <li id="tab<?php echo $tab ?>_menu" <?php echo $style_att ?>>
                    <a class="<?php echo $status_cls ?>" href="test_server.php?tab=<?php echo $tab ?>"><?php echo $tab_name ?></a>
                </li>
            <?php $tab++; endforeach ?>
        </ul>
    </div>
    <?php $tab = 0; foreach ($tabs as $tab_data):
        // iteration variables initialization
        unset($tab_data['status']);
        $notice = array_shift($tab_data);
        $style_att = $active_tab != $tab ? 'style="display:none"' : '';
    ?>
        <div id="tab<?php echo $tab ?>" <?php echo $style_att ?>>
            <div class="notice"><?php echo $notice ?></div>
            <?php foreach ($tab_data as $group_name => $group_data): ?>
                <label class="group"><?php echo $group_name ?></label>
                <ul class="checklist">
                    <?php foreach ($group_data as $test_data):
                        // iteration variables initialization
                        $test_class = array_shift($test_data) ? 'passed' : 'failed';
                        $test_text = array_shift($test_data);
                    ?>
                        <li class="<?php echo $test_class ?>"><?php echo $test_text ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endforeach ?>
        </div>
    <?php $tab++; endforeach ?>
</div>
</body>
</html>

<?php
/**
 * Static class with Environment Testing helper
 *
 * @static
 */
class ETester
{
    /**
     * Check passed php version with minimum required for running Modera
     *
     * @param string $version minimum suitable php version
     * @return bool
     */
    public static function phpVersion($version)
    {
        return version_compare(phpversion(), $version, ">=");
    }

    /**
     * Check if current server api is suitable for running Modera
     *
     * NB! At the moment all available php installations are suitable
     *
     * @param string $sapi
     * @return bool
     */
    public static function serverApi($sapi)
    {
        return true;
    }

    /**
     * Check if ioncube loaders are available
     *
     * @return bool
     */
    public static function ioncubeAvailable()
    {
        if (!extension_loaded('ionCube Loader')) {
            // trying to load ionCube extension
            $__oc = strtolower(substr(php_uname(), 0, 3));
            $__ln = '/ioncube/ioncube_loader_' . $__oc . '_' . substr(phpversion(), 0, 3)
                . (($__oc=='win') ? '.dll' : '.so');
            $__oid = $__id = realpath(ini_get('extension_dir'));
            $__here = dirname(__FILE__);

            if ((@$__id[1])==':') {
                $__id = str_replace('\\', '/', substr($__id, 2));
                $__here = str_replace('\\', '/', substr($__here, 2));
            }

            $__rd = str_repeat('/..', substr_count($__id, '/')) . $__here . '/';
            $__i = strlen($__rd);

            while ($__i--) {
                if ($__rd[$__i] == '/') {
                    $__lp = substr($__rd, 0, $__i) . $__ln;
                    if(file_exists($__oid . $__lp)){
                        $__ln = $__lp;
                        break;
                    }
                }
            }

            @dl($__ln);

            if (!function_exists('_il_exec')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if specified php extension is available
     *
     * @param string $extension
     * @return bool
     */
    public static function phpExtension($extension)
    {
        return extension_loaded($extension);
    }

    /**
     * Check if specified function is available
     *
     * @param string $function
     * @return bool
     */
    public static function phpFunction($function)
    {
        return function_exists($function);
    }

    /**
     * Check if specified directory is writable by web server
     *
     * @param string $dir path to directory
     * @return bool
     */
    public static function dirIsWritable($dir)
    {
        return file_exists($dir) && is_writable($dir);
    }

    /**
     * Check if mysql credentials are valid
     *
     * @param string $username
     * @param string $password
     * @param string $host
     * @return bool
     */
    public static function mysqlCredentials($username, $password, $host)
    {
        if (mysql_connect(DB_HOST, DB_USER, DB_PASS)) {
            mysql_close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if site path is valid
     *
     * @param string $site_path
     * @return bool
     */
    public static function spathIsValid($site_path)
    {
        if (function_exists('checkSitePath') && checkSitePath()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if imagemagick is available
     *
     * @var stirng $path path to "convert" utility
     * @return FALSE|string
     */
    public static function imVersion($path)
    {
        if (trim($path) == '' || !ETester::phpFunction('exec')) {
            return false;
        } else {

            $ret_arr = array();
            $ret_val = '';
            exec("$path -version", $ret_arr, $ret_val);
            if (0 === $ret_val) {
                if (preg_match('/^Version: ImageMagick ([\d\.]+) /', $ret_arr[0], $m)) {
                    return $m[1];
                }
            }

            return false;
        }
    }
}

/**
 * Display image
 *
 * @param string image name
 */
function display_image($image_name)
{
    switch ($image_name) {
        case 'check_ok':
            $img = 'R0lGODlhIAAgAPcAAP//////zP//mf//Zv//M///AP/M///MzP/Mmf/MZv/MM//MAP+Z//+ZzP+Zmf+'
                .'ZZv+ZM/+ZAP9m//9mzP9mmf9mZv9mM/9mAP8z//8zzP8zmf8zZv8zM/8zAP8A//8AzP8Amf8AZv8AM/8A'
                .'AMz//8z/zMz/mcz/Zsz/M8z/AMzM/8zMzMzMmczMZszMM8zMAMyZ/8yZzMyZmcyZZsyZM8yZAMxm/8xmz'
                .'MxmmcxmZsxmM8xmAMwz/8wzzMwzmcwzZswzM8wzAMwA/8wAzMwAmcwAZswAM8wAAJn//5n/zJn/mZn/Zp'
                .'n/M5n/AJnM/5nMzJnMmZnMZpnMM5nMAJmZ/5mZzJmZmZmZZpmZM5mZAJlm/5lmzJlmmZlmZplmM5lmAJk'
                .'z/5kzzJkzmZkzZpkzM5kzAJkA/5kAzJkAmZkAZpkAM5kAAGb//2b/zGb/mWb/Zmb/M2b/AGbM/2bMzGbM'
                .'mWbMZmbMM2bMAGaZ/2aZzGaZmWaZZmaZM2aZAGZm/2ZmzGZmmWZmZmZmM2ZmAGYz/2YzzGYzmWYzZmYzM'
                .'2YzAGYA/2YAzGYAmWYAZmYAM2YAADP//zP/zDP/mTP/ZjP/MzP/ADPM/zPMzDPMmTPMZjPMMzPMADOZ/z'
                .'OZzDOZmTOZZjOZMzOZADNm/zNmzDNmmTNmZjNmMzNmADMz/zMzzDMzmTMzZjMzMzMzADMA/zMAzDMAmTM'
                .'AZjMAMzMAAAD//wD/zAD/mQD/ZgD/MwD/AADM/wDMzADMmQDMZgDMMwDMAACZ/wCZzACZmQCZZgCZMwCZ'
                .'AABm/wBmzABmmQBmZgBmMwBmAAAz/wAzzAAzmQAzZgAzMwAzAAAA/wAAzAAAmQAAZgAAMwAAAFnDWVKvU'
                .'mHNYWvOa2/Qb3bZdnXRdX7bfn7UfluUW4rZin7DfpXblZDAkLjmuKXPpWyHbMHnwY+pj4GWgZysnK61rq'
                .'WqpcXHxbm7ud3e3WjSZ2nNaHDWb4TZg4riiXSzc5jnl6HjoK7lrf7+/v39/fr6+vHx8evr6ywAAAAAIAA'
                .'gAAAI/wABCBxIsKDBgwgTKlzIsKHDhxAjSlz4D168iQn9tUun7iLGgv02miOnrt9HgvDMmcsnbty7kwJX'
                .'nDOnTxw4cOv4nfzHjiY5cN643VvxkZ87ffp+Bp23hyhGeOf05QO6rc4ed/32TYzHTio4blXDsTM50Z86q'
                .'fXAaguH1WG8FToF7mt3jiW3eWvbNvynrtxLge+g2K2ztl3chuzy5VPnD0A8dYOxhYNHliG/eOXy4RPH7j'
                .'HLbdokwzuMGJ9pe+PuifsmL3S4d1of9lN3ut63b/TwYkO3IjZEd/fs2fvWjV7rbOjikZYND9294seTL4/'
                .'4zx26cNmyrfsHc6C/Fezc+RHz3R3AvvPl06tfz769+4YBAQA7';
            break;

        case 'check_fail':
            $img = 'R0lGODlhIAAgAPcAAP//////zP//mf//Zv//M///AP/M///MzP/Mmf/MZv/MM//MAP+Z//+ZzP+Zmf+'
                .'ZZv+ZM/+ZAP9m//9mzP9mmf9mZv9mM/9mAP8z//8zzP8zmf8zZv8zM/8zAP8A//8AzP8Amf8AZv8AM/8A'
                .'AMz//8z/zMz/mcz/Zsz/M8z/AMzM/8zMzMzMmczMZszMM8zMAMyZ/8yZzMyZmcyZZsyZM8yZAMxm/8xmz'
                .'MxmmcxmZsxmM8xmAMwz/8wzzMwzmcwzZswzM8wzAMwA/8wAzMwAmcwAZswAM8wAAJn//5n/zJn/mZn/Zp'
                .'n/M5n/AJnM/5nMzJnMmZnMZpnMM5nMAJmZ/5mZzJmZmZmZZpmZM5mZAJlm/5lmzJlmmZlmZplmM5lmAJk'
                .'z/5kzzJkzmZkzZpkzM5kzAJkA/5kAzJkAmZkAZpkAM5kAAGb//2b/zGb/mWb/Zmb/M2b/AGbM/2bMzGbM'
                .'mWbMZmbMM2bMAGaZ/2aZzGaZmWaZZmaZM2aZAGZm/2ZmzGZmmWZmZmZmM2ZmAGYz/2YzzGYzmWYzZmYzM'
                .'2YzAGYA/2YAzGYAmWYAZmYAM2YAADP//zP/zDP/mTP/ZjP/MzP/ADPM/zPMzDPMmTPMZjPMMzPMADOZ/z'
                .'OZzDOZmTOZZjOZMzOZADNm/zNmzDNmmTNmZjNmMzNmADMz/zMzzDMzmTMzZjMzMzMzADMA/zMAzDMAmTM'
                .'AZjMAMzMAAAD//wD/zAD/mQD/ZgD/MwD/AADM/wDMzADMmQDMZgDMMwDMAACZ/wCZzACZmQCZZgCZMwCZ'
                .'AABm/wBmzABmmQBmZgBmMwBmAAAz/wAzzAAzmQAzZgAzMwAzAAAA/wAAzAAAmQAAZgAAMwAAAMIAANMKC'
                .'tcXF9siIpwZGeIqKt0pKcAkJN8uLt8yMtYxMeE2NuY7O+I6OpkoKOM9Peg/P+NAQORCQuZFRedMTMlISO'
                .'hVVeplZaRLS+57e9V9ffCOjvGtrXFSUvrAwIh5eaaUlLKurv7+/vv7+/f39/Hx8ejo6NfX1ywAAAAAIAA'
                .'gAAAI/wABCBxIsKDBgwgTKlzIsKHDhxAjPtzHb19Cfv9W+HPILx++j/n6FezYBZ5JfPwW9rNCz55Levj+'
                .'CdTX7147deTIjQOCb2E+efSCCsW3Qp+/e+nWpdMZDhy8jQnxxXNAj6qDq/dWIFVablxTb2T+6UO4T2q8e'
                .'WjTxuvSjqtXcN62hU24b0WOd/Hy6n3HTl26c2+9dftWDypCfvfeKV7sjp3Sc+W+xiWTb6zCf13eudvMzr'
                .'E6wJK3mbvnz7LCfF04ewYdzltcc4HEOswHr/O6z13BwRUd+6E+K+1W5969jds9iLTd3YYceNs2bdq44TO'
                .'tsF8X4eN0x30OPRu3fAz35Ztr67frV+fQtWVbb06mQn5dbqczD9fct/Trsx3Bds9iQn/wuPUVbIFwo956'
                .'RyRojmEH/QOPOvR1A9s/R3GDYIJHcLNCdV2AVl8gRdF0DzcYJoiIewnlQ4Y44nyDSFb6WMaPFYisgQ02a'
                .'wTSD3UF6TNjPfUEgk9pBv1zTz0vFrWQPvtk5A8/PAIwFkYURqlQjBJlqeWWXHbpJQABAQA7';
            break;

        case 'check_fail_gray':
            $img = 'R0lGODlhIAAUAPcAAP//////zP//mf//Zv//M///AP/M///MzP/Mmf/MZv/MM//MAP+Z//+ZzP+Zmf+'
                .'ZZv+ZM/+ZAP9m//9mzP9mmf9mZv9mM/9mAP8z//8zzP8zmf8zZv8zM/8zAP8A//8AzP8Amf8AZv8AM/8A'
                .'AMz//8z/zMz/mcz/Zsz/M8z/AMzM/8zMzMzMmczMZszMM8zMAMyZ/8yZzMyZmcyZZsyZM8yZAMxm/8xmz'
                .'MxmmcxmZsxmM8xmAMwz/8wzzMwzmcwzZswzM8wzAMwA/8wAzMwAmcwAZswAM8wAAJn//5n/zJn/mZn/Zp'
                .'n/M5n/AJnM/5nMzJnMmZnMZpnMM5nMAJmZ/5mZzJmZmZmZZpmZM5mZAJlm/5lmzJlmmZlmZplmM5lmAJk'
                .'z/5kzzJkzmZkzZpkzM5kzAJkA/5kAzJkAmZkAZpkAM5kAAGb//2b/zGb/mWb/Zmb/M2b/AGbM/2bMzGbM'
                .'mWbMZmbMM2bMAGaZ/2aZzGaZmWaZZmaZM2aZAGZm/2ZmzGZmmWZmZmZmM2ZmAGYz/2YzzGYzmWYzZmYzM'
                .'2YzAGYA/2YAzGYAmWYAZmYAM2YAADP//zP/zDP/mTP/ZjP/MzP/ADPM/zPMzDPMmTPMZjPMMzPMADOZ/z'
                .'OZzDOZmTOZZjOZMzOZADNm/zNmzDNmmTNmZjNmMzNmADMz/zMzzDMzmTMzZjMzMzMzADMA/zMAzDMAmTM'
                .'AZjMAMzMAAAD//wD/zAD/mQD/ZgD/MwD/AADM/wDMzADMmQDMZgDMMwDMAACZ/wCZzACZmQCZZgCZMwCZ'
                .'AABm/wBmzABmmQBmZgBmMwBmAAAz/wAzzAAzmQAzZgAzMwAzAAAA/wAAzAAAmQAAZgAAMwAAAN3d3cIAA'
                .'NMKCtcXF5sXF9siIr8jI+IqKt0pKd8uLt8yMtYxMeA2NpsmJuI6Oug/P+M+PuRBQeZFRYEoKOdMTNFHR+'
                .'hVVbtFReplZZxNTe57e9R8fPCOjvGtrfrAwGtWVop1damRkZCIiNzc3Nra2tXV1cHBwbS0tCwAAAAAIAA'
                .'UAAAI/wCxCRzYz0q+fPqsrBgosGCXeO/i6evHsGJDfPXsaayX79/AFfjeoTNnjhwQffssMrQyr57Llx2x'
                .'rejSTt1IcuLGkVmoUiA+eQ7qBXVAFJ8VfO3S3RQX7hsZjz2x6ZMnj57Vq/RopktXkik4cOug9vyXAx7Vs'
                .'/LgsVOnFGe4r93u8VTJrx8+eHjzulvL1ZxXcN3W6Ys68F8XeO4Ss+OLjhy5t4DLBZpL2EoXxYwdQ+5W7p'
                .'4/whWtxFvM9ma4t4EDiQWNbZ++d4xLbu7GDR8/1gNFx36Mutu2bdxQ4p651mbX3r+3aeNmJSVofTWVHgf'
                .'s+7e262FB9+vCTrpbcOW8JWa/ru3IkXvOe/qL591r53vclF83f2QdZYvrz03v/G9FIG7z0cfNahb1E09j'
                .'7t3zT0p2cZMNffURWBE/VpAxzjjerKOac3Xpg8ga2WSzhlyE7dOPPvfcE4g+nzG0Dz//BIIIIqpZFBAAO'
                .'w==';
            break;

        case 'check_ok_gray':
            $img = 'R0lGODlhIAAUAPcAAP//////zP//mf//Zv//M///AP/M///MzP/Mmf/MZv/MM//MAP+Z//+ZzP+Zmf+'
                .'ZZv+ZM/+ZAP9m//9mzP9mmf9mZv9mM/9mAP8z//8zzP8zmf8zZv8zM/8zAP8A//8AzP8Amf8AZv8AM/8A'
                .'AMz//8z/zMz/mcz/Zsz/M8z/AMzM/8zMzMzMmczMZszMM8zMAMyZ/8yZzMyZmcyZZsyZM8yZAMxm/8xmz'
                .'MxmmcxmZsxmM8xmAMwz/8wzzMwzmcwzZswzM8wzAMwA/8wAzMwAmcwAZswAM8wAAJn//5n/zJn/mZn/Zp'
                .'n/M5n/AJnM/5nMzJnMmZnMZpnMM5nMAJmZ/5mZzJmZmZmZZpmZM5mZAJlm/5lmzJlmmZlmZplmM5lmAJk'
                .'z/5kzzJkzmZkzZpkzM5kzAJkA/5kAzJkAmZkAZpkAM5kAAGb//2b/zGb/mWb/Zmb/M2b/AGbM/2bMzGbM'
                .'mWbMZmbMM2bMAGaZ/2aZzGaZmWaZZmaZM2aZAGZm/2ZmzGZmmWZmZmZmM2ZmAGYz/2YzzGYzmWYzZmYzM'
                .'2YzAGYA/2YAzGYAmWYAZmYAM2YAADP//zP/zDP/mTP/ZjP/MzP/ADPM/zPMzDPMmTPMZjPMMzPMADOZ/z'
                .'OZzDOZmTOZZjOZMzOZADNm/zNmzDNmmTNmZjNmMzNmADMz/zMzzDMzmTMzZjMzMzMzADMA/zMAzDMAmTM'
                .'AZjMAMzMAAAD//wD/zAD/mQD/ZgD/MwD/AADM/wDMzADMmQDMZgDMMwDMAACZ/wCZzACZmQCZZgCZMwCZ'
                .'AABm/wBmzABmmQBmZgBmMwBmAAAz/wAzzAAzmQAzZgAzMwAzAAAA/wAAzAAAmQAAZgAAMwAAAN3d3VO2U'
                .'1vGW2LOYmvOa2/Qb3bZdnXRdX7bflWTVX7UforZipXbla7jrpXBlWJ9YrnquXiTeKTHpMDnwI+qj4SchJ'
                .'CWkKeqp8HCwbS1tGjSZ2nNaHDWb4TZg4riiXGwcHzBe5jnl6HjoI29jNzc3Nra2tjY2NHR0SwAAAAAIAA'
                .'UAAAI/wCxCRxIsKBBbCvewTvIsCHBf+/WsVvosCLBflbWoSPXrp9Fi/zelUOnbxy+dx8rxlOHrtw4ceLS'
                .'eUx5cAW7luTEfft2Lx5Ng/3clSuX81u3Ont8/iT4Tl05fTq5IXXnb+lAeOyeiusmNRxVflax/ROqr163e'
                .'du8Vv0ILx5Ygfys6Ct5Nq27mRZXtNuHUuA7c3TraAtnBa9Fd3Pb/cOGle62wVbefoy3T1++ce7gtSvJ7X'
                .'G4d5I/ustH2h6+e+PAddZ2DvTPfu1K1wPnjZ68bdnOubXq7p49e7Rt49bNL3RKf+/O3aMnPDfFsGBXuDs'
                .'XLlu4cyvCHvwXz527xT8DAgA7';
            break;

        default:
            header("HTTP/1.0 404 Not Found");
            exit();
    }

    header("Content-type: image/gif");
    header("Cache-control: public");
    header("Expires: Thu, 01 Dec 2050 16:00:00 GMT");
    echo base64_decode($img);
    exit();
}