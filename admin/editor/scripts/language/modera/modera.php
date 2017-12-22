<?php
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

error_reporting(0);
if (empty($_GET["type"])) exit;
require_once("../../../../../class/config.php");
require_once(SITE_PATH . "/class/common.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/Database.php");

if (!MODERA_KEY || !MODERA_PRODUCT) {
	header("Content-type: application/x-javascript");
	//echo "self.close();";
	exit;
}

// ##############################################################

// init session object
$ses = new Session();

// create database instance
// using database connection id from Session instance
$sql = new sql();
$sql->con = $ses->dbc;
$database = new Database($sql);
$GLOBALS['database'] =& $database;
load_site_settings($database);
unset($sql);

// init language object
$lan = new AdminLanguage($language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();

// init Text object for this page
$txt = new Text($language2, "editor_all");

// #######################################################

$element["bookmark"] = '
function loadTxt()
    {
    document.getElementById("txtLang").innerHTML = "'.$txt->display("name").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("bookmark_title").'")
    }
';

$element["border"] = '
function loadTxt()
    {
    document.getElementById("txtLang").innerHTML = "'.$txt->display("color").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
	{
	switch(s)
		{
		case "No Border": return "'.$txt->display("no_border").'";
		case "Outside Border": return "'.$txt->display("outside_border").'";
		case "Left Border": return "'.$txt->display("left_border").'";
		case "Top Border": return "'.$txt->display("top_border").'";
		case "Right Border": return "'.$txt->display("right_border").'";
		case "Bottom Border": return "'.$txt->display("bottom_border").'";
		case "Pick": return "'.$txt->display("pick").'";
		case "Custom Colors": return "'.$txt->display("custom_colors").'";
		case "More Colors...": return "'.$txt->display("more_colors").'";
		default: return "";
		}
	}
function writeTitle()
	{
	document.write("'.$txt->display("border_title").'")
    }
';

$element["box"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("color").'";
    txtLang[1].innerHTML = "'.$txt->display("box_Shading").'";

    txtLang[2].innerHTML = "'.$txt->display("margin").'";
    txtLang[3].innerHTML = "'.$txt->display("left").'";
    txtLang[4].innerHTML = "'.$txt->display("right").'";
    txtLang[5].innerHTML = "'.$txt->display("top").'";
    txtLang[6].innerHTML = "'.$txt->display("bottom").'";

    txtLang[7].innerHTML = "'.$txt->display("padding").'";
    txtLang[8].innerHTML = "'.$txt->display("left").'";
    txtLang[9].innerHTML = "'.$txt->display("right").'";
    txtLang[10].innerHTML = "'.$txt->display("top").'";
    txtLang[11].innerHTML = "'.$txt->display("bottom").'";

    txtLang[12].innerHTML = "'.$txt->display("box_Dimension").'";
    txtLang[13].innerHTML = "'.$txt->display("width").'";
    txtLang[14].innerHTML = "'.$txt->display("height").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("pixels").'";
    optLang[1].text = "'.$txt->display("percent").'";
    optLang[2].text = "'.$txt->display("pixels").'";
    optLang[3].text = "'.$txt->display("percent").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
		case "No Border": return "'.$txt->display("no_border").'";
		case "Outside Border": return "'.$txt->display("outside_border").'";
		case "Left Border": return "'.$txt->display("left_border").'";
		case "Top Border": return "'.$txt->display("top_border").'";
		case "Right Border": return "'.$txt->display("right_border").'";
		case "Bottom Border": return "'.$txt->display("bottom_border").'";
		case "Pick": return "'.$txt->display("pick").'";
		case "Custom Colors": return "'.$txt->display("custom_colors").'";
		case "More Colors...": return "'.$txt->display("more_colors").'";
        default: return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("box_title").'")
    }
';

$element["characters"] = '
function loadTxt()
    {
    document.getElementById("txtLang").innerHTML = "'.$txt->display("characters_1").'";
    document.getElementById("btnClose").value = "'.$txt->display("close").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("characters_title").'")
    }
';

$element["color"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("colors_1").'";
    txtLang[1].innerHTML = "'.$txt->display("colors_NamedColors").'";
    txtLang[2].innerHTML = "'.$txt->display("colors_216WebSafe").'";
    txtLang[3].innerHTML = "'.$txt->display("colors_New").'";
    txtLang[4].innerHTML = "'.$txt->display("colors_Current").'";
    txtLang[5].innerHTML = "'.$txt->display("custom_colors").'";

    document.getElementById("btnAddToCustom").value = "'.$txt->display("colors_btnAddToCustom").'";
    document.getElementById("btnCancel").value = "'.$txt->display("colors_btnCancel").'";
    document.getElementById("btnRemove").value = "'.$txt->display("colors_btnRemove").'";
    document.getElementById("btnApply").value = "'.$txt->display("colors_btnApply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("colors_title").'")
    }

';

$element["editor_lang"] = '

LanguageDirectory="modera";

