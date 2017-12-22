<?php defined("MODERA_KEY")|| die(); ?><SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

// Add from the selection window
function addToParentList(toList, text, value) {
    var frm = document.forms["vorm"];
    var destinationList = frm.elements[toList+"[]"];

    count = destinationList.options.length;
    destinationList.options[count] = new Option(text, value );

    return true;
}

// Open new window for the selection
function newWin(myurl) {
    var newWindow;
    var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=no,location=no,directories=no,width=580,height=400';
    newWindow = window.open(myurl, "Add_from_Src_to_Dest", props);
    newWindow.focus();
}

// Deletes the selected items of supplied list.
function deleteSelectedItemsFromList(sourceList) {
    obj = document.forms["vorm"].elements[sourceList+"[]"];
    while (obj.selectedIndex != '-1') {
        if (obj.options[obj.selectedIndex].value != '-1') {
            obj.options[obj.selectedIndex] = null;
        } else break;
    }
}

//preview button
function previewContent() {
    var win = newWindow('../preview_page.php', 600, 400);
    var old_target = document.forms["vorm"].target;
    var old_action = document.forms["vorm"].action;
    document.forms["vorm"].target = "window";
    document.forms["vorm"].action = "../preview_page.php";
    selectAll();
    document.forms["vorm"].target = old_target;
    document.forms["vorm"].action = old_action;
}

// select all for submit
function selectAll() {

    obj = document.forms["vorm"].elements["question[]"];
    if (obj) {
        for (i = 0; i < obj.options.length; i++) obj.options[i].selected = true;
    }
    obj = document.forms["vorm"].elements["pics[]"];
    if (obj) {
        for (i = 0; i < obj.options.length; i++) obj.options[i].selected = true;
    }
    obj = document.forms["vorm"].elements["files[]"];
    if (obj) {
        for (i = 0; i < obj.options.length; i++) obj.options[i].selected = true;
    }

    if (valueCheck(document.forms["vorm"].elements['text']) == true && valueCheck(window.frames["contentFreim"].oEdit1) == true) {
        if (window.frames["contentFreim"].document.forms["Form1"].elements['FullSource'].value == 1) {
            document.forms["vorm"].elements['text'].value = "<!doctype html public \"-//w3c//dtd xhtml 1.0 transitional//en\" \"http://www.w3.org/tr/xhtml1/dtd/xhtml1-transitional.dtd\">" + window.frames["contentFreim"].oEdit1.getXHTML();
        }
        else {
            document.forms["vorm"].elements['text'].value = window.frames["contentFreim"].oEdit1.getXHTMLBody();
        }
    } else if (typeof submittedContent != "undefined") {
        document.forms["vorm"].elements['text'].value = submittedContent;
        document.forms["vorm"].elements['editor_reload'].value = 1;
    }

    if (valueCheck(document.forms["vorm"].elements['content']) == true && valueCheck(window.frames["contentFreim"].oEdit1) == true) {
        if (window.frames["contentFreim"].document.forms["Form1"].elements['FullSource'].value == 1) {
            document.forms["vorm"].elements['content'].value = "<!doctype html public \"-//w3c//dtd xhtml 1.0 transitional//en\" \"http://www.w3.org/tr/xhtml1/dtd/xhtml1-transitional.dtd\">" + window.frames["contentFreim"].oEdit1.getXHTML();
        }
        else {
            document.forms["vorm"].elements['content'].value = window.frames["contentFreim"].oEdit1.getXHTMLBody();
        }
    }
    if (valueCheck(document.forms["vorm"].elements['pictures']) == true) {
        document.forms["vorm"].elements['pictures'].value = window.frames["contentFreim"].document.forms["picvorm"].elements['pictures'].value;
    }

    document.forms["vorm"].submit();
}

function fieldsetInit(maxfs) {
    // check if editor found, Moz speciality fix
    if (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true && valueCheck(document.getElementById("contentFreim"))) {
        document.getElementById("contentFreim").height = 0;
        for (i = 0; i < maxfs; i++) {
            if ((i+1) != 1) {
                if ((i+1) == 2) {
                    document.getElementById("fieldset"+(i+1)).className = "inactive";
                    document.getElementById("fieldset"+(i+1)).style.visibility = "hidden";
                }
                else {
                    document.getElementById("fieldset"+(i+1)).style.display = "none";
                }
            }
        }
    }
    else {
        for (i = 0; i < maxfs; i++) {
            if ((i+1) != 1) {
                document.getElementById("fieldset"+(i+1)).style.display = "none";
            }
        }
    }
}

