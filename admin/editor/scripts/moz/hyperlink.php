<?php
require_once("../../../../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/module.xslprocess.class.php");
require_once(SITE_PATH . "/class/Database.php");

// ##############################################################

$sql = new sql();
$sql->connect();
$database = new Database($sql);
load_site_settings($database);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

//// init Text object for this page
//$txt = new Text($language2, "editor");
//$txtf = new Text($language2, "message");

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="../style/editor.css" rel="stylesheet" type="text/css">
<script>
    var sLangDir=window.opener.oUtil.langDir;
</script>
<script language="javascript" src="../language/modera/modera.php?type=hyperlink"></script>
<script>writeTitle()</script>
<script>

var activeModalWin;

function GetElement(oElement,sMatchTag)
    {
    while (oElement!=null&&oElement.tagName!=sMatchTag)
        {
        if(oElement.tagName=="BODY")return null;
        oElement=oElement.parentNode;
        }
    return oElement;
    }

function doWindowFocus()
    {
    window.opener.oUtil.onSelectionChanged=new Function("realTime()");
    }

function bodyOnLoad()
    {
    window.onfocus=doWindowFocus;
    window.opener.oUtil.onSelectionChanged=new Function("realTime()");

    if(window.opener.oUtil.obj.cmdAssetManager!="")
        document.getElementById("btnAsset").style.display="block";
    if(window.opener.oUtil.obj.cmdFileManager!="")
        document.getElementById("btnAsset").style.display="block";

    realTime()
    }

function openAsset()
    {
    if(window.opener.oUtil.obj.cmdAssetManager!="")
    eval(window.opener.oUtil.obj.cmdAssetManager);
    if(window.opener.oUtil.obj.cmdFileManager!="")
        eval(window.opener.oUtil.obj.cmdFileManager);
    }

function setAssetValue(v)
    {
    document.getElementById("inpURL").value = v;
    }

function modalDialogShow(url,width,height)
    {
    var left = screen.availWidth/2 - width/2;
    var top = screen.availHeight/2 - height/2;
    activeModalWin = window.open(url, "", "width="+width+"px,height="+height+",left="+left+",top="+top);
    window.onfocus = function(){if (activeModalWin.closed == false){activeModalWin.focus();};};

    }

function updateList()
    {
    var oEditor=window.opener.oUtil.oEditor;
    var inpBookmark = document.getElementById("inpBookmark");

    while(inpBookmark.options.length!=0)
        {
        //inpBookmark.options.remove(inpBookmark.options(0))
        inpBookmark.options[0] = null;
        }

    var aNode = oEditor.document.getElementsByTagName("A");
    for(var i=0;i<aNode.length;i++)
        {
        var op = document.createElement("OPTION");
        op.text=aNode[i].name;
        op.value="#"+aNode[i].name;
        inpBookmark.options[inpBookmark.options.length] = op;
        }
    }