function getTxt(s)
	{
	switch(s)
		{
		case "Save":return "'.$txt->display("editor_lang_Save").'";
		case "Preview":return "'.$txt->display("preview").'";
		case "Full Screen":return "'.$txt->display("editor_lang_FullScreen").'";
		case "Search":return "'.$txt->display("search").'";
		case "Check Spelling":return "'.$txt->display("editor_lang_CheckSpelling").'";
		case "Text Formatting":return "'.$txt->display("editor_lang_TextFormatting").'";
		case "List Formatting":return "'.$txt->display("editor_lang_ListFormatting").'";
		case "Paragraph Formatting":return "'.$txt->display("editor_lang_ParagraphFormatting").'";
		case "Styles":return "'.$txt->display("styles").'";
		case "Custom CSS":return "'.$txt->display("editor_lang_CustomCSS").'";
		case "Styles & Formatting":return "'.$txt->display("editor_lang_StylesFormatting").'";
 		case "Style Selection":return "'.$txt->display("editor_lang_style_selection").'";
		case "Paragraph":return "'.$txt->display("editor_lang_Paragraph").'";
		case "Font Name":return "'.$txt->display("editor_lang_FontName").'";
		case "Font Size":return "'.$txt->display("editor_lang_FontSize").'";
		case "Cut":return "'.$txt->display("cut").'";
		case "Copy":return "'.$txt->display("copy").'";
		case "Paste":return "'.$txt->display("paste").'";
		case "Undo":return "'.$txt->display("undo").'";
		case "Redo":return "'.$txt->display("redo").'";
		case "Bold":return "'.$txt->display("bold").'";
		case "Italic":return "'.$txt->display("italic").'";
		case "Underline":return "'.$txt->display("underline").'";
		case "Strikethrough":return "'.$txt->display("editor_lang_Strikethrough").'";
		case "Superscript":return "'.$txt->display("superscript").'";
		case "Subscript":return "'.$txt->display("subscript").'";
		case "Justify Left":return "'.$txt->display("editor_lang_JustifyLeft").'";
		case "Justify Center":return "'.$txt->display("editor_lang_JustifyCenter").'";
		case "Justify Right":return "'.$txt->display("editor_lang_JustifyRight").'";
		case "Justify Full":return "'.$txt->display("editor_lang_JustifyFull").'";
		case "Numbering":return "'.$txt->display("editor_lang_Numbering").'";
		case "Bullets":return "'.$txt->display("editor_lang_Bullets").'";
		case "Indent":return "'.$txt->display("editor_lang_Indent").'";
		case "Outdent":return "'.$txt->display("editor_lang_Outdent").'";
		case "Left To Right":return "'.$txt->display("editor_lang_LeftToRight").'";
		case "Right To Left":return "'.$txt->display("editor_lang_RightToLeft").'";
		case "Foreground Color":return "'.$txt->display("editor_lang_ForegroundColor").'";
		case "Background Color":return "'.$txt->display("editor_lang_BackgroundColor").'";
		case "Hyperlink":return "'.$txt->display("editor_lang_Hyperlink").'";
		case "Bookmark":return "'.$txt->display("bookmark").'";
		case "Special Characters":return "'.$txt->display("editor_lang_SpecialCharacters").'";
		case "Image":return "'.$txt->display("image").'";
		case "Flash":return "'.$txt->display("editor_lang_Flash").'";
		case "Media":return "'.$txt->display("editor_lang_Media").'";
		case "Content Block":return "'.$txt->display("editor_lang_ContentBlock").'";
		case "Internal Link":return "'.$txt->display("editor_lang_InternalLink").'";
		case "Object":return "'.$txt->display("editor_lang_Object").'";
		case "Insert Table":return "'.$txt->display("editor_lang_InsertTable").'";
		case "Table Size":return "'.$txt->display("editor_lang_TableSize").'";
		case "Edit Table":return "'.$txt->display("editor_lang_EditTable").'";
		case "Edit Cell":return "'.$txt->display("editor_lang_EditCell").'";
		case "Table":return "'.$txt->display("editor_lang_Table").'";
		case "Border & Shading":return "'.$txt->display("editor_lang_BorderShading").'";
		case "Show/Hide Guidelines":return "'.$txt->display("editor_lang_ShowHideGuidelines").'";
		case "Absolute":return "'.$txt->display("editor_lang_Absolute").'";
		case "Paste from Word":return "'.$txt->display("editor_lang_PastefromWord").'";
		case "Line":return "'.$txt->display("editor_lang_Line").'";
		case "Form Editor":return "'.$txt->display("editor_lang_FormEditor").'";
		case "Form":return "'.$txt->display("editor_lang_Form").'";
		case "Text Field":return "'.$txt->display("editor_lang_TextField").'";
		case "List":return "'.$txt->display("editor_lang_List").'";
		case "Checkbox":return "'.$txt->display("editor_lang_Checkbox").'";
		case "Radio Button":return "'.$txt->display("editor_lang_RadioButton").'";
		case "Hidden Field":return "'.$txt->display("editor_lang_HiddenField").'";
		case "File Field":return "'.$txt->display("editor_lang_FileField").'";
		case "Button":return "'.$txt->display("button").'";
		case "Clean":return "'.$txt->display("editor_lang_Clean").'";
		case "View/Edit Source":return "'.$txt->display("editor_lang_ViewEditSource").'";
		case "Tag Selector":return "'.$txt->display("editor_lang_TagSelector").'";
		case "Clear All":return "'.$txt->display("editor_lang_ClearAll").'";
		case "Tags":return "'.$txt->display("editor_lang_Tags").'";

		case "Heading 1":return "'.$txt->display("editor_lang_Heading_1").'";
		case "Heading 2":return "'.$txt->display("editor_lang_Heading_2").'";
		case "Heading 3":return "'.$txt->display("editor_lang_Heading_3").'";
		case "Heading 4":return "'.$txt->display("editor_lang_Heading_4").'";
		case "Heading 5":return "'.$txt->display("editor_lang_Heading_5").'";
		case "Heading 6":return "'.$txt->display("editor_lang_Heading_6").'";
		case "Preformatted":return "'.$txt->display("editor_lang_Preformatted").'";
		case "Normal (P)":return "'.$txt->display("editor_lang_NormalP").'";
		case "Normal (DIV)":return "'.$txt->display("editor_lang_NormalDIV").'";

		case "Size 1":return "'.$txt->display("editor_lang_Size_1").'";
		case "Size 2":return "'.$txt->display("editor_lang_Size_2").'";
		case "Size 3":return "'.$txt->display("editor_lang_Size_3").'";
		case "Size 4":return "'.$txt->display("editor_lang_Size_4").'";
		case "Size 5":return "'.$txt->display("editor_lang_Size_5").'";
		case "Size 6":return "'.$txt->display("editor_lang_Size_6").'";
		case "Size 7":return "'.$txt->display("editor_lang_Size_7").'";

		case "Are you sure you wish to delete all contents?":
			return "'.$txt->display("editor_lang_deleteallcontents").'";
		case "Remove Tag":return "'.$txt->display("editor_lang_RemoveTag").'";
		case "Custom Colors":return "'.$txt->display("custom_colors").'";
		case "More Colors...":return "'.$txt->display("more_colors").'";
		case "Box Formatting":return "'.$txt->display("editor_lang_BoxFormatting").'";
		case "Advanced Table Insert":return "'.$txt->display("editor_lang_AdvancedTableInsert").'";
		case "Edit Table/Cell":return "'.$txt->display("editor_lang_EditTableCell").'";
		case "Print":return "'.$txt->display("editor_lang_Print").'";
		case "Paste Text":return "'.$txt->display("editor_lang_PasteText").'";
        case "CSS Builder":return "'.$txt->display("editor_lang_css_builder").'";
		case "Remove Formatting":return "'.$txt->display("editor_lang_remove_formatting").'";
		default:return "";
		}
	}
