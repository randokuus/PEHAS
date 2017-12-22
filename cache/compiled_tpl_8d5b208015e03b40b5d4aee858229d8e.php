<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript" src="js/isic_pic_uploader.js"></script>

        <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgError msgGray">
                <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
            </p>
        </div>
        <!--/msgWrap-->
        <?php }} ?>


        

        <!--singleCol-->
        <div class="singleCol">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $this->getGlobals("PAGETITLE"); ?></h2>
                        </div>

                        <!--boxcontent-->
                        <div class="boxcontent">
                            <!--col3-->
                            <div class="col3">
                                <!--controlTable-->
                                <div class="formTable controlTable">
                                    <form id="vorm" name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
                                    <input type="hidden" name="add_multi" value="1">
                                    <?php echo $data["HIDDEN"]; ?>
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_pic|choose_pics"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <span id="btBrowse"><?php echo $this->getTranslate("module_isic_pic|browse"); ?></span>
                                            </div>
                                        </div>
                                        <!--/fLine-->
                                        
					                    <!--tableWrap-->
					                    <div class="tableWrap">
					                        <table class="tList" id="file_table">
					                        </table>
					                    </div>
					                    <!--/tableWrap-->
                                        
                                        <!--fSubmit-->
                                        <div class="fSubmit">
                                            <div class="fSubmitInner">
                                                <input type="button" value="<?php echo $data["BUTTON"]; ?>" onclick="startUpload();" />
                                            </div>
                                        </div>
                                        <!--/fSubmit-->
                                    </form>
                                </div>
                                <!--/controlTable-->
                            </div>
                            <!--/col3-->
                            <!--col4-->
                            <div class="col4 content">
                                <?php echo $this->getTranslate("module_isic_pic|pic_import_description"); ?>
                            </div>
                            <!--/col4-->
                        </div>
                        <!--/boxcontent-->
                    </div>
                </div>
                <!--/box-->
        </div>
        <!--/singleCol-->

<script type="text/javascript" src="js/swfobject.js"></script>
<script type="text/javascript" src="js/ext/adapter/yui/yui-utilities.js"></script>
<script type="text/javascript" src="js/ext/adapter/yui/ext-yui-adapter.js"></script>
<script type="text/javascript" src="js/ext/ext-all.js"></script>
<script type="text/javascript" src="js/modera/MessageBox.js"></script>
<link rel="stylesheet" type="text/css" href="js/ext/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="js/ext/resources/css/ytheme-gray.css" />

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
        //file_table.tBodies[0].insertBefore(row, details_row);
        file_table.appendChild(row);
        var cell = document.createElement('TD');
        row.appendChild(cell);
        cell.innerHTML = file.name;
        var cell = document.createElement('TD');
        row.appendChild(cell);
        cell.innerHTML = '<a href="javascript:if (confirm(\'<?php echo $this->getTranslate("module_isic_pic|confirmation"); ?>\')) fileUploader.deleteFile(' + row.file.id + ')"><img src="img/delete.gif" border="0" /></a>';
    }

    fileUploader.onDelete = function(id) {
        var i;
        filesCount--;
        for (i = 0; i < file_table.childNodes.length; i++) {
            if (file_table.childNodes[i].file && file_table.childNodes[i].file.id == id) {
                removeNode(file_table.childNodes[i]);
                break;
            }
        }
    }

    fileUploader.onProgress = function(currentIndex, count, currentPos, totalPos) {
        mb.updateProgress(currentPos / 100, '<?php echo $this->getTranslate("module_isic_pic|uploading"); ?> ' + currentIndex + ' <?php echo $this->getTranslate("module_isic_pic|of"); ?> ' + count);
        mb.updateProgress2(totalPos / 100);
    }

    fileUploader.onComplete = function(url) {
        mb.updateText('<?php echo $this->getTranslate("module_isic_pic|saving"); ?>');
        document.getElementById("vorm").elements["faction"].value = "finish_upload";
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
                   alert(files + ' <?php echo $this->getTranslate("module_isic_pic|error_log_uploading_file"); ?> ');
                   this.buttonDisplayed(true);
                   mb.hide();
                   break;
               }
            case 'io':
            case 'security':

                if (error == null) {
                    error = '';
                }

                alert('<?php echo $this->getTranslate("module_isic_pic|error_uploading_file"); ?> ' + files + error);
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
                    tmp = '<?php echo $this->getTranslate("module_isic_pic|sizes_of_files"); ?> ' + tmp + ' <?php echo $this->getTranslate("module_isic_pic|are_invalid"); ?>.';
                } else {
                    tmp = '<?php echo $this->getTranslate("module_isic_pic|size_of_file"); ?> ' + tmp + ' <?php echo $this->getTranslate("module_isic_pic|is_invalid"); ?>.';
                }
                tmp += ' <?php echo $this->getTranslate("module_isic_pic|file_size_should_be_from"); ?> 1 <?php echo $this->getTranslate("module_isic_pic|till"); ?> ' + max_file_size + ' <?php echo $this->getTranslate("module_isic_pic|bytes"); ?>';
                alert(tmp);
                this.buttonDisplayed(true);
            break;
        }
    }

});

    function startUpload() {
        if (filesCount <= 0) {
            alert('<?php echo $this->getTranslate("module_isic_pic|no_selected_files"); ?>');
            return;
        }
        fileUploader.buttonDisplayed(false);
        mb = Modera.MessageBox.show({
            title: '<?php echo $this->getTranslate("module_isic_pic|please_wait"); ?>',
            msg: '<?php echo $this->getTranslate("module_isic_pic|starting"); ?>',
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
        form_data['faction'] = 'prepare_upload';
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
      var url = document.location.protocol + '//' + document.location.hostname + ':' + port + '<?php echo $data["SELF"]; ?>';
      var cookie = getCookie("USR_SESS_SID");
      var phpcookie = getCookie("PHPSESSID");
      if (!cookie) {
        cookie = getCookie("PHPSESSID");
      }
      fileUploader.startUpload(url + "&SID=" + cookie + "&PHPSESSID=" + phpcookie + "&form_id=" + document.getElementById('vorm').elements['form_id'].value + "&faction=savefile&file=");
    }

//    enableControls();

</script>