function realTime()
    {

    var rdoLinkTo = document.getElementsByName("rdoLinkTo");
    var inpType = document.getElementById("inpType");
    var inpPage = document.getElementById("inpPage");
    var inpBookmark = document.getElementById("inpBookmark");
    var inpURL = document.getElementById("inpURL");
    var inpTargetCustom = document.getElementById("inpTargetCustom");
    var inpTarget = document.getElementById("inpTarget");
    var inpTitle = document.getElementById("inpTitle");

    var btnInsert = document.getElementById("btnInsert");
    var btnApply = document.getElementById("btnApply");
    var btnOk = document.getElementById("btnOk");

    var oEditor=window.opener.oUtil.oEditor;

    var oSel = oEditor.getSelection();
    //var oEl = window.opener.getSelectedElement(oSel);
    var oEl = GetElement(window.opener.getSelectedElement(oSel),"A");//new

    updateList();

    if(!oEl)//yus
        {
        btnInsert.style.display="block";
        btnApply.style.display="none";
        btnOk.style.display="none";

        inpTarget.value="";
        inpTargetCustom.value="";
        inpTitle.value="";

        inpType.value="";
        inpURL.value="";
        inpBookmark.value="";
        inpPage.value="";

        inpBookmark.disabled=true;
        inpPage.disabled=true;
        inpURL.disabled=false;
        inpType.disabled=false;
        rdoLinkTo[0].checked=true;
        rdoLinkTo[1].checked=false;
        rdoLinkTo[2].checked=false;

        rdoLinkType[0].checked=true;
        rdoLinkType[1].checked=false;

        return;
        }

    //Is there an A element ?
    if (oEl.nodeName == "A")
        {

        var range =oEditor.document.createRange();
        range.selectNode(oEl);
        oSel.removeAllRanges();
        oSel.addRange(range);

        btnInsert.style.display="none";
        btnApply.style.display="block";
        btnOk.style.display="block";


        var sURL = oEl.getAttribute("HREF");

        inpTarget.value="";
        inpTargetCustom.value="";
        var trg = oEl.getAttribute("TARGET");
        if(trg=="_self" || trg=="_blank" || trg=="_parent")
            inpTarget.value=trg;//inpTarget
        else
            inpTargetCustom.value=trg;

        inpTitle.value="";
        if(oEl.getAttribute("TITLE")!=null) inpTitle.value=oEl.getAttribute("TITLE");//inpTitle //1.5.1

        if(sURL==null)sURL="";

        if(sURL.substr(0,7)=="http://")
            {
            inpType.value="http://";//inpType
            inpURL.value=sURL.substr(7);//idLinkURL

            inpBookmark.disabled=true;
            inpPage.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;
            }
        else if(sURL.substr(0,8)=="https://")
            {
            inpType.value="https://";
            inpURL.value=sURL.substr(8);

            inpBookmark.disabled=true;
            inpPage.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;
            }
        else if(sURL.substr(0,7)=="mailto:")
            {
            inpType.value="mailto:";
            inpURL.value=sURL.split(":")[1];

            inpBookmark.disabled=true;
            inpPage.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;
            }
        else if(sURL.substr(0,6)=="ftp://")
            {
            inpType.value="ftp://";
            inpURL.value=sURL.substr(6);

            inpBookmark.disabled=true;
            inpPage.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;
            }
        else if(sURL.substr(0,5)=="news:")
            {
            inpType.value="news:";
            inpURL.value=sURL.split(":")[1];

            inpBookmark.disabled=true;
            inpPage.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;
            }
        else if(sURL.substr(0,11).toLowerCase()=="javascript:")
            {
            inpType.value="javascript:";
            inpURL.value=sURL.split(":")[1];
            if (inpURL.value.indexOf("openPicture") !=-1) {
                rdoLinkType[1].checked=true;
            }

            inpBookmark.disabled=true;
            inpURL.disabled=false;
            inpType.disabled=false;
            rdoLinkTo[0].checked=true;
            rdoLinkTo[1].checked=false;
            rdoLinkTo[2].checked=false;


            }
        else
            {
            inpType.value="";

            if(sURL.substring(0,1)=="#")
                {
                inpBookmark.value=sURL;
                inpURL.value="";
                inpBookmark.disabled=false;
                inpPage.disabled=true;
                inpURL.disabled=true;
                inpType.disabled=true;
                rdoLinkTo[0].checked=false;
                rdoLinkTo[1].checked=true;
                rdoLinkTo[2].checked=false;
                }
            else
                {
                inpBookmark.value=""
                inpURL.value=sURL;
                inpBookmark.disabled=true;
                inpPage.disabled=true;
                inpURL.disabled=false;
                inpType.disabled=false;
                rdoLinkTo[0].checked=true;
                rdoLinkTo[1].checked=false;
                rdoLinkTo[2].checked=false;
                }
            }
        }
    else
        {
        btnInsert.style.display="block";
        btnApply.style.display="none";
        btnOk.style.display="none";

        inpTarget.value="";
        inpTargetCustom.value="";
        inpTitle.value="";

        inpType.value="";
        inpURL.value="";
        inpPage.value="";
        inpBookmark.value="";

        inpBookmark.disabled=true;
        inpPage.disabled=true;
        inpURL.disabled=false;
        inpType.disabled=false;
        rdoLinkTo[0].checked=true;
        rdoLinkTo[1].checked=false;
        rdoLinkTo[2].checked=false;
        }
    }

