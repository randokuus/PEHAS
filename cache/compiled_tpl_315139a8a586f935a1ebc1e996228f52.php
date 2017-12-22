<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<base target="_self">
<head>
    <title>Files</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <link rel="stylesheet" href="main.css" type="text/css" media="all" />

        <SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
        <!--
        function submitTo() {
            document.forms["vorm"].elements['submit_to'].value = '1';
            document.forms["vorm"].submit();
        }
        // Open new window for the selection
        function newWindow(myurl, sizex, sizey) {
            var newWindow;
            var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=yes,location=no,directories=no,width='+sizex+',height='+sizey;
            newWindow = window.open(myurl, "window", props);
            newWindow.focus();
        }

        function openFile(loct) {
            var win=window.open(loct,'','width=600,height=400,menu=yes,status=yes,scrollbars=no');
        }

        //-->
        </SCRIPT>
<style>
  #loading{
    position:absolute;
    left:45%;
    top:40%;
    border:1px solid #aaaaaa;
    padding:2px;
    background:#f0eee3;
    width:150px;
    text-align:center;
    z-index:20001;
}

#loading .loading-indicator{
    border:1px solid #aaaaaa;
    background:white;
    color:#003366;
    font:bold 13px tahoma,arial,helvetica;
    padding:10px;
    margin:0;
}
</style>

</head>

<body id="body-frame" style="overflow:auto; width: 50%; height: 50%">
<div id="loading-mask" style="width:100%;height:100%;position:absolute;z-index:20000;left:0;top:0;background:#DEDEDE">&#160;</div>
<div id="loading">
    <div class="loading-indicator">
        <img src="../js/ext/resources/images/default/grid/loading.gif" style="width:16px;height:16px;" align="absmiddle"/>
        &#160;<?php echo $this->getTranslate("files_index|loading"); ?>... </div>
</div>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
function valueCheck(objToTest) {
    if (null == objToTest) {
        return false;
    }
    if ("undefined" == typeof(objToTest) ) {
        return false;
    }
    return true;

}

/*function objInfo(id, name, type, date, size, view_url, thumb_url, delete_url, folder){

  bullet = "<img src='pic/tree_dot.gif' alt='' border=0>"

  parent.frames.left.document.getElementById('objname').innerHTML = "<?php echo $this->getTranslate("files_index|info_name"); ?>: " + name + "." + type;
  //parent.frames.left.document.getElementById('objtype').innerHTML = "<?php echo $this->getTranslate("files_index|info_type"); ?>: " + type;
  parent.frames.left.document.getElementById('objdate').innerHTML = "<?php echo $this->getTranslate("files_index|info_date"); ?>: " + date;
  parent.frames.left.document.getElementById('objsize').innerHTML = "<?php echo $this->getTranslate("files_index|info_size"); ?>: " + size;
  parent.frames.left.document.getElementById('objmodify').innerHTML = bullet + ' <a href="javascript:parent.frames.right.browser.modifySelectedFile();"><?php echo $this->getTranslate("files_index|info_modify"); ?></a>';
  parent.frames.left.document.getElementById('objdelete').innerHTML = bullet + ' <a href="javascript:parent.frames.right.browser.deleteSelectedFile();"><?php echo $this->getTranslate("files_index|info_delete"); ?></a>';
  parent.frames.left.document.getElementById('objlinks').innerHTML = bullet + ' <a href="javascript:parent.frames.right.browser.viewSelectedFile();"><?php echo $this->getTranslate("files_index|info_file"); ?></a>';
  parent.frames.left.document.getElementById('filepreview').style.display = "block";
  if (thumb_url != "") {
      parent.frames.left.document.getElementById('objimage').innerHTML = '<a href="' + view_url + '"><img src="' + thumb_url + '" alt="" border="0"></a>';
  } else {
      parent.frames.left.document.getElementById('objimage').innerHTML = "&nbsp;";
  }

}*/

