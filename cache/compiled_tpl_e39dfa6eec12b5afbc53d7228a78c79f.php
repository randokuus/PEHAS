<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--

var extCheckUrl = '';
var extUnCheckUrl = '';
var dataCheckDisallowUrl = '';
var ehlCheckUrl = '';
var ehlUnCheckUrl = '';

function doExtCheck(url) {
    extCheckUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_user|confirm_allow_external_check"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = extCheckUrl;
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

function doExtCheckCB(btn, txt) {
    if (btn == 'yes') {
        document.location = extCheckUrl;
    }
}

function doExtUnCheck(url) {
    extUnCheckUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_user|confirm_disallow_external_check"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: popupDynamicWidth(),
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = extUnCheckUrl;
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

function doDataSyncDisallow(url) {
    dataCheckDisallowUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_user|confirm_data_sync_disallow"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: popupDynamicWidth(),
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = dataCheckDisallowUrl;
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

function doEhlCheck(url) {
    ehlCheckUrl = url;
    var $dialog = $('<div></div>')
            .html('<?php echo $this->getTranslate("module_isic_user|confirm_allow_ehl_check"); ?>')
            .dialog({
                autoOpen: false,
                title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
                resizable: false,
                modal: true,
                width: 450,
                buttons: {
                    "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                        $(this).dialog("close");
                        document.location = ehlCheckUrl;
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

function doEhlCheckCB(btn, txt) {
    if (btn == 'yes') {
        document.location = ehlCheckUrl;
    }
}

function doEhlUnCheck(url) {
    ehlUnCheckUrl = url;
    var $dialog = $('<div></div>')
            .html('<?php echo $this->getTranslate("module_isic_user|confirm_disallow_ehl_check"); ?>')
            .dialog({
                autoOpen: false,
                title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
                resizable: false,
                modal: true,
                width: 450,
                buttons: {
                    "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                        $(this).dialog("close");
                        document.location = ehlUnCheckUrl;
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

jQuery(document).ready(function() {
    jQuery('div#newsletterList div.newsletter input').attr('disabled', 'disabled');
    if (jQuery('div#newsletterList div.newsletter').length == 0) {
        jQuery('#newsletter_block').css('display', 'none');
        addClassToEven('div.formTable','div.fRow:visible');
    }
    jQuery('.special_offer').attr('disabled', 'disabled');
});

//-->
</script>
        <?php if(isset($data["IMESSAGE"]) && is_array($data["IMESSAGE"])){ foreach($data["IMESSAGE"] as $_foreach["IMESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgOk">
                <span><?php echo $_foreach["IMESSAGE"]["IMESSAGE"]; ?></span>
            </p>
        </div>
        <!--/msgWrap-->
        <?php }} ?>


        <!--col1-->
        <div class="col1">
            <!--colInner-->
            <div class="colInner">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $data["DATA_NAME_FIRST"]; ?> <?php echo $data["DATA_NAME_LAST"]; ?></h2>
                        </div>
                        
                        <!--formTable-->
                        <div class="formTable">
                            <form action="" method="" name="" class="jNice">
                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|name_first"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|name_last"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_NAME_LAST"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|user_code"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_USER_CODE"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|birthday"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_BIRTHDAY"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <!--fRow-->
                            <div class="fRow">

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|delivery_addr1"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_ADDR1"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|delivery_addr2"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_ADDR2"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|delivery_addr3"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_ADDR3"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->

                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|delivery_addr4"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_ADDR4"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|email"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_EMAIL"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->

                                <!--fLine-->
                                <div class="fLine">

                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|phone"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PHONE"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|special_offers"); ?>:
                                    </div>

                                    <div class="fCell">
                                        <?php echo $data["DATA_SPECIAL_OFFERS"]; ?>
                                    </div>
                                    <div class="fHint">
                                        <?php echo $this->getTranslate("module_isic_user|special_offers_help"); ?>
                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <div id="newsletter_block">
                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|newsletter"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <div id='newsletterList'>
                                            <?php if(isset($data["DATA_NEWSLETTERS"]) && is_array($data["DATA_NEWSLETTERS"])){ foreach($data["DATA_NEWSLETTERS"] as $_foreach["DATA_NEWSLETTERS"]){ ?>

                                                <div class="newsletter">
                                                    <?php echo $_foreach["DATA_NEWSLETTERS"]["FIELD_NEWSLETTER"]; ?>
                                                </div>
                                            <?php }} ?>

                                        </div>
                                    </div>
                                    <div class="fHint">
                                        <?php echo $this->getTranslate("module_isic_user|newsletter_help"); ?>
                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->
                            </div>

                            <?php if(isset($data["APPL_CONFIRMATION_MAILS"]) && is_array($data["APPL_CONFIRMATION_MAILS"])){ foreach($data["APPL_CONFIRMATION_MAILS"] as $_foreach["APPL_CONFIRMATION_MAILS"]){ ?>

                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|appl_confirmation_mails"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["APPL_CONFIRMATION_MAILS"]["TEXT"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->
                            <?php }} ?>


                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|data_sync_allowed"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DATA_SYNC_ALLOWED"]; ?>
                                    </div>
                                    <div class="fHint">
                                        <?php if(isset($data["DATA_SYNC_DISALLOW"]) && is_array($data["DATA_SYNC_DISALLOW"])){ foreach($data["DATA_SYNC_DISALLOW"] as $_foreach["DATA_SYNC_DISALLOW"]){ ?>
<input type="button" onClick="javascript:doDataSyncDisallow('<?php echo $_foreach["DATA_SYNC_DISALLOW"]["URL"]; ?>');" value="<?php echo $_foreach["DATA_SYNC_DISALLOW"]["TITLE"]; ?>" /><?php }} ?>

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <!--wButtons-->
                            <div class="wButtons">
                                <?php if(isset($data["BACK"]) && is_array($data["BACK"])){ foreach($data["BACK"] as $_foreach["BACK"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["BACK"]["URL"]; ?>'" value="<?php echo $_foreach["BACK"]["TITLE"]; ?>"  class="grayButton" /><?php }} ?>

                                <?php if(isset($data["MODIFY"]) && is_array($data["MODIFY"])){ foreach($data["MODIFY"] as $_foreach["MODIFY"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["MODIFY"]["URL_MODIFY"]; ?>'" value="<?php echo $_foreach["MODIFY"]["MODIFY"]; ?>" /><?php }} ?>

                            </div>
                            <!--/wButtons-->
                        </form>
                        </div>
                        <!--/formTable-->
                    </div>
                </div>
                <!--/box-->
            </div>
            <!--/colInner-->
        </div>
        <!--/col1-->

        <!--col2-->
        <div class="col2">
            <!--colInner-->
            <div class="colInner">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2>&nbsp;</h2>
                        </div>
                        <!--studentPhoto-->
                        <div class="studentPhoto">
                            <div class="studentPhotoInner">
                                <img src="<?php echo $data["DATA_PIC"]; ?>" alt="" border="0" />
                            </div>
                        </div>
                        <!--/studentPhoto-->
                    </div>
                </div>
                <!--/box-->
            </div>
            <!--/colInner-->
        </div>
        <!--/col2-->

        <div class="wideCol">
            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <div class="control">
                        </div>
                        <h2><?php echo $this->getTranslate("module_isic_user|user_statuses"); ?></h2>
                    </div>

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList">
                            <tr>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_isic_user|active"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|school"); ?></span>
                                </th>

                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|status"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|structure_unit"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|faculty"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|position"); ?></span>
                                </th>
                                <th>
                                    &ensp;
                                </th>
                            </tr>
                            
                            <?php if(isset($data["STATUS"]) && is_array($data["STATUS"])){ foreach($data["STATUS"] as $_foreach["STATUS"]){ ?>

                            <tr>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_ACTIVE"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_SCHOOL"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_STATUS"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_STRUCTURE_UNIT"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_FACULTY"]; ?></a></td>
                                <td><a href="<?php echo $_foreach["STATUS"]["URL_DETAIL"]; ?>"><?php echo $_foreach["STATUS"]["DATA_POSITION"]; ?></a></td>
                                <td>
                                    <div class="wicons">
                                        <?php if(isset($_foreach["STATUS"]["MOD"]) && is_array($_foreach["STATUS"]["MOD"])){ foreach($_foreach["STATUS"]["MOD"] as $_foreach["STATUS.MOD"]){ ?>
<a href="<?php echo $_foreach["STATUS.MOD"]["URL_MODIFY"]; ?> "title="Edit" class="ico iedit">Edit</a><?php }} ?>

                                        <?php if(isset($_foreach["STATUS"]["DEL"]) && is_array($_foreach["STATUS"]["DEL"])){ foreach($_foreach["STATUS"]["DEL"] as $_foreach["STATUS.DEL"]){ ?>
<br /><a href="<?php echo $_foreach["STATUS.DEL"]["URL_DELETE"]; ?>" title="Delete" class="ico idel">Delete</a><?php }} ?>

                                    </div>
                                </td>
                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                    <!--pagin-->
                    <div class="pagin">
                        <div>
                            &nbsp;
                        </div>
                    </div>
                    <!--/pagin-->
                    
                    <?php if(isset($data["ADD_STATUS"]) && is_array($data["ADD_STATUS"])){ foreach($data["ADD_STATUS"] as $_foreach["ADD_STATUS"]){ ?>

                    <form action="" method="" name="" class="jNice">
                        <!--wButtons-->
                        <div class="tListSubmit">
                            <input type="button" onClick="document.location='<?php echo $_foreach["ADD_STATUS"]["URL"]; ?>'" value="<?php echo $_foreach["ADD_STATUS"]["TITLE"]; ?>" />
                        </div>
                        <!--/wButtons-->
                    </form>
                    <?php }} ?>
                        
                </div>
            </div>
            <!--/box-->
        </div>
