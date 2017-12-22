<?php
/**
 * Additional functionality, parse general structure, toptitle, subtitle, topstructure, content, keywords etc. tags
 *
 * @package modera_net
 * @version $Revision: 455 $
 */

if (!is_array($GLOBALS["parameters"]["template"])) {
    $GLOBALS["parameters"]["template"] = array();
}

$GLOBALS["parameters"]["template"]["TOPSTRUCTURE"] = '';
$GLOBALS["parameters"]["template"]["STRUCTURE"] = '';
$GLOBALS["parameters"]["template"]["CONTENT"] = $content;
$GLOBALS["parameters"]["template"]["SITEPATH"] = $sitepath;

$GLOBALS["parameters"]["template"]["PAGETITLE"] = $GLOBALS["pagedata"]["title"];
$GLOBALS["parameters"]["template"]["PAGETITLEENC"] = urlencode($GLOBALS["pagedata"]["title"]);

// SITE MENU - get content title, keyword and lead
$data = $database->fetch_first_row("
   SELECT
       `content`, `title`, `keywords`, `lead`
   FROM
       `content`
   WHERE
       `language` = ?
       AND `content` = ?
       AND `visible` = 1
   ORDER BY
       `zort` ASC;"
   , $language, $content);

if ($data) {
	$raw_title = $data["title"];
	foreach (array("keywords", "lead", "title") as $k) {
	    $data[$k] = htmlspecialchars($data[$k]);
	}

	$tpl->addDataItem("KEYWORDS", $data["keywords"]);
	$tpl->addDataItem("LEAD", $data["lead"]);
	$tpl->addDataItem("TOPTITLE", $data["title"]);
	$tpl->addDataItem("SUBTITLE", $data["title"]);
	$tpl->addDataItem("TOPTITLEENC", urlencode($raw_title));
	$tpl->addDataItem("SUBTITLEENC", urlencode($raw_title));

	$GLOBALS["parameters"]["template"]["KEYWORDS"] = $data["keywords"];
	$GLOBALS["parameters"]["template"]["TOPTITLE"] = $data["title"];
	$GLOBALS["parameters"]["template"]["SUBTITLE"] = $data["title"];
	$GLOBALS["parameters"]["template"]["TOPTITLEENC"] = urlencode($raw_title);
	$GLOBALS["parameters"]["template"]["SUBTITLEENC"] = urlencode($raw_title);
}