';

$element["flash"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("source").'";
    txtLang[1].innerHTML = "'.$txt->display("background").'";
    txtLang[2].innerHTML = "'.$txt->display("width").'";
    txtLang[3].innerHTML = "'.$txt->display("height").'";
    txtLang[4].innerHTML = "'.$txt->display("flash_Quality").'";
    txtLang[5].innerHTML = "'.$txt->display("flash_Align").'";
    txtLang[6].innerHTML = "'.$txt->display("flash_Loop").'";
    txtLang[7].innerHTML = "'.$txt->display("yes").'";
    txtLang[8].innerHTML = "'.$txt->display("no").'";

    txtLang[9].innerHTML = "'.$txt->display("flash_ClassID").'";
    txtLang[10].innerHTML = "'.$txt->display("flash_CodeBase").'";
    txtLang[11].innerHTML = "'.$txt->display("flash_PluginsPage").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("flash_Low").'"
    optLang[1].text = "'.$txt->display("flash_High").'"
    optLang[2].text = "'.$txt->display("flash_NotSet").'"
    optLang[3].text = "'.$txt->display("left").'"
    optLang[4].text = "'.$txt->display("right").'"
    optLang[5].text = "'.$txt->display("top").'"
    optLang[6].text = "'.$txt->display("bottom").'"

    document.getElementById("btnPick").value = "'.$txt->display("pick").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Custom Colors": return "'.$txt->display("custom_colors").'";
        case "More Colors...": return "'.$txt->display("more_colors").'";
        default: return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("flash_title").'")
    }
';

$element["form_button"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("type").'";
    txtLang[1].innerHTML = "'.$txt->display("name").'";
    txtLang[2].innerHTML = "'.$txt->display("value").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("button").'"
    optLang[1].text = "'.$txt->display("form_button_Submit").'"
    optLang[2].text = "'.$txt->display("reset").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_button_title").'")
    }
';

$element["form_check"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("name").'";
    txtLang[1].innerHTML = "'.$txt->display("value").'";
    txtLang[2].innerHTML = "'.$txt->display("default").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("checked").'"
    optLang[1].text = "'.$txt->display("unchecked").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_check_title").'")
    }
';

$element["form_file"] = '
function loadTxt()
    {
    document.getElementById("txtLang").innerHTML = "'.$txt->display("name").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_file_title").'")
    }
';

$element["form_form"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("name").'";
    txtLang[1].innerHTML = "'.$txt->display("form_form_Action").'";
    txtLang[2].innerHTML = "'.$txt->display("form_form_Method").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_form_title").'")
    }
';

$element["form_hidden"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("name").'";
    txtLang[1].innerHTML = "'.$txt->display("value").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_hidden_title").'")
    }

';

$element["form_list"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("name").'";
    txtLang[1].innerHTML = "'.$txt->display("size").'";
    txtLang[2].innerHTML = "'.$txt->display("form_list_Multipleselect").'";
    txtLang[3].innerHTML = "'.$txt->display("value").'";

    document.getElementById("btnAdd").value = "'.$txt->display("form_list_btnAdd").'";
    document.getElementById("btnUp").value = "'.$txt->display("form_list_btnUp").'";
    document.getElementById("btnDown").value = "'.$txt->display("form_list_btnDown").'";
    document.getElementById("btnDel").value = "'.$txt->display("form_list_btnDel").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_list_title").'")
    }
';

$element["form_radio"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("name").'";
    txtLang[1].innerHTML = "'.$txt->display("value").'";
    txtLang[2].innerHTML = "'.$txt->display("default").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("checked").'"
    optLang[1].text = "'.$txt->display("unchecked").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_radio_title").'")
    }
';

$element["form_text"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("type").'";
    txtLang[1].innerHTML = "'.$txt->display("name").'";
    txtLang[2].innerHTML = "'.$txt->display("size").'";
    txtLang[3].innerHTML = "'.$txt->display("form_text_MaxLength").'";
    txtLang[4].innerHTML = "'.$txt->display("form_text_NumLine").'";
    txtLang[5].innerHTML = "'.$txt->display("value").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("form_text_Text").'"
    optLang[1].text = "'.$txt->display("form_text_Textarea").'"
    optLang[2].text = "'.$txt->display("form_text_Password").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("form_text_title").'")
    }
';