function enableFieldset(current, fs, fs_extra, maxtab, maxfs) {
    for (i = 0; i < maxtab; i++) {
        document.getElementById("tabset"+(i+1)).className = "";
    }
    document.getElementById("tabset"+current).className = "active";

    for (i = 0; i < maxfs; i++) {
        if ((i+1) != fs && (i+1) != fs_extra) {
            if ((i+1) == 2 && (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true)) {
                document.getElementById("contentFreim").height = 0;
                document.getElementById("fieldset"+(i+1)).className = "inactive";
                document.getElementById("fieldset"+(i+1)).style.visibility = "hidden";
            }
            else {
                document.getElementById("fieldset"+(i+1)).style.display = "none";
            }
        }
    }

    // make selection active, special case for editor under moz
    if (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true && current == 2) {
        document.getElementById("contentFreim").height = 350;
        document.getElementById(fs).className = "";
        document.getElementById(fs).style.visibility = "visible";
        document.getElementById(fs).style.display = "block";
    }
    else {
        if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
        else {document.getElementById(fs).style.display = "block";}
    }

    if (fs_extra != '') {
        if (document.getElementById(fs_extra).style.display !="none"){document.getElementById(fs_extra).style.display = "none";}
        else {document.getElementById(fs_extra).style.display = "block"; }
    }

    if (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true) {
        if (current == 2 && document.forms["vorm"].elements["editor_reload"].value == 0) {
            document.forms["vorm"].elements["editor_reload"].value = 1;
            document.getElementById("contentFreim").src = document.forms["vorm"].elements["editor_src"].value;
        }
    }

    return;
}

function enableSingleFieldset(fs) {
    if (fs != '') {
        if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
        else {document.getElementById(fs).style.display = "block"; }
    }
    return;
}

function fieldJump(current, maxtab, jumpto) {
    for (i = 0; i < maxtab; i++) {
        document.getElementById("tabset"+(i+1)).className = "";
    }
    document.getElementById("tabset"+current).className = "active";

    document.location = jumpto;

    return;
}

function valueCheck(objToTest) {
    if (null == objToTest) {
        return false;
    }
    if ("undefined" == typeof(objToTest) ) {
        return false;
    }
    return true;

}

</SCRIPT>


<form method="post" action="<?php echo $data["PHP_SELF"]; ?>" <?php echo $data["ENCTYPE"]; ?> class="formpanel" name="vorm">

<?php if(isset($data["NOTICE"]) && is_array($data["NOTICE"])){ foreach($data["NOTICE"] as $_foreach["NOTICE"]){ ?>

<div class="form-notice"><?php echo $_foreach["NOTICE"]["CONTENT"]; ?></div>
<?php }} ?>


<?php if(isset($data["INFO"]) && is_array($data["INFO"])){ foreach($data["INFO"] as $_foreach["INFO"]){ ?>

    <fieldset title="<?php echo $_foreach["INFO"]["TITLE"]; ?>" <?php echo $_foreach["INFO"]["STYLE"]; ?>>
    <legend><?php echo $_foreach["INFO"]["TITLE"]; ?></legend>
        <table class="inputfield">
        <tr>
            <td><img src="pic/bullet_<?php echo $_foreach["INFO"]["TYPE"]; ?>.gif" alt="" border="0"></td>
            <td><label for="action" class="left"><?php echo $_foreach["INFO"]["INFO"]; ?></label></td>
        </tr>
        </table>
    </fieldset>
<?php }} ?>


