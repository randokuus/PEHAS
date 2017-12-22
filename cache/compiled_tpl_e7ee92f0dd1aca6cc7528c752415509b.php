<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--
var distributeUrl = '';
var activateUrl = '';
var prolongUrl = '';
var returnUrl = '';

function doDistribute(url) {
    distibuteUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_distribute"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = distibuteUrl;
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

function doActivate(url) {
    activateUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_activate"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = activateUrl;
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

function doActivateCB(btn, txt) {
    if (btn == 'yes') {
        document.location = activateUrl;
    }
}

function doProlong(url) {
    prolongUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_prolong"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = prolongUrl;
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

function doReturn(url) {
    returnUrl = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_return"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = returnUrl;
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


        

        <!--col1-->
        <div class="col1">
            <!--colInner-->
            <div class="colInner">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $data["DATA_PERSON_NAME_FIRST"]; ?> <?php echo $data["DATA_PERSON_NAME_LAST"]; ?></h2>
                        </div>

                        <!--formTable-->
                        <div class="formTable">
                            <form action="" method="" name="" class="jNice">
                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|state"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_STATE_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|kind"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_KIND_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|bank"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_BANK_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|type"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_TYPE_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php if(isset($data["SCHOOL"]) && is_array($data["SCHOOL"])){ foreach($data["SCHOOL"] as $_foreach["SCHOOL"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|school"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["SCHOOL"]["DATA_SCHOOL_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php }} ?>

                            </div>
                            <!--/fRow-->
                            
                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_NAME_LAST"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_id"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_NUMBER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_birthday"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_BIRTHDAY"]; ?>
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
                                        <?php echo $this->getTranslate("module_isic_card|delivery_addr1"); ?>:
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
                                        <?php echo $this->getTranslate("module_isic_card|delivery_addr2"); ?>:
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
                                        <?php echo $this->getTranslate("module_isic_card|delivery_addr3"); ?>:
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
                                        <?php echo $this->getTranslate("module_isic_card|delivery_addr4"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_ADDR4"]; ?>
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
                                        <?php echo $this->getTranslate("module_isic_card|person_email"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_EMAIL"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_phone"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_PHONE"]; ?>
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
                                        <?php echo $this->getTranslate("module_isic_card|activation_date"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ACTIVATION_DATE"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|expiration_date"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_EXPIRATION_DATE"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|isic_number"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ISIC_NUMBER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|card_number"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_CARD_NUMBER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                            </div>
                            <!--/fRow-->
                                
                            <?php if(isset($data["DEACTIVATION"]) && is_array($data["DEACTIVATION"])){ foreach($data["DEACTIVATION"] as $_foreach["DEACTIVATION"]){ ?>

                            <!--fRow-->
                            <div class="fRow">
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|deactivation_reason"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATION"]["DATA_REASON"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|deactivation_user"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATION"]["DATA_USER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|deactivation_date"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATION"]["DATA_DATE"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->
                            <?php }} ?>

                            <!--wButtons-->
                            <div class="wButtons">
                                <?php if(isset($data["BACK"]) && is_array($data["BACK"])){ foreach($data["BACK"] as $_foreach["BACK"]){ ?>
<input type="button" class="grayButton" onClick="document.location='<?php echo $_foreach["BACK"]["URL_BACK"]; ?>'" value="<?php echo $_foreach["BACK"]["BACK"]; ?>" /><?php }} ?>

                                <?php if(isset($data["MODIFY"]) && is_array($data["MODIFY"])){ foreach($data["MODIFY"] as $_foreach["MODIFY"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["MODIFY"]["URL"]; ?>'" value="<?php echo $_foreach["MODIFY"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["DISTRIBUTE"]) && is_array($data["DISTRIBUTE"])){ foreach($data["DISTRIBUTE"] as $_foreach["DISTRIBUTE"]){ ?>
<input type="button" onClick="javascript:doDistribute('<?php echo $_foreach["DISTRIBUTE"]["URL"]; ?>');" value="<?php echo $_foreach["DISTRIBUTE"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["ACTIVATE"]) && is_array($data["ACTIVATE"])){ foreach($data["ACTIVATE"] as $_foreach["ACTIVATE"]){ ?>
<input type="button" onClick="javascript:doActivate('<?php echo $_foreach["ACTIVATE"]["URL"]; ?>');" value="<?php echo $_foreach["ACTIVATE"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["DEACTIVATE"]) && is_array($data["DEACTIVATE"])){ foreach($data["DEACTIVATE"] as $_foreach["DEACTIVATE"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["DEACTIVATE"]["URL"]; ?>'" value="<?php echo $_foreach["DEACTIVATE"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["RETURN"]) && is_array($data["RETURN"])){ foreach($data["RETURN"] as $_foreach["RETURN"]){ ?>
<input type="button" onClick="javascript:doReturn('<?php echo $_foreach["RETURN"]["URL"]; ?>');" value="<?php echo $_foreach["RETURN"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["REPLACE"]) && is_array($data["REPLACE"])){ foreach($data["REPLACE"] as $_foreach["REPLACE"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["REPLACE"]["URL"]; ?>'" value="<?php echo $_foreach["REPLACE"]["TITLE"]; ?>" /><?php }} ?>

                                <?php if(isset($data["PROLONG"]) && is_array($data["PROLONG"])){ foreach($data["PROLONG"] as $_foreach["PROLONG"]){ ?>
<input type="button" onClick="javascript:doProlong('<?php echo $_foreach["PROLONG"]["URL"]; ?>');" value="<?php echo $_foreach["PROLONG"]["TITLE"]; ?>" /><?php }} ?>

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
                        <h2><?php echo $this->getTranslate("module_isic_user|card_validities"); ?></h2>
                    </div>

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList">
                            <tr>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|school"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|structure_unit"); ?></span>
                                </th>
                                <!--<th>-->
                                    <!--<span class=""><?php echo $this->getTranslate("module_user_status|class"); ?></span>-->
                                <!--</th>-->
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|course"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_user_status|position"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_isic_card_validities|active"); ?></span>
                                </th>
                            </tr>
                            
                            <?php if(isset($data["VALIDITY"]) && is_array($data["VALIDITY"])){ foreach($data["VALIDITY"] as $_foreach["VALIDITY"]){ ?>

                            <tr>
                                <td><?php echo $_foreach["VALIDITY"]["SCHOOL"]; ?></td>
                                <td><?php echo $_foreach["VALIDITY"]["STRUCTURE_UNIT"]; ?></td>
                                <!--<td><?php echo $_foreach["VALIDITY"]["CLASS"]; ?></td>-->
                                <td><?php echo $_foreach["VALIDITY"]["COURSE"]; ?></td>
                                <td><?php echo $_foreach["VALIDITY"]["POSITION"]; ?></td>
                                <td><?php echo $_foreach["VALIDITY"]["ACTIVE"]; ?></td>
                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                </div>
            </div>
            <!--/box-->                
        </div>