$element["hyperlink"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("source").'";
    txtLang[1].innerHTML = "'.$txt->display("hyperlink_Page").'";
    txtLang[2].innerHTML = "'.$txt->display("bookmark").'";
    txtLang[3].innerHTML = "'.$txt->display("hyperlink_Target").'";
    txtLang[4].innerHTML = "'.$txt->display("title").'";
    txtLang[5].innerHTML = "'.$txt->display("hyperlink_Linktype").'";
    txtLang[6].innerHTML = "'.$txt->display("hyperlink_Plainlink").'";
    txtLang[7].innerHTML = "'.$txt->display("hyperlink_Imagelink").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("hyperlink_Self").'"
    optLang[1].text = "'.$txt->display("hyperlink_Blank").'"
    optLang[2].text = "'.$txt->display("hyperlink_Parent").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("hyperlink_title").'")
    }
';

$element["image"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("source").'";
    txtLang[1].innerHTML = "'.$txt->display("title").'";
    txtLang[2].innerHTML = "'.$txt->display("spacing").'";
    txtLang[3].innerHTML = "'.$txt->display("alignment").'";
    txtLang[4].innerHTML = "'.$txt->display("top").'";
    txtLang[5].innerHTML = "'.$txt->display("image_Border").'";
    txtLang[6].innerHTML = "'.$txt->display("bottom").'";
    txtLang[7].innerHTML = "'.$txt->display("width").'";
    txtLang[8].innerHTML = "'.$txt->display("left").'";
    txtLang[9].innerHTML = "'.$txt->display("height").'";
    txtLang[10].innerHTML = "'.$txt->display("right").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("image_absBottom").'";
    optLang[1].text = "'.$txt->display("image_absMiddle").'";
    optLang[2].text = "'.$txt->display("baseline").'";
    optLang[3].text = "'.$txt->display("bottom").'";
    optLang[4].text = "'.$txt->display("left").'";
    optLang[5].text = "'.$txt->display("middle").'";
    optLang[6].text = "'.$txt->display("right").'";
    optLang[7].text = "'.$txt->display("image_textTop_2").'";
    optLang[8].text = "'.$txt->display("top").'";

    document.getElementById("btnBorder").value = "'.$txt->display("border_style").'";
    document.getElementById("btnReset").value = "'.$txt->display("reset").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("image_title").'")
    }
';

$element["image_background"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("image_background_ImageSource").'";
    txtLang[1].innerHTML = "'.$txt->display("image_background_Repeat").'";
    txtLang[2].innerHTML = "'.$txt->display("image_background_HorizontalAlign").'";
    txtLang[3].innerHTML = "'.$txt->display("image_background_VerticalAlign").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("image_background_Repeat").'"
    optLang[1].text = "'.$txt->display("image_background_Norepeat").'"
    optLang[2].text = "'.$txt->display("image_background_Repeathorizontally").'"
    optLang[3].text = "'.$txt->display("image_background_Repeatvertically").'"
    optLang[4].text = "'.$txt->display("left").'"
    optLang[5].text = "'.$txt->display("center").'"
    optLang[6].text = "'.$txt->display("right").'"
    optLang[7].text = "'.$txt->display("pixels").'"
    optLang[8].text = "'.$txt->display("percent").'"
    optLang[9].text = "'.$txt->display("top").'"
    optLang[10].text = "'.$txt->display("center").'"
    optLang[11].text = "'.$txt->display("bottom").'"
    optLang[12].text = "'.$txt->display("pixels").'"
    optLang[13].text = "'.$txt->display("percent").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("background_image").'")
    }
';

$element["length"] = '
function loadTxt()
    {
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("background_image").'")
    }
';

$element["list"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("list_Numbered").'";
    txtLang[1].innerHTML = "'.$txt->display("list_Bulleted").'";
    txtLang[2].innerHTML = "'.$txt->display("list_StartingNumber").'";
    txtLang[3].innerHTML = "'.$txt->display("left_margin").'";
    txtLang[4].innerHTML = "'.$txt->display("list_UsingImageurl").'"
    txtLang[5].innerHTML = "'.$txt->display("left_margin").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Please select a list.":return "'.$txt->display("list_Pleaseselectalist").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("list_title").'")
    }
';

$element["media"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("source").'";
    txtLang[1].innerHTML = "'.$txt->display("width").'";
    txtLang[2].innerHTML = "'.$txt->display("height").'";
    txtLang[3].innerHTML = "'.$txt->display("media_AutoStart").'";
    txtLang[4].innerHTML = "'.$txt->display("media_ShowControls").'";
    txtLang[5].innerHTML = "'.$txt->display("media_ShowStatusBar").'";
    txtLang[6].innerHTML = "'.$txt->display("media_ShowDisplay").'";
    txtLang[7].innerHTML = "'.$txt->display("media_AutoRewind").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("media_title").'")
    }
';

$element["paragraph"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("alignment").'";
    txtLang[1].innerHTML = "'.$txt->display("paragraph_Indentation").'";
    txtLang[2].innerHTML = "'.$txt->display("paragraph_WordSpacing").'";
    txtLang[3].innerHTML = "'.$txt->display("character_spacing").'";
    txtLang[4].innerHTML = "'.$txt->display("paragraph_LineHeight").'";
    txtLang[5].innerHTML = "'.$txt->display("paragraph_TextCase").'";
    txtLang[6].innerHTML = "'.$txt->display("white_space").'";

    document.getElementById("divPreview").innerHTML = "Lorem ipsum dolor sit amet, " +
        "consetetur sadipscing elitr, " +
        "sed diam nonumy eirmod tempor invidunt ut labore et " +
        "dolore magna aliquyam erat, " +
        "sed diam voluptua. At vero eos et accusam et justo " +
        "duo dolores et ea rebum. Stet clita kasd gubergren, " +
        "no sea takimata sanctus est Lorem ipsum dolor sit amet.";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("not_set").'";
    optLang[1].text = "'.$txt->display("left").'";
    optLang[2].text = "'.$txt->display("right").'";
    optLang[3].text = "'.$txt->display("center").'";
    optLang[4].text = "'.$txt->display("justify").'";
    optLang[5].text = "'.$txt->display("not_set").'";
    optLang[6].text = "'.$txt->display("capitalize").'";
    optLang[7].text = "'.$txt->display("uppercase").'";
    optLang[8].text = "'.$txt->display("lowercase").'";
    optLang[9].text = "'.$txt->display("none").'";
    optLang[10].text = "'.$txt->display("not_set").'";
    optLang[11].text = "'.$txt->display("no_wrap").'";
    optLang[12].text = "'.$txt->display("pre").'";
    optLang[13].text = "'.$txt->display("normal").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = " '.$txt->display("ok").' ";
    }
