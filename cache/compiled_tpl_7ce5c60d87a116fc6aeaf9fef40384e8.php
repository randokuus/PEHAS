<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--
var deactivate_url = '';

function assignDeactivate() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_user|confirm_status_deactivate"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_user|question_status_deactivate"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = '<?php if(isset($data["DEACTIVATE"]) && is_array($data["DEACTIVATE"])){ foreach($data["DEACTIVATE"] as $_foreach["DEACTIVATE"]){ ?>
<?php echo $_foreach["DEACTIVATE"]["URL"]; ?><?php }} ?>
';
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
                                        <?php echo $this->getTranslate("module_isic_user|active"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ACTIVE"]; ?>
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

                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_user|group"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_GROUP_NAME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|structure_unit"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_STRUCTURE_UNIT"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|faculty"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_FACULTY"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|position"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_POSITION"]; ?>
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
                                        <?php echo $this->getTranslate("module_user_status|status_addtime"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ADDTIME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|status_addtype"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ADDTYPE"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|status_adduser"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_ADDUSER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php if(isset($data["DEACTIVATED"]) && is_array($data["DEACTIVATED"])){ foreach($data["DEACTIVATED"] as $_foreach["DEACTIVATED"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|status_modtime"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATED"]["DATA_MODTIME"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|status_modtype"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATED"]["DATA_MODTYPE"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_user_status|status_moduser"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["DEACTIVATED"]["DATA_MODUSER"]; ?>
                                    </div>
                                    <div class="fHint">
                                        
                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php }} ?>

                            </div>
                            <!--/fRow-->

                            <!--wButtons-->
                            <div class="wButtons">
                                <input type="button" class="grayButton" onClick="document.location='<?php echo $data["URL_BACK"]; ?>'" value="<?php echo $data["BACK"]; ?>" />
                                <?php if(isset($data["DEACTIVATE"]) && is_array($data["DEACTIVATE"])){ foreach($data["DEACTIVATE"] as $_foreach["DEACTIVATE"]){ ?>
<input id="deactivateButton" type="button" onClick="javascript:assignDeactivate();" value="<?php echo $this->getTranslate("module_isic_user|action_deactivate"); ?>" /><?php }} ?>

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