function applyHyperlink()
    {
    //if(!window.opener.oUtil.obj.checkFocus()){return;}//Focus stuff

    var oEditor=window.opener.oUtil.oEditor;

    //var oSel=oEditor.document.selection.createRange();
    var oSel=oEditor.getSelection();
    var range = oSel.getRangeAt(0);
    window.opener.oUtil.obj.saveForUndo();

    var rdoLinkTo = document.getElementsByName("rdoLinkTo");
    var inpType = document.getElementById("inpType");
    var inpBookmark = document.getElementById("inpBookmark");
    var inpPage = document.getElementById("inpPage");
    var inpURL = document.getElementById("inpURL");
    var inpTargetCustom = document.getElementById("inpTargetCustom");
    var inpTarget = document.getElementById("inpTarget");
    var inpTitle = document.getElementById("inpTitle");

    var sURL;
    if(rdoLinkTo[0].checked)
        sURL=inpType.value + inpURL.value;
    else if (rdoLinkTo[1].checked) {
        sURL=inpPage.value;
    }
    else if (rdoLinkTo[2].checked) {
        sURL=inpBookmark.value;
    }


    if((inpURL.value!="" && rdoLinkTo[0].checked) ||
        (inpPage!="" && rdoLinkTo[1].checked) ||
        (inpBookmark!="" && rdoLinkTo[2].checked))
        {
        var emptySel = false;
        if(document.getElementById("btnInsert").style.display=="block" ||
            document.getElementById("btnInsert").style.display=="")
            {

            if(range.toString()=="")
                { //If no (text) selection, then build selection using the typed URL
                if (range.startContainer.nodeType==Node.ELEMENT_NODE)
                    {
                    if (range.startContainer.childNodes[range.startOffset].nodeType != Node.TEXT_NODE)
                        {
                        if (range.startContainer.childNodes[range.startOffset].nodeName=="BR") emptySel = true; else emptySel=false;
                        }
                        else
                        {
                        emptySel = true;
                        }
                    } else {
                        emptySel = true;
                    }
                }

            if (emptySel)
                {
                var node = oEditor.document.createTextNode(sURL);
                range.insertNode(node);
                oEditor.document.designMode = "on";

                range = oEditor.document.createRange();
                range.setStart(node, 0);
                range.setEnd(node, sURL.length);

                oSel = oEditor.getSelection();
                oSel.removeAllRanges();
                oSel.addRange(range);
                }

            }

        var isSelInMidText = (range.startContainer.nodeType==Node.TEXT_NODE) && (range.startOffset>0)

        oEditor.document.execCommand("CreateLink", false, sURL);

        oSel = oEditor.getSelection();
        range = oSel.getRangeAt(0);

        //get A element
        if (range.startContainer.nodeType == Node.TEXT_NODE) {
            var node = (emptySel || !isSelInMidText ? range.startContainer.parentNode : range.startContainer.nextSibling); //A node
            range = oEditor.document.createRange();
            range.selectNode(node);

            oSel = oEditor.getSelection();
            oSel.removeAllRanges();
            oSel.addRange(range);

        }

        var oEl = range.startContainer.childNodes[range.startOffset];
        if(oEl)
            {
            if(inpTarget.value=="" && inpTargetCustom.value=="") oEl.removeAttribute("target",0);//target
            else
                {
                if(inpTargetCustom.value!="")
                    oEl.target=inpTargetCustom.value;
                else
                    oEl.target=inpTarget.value;
                }

            if(inpTitle.value=="") oEl.removeAttribute("title",0);//1.5.1
            else oEl.title=inpTitle.value;
            }


        window.opener.realTime(window.opener.oUtil.obj);
        window.opener.oUtil.obj.selectElement(0);
        }
    else
        {
        oEditor.document.execCommand("unlink", false, null);//unlink
        window.opener.realTime(window.opener.oUtil.obj);
        window.opener.oUtil.activeElement=null;
        }
    realTime();
    window.focus();
    }