function writeTitle()
    {
    document.write("'.$txt->display("paragraph_title").'")
    }
';

$element["paste_word"] = '
function loadTxt()
	{
    document.getElementById("txtLang").innerHTML = "'.$txt->display("paste_word_txtLang").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
	}
function writeTitle()
	{
	document.write("'.$txt->display("paste_word_title").'")
	}
';

$element["paste_text"] = '
function loadTxt()
	{
    document.getElementById("txtLang").innerHTML = "'.$txt->display("paste_text_txtLang").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
	}
function writeTitle()
	{
	document.write("'.$txt->display("paste_text_title").'")
	}
';

$element["percent"] = '
function loadTxt()
    {
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("percent_title").'")
    }
';

$element["preview"] = '
function loadTxt()
    {
    document.getElementById("btnClose").value = "'.$txt->display("close").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("preview_title").'")
    }
';

$element["search"] = '
function loadTxt()
	{
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("search").'";
    txtLang[1].innerHTML = "'.$txt->display("replace").'";
    txtLang[2].innerHTML = "'.$txt->display("search_Matchcase").'";
    txtLang[3].innerHTML = "'.$txt->display("search_Matchwholeword").'";

    document.getElementById("btnSearch").value = "'.$txt->display("search_btnSearch").'";
    document.getElementById("btnReplace").value = "'.$txt->display("replace").'";
    document.getElementById("btnReplaceAll").value = "'.$txt->display("search_btnReplaceAll").'";
    document.getElementById("btnClose").value = "'.$txt->display("close").'";
	}
function getTxt(s)
    {
    switch(s)
        {
        case "Finished searching": return "'.$txt->display("search_finished").'";
        default: return "";
        }
    }
function writeTitle()
	{
	document.write("'.$txt->display("search_title").'")
	}
';

$element["source_html"] = '
function loadTxt()
    {
    document.getElementById("txtLang").innerHTML = "'.$txt->display("source_html_txtLang").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Search":return "'.$txt->display("search").'";
        case "Cut":return "'.$txt->display("cut").'";
        case "Copy":return "'.$txt->display("copy").'";
        case "Paste":return "'.$txt->display("paste").'";
        case "Undo":return "'.$txt->display("undo").'";
        case "Redo":return "'.$txt->display("redo").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("source_html_title").'")
    }
';

$element["spellcheck"] = '
function loadTxt()
	{
	document.getElementById("btnCheckAgain").value = "'.$txt->display("spellcheck_btnCheckAgain").'";
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
	}
function getTxt(s)
	{
	switch(s)
		{
		case "Required":
			return "'.$txt->display("spellcheck_ieSpell").'";
		default:return "";
		}
	}
function writeTitle()
	{
	document.write("'.$txt->display("spellcheck_title").'")
	}
';

$element["styles"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("styles").'";
    txtLang[1].innerHTML = "'.$txt->display("preview").'";
    txtLang[2].innerHTML = "'.$txt->display("apply_to").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("selected_text").'"
    optLang[1].text = "'.$txt->display("current_tag").'"

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }

function getTxt(s)
    {
    switch(s)
        {
        case "You\'re selecting BODY element.":
            return "'.$txt->display("youre_selecting_body_element").'";
        case "Please select a text.":
            return "'.$txt->display("please_select_a_text").'";
        default:return "";
        }
    }

function writeTitle()
    {
    document.write("'.$txt->display("styles_title").'")
    }
';

$element["styles_cssText2"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("preview").'";
    txtLang[1].innerHTML = "'.$txt->display("css_text").'";
    txtLang[2].innerHTML = "'.$txt->display("class_name").'";
    //txtLang[3].innerHTML = "'.$txt->display("apply_to").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "You\'re selecting BODY element.":
            return "'.$txt->display("youre_selecting_body_element").'";
        case "Please select a text.":
            return "'.$txt->display("please_select_a_text").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("custom_css").'")
    }
';

$element["styles_cssText"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("css_text").'";
    txtLang[1].innerHTML = "'.$txt->display("class_name").'";
    //txtLang[2].innerHTML = "'.$txt->display("preview").'";
    //txtLang[3].innerHTML = "'.$txt->display("apply_to").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = " '.$txt->display("ok").' ";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "You\'re selecting BODY element.":
            return "'.$txt->display("youre_selecting_body_element").'";
        case "Please select a text.":
            return "'.$txt->display("please_select_a_text").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("custom_css").'")
    }
';

