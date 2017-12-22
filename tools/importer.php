<?php

/**
 * Modera.net language file importer
 * @access public
 */


require_once(dirname(__FILE__) . '/../class/config.php');
require_once(SITE_PATH . '/class/common.php');
require_once(SITE_PATH . '/class/mysql.class.php');
require_once(SITE_PATH . '/class/Database.php');
require_once(SITE_PATH . '/class/Locale.php');

error_reporting(E_ALL ^ E_NOTICE);

///////////////////////////////////////////////////////////////////////////////////////////////////
// Library
///////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Extract array of translations from language file
 *
 * Can extract translations only if no functions or concatenation is used
 * in language file:
 *
 * 'token' => 'translation' // will work
 * 'token' => 'translation' . "\n" // will not work
 *
 * @param string $data modera language file
 * @return array
 */
function extract_translations($data)
{
    //T_ML_COMMENT does not exist in PHP 5.
    //The following three lines define it in order to
    //preserve backwards compatibility.
    //The next two lines define the PHP 5 only T_DOC_COMMENT,
    //which we will mask as T_ML_COMMENT for PHP 4.
    if (!defined('T_ML_COMMENT')) {
       define('T_ML_COMMENT', T_COMMENT);
    } else {
       define('T_DOC_COMMENT', T_ML_COMMENT);
    }

    $translations = array();
    $module = $wait = $tr_token = null;

    foreach (token_get_all($data) as $token) {
        if (is_array($token)) {
            list($id, $text) = $token;

            switch ($id) {
                case T_CASE:
                    $wait ='module_name';
                    break;

                case T_CONSTANT_ENCAPSED_STRING:
                    switch ($wait) {
                        case 'module_name':
                            $module = trim($text, '"');
                            $translations[$module] = array();
                            $wait = 'array';
                            break;

                        case 'translation_token':
                            $tr_token = trim($text, '"');
                            $wait = 'translation_value';
                            break;

                        case 'translation_value':
                            $translations[$module][$tr_token] = trim($text, '"');
                            $wait = 'translation_token';
                    }
                    break;

                case T_ARRAY:
                    if ('array' == $wait) {
                        $wait = 'translation_token';
                    }
                    break;

                case T_BREAK;
                    $wait = null;
                    break;
            }
        }
    }

    return $translations;
}

/**
 * Get array of languages
 *
 * @param Database $database
 * @return array
 */