function selectFile(id, name, type, date, size, url, url1,url2){
    var imageselector = false;

    if (valueCheck(parent.document.getElementById('inpURL'))) {
        imageselector = true;
    }
    else {
        imageselector = false;
    }

    if(navigator.appName.indexOf('Microsoft')!=-1) {
      if (url1 != "") {
            conf = window.confirm('<?php echo $this->getTranslate("files_index|info_thumb"); ?>');
            if (conf) {
                if (imageselector)    top.document.forms[0].elements['inpURL'].value= url1;
                else window.returnValue= url1;
            }
            else {
                if (imageselector)    top.document.forms[0].elements['inpURL'].value= url;
                else window.returnValue= url;
            }
      }
      else {
            if (imageselector)    top.document.forms[0].elements['inpURL'].value= url;
            else window.returnValue= url;
      }
    }

    else {
      if (url1 != "") {
            conf = window.confirm('<?php echo $this->getTranslate("files_index|info_thumb"); ?>');
            if (conf) {
                if (imageselector)    top.document.forms[0].elements['inpURL'].value= url1;
                else window.opener.setAssetValue(url1);
            }
            else {
                if (imageselector)    top.document.forms[0].elements['inpURL'].value= url;
                else window.opener.setAssetValue(url);
            }
      }
      else {
            if (imageselector)    top.document.forms[0].elements['inpURL'].value= url;
            else window.opener.setAssetValue(url);
      }
    }

    if (imageselector == false) self.close();
}
</SCRIPT>

