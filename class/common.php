<?php
/**
 * Modera.net common functions
 *
 * @package modera_net
 * @access public
 * @author Siim Viidu
 * @version $Revision: 1027 $
 */

define('MODERA_PENDING_CREATION', 1);
define('MODERA_PENDING_CHANGES', 2);
define('MODERA_PENDING_REMOVAL', 3);

/**
 * Modera.net custom error handling function. depending on settings, will also output error to HTML or/and write
 * error log in XML format to file cache/error.log. Check config.php for additional error configuration
 *
 * @access private
 * @param string error name, type. It only works with the E_USER family of constants, and will default to E_USER_NOTICE. E_USER_ERROR is considered fatal
 * @param string Given error message
 * @param string the script that caused/initiated the error
 * @param integer linenumber where error occured
 * @param array available vars at time of error
 */

function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {

   // timestamp for the error entry
   $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
   $errortype = array (
               E_ERROR          => "Error",
               E_WARNING        => "Warning",
               E_PARSE          => "Parsing Error",
               E_NOTICE          => "Notice",
               E_CORE_ERROR      => "Core Error",
               E_CORE_WARNING    => "Core Warning",
               E_COMPILE_ERROR  => "Compile Error",
               E_COMPILE_WARNING => "Compile Warning",
               E_USER_ERROR      => "User Error",
               E_USER_WARNING    => "User Warning",
               E_USER_NOTICE    => "User Notice",
               );

   if (defined('E_STRICT')) $errortype[E_STRICT] = "Runtime Notice";

   // set of errors for which a var trace will be saved
   if (defined('ERRORS_DEFINE')) {
        $user_errors = unserialize(ERRORS_DEFINE);
   }
   else {
       $user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_USER_WARNING);
    }

    if (in_array($errno, $user_errors)) {

        if ($GLOBALS["modera_debug"] == true && $errno != E_USER_ERROR) {
            echo "\n\n<!--" . " ERROR " . $dt. " " . $errortype[$errno] . "(".$errno.") - " . $errmsg . "\n";
            echo "\t ".$filename." (line ".$linenum.") -->\n\n";
        }

        mt_srand();
        $err = "<errorentry>\n";
        $err .= "\t<id>" . (time() . mt_rand()) . "</id>\n";
        $err .= "\t<datetime>" . $dt . "</datetime>\n";
        $err .= "\t<errornum>" . $errno . "</errornum>\n";
        $err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
        $err .= "\t<errormsg>" . validXML($errmsg) . "</errormsg>\n";
        $err .= "\t<scriptname>" . validXML($filename) . "</scriptname>\n";
        $err .= "\t<scriptlinenum>" . validXML($linenum) . "</scriptlinenum>\n";

        $err .= "\t<usrclient>" . validXML($_SERVER['HTTP_USER_AGENT']) . "</usrclient>\n";
        $err .= "\t<usrip>" . validXML($_SERVER['REMOTE_ADDR']) . "</usrip>\n";
        $err .= "\t<usrrequest>" . validXML($_SERVER['REQUEST_URI']) . "</usrrequest>\n";

        //$err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
       $err .= "</errorentry>\n\n";

        // save to the error log
        if (ERRORS_LOG && @$err != "") {
            @error_log($err, 3, SITE_PATH. "/cache/error.log");
        }

        // send email to Admin with error
        if (ERRORS_EMAIL_ADMIN && $err != "" && validateEmail($GLOBALS["site_admin"])) {
           @error_log($err, 1, $GLOBALS["site_admin"]);
        }

        // Fatal type user specified error, exiting
        if ($errno == E_USER_ERROR) {
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>modera.net</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <style>
                    body{   margin:0px; margin-bottom:20px;     font-family:Verdana, Arial, Helvetica, sans-serif;  font-size:11px; color:black;}
                    a{ color:#7ba3d0;   text-decoration:none;}
                    a:hover{ color:#534968;     text-decoration:underline;}
                    a:active{ color:#c60000; }
                    a span,a:hover span{cursor:hand;cursor:pointer;}
                    p,ol,ul{    font-size:12px; line-height:15px;}
                    ul{     list-style-image:url(bullet_ul.gif);}
                    h1{     font-size:18px; font-weight: bold;      color:#c60000;}
                    h2{     font-size:12px;     color:#c60000; }
                    INPUT.checkbox, textinput {     background-color: transparent;  }
                    SELECT { font-family:Geneva, Arial, Helvetica, sans-serif;  font-size:11px;     color:#000;     background-color: #EEE; margin-right: 10px; }
                    BUTTON { font-family:Geneva, Arial, Helvetica, sans-serif;  font-size:11px;     background-color: #DDDDDD;  border-width: 1px;  }
                    BUTTON IMG { vertical-align: middle; }
                    #message-frame #box { width: 380px;  height: 150px; position: absolute; left: 50%;  top: 50%;   margin-left: -170px;    margin-top: -100px; }
                    #message-frame #box form {  margin: 0px; }
                    #message-frame #box input { width: 120px;   }
                    #message-frame #box #login { background-color: #CCC; padding: 10px; margin-top: 1px;    }
                </style>
            </head>
            <body id="message-frame">
                <div id="box">';

            echo "<h1>ERROR</h1>\n";
            echo "<h2>$errmsg</h2>\n";
            echo "<br/><br/>\n";
            echo "<p><a href=\"javascript:history.back()\">back to previous page</a></p>\n";
            echo "<p>&copy; modera.net</p>\n";

            echo '</div>
            </body>
            </html>';

            exit;
        }

    }

}

/**
 * Modera.net custom error trigger. Will generate a E_USER_NOTICE type of error
 *
 * @access public
 * @param string Given error message
 * @return boolean will return true in all cases
 */

function triggerError($error) {
   trigger_error($error, E_USER_NOTICE);
   return true;
}

/**
 * Will output XML safe strings, entities replaced by utf equivalent #..;
 *
 * @access public
 * @param string String text
 * @return string
 */

function validXML ($text) {
    //return entititesToUnicode(htmlentities($input, ENT_QUOTES, "UTF-8"));
   $text = str_replace("&","&amp;",$text);
   $text = str_replace("<","&lt;",$text);
   $text = str_replace(">","&gt;",$text);
   $text = str_replace("'","&apos;",$text);
   $text = str_replace("\"","&quot;",$text);
   return $text;
}

/**
 * Converts entities in a string to unicode entities
 *
 * @access public
 * @param string String text
 * @return string
 */

function entititesToUnicode ($input) {
  $htmlEntities = array_values (get_html_translation_table (HTML_ENTITIES, ENT_QUOTES));
  $entitiesDecoded = array_keys  (get_html_translation_table (HTML_ENTITIES, ENT_QUOTES));
  $num = count ($entitiesDecoded);
  for ($u = 0; $u < $num; $u++) {
   $utf8Entities[$u] = '&#'.ord($entitiesDecoded[$u]).';';
  }
          // replace &amp; back, we do not like the &#38; pos no 95
         if ($utf8Entities[95] == "&#38;") $utf8Entities[95] = "&amp;";

$output = str_replace ($htmlEntities, $utf8Entities, $input);
return $output;
}

//function validXML($text) {
//  $text = str_replace("&","&amp;",$text);
//  $text = str_replace("<","&lt;",$text);
//  $text = str_replace(">","&gt;",$text);
//  //$text = htmlentities($text);
//  return $text;
//}

/**
 * Format date according to chosen language and language file content.
 * The default Modera.net way to format dates. Locale-s are not needed
 *
 * @access public
 * @param string PHP style date formatting commands
 * @param mixed Unix_timestamp OR when using string 01/12/2005 type dates, the function will attempt to convert to Unix timestamp
 * @return mixed will return formatted date or False when problems
 */

function formatDate($format, $timestamp) {
    global $language;
//special cases
//D - A textual representation of a day, three letters Mon through Sun
//l (lowercase 'L') - A full textual representation of the day of the week Sunday through Saturday
//F - A full textual representation of a month, such as January or March January through December
//M - A short textual representation of a month, three letters Jan through Dec

    if (!is_numeric($timestamp)) {
        $timestamp = strtotime($timestamp);
    }

    $format = preg_replace("/D/", "X1X", $format);
    $format = preg_replace("/l/", "X2X", $format);
    $format = preg_replace("/F/", "X3X", $format);
    $format = preg_replace("/M/", "X4X", $format);

    $date = date($format, $timestamp);

    $txtf = new Text($language, "output");

    $date = preg_replace("/X1X/", substr($txtf->display("day_".date("w", $timestamp)),0,3), $date);
    $date = preg_replace("/X2X/", $txtf->display("day_".date("w", $timestamp)), $date);
    $date = preg_replace("/X3X/", $txtf->display("month_".date("n", $timestamp)), $date);
    $date = preg_replace("/X4X/", substr($txtf->display("month_".date("n", $timestamp)),0,3), $date);

    return $date;
}

/**
 * Format date according to chosen Locale (set in admin->settings)
 *
 * @access public
 * @param string PHP style date formatting commands
 * @param mixed Unix_timestamp OR when using string 01/12/2005 type dates, the function will attempt to convert to Unix timestamp
 * @return mixed will return formatted date or False when problems
 */

function formatDateLocale($format, $timestamp) {
    if (isset($format) && isset($timestamp)) {
        if (is_numeric($timestamp)) {
            return strftime($format, $timestamp);
        }
        else {
            // attempt to make the string type time format to unix timestamp
            return strftime($format, strtotime($timestamp));
        }
    }
    else {
        return false;
    }
}

/**
 * Set Locale for Modera.net and related modules. Locale's are not used by default, however custom modules and future
 * support is expected. E_USER_WARNING error is thrown when locale set failed
 *
 * @access public
 * @param string LC_ALL(default), LC_COLLATE, LC_CTYPE, LC_MONETARY, LC_NUMERIC, LC_TIME
 * @param string locale name, only UTF8 is supported by modera.net
 * @return boolean true/false depending on success.
 */

function moderaSetLocale($scope = LC_ALL, $locale) {
    if (isset($locale) && isset($scope)) {
        $status = setlocale($scope, $locale);
        if ($status == false || $status = "") {
            trigger_error("Modera.net: Set locale failed (".$locale."). Check if locale exists and locale functionality exists in the server", E_USER_WARNING);
            return false;
        }
        else {
            return true;
        }
    }
}

/**
 * Register module and check it's key when required
 *
 * Should be called in module class files following way:
 * <code>
 * if (!module_register('forum', '1.3.2', true)) {
 *     return;
 * }
 * </code>
 *
 * @param string $module Module name (e.g. "forum")
 * @param string $version Module version (e.g. 1.3.2)
 * @param bool $key_required if TRUE than module is key verification is required
 * @return bool
 * @access private
 */
function module_register($module, $version, $key_required = true)
{
    if ($key_required) {
        $key_const = 'MODULE_' . strtoupper($module) . '_KEY';
        if (!defined($key_const) || !check_key($module, $version, constant($key_const))) {
            return false;
        }
    }

    return true;
}

/**
 * Check key validity
 *
 * This function is used for checking core and module keys
 *
 * @param string $product
 * @param string $version
 * @param string $key
 * @return bool
 * @access private
 */
function check_key($product, $version, $key)
{
    $dt = "912xCvbZ" . $product . "vrzn" . $version . substr(SITE_URL, 8,4) . "X"
        . substr(SITE_URL, -6) . "1569" . base64_encode(SITE_URL) . "==" . $version;
    return substr(chunk_split(strtoupper(md5($dt)),4,"-"),0,-1) == $key;
}

/**
 * Modera.net license key checker
 *
 * @access private
 */


function hokusPokus() {
    if (!checkSitePath()) {
        trigger_error("WARNING! Attempt to access paths other than the current document root. Check Site path under config.", E_USER_ERROR);
        exit;
    }

    if (!MODERA_KEY || MODERA_KEY == "" || strlen(MODERA_KEY) != 39) {
        trigger_error("Modera.key is invalid or not found. Site will not run"
            . " without a valid Modera.key", E_USER_ERROR);
        return false;
    } else if (!check_key(MODERA_PRODUCT, MODERA_VERSION, MODERA_KEY)) {
        trigger_error("Modera.key is invalid. Site will not run without a valid"
            . " Modera.key", E_USER_ERROR);
        return false;
    } else {
        return true;
    }
}

/**
 * Function checks SITE_PATH with real path.
 * @return bool - if SITE_PATH is correct, returns TRUE, otherwise FALSE.
 */
function checkSitePath()
{
    $pathes = array(__FILE__);

    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $pathes[] = $_SERVER['PATH_TRANSLATED'];
    }

    if (isset($_SERVER['ORIG_PATH_TRANSLATED'])) {
        $pathes[] = $_SERVER['ORIG_PATH_TRANSLATED'];
    }

    $plen = strlen(SITE_PATH);
    foreach ($pathes as $path) {
        if (0 === strncmp($path, SITE_PATH, $plen)) {
            return true;
        }
    }

    return false;
}


/**
 * Check if chosen parameters in GET or POST exist and make a decision whether to use or not use cache
 *
 * @access public
 * @return boolean true/false false = do not use cache, true = use cache
 */

function checkParameters() {

  $usecache = true;

  if (sizeof($_POST) == 0) {
    $vars = array('nocache');
    if (is_array($vars)) {
        for ($c = 0; $c < sizeof($vars); $c++) {
            if ($_GET[$vars[$c]] != "") {
                $usecache = false;
                break;
            }
        }
    }
  }
  else {
    $usecache = false;
  }

  if ($GLOBALS["conf_tpl_cache_module"] == FALSE) $usecache=false;

  return $usecache;
}

/**
 * Generate universal URL, based on GET, POST and SESSION certain data.
 * currently only a number of general parameters are used. Primarily used by the general page cache
 * the default vars used are: language, user, structure, content, print, query, error, mode, articleid, topicid
 *
 * @param string the key to add to url
 * @param string the value to add to url
 * @param array array containing the keys to add to the universal url (keys from GET and POST)
 * @access public
 * @return string return the universal url, for example <i>?structure=001&content=2&language=EN&test=hello</i>
 */

function url ($key='', $val='', $vars='') {
  // only GET vars in here (siim)
  if (!is_array($vars)) {
    $vars = array(
      'structure',
      'content',
      'print',
      'query',
      'error',
      'mode',
      'articleid',
      'topicid'
      );
  }

  $url = '';
  $arr = array();
  $first = '?';

  $url .= $first.'language='.urlencode($_SESSION["language"]).'&user='.urlencode($GLOBALS["user_data"][0]);
  $first = '&';

  for ($i=0; $i < sizeof($vars); $i++) {
    $var = $vars[$i];
    $val_post = $_POST[$var];
    $val_get = $_GET[$var];
    if (isset($val_post) && ($var != $key)) {
      if (!empty($val_post)) {
        $url .= $first.$var.'='.urlencode($val_post);
        $first = '&';
      }
    }
    elseif (isset($val_get) && ($var != $key)) {
      if (!empty($val_get)) {
        $url .= $first.$var.'='.urlencode($val_get);
        $first = '&';
      }
    }
  }

  if (!empty($key) && !empty($val)) {
    $url .= $first.$key.'='.urlencode($val);
  }

  return $url;
}

/**
 * Delete cache files from cache/ folder based on regular expression type substring match
 * matching is done on the basis of matching either match1 OR match2
 *
 * @param string match1 to use (for example "tpl_news")
 * @param string match2 to use (for example "xslp_news")
 * @access public
 * @return boolean true/false
 */

function clearCacheFiles($match1, $match2) {
        if (ereg("\.", $match1) || ereg("\.", $match2)) return false;

        if ($match1 == "") $match1 = "xxxxxxxx";
        if ($match2 == "") $match2 = "xxxxxxxx";

        $opendir = SITE_PATH . "/cache";
        $files = array();
        if ($dir = @opendir($opendir)) {
          while (($file = readdir($dir)) !== false) {
              if (!is_dir($opendir . $file) && $file != "." && $file != ".." && $file != ".htaccess" && $file != "error.log" && (@preg_match("/^".$match1."/", $file) || @preg_match("/^".$match2."/", $file))) {
                  $files[] = $file;
              }
          }
          sort($files);
          reset($files);
        }
        for ($c = 0; $c < sizeof($files); $c++) {
            @unlink(SITE_PATH . "/cache/" . $files[$c]);
        }
    return true;
}

/**
 * Check if the string given is an URL, if not, add's url http:// or https:// part
 *
 * @param string the url to check
 * @access public
 * @return string final url
 */

function makeUrl ($url) {
    if (eregi('^http', $url)) {
        return $url;
    }
    else {
        if (isset($_SERVER["HTTPS"])) {
            return 'https://'.$url;
        }
        else {
            return 'http://'.$url;
        }
    }
}

/**
 * Make the string sql safe. currently only addslashes functionality is used
 *
 * @param string text
 * @access public
 * @return string
 */

function sqlsafe ($str) {
    //$str = str_replace("'", "''", $str);
    //$str = stripslashes($str);
    $str = addslashes($str);
  return $str;
}

/**
 * Jump to an internal modera.net address with adding the necessary parameters
 *
 * @param string url part, for example <i>?structure=001</i>
 * @access public
 */

function doJump ($param) {
    if (!ereg("\?", $param)) $param = "?" . $param;
    if (ereg($_SERVER['PHP_SELF'], $param)) {
        redirect($param);
    }
    else {
        //redirect($_SERVER['PHP_SELF'] . $param);
        redirect($param);
    }
    exit;
}

/**
 * Redirect to a new location, function can detect if headers have been sent or not. Corresponding error will be thrown
 *
 * @param string Url to redirect to. If URL has no HTTP* component, internal URL is assumed
 * @access public
 */

function redirect ($url) {
    // try to determine if htmlspecialchars() was applied to passed url
    // and convert &amp; back to &
    if (!preg_match('/&(?!amp;)/', $url)) {
        $url = str_replace('&amp;', '&', $url);
    }

    if (0 != strncasecmp($url, 'http', 4)) {
        if ($_SERVER['HTTPS'] && 0 == strncmp(SITE_URL, 'http:', 5)) {
            $site_url = 'https' . substr(SITE_URL, 4);
        } else {
            $site_url = SITE_URL;
        }

        if (0 == strncmp($url, '/', 1)) {
            $url = $site_url . $url;
        }
        else {
            $url = $site_url . "/" . $url;
        }
    }

    // close session write
    @session_write_close();

    // check if we have the necessary version
    if (version_compare(phpversion(), "4.3.0", "<")) {
        if (!headers_sent()) {
           header("Location: $url");
           exit;
        }
        else {
            trigger_error(
                "Cannot redirect to: <i>$url</i><br/>Headers already sent. <br/><br/>
                <p><a href=\"$url\">Click here to follow the link</a></p>",
                E_USER_ERROR);
        }
    }
    else {
        if (!headers_sent($filename, $linenum)) {
           header("Location: $url");
           exit;
        }
        else {
            trigger_error(
                "Cannot redirect to: <i>$url</i><br/>Headers sent at ($filename), line ($linenum) <br/><br/>
                <p><a href=\"$url\">Click here to follow the link</a></p>",
                E_USER_ERROR);
        }
    }
}

/**
 * Will add chosen parameter to all the <a> and <form> type HTML tags in the given string
 * used in case of adding random "rnd" parameter to each url to prevent browser caching for example
 *
 * @param string the string containg HTML to be replaced
 * @param string the string form parameter to add, for example <i>rnd=1234</i>
 * @access public
 * @return string the replced HTML
 */

function addParameterToUrl($where, $what) {
    $search = array(
       "'(<a[^>]*href=\"(?!http://|https://|ftp://|mailto:|javascript:|#)[^?\">]*\\?[^\">]*)\"'iU",
      "'(<a[^>]*href=\"(?!http://|https://|ftp://|mailto:|javascript:|#)[^?\">]*)\"'iU",
      "'(<form[^>]*action=\"(?!http://|https://|ftp://|mailto:|javascript:)[^?\">]*\\?[^\">]*)\"'iU",
      "'(<form[^>]*action=\"(?!http://|https://|ftp://|mailto:|javascript:)[^?\">]*)\"'iU"
      );
    $replace = array(
      '\\1&'.$what.'"',
       '\\1?'.$what.'"',
      '\\1&'.$what.'"',
       '\\1?'.$what.'"');
    $where = preg_replace($search, $replace, $where);

    return $where;
}

/**
 * Generates a random number in between 1 and 32768
 *
 * @access public
 * @return integer random number
 */

function randomNumber() {
    return rand(1, 32768);
}

/**
 * Simple email function using PHP-s builtin mail() function
 * the email will be text/plain, with charset UTF-8
 * when mail send fails and debug is set true, an HTML commented error will be echoed to output
 *
 * @access public
 * @param string From email field
 * @param string Reciepent email
 * @param string Subject of the message
 * @param string content of the message
 * @return boolean mail send status
 */

function sendEmailTo($from, $to, $subject, $text) {
    if ($to && $from && ereg("@", $to)) {
        $addit_header= "From: $from\nMime-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 7bit";
        $status = mail($to, $subject, $text, $addit_header);
        if ($status == false && $GLOBALS["modera_debug"] == true) {
            echo "<!-- sendEmailTo(): MAIL SEND FAILED -->\n";
        }
        return $status;
    }
    else {
        return $false;
    }
}

/**
 * Regular expression based general email address validity checker
 *
 * @access public
 * @param string Email address to check
 * @return boolean true for correct, false for incorrect address
 */

function validateEmail($email) {
$regex =
  '^'.
  '[_a-z0-9-]+'.        /* One or more underscore, alphanumeric,
                           or hyphen charactures. */
  '(\.[_a-z0-9-]+)*'.  /* Followed by zero or more sets consisting
                           of a period and one or more underscore,
                           alphanumeric, or hyphen charactures. */
  '@'.                  /* Followed by an "at" characture. */
  '[a-z0-9-]+'.        /* Followed by one or more alphanumeric
                           or hyphen charactures. */
  '(\.[a-z0-9-]{2,})+'. /* Followed by one or more sets consisting
                           of a period and two or more alphanumeric
                           or hyphen charactures. */
  '$';

if (eregi($regex, $email)) {
    return true;
}
else {
    return false;
}

}

/**
 * Convert UTF-8 string to ISO 8859-1 equivalent string.
 * This function uses Iconv library, if not found, the function will thrown and E_USER_ERROR
 *
 * @access public
 * @param string the UTF-8 encoded text
 * @return mixed ISO string when successful, false on failure
 */

function UTFtoISO($text) {
    return stringConversion("UTF-8", "ISO-8859-1//TRANSLIT", $text);
}

/**
 * Convert input encoded string to chosen output encoding.
 * This function uses Iconv library, if not found, the function will thrown and E_USER_ERROR
 *
 * @access public
 * @param string Input encoding, for example UTF-8
 * @param string Output encoding, for example ISO-8859-1
 * @param string The string to convert
 * @return mixed converted string when successful, false on failure
 */

function stringConversion($input_enc, $output_enc, $text) {
    if (function_exists(iconv)) {
        return iconv($input_enc, $output_enc, $text);
    }
    else {
        trigger_error("Iconv(): String conversion failure. Check Iconv library and functionality availability", E_USER_ERROR);
        return false;
    }
}


/**
 * Will strip all HTML from a given string and will replace P tag with double line feed and <BR> with single
 *
 * @access public
 * @param string The string containing HTML to strip
 * @return string Plain text string without HTML
 */

function returnPlainText($text) {
    $text = preg_replace("/<P>/", "\n\n", $text);
    $text = preg_replace("/<p>/", "\n\n", $text);
    $text = preg_replace("/<br>/", "\n", $text);
    $text = preg_replace("/<BR>/", "\n", $text);
    $text = preg_replace("/<br \/>/", "\n", $text);
    $text = preg_replace("/<BR \/>/", "\n", $text);
    $text = strip_tags($text);
    return $text;
}

/**
 * Process url - strip parts, add parts
 *
 * @param string $page address of the page to add to url, default is _SERVER['PHP_SELF']
 * @param string $query the string with parameters to work with, typically _SERVER['QUERY_STRING']
 * @param string $add the parameters to add, for exampe <i>parameter1=test&parameter2=1234</i>
 * @param array $skip array containing elements to strip from the url
 *  <i>array("parameter1, "parameter2")</i>
 * @return string resulting url
 */
function processUrl($page, $query, $add, $skip)
{
    if (!$page) {
        $page = $_SERVER['PHP_SELF'];
    }

    if (!is_array($skip)) {
        if ($skip) {
            $skip = array($skip);
        } else {
            $skip = array();
        }
    }

    // following parameters should be removed always from processing request query
    $skip[] = 'rnd';
    $skip[] = 'nocache';

    $params = array();

    // create old parameters array from $query varialbe
    // empty parameters will be discarded
    foreach (explode('&', $query) as $param_chunk) {
        if (strpos($param_chunk, '=')) {
            list($name, $value) = explode('=', $param_chunk);
            if ('' != $value) {
                $params[$name] = $value;
            }
        }
    }

    // remove specified query parameters
    // also remove empty parameters
    if ($skip) {
        foreach ($skip as $skip_param) {
            if (array_key_exists($skip_param, $params)) {
                unset($params[$skip_param]);
            }
        }
    }

    // construct new query
    if ($params) {
        $query = '';
        foreach ($params as $k => $v) {
            if ($query) {
                $query .= "&$k=$v";
            } else {
                $query .= "$k=$v";
            }
        }
        if ($add) {
            $query .= '&' . $add;
        }
    } else {
        $query = $add;
    }

    if ($query) {
        return htmlspecialchars($page . '?' . $query);
    } else {
        return htmlspecialchars($page . '#');
    }
}

  /**
 * Process Query string - strip parts, add parts
 *
 * @access public
 * @param string the string with parameters to work with, typically _SERVER['QUERY_STRING']
 * @param string the parameters to add, for exampe <i>parameter1=test&parameter2=1234</i>
 * @param array array containing elements to strip from the url <i>array("parameter1, "parameter2")</i>
 * @return string Finised URL
 */

  function processQuery ($query, $add, $skip) {

    if (is_array($skip)) {
        for ($t = 0; $t < sizeof($skip); $t++) {
            $query = ereg_replace("(&|\?)?".$skip[$t]."=([^&])*", "", $query);
        }
    }
    $query = ereg_replace("(&|\?)?rnd=([^&])*", "", $query);
    $query = ereg_replace("(&|\?)?nocache=([^&])*", "", $query);
    if (substr($query, -1) == "&" || substr($query, -1) == "?") $query = substr($query, 0, -1);
    if ($query) {
        if ($add) {
            $final = "?" . $query . "&" . $add;
        }
        else {
            $final = "?" . $query;
        }
    }
    else {
        if ($add) {
            $final = "?" . $add;
        }
        else {
            $final = "#";
        }
    }
    return $final;
  }

/**
 * Return HTML with page separation, allowing to split lists to multiple pages
 *
 * @access public
 * @param integer variable start, the point at which to start counting, the very fist element in the result list is 0. This variable will be added to the URL.
 * @param integer how many total results
 * @param string the url to add to all lins
 * @param integer maximum results per page
 * @param string Text for the link Previous
 * @param string Text for the link Next
 * @return string HTML with page links
 */

function resultPages($start, $total, $url, $maxresults, $txt_prev, $txt_next) {

$diff = $total;
$url = htmlspecialchars($url);

if (!$maxresults || $maxresults == 0) $maxresults = $total;

while ($diff > 0) {
    $diff -= $maxresults;
    $lk++;
}

if ($lk != 1) {

    $prev = $start - $maxresults;
    $next = $start + $maxresults;

    if ($prev > 0) {
        $result .= "&nbsp;<a href=\"$url&amp;start=$prev\" class=\"prev\">" . $txt_prev . "</a>&nbsp;\n";
    }
    if ($prev == 0) {
        $result .= "&nbsp;<a href=\"$url\" class=\"prev\">" . $txt_prev . "</a>&nbsp;\n";
    }


    $page = 1;

    $pos = ceil($start/$maxresults);
    if ($pos == 0) $pos = 1;

    if ($lk > 10) {

        $st = $pos-5; $en = $pos+5;
        if ($st < 0) { $st = 0; $en = 10; }
        if ($en > $lk) { $st = $lk-10; $en = $lk; }

        if ($st > 0) {
            $result .= "<i>...&nbsp;</i>";
        }

        $page = $st+1;
        for ($i = $st; $i < $en; $i++) {
                $add = $i * $maxresults;
            if ($add < $total) {
                if ($add == $start) { $result .= "<span>$page</span> \n"; }
                else if ($add == 0) { $result .= "<a href=\"$url\">$page</a> \n"; }
                else { $result .= "<a href=\"$url&amp;start=$add\">$page</a> \n";   }
            }
        $page++;
        }

        if ($en < $lk) {
            $result .= "<i>&nbsp;...</i>";
        }

    }
    else {
        for ($i = 0; $i < $lk ; $i++) {
            $add = $i * $maxresults;
            if ($add == $start) { $result .= "<span>$page</span> \n"; }
            else if ($add == 0) { $result .= "<a href=\"$url\">$page</a> \n"; }
            else { $result .= "<a href=\"$url&amp;start=$add\">$page</a> \n";   }
        $page++;
        }
    }

    if ($next < $total) { $result .= "&nbsp;<a href=\"$url&amp;start=$next\" class=\"next\">" . $txt_next . "</a>\n";  }

}

return  $result;
}

/**
 * Loads site settings from database and store it in global variable
 *
 * @param Database $database
 * @global array $GLOBALS['site_settings']
 * @global string $GLOBALS['language']
 */
function load_site_settings(&$database)
{
    $GLOBALS['site_settings'] = $database->fetch_first_row('SELECT * FROM `settings`');
    // resetting 'name' element, because it should be populated later
    // by load_site_name() function
    $GLOBALS['site_settings']['name'] = '';
}

/**
 * Load site from database and store it in global variable
 *
 * @param string $language
 * @global string $GLOBALS['site_settings']['lang']
 */
function load_site_name($language)
{
    require_once(SITE_PATH . '/class/ModeraTranslator.php');
    $translator = &ModeraTranslator::instance($language, 'output');
    $name = $translator->tr('site_name');

    if (('*site_name*' == $name || 'site_name' == $name) &&
        $language != $GLOBALS['site_settings']['lang'])
    {
        $translator =&ModeraTranslator::instance($GLOBALS['site_settings']['lang']
            , 'output');
        $name = $translator->tr('site_name');

    }

    $GLOBALS['site_settings']['name'] = $name;
}

/**
 * Check if current product is professional edition
 *
 * Return TRUE if current product is professional edition, FALSE otherwise
 *
 * @return bool
 */
function pro_version()
{
    static $pro;

    if (null === $pro) {
        $pro = '-pro' == substr(MODERA_PRODUCT, -4);
    }

    return $pro;
}

/**
 * Get base site path
 *
 * Base site path might be used for constructing urls relative to site root and
 * for specifying url path for http cookies. Path always starts and ends with
 * slash
 *
 * @staticvar string $path cache for determined base path
 * @uses SITE_URL
 * @return string
 */
function base_site_path()
{
    static $path;

    if (!$path) {
        if (false !== $pos = strpos(SITE_URL, '/', 8)) {
            $path = substr(SITE_URL, $pos) . '/';
        } else {
            $path = '/';
        }
    }

    return $path;
}