function changeLinkTo()
    {
    var rdoLinkTo = document.getElementsByName("rdoLinkTo");
    var inpType = document.getElementById("inpType");
    var inpBookmark = document.getElementById("inpBookmark");
    var inpPage = document.getElementById("inpPage");
    var inpURL = document.getElementById("inpURL");

    if(rdoLinkTo[0].checked)
        {
        inpBookmark.disabled=true;
        inpPage.disabled=true;
        inpPage.value="";
        inpURL.disabled=false;
        inpType.disabled=false;
        }
    else if (rdoLinkTo[1].checked)
        {
        inpBookmark.disabled=true;
        inpPage.disabled=false;
        inpURL.disabled=true;
        inpType.disabled=true;
        }
    else
        {
        inpBookmark.disabled=false;
        inpPage.disabled=true;
        inpPage.value="";
        inpURL.disabled=true;
        inpType.disabled=true;
        }
    }

/* modera image opener - siim */
function changeLinkType() {

    var rdoLinkTo = document.getElementsByName("rdoLinkTo");
    var rdoLinkType = document.getElementsByName("rdoLinkType");
    var inpType = document.getElementById("inpType");
    var inpBookmark = document.getElementById("inpBookmark");
    var inpPage = document.getElementById("inpPage");
    var inpURL = document.getElementById("inpURL");
    var inpTargetCustom = document.getElementById("inpTargetCustom");
    var inpTarget = document.getElementById("inpTarget");

    if (rdoLinkTo[0].checked) {

        /*ordinary link */
        if(rdoLinkType[0].checked) {
            inpTarget.disabled=false;
            inpTargetCustom.disabled=false;

            if (inpURL.value.indexOf("openPicture") !=-1 && inpType.value == "javascript:") {
                inpURL.value = inpURL.value.substring(13,inpURL.value.length-2);
                inpType.value="";
            }
        }
        /* image open link*/
        else {
            filetype = inpURL.value.substring(inpURL.value.length-3, inpURL.value.length);
            if (filetype.toLowerCase() == "jpe" || filetype.toLowerCase() == "jpg" || filetype.toLowerCase() == "gif" || filetype.toLowerCase() == "png" || filetype.toLowerCase() == "tif") {
                inpTarget.disabled=true;
                inpTargetCustom.disabled=true;
                inpType.value="javascript:";
                inpURL.value = "openPicture('" + inpURL.value + "')";
            }
            else {
                rdoLinkType[0].checked = true;
            }
        }
    }
}

/* modera link to page - siim */
function changeLinkToPage() {

    var inpType = document.getElementById("inpType");
    var inpPage = document.getElementById("inpPage");
    var inpURL = document.getElementById("inpURL");

    if (inpPage.value) {
        inpURL.value = inpPage.value;
        inpType.value="";
    }
}

</script>
</head>
<body onload="loadTxt();bodyOnLoad()" style="overflow:hidden;">

