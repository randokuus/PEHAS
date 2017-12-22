<?php defined("MODERA_KEY")|| die(); ?><SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function fieldsetInit(maxfs) {
    for (i = 0; i < maxfs; i++) {
        if ((i+1) != 1) {
            document.getElementById("fieldset"+(i+1)).style.display = "none";
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
            document.getElementById("fieldset"+(i+1)).style.display = "none";
        }
    }
    if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
    else {document.getElementById(fs).style.display = "block";}

    if (fs_extra != '') {
        if (document.getElementById(fs_extra).style.display !="none"){document.getElementById(fs_extra).style.display = "none";}
        else {document.getElementById(fs_extra).style.display = "block"; }
    }

    if (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true) {
        if (current == 2 && document.forms["vorm"].elements["editor_reload"].value == 0) {
            document.forms["vorm"].elements["editor_reload"].value = 1;
            document.all.contentFreim.src = document.forms["vorm"].elements["editor_src"].value;
        }
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

function enableSingleFieldset(fs) {
    if (fs != '') {
        if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
        else {document.getElementById(fs).style.display = "block"; }
    }
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
<script type="text/javascript" src="../js/file_uploader.js"></script>
<form id="vorm" method="post" action="<?php echo $data["PHP_SELF"]; ?>" <?php echo $data["ENCTYPE"]; ?> class="formpanel" name="vorm">

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
        <table class="inputfield" id="file_table">
        <tr>
            <td align="left" colspan="3" id="btBrowse" height="40px"><?php echo $this->getTranslate("admin_files|browse"); ?></td>
        </tr>
        <tr>
            <td><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET"]["COLOR"]; ?>"><?php echo $this->getTranslate("admin_files|file"); ?></font>&nbsp;</label></td>
            <td><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET"]["COLOR"]; ?>"><?php echo $this->getTranslate("admin_files|text"); ?></font>&nbsp;</label></td>
            <td></td>
        </tr>
        <?php if(isset($_foreach["FIELDSET"]["MAIN"]) && is_array($_foreach["FIELDSET"]["MAIN"])){ foreach($_foreach["FIELDSET"]["MAIN"] as $_foreach["FIELDSET.MAIN"]){ ?>

        <tr id="details_row">
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
<script type="text/javascript" src="../js/swfobject.js"></script>
<script type="text/javascript">
    var version = deconcept.SWFObjectUtil.getPlayerVersion();
    if (version["major"] < 8 /*|| (navigator.appName.indexOf("Microsoft") == -1 && document.location.protocol != 'http:')*/) {
        document.location = 'files_admin.php?show=add_single';
    }
</script>

<script type="text/javascript" src="../js/ext/adapter/yui/yui-utilities.js"></script>
<script type="text/javascript" src="../js/ext/adapter/yui/ext-yui-adapter.js"></script>
<script type="text/javascript" src="../js/ext/ext-all.js"></script>
<script type="text/javascript" src="../js/modera/MessageBox.js"></script>

<link rel="stylesheet" type="text/css" href="../js/ext/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="../js/ext/resources/css/ytheme-gray.css" />

<script type="text/javascript">
    var mb;
    var file_table = document.getElementById("file_table");
    var details_row = document.getElementById("details_row");

    var fileUploader;

    var filesCount = 0;


    function removeNode(obj) {
        var parent;
        parent = obj.parentNode;
        while (obj.childNodes.length > 0) {
            removeNode(obj.childNodes[obj.childNodes.length - 1]);
        }
        var tag = obj.tagName;
        parent.removeChild(obj);
    }

    function getCookie(sName) {
        // cookies are separated by semicolons
        var aCookie = document.cookie.split("; ");
        for (var i=0; i < aCookie.length; i++) {
            // a name/value pair (a crumb) is separated by an equal sign
            var aCrumb = aCookie[i].split("=");
            if (sName == aCrumb[0])
            return unescape(aCrumb[1]);
        }

        // a cookie with the requested name does not exist
        return null;
    }

Ext.onReady(function() {

    var butparent = document.getElementById('btBrowse');

    fileUploader = new FileUploader(butparent,document.forms['vorm'].elements['max_size'].value);

    fileUploader.onAddFile = function(file) {
        filesCount++;
        var row = document.createElement('TR');
        row.file = file;
        file_table.tBodies[0].insertBefore(row, details_row);
        var cell = document.createElement('TD');
        row.appendChild(cell);
        cell.innerHTML = '<label for="action" class="left"><font color="<?php echo $data["COLOR"]; ?>">' + file.name + '</font>&nbsp;</label>';
        var cell = document.createElement('TD');
        row.appendChild(cell);
        var input = document.createElement('INPUT');
        input.type = "text";
        input.name = "description[" + file.id + "]";
        cell.appendChild(input);
        row.textInput = input;
        var cell = document.createElement('TD');
        row.appendChild(cell);
        cell.innerHTML = '<a href="javascript:if (confirm(\'<?php echo $this->getTranslate("admin_files|delete_file"); ?>\')) fileUploader.deleteFile(' + row.file.id + ')"><img src="/admin/pic/delete.gif" border="0" width="9px" height="11px"/></a>';
    }

    fileUploader.onDelete = function(id) {
        var i;
        filesCount--;
        for (i = 1; i < file_table.tBodies[0].childNodes.length; i++) {
            if (file_table.tBodies[0].childNodes[i].file && file_table.tBodies[0].childNodes[i].file.id == id) {
                removeNode(file_table.tBodies[0].childNodes[i]);
                break;
            }
        }
    }

    fileUploader.onProgress = function(currentIndex, count, currentPos, totalPos) {
        mb.updateProgress(currentPos / 100, '<?php echo $this->getTranslate("admin_files|uploading"); ?> ' + currentIndex + ' <?php echo $this->getTranslate("admin_files|of"); ?> ' + count);
        mb.updateProgress2(totalPos / 100);
    }

    fileUploader.onComplete = function(url) {

        mb.updateText('<?php echo $this->getTranslate("admin_files|saving"); ?>');
        document.getElementById("vorm").elements["do"].value = "finish_upload";
        document.getElementById("vorm").submit();

    }

    fileUploader.buttonDisplayed = function(type) {

        if (type) {
            this.flash.style.left = "";
            this.flash.style.width ="";
            this.flash.style.height = "";
        } else {
            this.flash.style.width = "0px";
            this.flash.style.height = "0px";
            this.flash.style.left   = "-400px";
        }
    }

    fileUploader.onError = function(type, files, error) {

        switch (type) {
            case 'http':
               if (error == 506) {
                   alert(files + ' <?php echo $this->getTranslate("admin_files|error_log_uploading_file"); ?> ');
                   this.buttonDisplayed(true);
                   mb.hide();
                   break;
               }
            case 'io':
            case 'security':

                if (error == null) {
                    error = '';
                }

                alert('<?php echo $this->getTranslate("admin_files|error_uploading_file"); ?> ' + files + error);
                this.buttonDisplayed(true);
                mb.hide();
                break;
            case 'size':
                var max_file_size = document.forms["vorm"].elements["max_size"].value;
                var tmp = "";
                for (var i = 0; i < files.length; i++) {
                    if (tmp != "") tmp += ", ";
                    tmp += files[i].name;
                }
                if (files.length > 1) {
                    tmp = '<?php echo $this->getTranslate("admin_files|sizes_of_files"); ?> ' + tmp + ' <?php echo $this->getTranslate("admin_files|are_invalid"); ?>.';
                } else {
                    tmp = '<?php echo $this->getTranslate("admin_files|size_of_file"); ?> ' + tmp + ' <?php echo $this->getTranslate("admin_files|is_invalid"); ?>.';
                }
                tmp += ' <?php echo $this->getTranslate("admin_files|file_size_should_be_from"); ?> 1 <?php echo $this->getTranslate("admin_files|till"); ?> ' + max_file_size + ' <?php echo $this->getTranslate("admin_files|bytes"); ?>';
                alert(tmp);
                this.buttonDisplayed(true);
            break;
        }
    }

});


    function startUpload() {
        if (filesCount <= 0) {
            alert('<?php echo $this->getTranslate("admin_files|no_selected_files"); ?>');
            return;
        }
        fileUploader.buttonDisplayed(false);
        mb = Modera.MessageBox.show({
            title:'<?php echo $this->getTranslate("admin_files|please_wait"); ?>',
            msg:'<?php echo $this->getTranslate("admin_files|starting"); ?>',
            width:240,
            progress2:true,
            closable:false
        });
        var form_data = new Object;
        var form = document.getElementById('vorm');
        var conn = new Ext.data.Connection();
        var i;
        var el;
        for (i = 0; i < form.elements.length; i++) {
            el = form.elements[i];
            if (el.type != 'checkbox' || el.checked) {
                form_data[el.name] = el.value;
            }
        }
        form_data['do'] = 'prepare_upload';
        conn.request({
            url: form.action,
            params: form_data,
            method: 'POST',
            callback: uploadFiles
        });
    }

    function uploadFiles() {
        var port = document.location.port;
        if (port == '') {
            if (document.location.protocol == 'http:') {
                port = '80';
            } else {
                port = '443';
            }
        }
        var url = document.location.protocol + '//' + document.location.hostname + ':' + port + document.location.pathname;
        fileUploader.startUpload(url + "?show=add&ADM_SESS_SID=" + getCookie("ADM_SESS_SID") + "&ADM_LANG_SID=" + getCookie("ADM_LANG_SID") + "&form_id=" + document.getElementById('vorm').elements['form_id'].value + "&do=savefile&file=");
    }


</script>
<div class="buttonbar">
    <button type="button" onclick="startUpload()"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SENDBUTTONTXT"]; ?></button>
</div>

</form>