<!-- begin list -->
<!-- GC --> <!-- LIBS -->     <script type="text/javascript" src="../js/ext/adapter/yui/yui-utilities.js"></script>     <script type="text/javascript" src="../js/ext/adapter/yui/ext-yui-adapter.js"></script>     <!-- ENDLIBS -->
<link rel="stylesheet" type="text/css" href="../js/ext/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="../img/ext-modera/theme.css" />
<link rel="stylesheet" type="text/css" href="../img/file_browser.css" />
<script type="text/javascript" src="../js/ext/ext-all.js"></script>
<script type="text/javascript" src="../js/file_browser.js"></script>
<script type="text/javascript" src="../js/editor.js"></script>
<script type="text/javascript">
function onSelectFile(record) {
    var mod_date = new Date(record.get('last_modified'));
    var delete_url = record.get('folder') + record.get('obj') + '.' + record.get('type');
<?php if(isset($data["BROWSER"]) && is_array($data["BROWSER"])){ foreach($data["BROWSER"] as $_foreach["BROWSER"]){ ?>


  /* objInfo(
        record.get('id'),
        record.get('obj'),
        record.get('type'),
        mod_date.format('d.m.y h:i'),
        record.get('size'),
        record.get('view_url'),
        record.get('thumb_url'),
        encodeURI(delete_url),
        record.get('folder')
    );*/
<?php }} ?>


    selectFile(
        record.get('id'),
        record.get('obj'),
        record.get('type'),
        mod_date.format('d.m.y h:i'),
        record.get('size'),
        record.get('view_url'),
        record.get('thumb_url'),
        encodeURI(delete_url),
        record.get('folder')
    );

}
var browser;
Ext.onReady(function(){
    var lang_data = {
        foldersTitle: '<?php echo $this->getTranslate("admin_files|folders"); ?>',
        viewDetailTitle: '<?php echo $this->getTranslate("files_index|view_detail"); ?>',
        gridFileName: '<?php echo $this->getTranslate("files_index|info_name"); ?>',
        gridFileSize: '<?php echo $this->getTranslate("files_index|info_size"); ?>',
        gridFileDate: '<?php echo $this->getTranslate("files_index|info_date"); ?>',
        gridFileDescription: '<?php echo $this->getTranslate("admin_files|text"); ?>',
        viewIconTitle: '<?php echo $this->getTranslate("files_index|view_icon"); ?>',
        confirmDelete: '<?php echo $this->getTranslate("files_index|info_confirmation"); ?>',
        confirmMove: '<?php echo $this->getTranslate("files_index|move_file_confirmation"); ?>',
        confirmCopy: '<?php echo $this->getTranslate("files_index|copy_file_confirmation"); ?>',
        confirmEmpty: '<?php echo $this->getTranslate("admin_files|empty_folder_confirmation"); ?>',
        confirmDeleteFolder: '<?php echo $this->getTranslate("admin_files|delete_folder_confirmation"); ?>',
        confirmCreateThumbnails: '<?php echo $this->getTranslate("admin_files|create_thumbnails_confirmation"); ?>',
        labelView: '<?php echo $this->getTranslate("files_index|info_file"); ?>',
        labelModify: '<?php echo $this->getTranslate("files_index|info_modify"); ?>',
        labelDelete: '<?php echo $this->getTranslate("files_index|info_delete"); ?>',
        labelCopy: '<?php echo $this->getTranslate("files_index|copy"); ?>',
        labelMove: '<?php echo $this->getTranslate("files_index|move"); ?>',
        labelCancel: '<?php echo $this->getTranslate("files_index|cancel"); ?>',
        labelEmpty: '<?php echo $this->getTranslate("files_index|empty"); ?>',
        labelCreateThumbnails: '<?php echo $this->getTranslate("admin_files|create_thumbnails"); ?>',
        labelCreate: '<?php echo $this->getTranslate("admin_files|addfolder"); ?>',
        labelRestore: '<?php echo $this->getTranslate("admin_files|restore"); ?>',
        processDelete: '<?php echo $this->getTranslate("files_index|deleting"); ?>',
        processMove: '<?php echo $this->getTranslate("files_index|moving"); ?>',
        processCopy: '<?php echo $this->getTranslate("files_index|copying"); ?>',
        processCreatingFolder: '<?php echo $this->getTranslate("files_index|creating_folder"); ?>',
        processEmptyingFolder: '<?php echo $this->getTranslate("files_index|emptying_folder"); ?>',
        processDeletingFolder: '<?php echo $this->getTranslate("files_index|deleting_folder"); ?>',
        processCopyingFolder: '<?php echo $this->getTranslate("files_index|copying_folder"); ?>',
        processMovingFolder: '<?php echo $this->getTranslate("files_index|moving_folder"); ?>',
        processRenamingFolder: '<?php echo $this->getTranslate("files_index|rename_folder"); ?>',
        processCreatingThumbnails: '<?php echo $this->getTranslate("files_index|creating_thumbnails"); ?>',
        processRestoring: '<?php echo $this->getTranslate("admin_files|restoring"); ?>',
        pictureView: '<?php echo $this->getTranslate("admin_content|picture_view"); ?>',
        detailView: '<?php echo $this->getTranslate("admin_content|detail_view"); ?>',
        addNew: '<?php echo $this->getTranslate("admin_content|add_new"); ?>',
        filter: '<?php echo $this->getTranslate("admin_content|filter"); ?>',
        promptFolderName: '<?php echo $this->getTranslate("files_index|prompt_folder_name"); ?>',
        msg_set_backend_url: '<?php echo $this->getTranslate("files_index|msg_set_backend_url"); ?>',
        all_files: '<?php echo $this->getTranslate("files_index|sel_all"); ?>',
        only_images: "<?php echo $this->getTranslate("files_index|sel_pic"); ?>",
        not_images: '<?php echo $this->getTranslate("files_index|sel_nopic"); ?>',
        doc_files: '<?php echo $this->getTranslate("files_index|sel_doc"); ?>',
        xsl_files: '<?php echo $this->getTranslate("files_index|sel_xls"); ?>',
        zip_files: '<?php echo $this->getTranslate("files_index|sel_zip"); ?>',
        lbl_alert: '<?php echo $this->getTranslate("files_index|lbl_alert"); ?>',
        msg_you_cant_delete_recycle: "<?php echo $this->getTranslate("files_index|msg_you_cant_delete_recycle"); ?>",
        msg_you_cant_delete_folder: "<?php echo $this->getTranslate("files_index|msg_you_cant_delete_folder"); ?>",
        msg_you_cant_rename_folder: "<?php echo $this->getTranslate("files_index|msg_you_cant_rename_folder"); ?>",
        msg_you_cant_restore_folder: "<?php echo $this->getTranslate("files_index|msg_you_cant_restore_folder"); ?>",
        msg_empty_name: "<?php echo $this->getTranslate("files_index|msg_empty_name"); ?>",
        ovwc_rplfolowed: "<?php echo $this->getTranslate("files_index|ovwc_rplfolowed"); ?>",
        ovwc_rplby: "<?php echo $this->getTranslate("files_index|ovwc_rplby"); ?>",
        ovwc_lastupdated: "<?php echo $this->getTranslate("files_index|ovwc_lastupdated"); ?>",
        ovwc_folder: "<?php echo $this->getTranslate("files_index|ovwc_folder"); ?>",
        ovwc_file: "<?php echo $this->getTranslate("files_index|ovwc_file"); ?>",
        ovwc_files: "<?php echo $this->getTranslate("files_index|ovwc_files"); ?>",
        processRefreshFolder: "<?php echo $this->getTranslate("files_index|processrefreshfolder"); ?>",
        labelRefresh: "<?php echo $this->getTranslate("files_index|label_refresh"); ?>",
        labelOwner: "<?php echo $this->getTranslate("files_index|label_owner"); ?>",
        labelRename: "<?php echo $this->getTranslate("files_index|label_rename"); ?>",
        labelErrorTitle: "<?php echo $this->getTranslate("files_index|label_error_title"); ?>",
        errorParentPriv: "<?php echo $this->getTranslate("files_index|error_parent_priv"); ?>",
        labelPrivs: "<?php echo $this->getTranslate("files_index|labelprivs"); ?>",
        labelShowDeleted: "<?php echo $this->getTranslate("files_index|labelshowdeleted"); ?>",
        titlePleaseWait: "<?php echo $this->getTranslate("files_index|titlepleasewait"); ?>",
        titleSave: "<?php echo $this->getTranslate("admin_settings|button"); ?>",
        restoreParentsConfirmation: "<?php echo $this->getTranslate("files_index|restoreparentsconfirmation"); ?>",
        systemType: "<?php echo $this->getTranslate("files_index|systemtype"); ?>",
        systemSource: "<?php echo $this->getTranslate("files_index|systemsource"); ?>",
        systemTypes: {
                file: "<?php echo $this->getTranslate("files_index|systemtypesfile"); ?>",
                folder: "<?php echo $this->getTranslate("files_index|systemtypesfolder"); ?>",
                image: "<?php echo $this->getTranslate("files_index|systemtypesimage"); ?>"
        }
    };

<?php if(isset($data["SELECTOR"]) && is_array($data["SELECTOR"])){ foreach($data["SELECTOR"] as $_foreach["SELECTOR"]){ ?>

    FileBrowser.prototype.selectorMode = true;
    FileBrowser.prototype.onSelectFile = onSelectFile;
<?php }} ?>


    browser = new FileBrowser(document.body, {
        backendUrl: "browser.php",
        lang: lang_data,
        viewstate: <?php echo $data["VIEWSTATE"]; ?>,
        disabledstate: <?php echo $data["DISABLEDSTATE"]; ?>,
        permaccess: <?php echo $data["PERMACCESS"]; ?>,
        startFolder: '<?php echo $data["FOLDER"]; ?>',
        preselectedFiles: <?php echo $data["SELECTEDFILES"]; ?>
    });

    var loading = Ext.get('loading');
    var mask = Ext.get('loading-mask');
    mask.setOpacity(.8);
    mask.shift({
        xy:loading.getXY(),
        width:loading.getWidth(),
        height:loading.getHeight(),
        remove:true,
        duration:1,
        opacity:.3,
        easing:'bounceOut',
        callback : function(){
          loading.fadeOut({duration:0.2,remove:true});
          browser.render();
        }
    });
});
</script>
</body>
</html>