<table width=100% height=100% align=center cellpadding=0 cellspacing=0>
<tr>
<td valign=top style="padding:5;">
    <table width=100%>
    <tr>
        <td nowrap>
            <input type="radio" value="url" name="rdoLinkTo" class="inpRdo" checked onclick="changeLinkTo()">
            <span id="txtLang" name="txtLang">Source</span>:
        </td>
        <td width="100%">
            <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            <td nowrap>
            <select ID="inpType" NAME="inpType" class="inpSel">
                <option value=""></option>
                <option value="http://">http://</option>
                <option value="https://">https://</option>
                <option value="mailto:">mailto:</option>
                <option value="ftp://">ftp://</option>
                <option value="news:">news:</option>
                <option value="javascript:">javascript:</option>
            </select>
            </td>
            <td width="100%"><INPUT type="text" ID="inpURL" NAME="inpURL" style="width:100%" class="inpTxt"></td>
            <td><input type="button" value="" onclick="openAsset()" id="btnAsset" name="btnAsset" style="display:none;background:url('openAsset.gif');width:20px;height:16px;border:#a5acb2 1px solid;margin-left:1px;"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td nowrap>
            <input type="radio" value="page" name="rdoLinkTo" class="inpRdo" onclick="changeLinkTo()">
            <span id="txtLang" name="txtLang">Page</span>:
        </td>
        <td>
        <select name="inpPage" id="inpPage" class="inpSel" onclick="changeLinkToPage()" disabled style="width:160px">
        <option value=""></option>
        <?
            $xslp = new xslprocess;
            $xslp->all_visible = true;
            $xslp->cachelevel = TPL_CACHE_NOTHING;
            echo $xslp->menu("sitemap_select.xsl", "not published");
        ?>
        </select></td>
    </tr>
    <tr>
        <td nowrap>
            <input type="radio" value="bookmark" name="rdoLinkTo" class="inpRdo" onclick="changeLinkTo()">
            <span id="txtLang" name="txtLang">Bookmark</span>:
        </td>
        <td>
        <select name="inpBookmark" id="inpBookmark" class="inpSel" disabled style="width:160px">
        </select></td>
    </tr>
    <tr>
        <td nowrap><span id="txtLang" name="txtLang">Target</span>:</td>
        <td><INPUT type="text" ID="inpTargetCustom" NAME="inpTargetCustom" size=15 class="inpTxt">
        <select ID="inpTarget" NAME="inpTarget" class="inpSel">
            <option value=""></option>
            <option value="_self" id="optLang" name="optLang">Self</option>
            <option value="_blank" id="optLang" name="optLang">Blank</option>
            <option value="_parent" id="optLang" name="optLang">Parent</option>
        </select></td>
    </tr>
    <tr>
        <td nowrap>&nbsp;<span id="txtLang" name="txtLang">Title</span>:</td>
        <td><INPUT type="text" ID="inpTitle" NAME="inpTitle" style="width:160px" class="inpTxt"></td>
    </tr>
    <tr>
        <td nowrap>&nbsp;<span id="txtLang" name="txtLang">Link type</span>:</td>
        <td>
            <input type="radio" value="none" name="rdoLinkType" id="rdoLinkType" class="inpRdo" checked onclick="changeLinkType()">
                <span id="txtLang" name="txtLang">Plain link</span><br/>
            <input type="radio" value="image" name="rdoLinkType"  id="rdoLinkType" class="inpRdo" onclick="changeLinkType()">
                <span id="txtLang" name="txtLang">Image link, open in new window</span><br/>
        </td>
    </tr>
    </table>
</td>
</tr>
<tr>
<td class="dialogFooter" style="padding:6;" align="right">
    <table cellpadding=1 cellspacing=0>
    <td>
    <input type=button name=btnCancel id=btnCancel value="cancel" onclick="self.close()" class="inpBtn" onmouseover="this.className='inpBtnOver';" onmouseout="this.className='inpBtnOut'">
    </td>
    <td>
    <input type=button name=btnInsert id=btnInsert value="insert" onclick="applyHyperlink();self.close()" class="inpBtn" onmouseover="this.className='inpBtnOver';" onmouseout="this.className='inpBtnOut'">
    </td>
    <td>
    <input type=button name=btnApply id=btnApply value="apply" style="display:none" onclick="applyHyperlink();" class="inpBtn" onmouseover="this.className='inpBtnOver';" onmouseout="this.className='inpBtnOut'">
    </td>
    <td>
    <input type=button name=btnOk id=btnOk value=" ok " style="display:none;" onclick="applyHyperlink();self.close()" class="inpBtn" onmouseover="this.className='inpBtnOver';" onmouseout="this.className='inpBtnOut'">
    </td>
    </table>
</td>
</tr>
</table>

</body>
</html>