$element["table_edit"] = '
function loadTxt()
    {
    var txtLang =  document.getElementsByName("txtLang");
    //txtLang[0].innerHTML = "'.$txt->display("size").'";
    txtLang[0].innerHTML = "'.$txt->display("autofit").'";
    txtLang[1].innerHTML = "'.$txt->display("properties").'";
    txtLang[2].innerHTML = "'.$txt->display("style").'";
    //txtLang[4].innerHTML = "'.$txt->display("insert_row").'";
    //txtLang[5].innerHTML = "'.$txt->display("insert_column").'";
    //txtLang[6].innerHTML = "'.$txt->display("table_edit_SpanSplitRow").'";
    //txtLang[7].innerHTML = "'.$txt->display("table_edit_SpanSplitColumn").'";
    //txtLang[8].innerHTML = "'.$txt->display("delete_row").'";
    //txtLang[9].innerHTML = "'.$txt->display("delete_column").'";
    txtLang[3].innerHTML = "'.$txt->display("width").'";
    txtLang[4].innerHTML = "'.$txt->display("autofit_to_contents").'";
    txtLang[5].innerHTML = "'.$txt->display("table_edit_Fixedtablewidth").'";
    txtLang[6].innerHTML = "'.$txt->display("autofit_to_window").'";
    txtLang[7].innerHTML = "'.$txt->display("height").'";
    txtLang[8].innerHTML = "'.$txt->display("autofit_to_contents").'";
    txtLang[9].innerHTML = "'.$txt->display("table_edit_Fixedtableheight").'";
    txtLang[10].innerHTML = "'.$txt->display("autofit_to_window").'";
    txtLang[11].innerHTML = "'.$txt->display("alignment").'";
    txtLang[12].innerHTML = "'.$txt->display("margin").'";
    txtLang[13].innerHTML = "'.$txt->display("left").'";
    txtLang[14].innerHTML = "'.$txt->display("right").'";
    txtLang[15].innerHTML = "'.$txt->display("top").'";
    txtLang[16].innerHTML = "'.$txt->display("bottom").'";
    txtLang[17].innerHTML = "'.$txt->display("borders").'";
    txtLang[18].innerHTML = "'.$txt->display("collapse").'";
    txtLang[19].innerHTML = "'.$txt->display("background").'";
    txtLang[20].innerHTML = "'.$txt->display("table_edit_CellSpacing").'";
    txtLang[21].innerHTML = "'.$txt->display("table_edit_CellPadding").'";
    txtLang[22].innerHTML = "'.$txt->display("css_text").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("pixels").'"
    optLang[1].text = "'.$txt->display("percent").'"
    optLang[2].text = "'.$txt->display("pixels").'"
    optLang[3].text = "'.$txt->display("percent").'"
    optLang[4].text = "'.$txt->display("left").'"
    optLang[5].text = "'.$txt->display("center").'"
    optLang[6].text = "'.$txt->display("right").'"
    optLang[7].text = "'.$txt->display("no_border").'"
    optLang[8].text = "'.$txt->display("yes").'"
    optLang[9].text = "'.$txt->display("no").'"

    document.getElementById("btnPick").value="'.$txt->display("pick").'";
    document.getElementById("btnImage").value="'.$txt->display("image").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Cannot delete column.":
            return "'.$txt->display("cannot_delete_column").'";
        case "Cannot delete row.":
            return "'.$txt->display("cannot_delete").'";
        case "Custom Colors": return "'.$txt->display("custom_colors").'";
        case "More Colors...": return "'.$txt->display("more_colors").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("table_edit_title").'")
    }
';

$element["table_editCell"] = '
function loadTxt()
    {

    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("autofit").'";
    txtLang[1].innerHTML = "'.$txt->display("properties").'";
    txtLang[2].innerHTML = "'.$txt->display("style").'";
    txtLang[3].innerHTML = "'.$txt->display("width").'";
    txtLang[4].innerHTML = "'.$txt->display("autofit_to_contents").'";
    txtLang[5].innerHTML = "'.$txt->display("table_editCell_6").'";
    txtLang[6].innerHTML = "'.$txt->display("height").'";
    txtLang[7].innerHTML = "'.$txt->display("autofit_to_contents").'";
    txtLang[8].innerHTML = "'.$txt->display("table_editCell_9").'";
    txtLang[9].innerHTML = "'.$txt->display("table_editCell_10").'";
    txtLang[10].innerHTML = "'.$txt->display("padding").'";
    txtLang[11].innerHTML = "'.$txt->display("left").'";
    txtLang[12].innerHTML = "'.$txt->display("right").'";
    txtLang[13].innerHTML = "'.$txt->display("top").'";
    txtLang[14].innerHTML = "'.$txt->display("bottom").'";
    txtLang[15].innerHTML = "'.$txt->display("white_space").'";
    txtLang[16].innerHTML = "'.$txt->display("background").'";
    txtLang[17].innerHTML = "'.$txt->display("preview").'";
    txtLang[18].innerHTML = "'.$txt->display("css_text").'";
    txtLang[19].innerHTML = "'.$txt->display("apply_to").'";

    document.getElementById("btnPick").value = "'.$txt->display("pick").'";
    document.getElementById("btnImage").value = "'.$txt->display("table_editCell_btnImage").'";
    document.getElementById("btnText").value = "'.$txt->display("table_editCell_btnText").'";
    document.getElementById("btnBorder").value = "'.$txt->display("border_style").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("pixels").'"
    optLang[1].text = "'.$txt->display("percent").'"
    optLang[2].text = "'.$txt->display("pixels").'"
    optLang[3].text = "'.$txt->display("percent").'"
    optLang[4].text = "'.$txt->display("not_set").'"
    optLang[5].text = "'.$txt->display("top").'"
    optLang[6].text = "'.$txt->display("middle").'"
    optLang[7].text = "'.$txt->display("bottom").'"
    optLang[8].text = "'.$txt->display("baseline").'"
    optLang[9].text = "'.$txt->display("table_editCell_30").'"
    optLang[10].text = "'.$txt->display("table_editCell_31").'"
    optLang[11].text = "'.$txt->display("texttop").'"
    optLang[12].text = "'.$txt->display("textbottom").'"
    optLang[13].text = "'.$txt->display("not_set").'"
    optLang[14].text = "'.$txt->display("left").'"
    optLang[15].text = "'.$txt->display("center").'"
    optLang[16].text = "'.$txt->display("right").'"
    optLang[17].text = "'.$txt->display("justify").'"
    optLang[18].text = "'.$txt->display("not_set").'"
    optLang[19].text = "'.$txt->display("no_wrap").'"
    optLang[20].text = "'.$txt->display("pre").'"
    optLang[21].text = "'.$txt->display("normal").'"
    optLang[22].text = "'.$txt->display("table_editCell_43").'"
    optLang[23].text = "'.$txt->display("table_editCell_44").'"
    optLang[24].text = "'.$txt->display("table_editCell_45").'"
    optLang[25].text = "'.$txt->display("table_editCell_46").'"
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Custom Colors": return "'.$txt->display("custom_colors").'";
        case "More Colors...": return "'.$txt->display("more_colors").'";
        default: return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("table_editCell_title").'")
    }
