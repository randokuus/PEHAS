<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--
var del_url = '';

function del(url) {
    del_url = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $data["CONFIRMATION"]; ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = del_url;
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

function assignAdminConfirm() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_admin_list"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.confirm.confirm_type.value = 'admin_confirm';
                document.confirm.submit();
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

function assignConfirmAdminConfirm() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_admin_confirm_list"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.confirm.confirm_type.value = 'admin_confirm';
                document.confirm.confirm_admin_confirm.value = 'true';
                document.confirm.submit();
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

function assignReject() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_reject"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.confirm.confirm_type.value = 'reject';
                document.confirm.submit();
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

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container    DOM element
 */
function markAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {
        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            if ( checkbox.disabled == false ) {
                checkbox.checked = true;
                $(checkbox).next().addClass('jNiceChecked');
            }
        }
    }

    return true;
}

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container    DOM element
 */
function unMarkAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {
        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            checkbox.checked = false;
            $(checkbox).next().removeClass('jNiceChecked');
        }
    }

    return true;
}

function toggleAllRows(container_id) {
    var check_all = document.getElementById('check_all');
    if (check_all.checked) {
        markAllRows(container_id);
    } else {
        unMarkAllRows(container_id);
    }
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


            <?php if(isset($data["AMESSAGE"]) && is_array($data["AMESSAGE"])){ foreach($data["AMESSAGE"] as $_foreach["AMESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgAtt msgGray">
                    <span><?php echo $this->getTranslate("output|attention"); ?> <?php echo $_foreach["AMESSAGE"]["MESSAGE"]; ?></span>
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
                        <div class="filterTable fourCols">
                            <form method="post" action="<?php echo $_foreach["SEARCH"]["SELF"]; ?>" name="filter" class="jNice">
                            <?php echo $_foreach["SEARCH"]["HIDDEN"]; ?>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_PERSON_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_PERSON_NAME_LAST"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_id"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_PERSON_NUMBER"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|region"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_REGION_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|school"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_SCHOOL_ID"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_stru_unit2"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_PERSON_STRU_UNIT2"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_stru_unit"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_PERSON_STRU_UNIT"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|type"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_TYPE_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|application_type"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_APPLICATION_TYPE_ID"]; ?>
                                    </div>
                                    <?php if(isset($_foreach["SEARCH"]["JOINED"]) && is_array($_foreach["SEARCH"]["JOINED"])){ foreach($_foreach["SEARCH"]["JOINED"] as $_foreach["SEARCH.JOINED"]){ ?>

                                        <div class="tLine">
                                            <label for=""><?php echo $this->getTranslate("module_isic_card|joined_schools"); ?>:</label>
                                            <?php echo $_foreach["SEARCH.JOINED"]["FIELD_FILTER_JOINED"]; ?>
                                        </div>
                                    <?php }} ?>

                                </div>
                                <div class="tLineButtons">
                                    <input type="submit" value="<?php echo $this->getTranslate("module_isic_card|search"); ?>" />
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


           <form method="post" action="<?php echo $data["SELF"]; ?>" name="confirm" id="confirm" class="jNice">
           <input type="hidden" name="write_confirm" value="1" id="write_confirm">
           <input type="hidden" name="confirm_type" value="" id="confirm_type">
           <?php echo $data["HIDDEN"]; ?>

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
                          <style>
                            .tList th.width-limited { width: 40px; }
                          </style>
                        <table class="tList">
                            <tr>
                    <?php if(isset($data["CONFIRM_TITLE"]) && is_array($data["CONFIRM_TITLE"])){ foreach($data["CONFIRM_TITLE"] as $_foreach["CONFIRM_TITLE"]){ ?>

                    <th><?php echo $_foreach["CONFIRM_TITLE"]["TITLE"]; ?></th>
                    <?php }} ?>

                    <?php if(isset($data["TITLE"]) && is_array($data["TITLE"])){ foreach($data["TITLE"] as $_foreach["TITLE"]){ ?>

                    <th class="<?php echo $_foreach["TITLE"]["TH_CLASS"]; ?>"><span class="<?php echo $_foreach["TITLE"]["CLASS"]; ?>"><a href="<?php echo $_foreach["TITLE"]["URL"]; ?>"><?php echo $_foreach["TITLE"]["NAME"]; ?></a></span></th>
                    <?php }} ?>

                    <th><span class=""><?php echo $this->getTranslate("module_isic_card|bindings"); ?></span></th>
                    <th>&nbsp;</th>
                            </tr>
                            <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

                            <tr>
                                <?php if(isset($_foreach["DATA"]["CONFIRM"]) && is_array($_foreach["DATA"]["CONFIRM"])){ foreach($_foreach["DATA"]["CONFIRM"] as $_foreach["DATA.CONFIRM"]){ ?>

                                <td><?php echo $_foreach["DATA.CONFIRM"]["DATA"]; ?></td>
                                <?php }} ?>

                                <td><a href="<?php echo $_foreach["DATA"]["URL_IMAGE"]; ?>"><?php echo $_foreach["DATA"]["IMAGE"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_PERSON_NAME_FIRST"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_PERSON_NAME_LAST"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_PERSON_NUMBER"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_CONFIRM_PAYMENT_COLLATERAL"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_CONFIRM_PAYMENT_COST"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_CARD_TYPE_NAME"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_SCHOOL_NAME"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_PERSON_STRU_UNIT"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_PERSON_STRU_UNIT2"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_APPL_TYPE_NAME"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_STATE_NAME"]; ?></a></td>
                                <td><?php if(isset($_foreach["DATA"]["BIND"]) && is_array($_foreach["DATA"]["BIND"])){ foreach($_foreach["DATA"]["BIND"] as $_foreach["DATA.BIND"]){ ?>
<a href="<?php echo $_foreach["DATA.BIND"]["URL"]; ?>" target="_blank"><?php echo $_foreach["DATA.BIND"]["NAME"]; ?></a><br /><?php }} ?>
</td>
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

                            <?php if(isset($data["CHECK_ALL"]) && is_array($data["CHECK_ALL"])){ foreach($data["CHECK_ALL"] as $_foreach["CHECK_ALL"]){ ?>

                            <tr>
                                <td colspan="14"><input type="checkbox" class="checkbox" id="check_all" name="check_all" value="0" onclick="toggleAllRows('confirm')" /> <?php echo $this->getTranslate("module_isic_card|check_all"); ?></TPL:CHECK_ALL.TXT_MODULE_ISIC_CARD></td>
                            <?php echo $_foreach["CHECK_ALL"]["DUMMY"]; ?>
                            </tr>
                            <?php }} ?>

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
            <?php if(isset($data["CONFIRM_BUTTON"]) && is_array($data["CONFIRM_BUTTON"])){ foreach($data["CONFIRM_BUTTON"] as $_foreach["CONFIRM_BUTTON"]){ ?>

            <div class="wButtons tListButtons">
                <?php if(isset($_foreach["CONFIRM_BUTTON"]["ADMIN_CONFIRM"]) && is_array($_foreach["CONFIRM_BUTTON"]["ADMIN_CONFIRM"])){ foreach($_foreach["CONFIRM_BUTTON"]["ADMIN_CONFIRM"] as $_foreach["CONFIRM_BUTTON.ADMIN_CONFIRM"]){ ?>

                <input type="button" id="button_admin_confirm" value="<?php echo $_foreach["CONFIRM_BUTTON.ADMIN_CONFIRM"]["TITLE"]; ?>" onclick="javascript:assignAdminConfirm();" />
                <?php }} ?>

                <?php if(isset($_foreach["CONFIRM_BUTTON"]["CONFIRM_ADMIN_CONFIRM"]) && is_array($_foreach["CONFIRM_BUTTON"]["CONFIRM_ADMIN_CONFIRM"])){ foreach($_foreach["CONFIRM_BUTTON"]["CONFIRM_ADMIN_CONFIRM"] as $_foreach["CONFIRM_BUTTON.CONFIRM_ADMIN_CONFIRM"]){ ?>

                <input type="button" id="button_confirm_admin_confirm" value="<?php echo $_foreach["CONFIRM_BUTTON.CONFIRM_ADMIN_CONFIRM"]["TITLE"]; ?>" onclick="javascript:assignConfirmAdminConfirm();" />
                <?php }} ?>

                <?php if(isset($_foreach["CONFIRM_BUTTON"]["REJECT"]) && is_array($_foreach["CONFIRM_BUTTON"]["REJECT"])){ foreach($_foreach["CONFIRM_BUTTON"]["REJECT"] as $_foreach["CONFIRM_BUTTON.REJECT"]){ ?>

                <input type="button" id="button_reject" value="<?php echo $_foreach["CONFIRM_BUTTON.REJECT"]["TITLE"]; ?>" onclick="javascript:assignReject();" />
                <?php }} ?>

            </div>
            <?php }} ?>

            </form>