function languages(&$database)
{
    $languages = array();
    $res =& $database->query('SELECT `language`, `title` FROM `languages`');
    while ($row = $res->fetch_assoc()) {
        $languages[$row['language']] = $row['title'];
    }

    return $languages;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// Business logic
///////////////////////////////////////////////////////////////////////////////////////////////////

// data initialisation
$form = array('data' => '', 'language' => '');

$sql = new sql();
$sql->connect();
$database = new Database($sql);

// handle form upload
if ('POST' == $_SERVER['REQUEST_METHOD']) {
    if (array_key_exists('file', $_FILES) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        $form['data'] = fread($fp, filesize($_FILES['file']['tmp_name']));
        fclose($fp);

        // try to extract language code from file name
        if (preg_match('/_([A-Z]{2})\.php$/', $_FILES['file']['name'], $m)) {
            $form['language'] = strtolower($m[1]);
            // correct language code for Estonian
            if ('ee' == $form['language']) $form['language'] = 'et';
        }

    } else if (array_key_exists('data', $_POST)) {
        $form['data'] = $_POST['data'];
    }

    if (array_key_exists('import_type', $_POST) && in_array($_POST['import_type']
        , array('database'. 'sql')))
    {
        $import_type = $_POST['import_type'];
    } else {
        $import_type = 'sql';
    }

    if (array_key_exists('insert_data', $_POST)) {
        $language = $_POST['language'];

        // insert data into database
        foreach (extract_translations($form['data']) as $module => $translations) {
            $module = str_replace('\\"', '"', $module);
            foreach ($translations as $token => $translation) {
                $token = str_replace('\\"', '"', $token);
                $translation = str_replace('\\"', '"', $translation);

                @$database->query('INSERT INTO `tokens` (`token`, `domain`) VALUES (?@)'
                    , array($token, $module));
                @$database->query('INSERT INTO `translations` (`token`, `domain`, `language`, `translation`) VALUES (?@)'
                    , array($token, $module, $language, $translation));
            }
        }

        $form['notice'] = 'Data was successfully imported';

    } else {
        // parse data and print result
        $content = '';
        foreach (extract_translations($form['data']) as $module => $translations) {
            $module = str_replace('\\"', '"', $module);
            $content .= sprintf("<h2>Category: '%s'</h2>\n", htmlspecialchars($module));
            foreach ($translations as $token => $translation) {
                $token = str_replace('\\"', '"', $token);
                $translation = str_replace('\\"', '"', $translation);

                $content .= sprintf("<strong>%s</strong> - %s<br />\n", htmlspecialchars($token)
                    , $translation);
            }
        }
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<title>Modera.net import language files</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style>
				body {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;	color:black; }
				p,ol,ul {font-size:12px; line-height:15px; }
				h1{	font-size:18px;	font-weight: bold; color:#c60000; }
                fieldset        { border: 1px dashed #000; padding: 20px; }
                legend          { background: #fff; padding: 5px; font-weight: bold;}
                input.txtfield,
                textarea        { background-color: #efefef; }
		</style>
</head>
<body>
<h1>Modera.net import language files</h1>
<? if (!extension_loaded('tokenizer')): ?>
<p>Language file importer requires PHP <a href="http://www.php.net/tokenizer">tokenizer</a> extension.</p>
</body>
</html>
<? exit(); ?>
<? endif; ?>
<form method="post" enctype="multipart/form-data" action="<?php echo basename(__FILE__); ?>">
<fieldset>
    <legend>Select importing data</legend><br/>
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />

    <table border="0" cellpadding="5" cellspacing="2" border="0">
    <tr>
        <?php if(!empty($form['notice'])): ?>
        <td width="50%" style="background-color: #ffeeee;"><?php echo htmlspecialchars($form['notice'])?></td>
        <?php else: ?>
        <td width="50%">&nbsp;</td>
        <?php endif; ?>

        <td width="50%" align="left" style="background-color: #efefef;">
        <strong>NB!</strong> Translation strings in language file should not be wrapped by any
        functions and concatenation of strings should not be used. Only plain strings enclosed in quotes or double quotes are supported.<br/>
        Please make sure that language data is enclosed in PHP tags <code>&lt;?php ... ?&gt;</code>
        </td>
    </tr>
    </table>

    <table border="0" width="100%">
    <tr>
        <td><label for="file">Upload language file:</label></td>
        <td width="100%"><label for="data">Or insert language data:</label></td>
    </tr>
    <tr>
        <td valign="top"><input type="file" id="file" name="file" class="txtfield" /></td>
        <td><textarea id="data" name="data" rows="15" style="width:100%;" class="txtfield"><?php echo htmlspecialchars($form['data']); ?></textarea></td>
    </tr>
    </table>
    <br />
    <input type="submit" name="send_data" value="Send data" style="width: 300px;"/>
</fieldset>
<br /><br />
<?php if (!empty($content)): ?>
    <fieldset>
    <legend>Select destination language</legend>
            <label for="language">Language</label>
            <select id="language" name="language">
            <?php foreach (languages($database) as $code => $title): ?>
                <option value="<?php echo $code; ?>" <?php echo ($form['language'] == $code) ? 'selected="selected" ' : ''; ?>><?php echo htmlspecialchars($title); ?></option>
            <?php endforeach; ?>
            </select>
            <br /><br />
            <input type="submit" name="insert_data" value="Insert translations into datbase" style="width: 300px;"/>
    </fieldset>
<?php endif; ?>
</form>
<?php if (!empty($content)): ?>
<div style="padding: 10px; background-color: #efefef; border: 1px solid #ccc; margin-top: 20px;">
<?php echo $content ?>
</div>
<?php endif; ?>
</body>
</html>