';

$element["table_insert"] = '
function loadTxt()
    {
    var txtLang =  document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("table_insert_Rows").'";
    txtLang[1].innerHTML = "'.$txt->display("spacing").'";
    txtLang[2].innerHTML = "'.$txt->display("table_insert_Columns").'";
    txtLang[3].innerHTML = "'.$txt->display("padding").'";
    txtLang[4].innerHTML = "'.$txt->display("borders").'";
    txtLang[5].innerHTML = "'.$txt->display("collapse").'";

	var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("no_border").'";
    optLang[1].text = "'.$txt->display("yes").'";
    optLang[2].text = "'.$txt->display("no").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnInsert").value = "'.$txt->display("insert").'";

    document.getElementById("btnSpan1").value = "'.$txt->display("table_insert_btnSpan1").'";
    document.getElementById("btnSpan2").value = "'.$txt->display("table_insert_btnSpan2").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("table_insert_title").'")
    }
';

$element["table_size"] = '
function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("insert_row").'";
    txtLang[1].innerHTML = "'.$txt->display("insert_column").'";
    txtLang[2].innerHTML = "'.$txt->display("table_size_IncreaseDecreaseRowspan").'";
    txtLang[3].innerHTML = "'.$txt->display("table_size_IncreaseDecreaseColspan").'";
    txtLang[4].innerHTML = "'.$txt->display("delete_row").'";
    txtLang[5].innerHTML = "'.$txt->display("delete_column").'";

	document.getElementById("btnInsRowAbove").title="'.$txt->display("table_size_InsertRowAbove").'";
	document.getElementById("btnInsRowBelow").title="'.$txt->display("table_size_InsertRowBelow").'";
	document.getElementById("btnInsColLeft").title="'.$txt->display("table_size_InsertColumnLeft").'";
	document.getElementById("btnInsColRight").title="'.$txt->display("table_size_InsertColumnRight").'";
	document.getElementById("btnIncRowSpan").title="'.$txt->display("table_size_IncreaseRowspan").'";
	document.getElementById("btnDecRowSpan").title="'.$txt->display("table_size_DecreaseRowspan").'";
	document.getElementById("btnIncColSpan").title="'.$txt->display("table_size_IncreaseColspan").'";
	document.getElementById("btnDecColSpan").title="'.$txt->display("table_size_DecreaseColspan").'";
	document.getElementById("btnDelRow").title="'.$txt->display("delete_row").'";
	document.getElementById("btnDelCol").title="'.$txt->display("delete_column").'";
	document.getElementById("btnClose").value = "'.$txt->display("table_size_close").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Cannot delete column.":
            return "'.$txt->display("cannot_delete_column").'";
        case "Cannot delete row.":
            return "'.$txt->display("cannot_delete").'";
        default:return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("table_size_title").'")
    }
';

$element["text1"] = '
var sStyleWeight1;
var sStyleWeight2;
var sStyleWeight3;
var sStyleWeight4;

function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("font").'";
    txtLang[1].innerHTML = "'.$txt->display("style").'";
    txtLang[2].innerHTML = "'.$txt->display("size").'";
    txtLang[3].innerHTML = "'.$txt->display("foreground").'";
    txtLang[4].innerHTML = "'.$txt->display("background").'";
    //txtLang[5].innerHTML = "'.$txt->display("effects").'";

    txtLang[5].innerHTML = "'.$txt->display("decoration").'";
    txtLang[6].innerHTML = "'.$txt->display("textcase").'";
    txtLang[7].innerHTML = "'.$txt->display("minicaps").'";
    txtLang[8].innerHTML = "'.$txt->display("vertical").'";

    txtLang[9].innerHTML = "'.$txt->display("not_set").'";
    txtLang[10].innerHTML = "'.$txt->display("underline").'";
    txtLang[11].innerHTML = "'.$txt->display("overline").'";
    txtLang[12].innerHTML = "'.$txt->display("linethrough").'";
    txtLang[13].innerHTML = "'.$txt->display("none").'";

    txtLang[14].innerHTML = "'.$txt->display("not_set").'";
    txtLang[15].innerHTML = "'.$txt->display("capitalize").'";
    txtLang[16].innerHTML = "'.$txt->display("uppercase").'";
    txtLang[17].innerHTML = "'.$txt->display("lowercase").'";
    txtLang[18].innerHTML = "'.$txt->display("none").'";

    txtLang[19].innerHTML = "'.$txt->display("not_set").'";
    txtLang[20].innerHTML = "'.$txt->display("smallcaps").'";
    txtLang[21].innerHTML = "'.$txt->display("normal").'";

    txtLang[22].innerHTML = "'.$txt->display("not_set").'";
    txtLang[23].innerHTML = "'.$txt->display("superscript").'";
    txtLang[24].innerHTML = "'.$txt->display("subscript").'";
    txtLang[25].innerHTML = "'.$txt->display("relative").'";
    txtLang[26].innerHTML = "'.$txt->display("baseline").'";

    txtLang[27].innerHTML = "'.$txt->display("character_spacing").'";
    //txtLang[28].innerHTML = "'.$txt->display("preview").'";
    //txtLang[29].innerHTML = "'.$txt->display("apply_to").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("regular").'"
    optLang[1].text = "'.$txt->display("italic").'"
    optLang[2].text = "'.$txt->display("bold").'"
    optLang[3].text = "'.$txt->display("bold_italic").'"

    optLang[0].value = "'.$txt->display("regular").'"
    optLang[1].value = "'.$txt->display("italic").'"
    optLang[2].value = "'.$txt->display("bold").'"
    optLang[3].value = "'.$txt->display("bold_italic").'"

    sStyleWeight1 = "'.$txt->display("regular").'"
    sStyleWeight2 = "'.$txt->display("italic").'"
    sStyleWeight3 = "'.$txt->display("bold").'"
    sStyleWeight4 = "'.$txt->display("bold_italic").'"

    optLang[4].text = "'.$txt->display("top").'"
    optLang[5].text = "'.$txt->display("middle").'"
    optLang[6].text = "'.$txt->display("bottom").'"
    optLang[7].text = "'.$txt->display("texttop").'"
    optLang[8].text = "'.$txt->display("textbottom").'"
    //optLang[9].text = "'.$txt->display("selected_text").'"
    //optLang[10].text = "'.$txt->display("current_tag").'"

    document.getElementById("btnPick1").value = "'.$txt->display("pick").'";
    document.getElementById("btnPick2").value = "'.$txt->display("pick").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnApply").value = "'.$txt->display("apply").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Custom Colors": return "'.$txt->display("custom_colors").'";
        case "More Colors...": return "'.$txt->display("more_colors").'";
        default: return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("text_formatting").'")
    }
