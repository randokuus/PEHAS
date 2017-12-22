<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--
var deactivate_url = '';

function assignDeactivate() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_user|confirm_deactivate_multiple"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_user|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.process.processAction.value = 'Deactivate';
                document.process.submit();
            },
            "<?php echo $this->getTranslate("module_isic_card|cancel"); ?>": function() {
                $(this).dialog("close");
            }
        }
    });
    $('div.ui-dialog').append('<i class="ll"><i></i></i><i class="rr"><i></i></i><i class="tt"><i></i></i><i class="bb"><i></i></i><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');
    $('div.ui-dialog div.ui-dialog-buttonpane span.ui-button-text').wrap('<span></span>');
    $dialog.dialog('open');
}

function checkAll(name, isChecked) {
    var elements = document.getElementsByName(name);
    for(var i=0; i<elements.length; i++) {
        elements[i].checked = isChecked;
        if (isChecked) {
            $(elements[i]).next().addClass('jNiceChecked');
        } else {
            $(elements[i]).next().removeClass('jNiceChecked');
        }
    }
}

function submitForm(submitType) {
    if (submitType == 'export') {
        document.filter.export.value = 1;
    } else {
        document.filter.export.value = 0;
    }
    document.filter.submit();
}

//-->
</script>

            <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgError msgGray">
                    <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>


            <?php if(isset($data["IMESSAGE"]) && is_array($data["IMESSAGE"])){ foreach($data["IMESSAGE"] as $_foreach["IMESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgOk">
                    <span><?php echo $_foreach["IMESSAGE"]["IMESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>




            <?php if(isset($data["SEARCH"]) && is_array($data["SEARCH"])){ foreach($data["SEARCH"] as $_foreach["SEARCH"]){ ?>

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2>Filter</h2>
                    </div>
                    <!--boxcontent-->
                    <div class="boxcontent">
                        <!--filterTable-->
                        <div class="filterTable threeCols">
                            <form method="post" action="<?php echo $_foreach["SEARCH"]["SELF"]; ?>" name="filter" class="jNice">
                                <input type="hidden" id="export" name="export" value="" />
                            <?php echo $_foreach["SEARCH"]["HIDDEN"]; ?>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|name_first"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|name_last"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_NAME_LAST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|user_code"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_USER_CODE"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|region"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_REGION"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|school"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_SCHOOL"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|stru_unit"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_STRU_UNIT"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|faculty"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_FACULTY"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|status"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_STATUS"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|active"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_ACTIVE"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons" style="top: 25%;">
                                    <input type="button" value="<?php echo $this->getTranslate("module_isic_user|search"); ?>" onclick="javascript:submitForm('');" />
                                    <input type="button" value="<?php echo $this->getTranslate("module_isic_user|export"); ?>" onclick="javascript:submitForm('export');" />
                                </div>
                            </form>
                        </div>
                        <!--/filterTable-->
                    </div>
                    <!--/boxcontent-->
                </div>
            </div>
            <!--/box-->
            <?php }} ?>


           <form method="post" action="<?php echo $data["SELF"]; ?>" name="process" id="process" class="jNice">
           <?php echo $data["HIDDEN"]; ?>
           <input type="hidden" id="processAction" name="processAction" value="" />

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <div class="control">
                        </div>
                        <h2><?php echo $this->getGlobals("PAGETITLE"); ?></h2>
                    </div>

                    <!--pagin-->
                    <div class="pagin">
                        <div>
                            <?php echo $data["PAGES"]; ?>
                        </div>
                        <div>
                            <small>(<?php echo $data["RESULTS"]; ?>)</small>
                        </div>
                    </div>
                    <!--/pagin-->

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList">
                            <tr>
                                <th><?php echo $this->getTranslate("module_isic_user|select"); ?></th>
                                <?php if(isset($data["TITLE"]) && is_array($data["TITLE"])){ foreach($data["TITLE"] as $_foreach["TITLE"]){ ?>

                                <th><span class="<?php echo $_foreach["TITLE"]["CLASS"]; ?>"><a href="<?php echo $_foreach["TITLE"]["URL"]; ?>"><?php echo $_foreach["TITLE"]["NAME"]; ?></a></span></th>
                                <?php }} ?>

                                <th>&nbsp;</th>
                            </tr>
                            <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

                            <tr>
                                <td><input type="checkbox" name="processItems[]" value="<?php echo $_foreach["DATA"]["DATA_ID"]; ?>" /></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_IMAGE"]; ?>"><?php echo $_foreach["DATA"]["IMAGE"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_NAME_FIRST"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_NAME_LAST"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_USER_CODE"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_SCHOOL"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_STATUS"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_STRU_UNIT"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_FACULTY"]; ?></a></td>
                                <!--<td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_CLASS"]; ?></a></td>-->
                                <!--<td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_COURSE"]; ?></a></td>-->
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_ACTIVE"]; ?></a></td>
                                <td>
                                    <div class="wicons">
                                        <?php if(isset($_foreach["DATA"]["MOD"]) && is_array($_foreach["DATA"]["MOD"])){ foreach($_foreach["DATA"]["MOD"] as $_foreach["DATA.MOD"]){ ?>
<a href="<?php echo $_foreach["DATA.MOD"]["URL_MODIFY"]; ?>" class="ico iedit" title="Edit">Edit</a><?php }} ?>

                                        <?php if(isset($_foreach["DATA"]["DEL"]) && is_array($_foreach["DATA"]["DEL"])){ foreach($_foreach["DATA"]["DEL"] as $_foreach["DATA.DEL"]){ ?>
<a href="<?php echo $_foreach["DATA.DEL"]["URL_DELETE"]; ?>" class="ico idel" title="Delete">Delete</a><?php }} ?>

                                    </div>
                                </td>
                            </tr>
                            <?php }} ?>

                            <tr>
                                <td colspan="14"><input type="checkbox" onclick="checkAll('processItems[]', this.checked)" /> <?php echo $this->getTranslate("module_isic_user|select_all"); ?></td>
                            </tr>
                        </table>
                    </div>
                    <!--/tableWrap-->

                    <!--pagin-->
                    <div class="pagin">
                        <div>
                            <?php echo $data["PAGES"]; ?>
                        </div>
                        <div>
                            <small>(<?php echo $data["RESULTS"]; ?>)</small>
                        </div>
                    </div>
                    <!--/pagin-->
                </div>
            </div>
            <!--/box-->
            <?php if(isset($data["ACTIONS"]) && is_array($data["ACTIONS"])){ foreach($data["ACTIONS"] as $_foreach["ACTIONS"]){ ?>

            <div class="wButtons tListButtons">
                <input type="button" id="button_deactivate" value="<?php echo $_foreach["ACTIONS"]["TITLE"]; ?>" onclick="javascript:assignDeactivate();" />
            </div>
            <?php }} ?>

            </form>