<?php if(isset($data["FIELDSET"]) && is_array($data["FIELDSET"])){ foreach($data["FIELDSET"] as $_foreach["FIELDSET"]){ ?>

    <fieldset id="fieldset<?php echo $_foreach["FIELDSET"]["ID"]; ?>" title="<?php echo $_foreach["FIELDSET"]["TITLE"]; ?>" <?php echo $_foreach["FIELDSET"]["STYLE"]; ?>>
    <legend><?php echo $_foreach["FIELDSET"]["TITLE"]; ?></legend>
        <table class="inputfield">
        <?php if(isset($_foreach["FIELDSET"]["MAIN"]) && is_array($_foreach["FIELDSET"]["MAIN"])){ foreach($_foreach["FIELDSET"]["MAIN"] as $_foreach["FIELDSET.MAIN"]){ ?>

        <tr>
            <td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.MAIN"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.MAIN"]["DESC"]; ?>:</font>&nbsp;</label></td>
            <td align="left"><?php echo $_foreach["FIELDSET.MAIN"]["FIELD"]; ?>&nbsp;<label for="action" class="left"><?php echo $_foreach["FIELDSET.MAIN"]["EXTRA"]; ?></label></td>
            <td align="left">&nbsp;</td>
        </tr>
        <?php }} ?>

        <?php if(isset($_foreach["FIELDSET"]["BUTTONS"]) && is_array($_foreach["FIELDSET"]["BUTTONS"])){ foreach($_foreach["FIELDSET"]["BUTTONS"] as $_foreach["FIELDSET.BUTTONS"]){ ?>

        <tr>
            <td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.BUTTONS"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.BUTTONS"]["DESC"]; ?>:</font>&nbsp;</label></td>
            <td aling="left" colspan="2">
                <table border="0">
                    <tr>
                        <td align="left"><?php echo $_foreach["FIELDSET.BUTTONS"]["FIELD"]; ?></td>
                        <td align="left"><?php echo $_foreach["FIELDSET.BUTTONS"]["EXTRA"]; ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php }} ?>

        <?php if(isset($_foreach["FIELDSET"]["EXTERN"]) && is_array($_foreach["FIELDSET"]["EXTERN"])){ foreach($_foreach["FIELDSET"]["EXTERN"] as $_foreach["FIELDSET.EXTERN"]){ ?>

        <tr>
            <td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.EXTERN"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.EXTERN"]["DESC"]; ?>:</font>&nbsp;</label></td>
            <td align="left"><?php echo $_foreach["FIELDSET.EXTERN"]["FIELD"]; ?></td>
            <td align="left"><label for="action" class="left"><?php echo $_foreach["FIELDSET.EXTERN"]["EXTRA"]; ?></label></td>
        </tr>
        <?php }} ?>

        <?php if(isset($_foreach["FIELDSET"]["NOTHING"]) && is_array($_foreach["FIELDSET"]["NOTHING"])){ foreach($_foreach["FIELDSET"]["NOTHING"] as $_foreach["FIELDSET.NOTHING"]){ ?>

        <table class="inputfield" width="100%">
        <tr>
            <td align="left" colspan="3"><?php echo $_foreach["FIELDSET.NOTHING"]["FIELD"]; ?></td>
        </tr>
        </table>
        <?php }} ?>

        </table>
    </fieldset>
<?php }} ?>


        <?php if(isset($data["NOTHING"]) && is_array($data["NOTHING"])){ foreach($data["NOTHING"] as $_foreach["NOTHING"]){ ?>

        <table class="inputfield" width="100%">
        <tr>
            <td align="left" colspan="3"><?php echo $_foreach["NOTHING"]["FIELD"]; ?></td>
        </tr>
        </table>
        <?php }} ?>


<?php if(isset($data["HIDDEN"]) && is_array($data["HIDDEN"])){ foreach($data["HIDDEN"] as $_foreach["HIDDEN"]){ ?>

    <input type="hidden" name="<?php echo $_foreach["HIDDEN"]["NAME"]; ?>" value="<?php echo $_foreach["HIDDEN"]["VALUE"]; ?>">
<?php }} ?>


<p></p>
<div class="buttonbar">
    <button type="button" onClick="selectAll();"><img src="pic/button_accept.gif" alt="OK!" border="0"><?php echo $data["SENDBUTTONTXT"]; ?></button>
    <?php if(isset($data["BUTTONS"]) && is_array($data["BUTTONS"])){ foreach($data["BUTTONS"] as $_foreach["BUTTONS"]){ ?>

        &nbsp;&nbsp;<button type="button" onClick="<?php echo $_foreach["BUTTONS"]["ACTION"]; ?>"><img src="<?php echo $_foreach["BUTTONS"]["IMAGE"]; ?>" alt="" border="0"><?php echo $_foreach["BUTTONS"]["BUTTON"]; ?></button>
        <?php echo $_foreach["BUTTONS"]["BTNEXTRA"]; ?>
    <?php }} ?>

</div>

</form>