';

$element["text2"] = '
var sStyleWeight1;
var sStyleWeight2;
var sStyleWeight3;
var sStyleWeight4;

function loadTxt()
    {
    var txtLang = document.getElementsByName("txtLang");
    txtLang[0].innerHTML = "'.$txt->display("font").'";
    txtLang[1].innerHTML = "'.$txt->display("style").'";
    txtLang[2].innerHTML = "'.$txt->display("size").'";
    txtLang[3].innerHTML = "'.$txt->display("foreground").'";
    txtLang[4].innerHTML = "'.$txt->display("background").'";
    //txtLang[5].innerHTML = "'.$txt->display("effects").'";

    txtLang[5].innerHTML = "'.$txt->display("decoration").'";
    txtLang[6].innerHTML = "'.$txt->display("textcase").'";
    txtLang[7].innerHTML = "'.$txt->display("minicaps").'";
    txtLang[8].innerHTML = "'.$txt->display("vertical").'";

    txtLang[9].innerHTML = "'.$txt->display("not_set").'";
    txtLang[10].innerHTML = "'.$txt->display("underline").'";
    txtLang[11].innerHTML = "'.$txt->display("overline").'";
    txtLang[12].innerHTML = "'.$txt->display("linethrough").'";
    txtLang[13].innerHTML = "'.$txt->display("none").'";

    txtLang[14].innerHTML = "'.$txt->display("not_set").'";
    txtLang[15].innerHTML = "'.$txt->display("capitalize").'";
    txtLang[16].innerHTML = "'.$txt->display("uppercase").'";
    txtLang[17].innerHTML = "'.$txt->display("lowercase").'";
    txtLang[18].innerHTML = "'.$txt->display("none").'";

    txtLang[19].innerHTML = "'.$txt->display("not_set").'";
    txtLang[20].innerHTML = "'.$txt->display("smallcaps").'";
    txtLang[21].innerHTML = "'.$txt->display("normal").'";

    txtLang[22].innerHTML = "'.$txt->display("not_set").'";
    txtLang[23].innerHTML = "'.$txt->display("superscript").'";
    txtLang[24].innerHTML = "'.$txt->display("subscript").'";
    txtLang[25].innerHTML = "'.$txt->display("relative").'";
    txtLang[26].innerHTML = "'.$txt->display("baseline").'";

    txtLang[27].innerHTML = "'.$txt->display("character_spacing").'";
    //txtLang[28].innerHTML = "'.$txt->display("preview").'";

    var optLang = document.getElementsByName("optLang");
    optLang[0].text = "'.$txt->display("regular").'"
    optLang[1].text = "'.$txt->display("italic").'"
    optLang[2].text = "'.$txt->display("bold").'"
    optLang[3].text = "'.$txt->display("bold_italic").'"

    optLang[0].value = "'.$txt->display("regular").'"
    optLang[1].value = "'.$txt->display("italic").'"
    optLang[2].value = "'.$txt->display("bold").'"
    optLang[3].value = "'.$txt->display("bold_italic").'"

    sStyleWeight1 = "'.$txt->display("regular").'"
    sStyleWeight2 = "'.$txt->display("italic").'"
    sStyleWeight3 = "'.$txt->display("bold").'"
    sStyleWeight4 = "'.$txt->display("bold_italic").'"

    optLang[4].text = "'.$txt->display("top").'"
    optLang[5].text = "'.$txt->display("middle").'"
    optLang[6].text = "'.$txt->display("bottom").'"
    optLang[7].text = "'.$txt->display("texttop").'"
    optLang[8].text = "'.$txt->display("textbottom").'"

    document.getElementById("btnPick1").value = "'.$txt->display("pick").'";
    document.getElementById("btnPick2").value = "'.$txt->display("pick").'";

    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function getTxt(s)
    {
    switch(s)
        {
        case "Custom Colors": return "'.$txt->display("custom_colors").'";
        case "More Colors...": return "'.$txt->display("more_colors").'";
        default: return "";
        }
    }
function writeTitle()
    {
    document.write("'.$txt->display("text_formatting").'")
    }
';

$element["url"] = '
function loadTxt()
    {
    document.getElementById("btnCancel").value = "'.$txt->display("cancel").'";
    document.getElementById("btnOk").value = "'.$txt->display("ok").'";
    }
function writeTitle()
    {
    document.write("'.$txt->display("url_title").'")
    }
';

// #######################################################

$data = $element[$_GET["type"]];

header("Content-type: application/x-javascript");
if ($data) {
	echo $data;
}
else {
	echo "self.close();";
}
exit;

?>