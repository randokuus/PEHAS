<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript" charset="utf-8">

<?php if(isset($data["EDIT_PIC_JS"]) && is_array($data["EDIT_PIC_JS"])){ foreach($data["EDIT_PIC_JS"] as $_foreach["EDIT_PIC_JS"]){ ?>

//setup the callback function
function onEndCrop( coords ) {
    jQuery('#x1').val(coords.x);
    jQuery('#y1').val(coords.y);
    jQuery('#x2').val(coords.x2);
    jQuery('#y2').val(coords.y2);
    jQuery('#width').val(coords.w);
    jQuery('#height').val(coords.h);
}

jQuery(document).ready(function() {
    jQuery('#cropImage').Jcrop({
        minSize: [<?php echo $_foreach["EDIT_PIC_JS"]["MIN_WIDTH"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["MIN_HEIGHT"]; ?>],
        onChange: onEndCrop,
        onSelect: onEndCrop,
        aspectRatio: <?php echo $_foreach["EDIT_PIC_JS"]["ASPECT_RATIO"]; ?>,
//      setSelect: [<?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_WIDTH"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_HEIGHT"]; ?>],
        setSelect: [<?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["X2"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y2"]; ?>],
        allowSelect : false
    });
});
<?php }} ?>



// if bank account owner name field is empty and person's name is filled 
// then using person name for bank-account name as well
function generateBankAccountName() {
    var p_name_first = document.vorm.elements['person_name_first'];
    var p_name_last = document.vorm.elements['person_name_last'];
    var p_bacc_name = document.vorm.elements['person_bankaccount_name'];
    if ((p_name_first.value != '' || p_name_last.value != '') && p_bacc_name.value == '') {
        p_bacc_name.value = p_name_first.value + ' ' + p_name_last.value;
    }
}

function doSubmit() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_save_msg"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.vorm.submit();
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
    if (jQuery('div#newsletterList div.newsletter input:visible').length == 0) {
        jQuery('#newsletter_block').css('display', 'none');
        addClassToEven('div.formTable','div.fRow:visible');
    }
});

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

     
     

    <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
        <input type="hidden" name="x1" id="x1" />
        <input type="hidden" name="y1" id="y1" />
        <input type="hidden" name="x2" id="x2" />
        <input type="hidden" name="y2" id="y2" />
        <input type="hidden" name="width" id="width" />
        <input type="hidden" name="height" id="height" />
        <?php echo $data["HIDDEN"]; ?>
        
        <!--col1-->
        <div class="col1">
            <!--colInner-->
            <div class="colInner">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $this->getTranslate("module_isic_card|change_application"); ?></h2>
                        </div>

                        <!--formTable-->
                        <div class="formTable">

                                <!--fRow-->
                                <div class="fRow">
                                <?php if(isset($data["STATE_ID"]) && is_array($data["STATE_ID"])){ foreach($data["STATE_ID"] as $_foreach["STATE_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["STATE_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|state"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["STATE_ID"]["FIELD_STATE_ID"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["SCHOOL_ID"]) && is_array($data["SCHOOL_ID"])){ foreach($data["SCHOOL_ID"] as $_foreach["SCHOOL_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["SCHOOL_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|school"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["SCHOOL_ID"]["FIELD_SCHOOL_ID"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["TYPE_ID"]) && is_array($data["TYPE_ID"])){ foreach($data["TYPE_ID"] as $_foreach["TYPE_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["TYPE_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|type"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["TYPE_ID"]["FIELD_TYPE_ID"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["APPLICATION_TYPE_ID"]) && is_array($data["APPLICATION_TYPE_ID"])){ foreach($data["APPLICATION_TYPE_ID"] as $_foreach["APPLICATION_TYPE_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["APPLICATION_TYPE_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|application_type"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["APPLICATION_TYPE_ID"]["FIELD_APPLICATION_TYPE_ID"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["DELIVERY_ID"]) && is_array($data["DELIVERY_ID"])){ foreach($data["DELIVERY_ID"] as $_foreach["DELIVERY_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ID"]["FIELD_DELIVERY_ID"]; ?>
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
                                <?php if(isset($data["PERSON_NAME_FIRST"]) && is_array($data["PERSON_NAME_FIRST"])){ foreach($data["PERSON_NAME_FIRST"] as $_foreach["PERSON_NAME_FIRST"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_NAME_FIRST"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_NAME_FIRST"]["FIELD_PERSON_NAME_FIRST"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_NAME_LAST"]) && is_array($data["PERSON_NAME_LAST"])){ foreach($data["PERSON_NAME_LAST"] as $_foreach["PERSON_NAME_LAST"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_NAME_LAST"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_NAME_LAST"]["FIELD_PERSON_NAME_LAST"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_NUMBER"]) && is_array($data["PERSON_NUMBER"])){ foreach($data["PERSON_NUMBER"] as $_foreach["PERSON_NUMBER"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_NUMBER"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_id"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_NUMBER"]["FIELD_PERSON_NUMBER"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_BIRTHDAY"]) && is_array($data["PERSON_BIRTHDAY"])){ foreach($data["PERSON_BIRTHDAY"] as $_foreach["PERSON_BIRTHDAY"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_BIRTHDAY"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_birthday"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_BIRTHDAY"]["FIELD_PERSON_BIRTHDAY"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PIC"]) && is_array($data["PIC"])){ foreach($data["PIC"] as $_foreach["PIC"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PIC"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|photo"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PIC"]["FIELD_PIC"]; ?>
                                        </div>
                                        <div class="fHint">
                                            <p><span><?php echo $_foreach["PIC"]["TOOLTIP"]; ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                </div>
                                <!--/fRow-->

                                <!--fRow-->
                                <div class="fRow">
                                <?php if(isset($data["DELIVERY_ADDR1"]) && is_array($data["DELIVERY_ADDR1"])){ foreach($data["DELIVERY_ADDR1"] as $_foreach["DELIVERY_ADDR1"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR1"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery_addr1"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR1"]["FIELD_DELIVERY_ADDR1"]; ?>
                                        </div>
                                        <div class="fHint">
                                            <p><span><?php echo $_foreach["DELIVERY_ADDR1"]["TOOLTIP"]; ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["DELIVERY_ADDR2"]) && is_array($data["DELIVERY_ADDR2"])){ foreach($data["DELIVERY_ADDR2"] as $_foreach["DELIVERY_ADDR2"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR2"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery_addr2"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR2"]["FIELD_DELIVERY_ADDR2"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["DELIVERY_ADDR3"]) && is_array($data["DELIVERY_ADDR3"])){ foreach($data["DELIVERY_ADDR3"] as $_foreach["DELIVERY_ADDR3"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR3"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery_addr3"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR3"]["FIELD_DELIVERY_ADDR3"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["DELIVERY_ADDR4"]) && is_array($data["DELIVERY_ADDR4"])){ foreach($data["DELIVERY_ADDR4"] as $_foreach["DELIVERY_ADDR4"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR4"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery_addr4"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR4"]["FIELD_DELIVERY_ADDR4"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                                                
                                <?php if(isset($data["PERSON_EMAIL"]) && is_array($data["PERSON_EMAIL"])){ foreach($data["PERSON_EMAIL"] as $_foreach["PERSON_EMAIL"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_EMAIL"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_email"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_EMAIL"]["FIELD_PERSON_EMAIL"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_PHONE"]) && is_array($data["PERSON_PHONE"])){ foreach($data["PERSON_PHONE"] as $_foreach["PERSON_PHONE"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_PHONE"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_phone"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_PHONE"]["FIELD_PERSON_PHONE"]; ?>
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
                                <?php if(isset($data["PERSON_POSITION"]) && is_array($data["PERSON_POSITION"])){ foreach($data["PERSON_POSITION"] as $_foreach["PERSON_POSITION"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_POSITION"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_position"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_POSITION"]["FIELD_PERSON_POSITION"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_STRU_UNIT"]) && is_array($data["PERSON_STRU_UNIT"])){ foreach($data["PERSON_STRU_UNIT"] as $_foreach["PERSON_STRU_UNIT"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_STRU_UNIT"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_structure_unit"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_STRU_UNIT"]["FIELD_PERSON_STRU_UNIT"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["PERSON_STRU_UNIT2"]) && is_array($data["PERSON_STRU_UNIT2"])){ foreach($data["PERSON_STRU_UNIT2"] as $_foreach["PERSON_STRU_UNIT2"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PERSON_STRU_UNIT2"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|person_structure_unit2"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PERSON_STRU_UNIT2"]["FIELD_PERSON_STRU_UNIT2"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                </div>
                                <!--/fRow-->

                                <?php if(isset($data["PERSON_NEWSLETTER"]) && is_array($data["PERSON_NEWSLETTER"])){ foreach($data["PERSON_NEWSLETTER"] as $_foreach["PERSON_NEWSLETTER"]){ ?>

                                    <!--fRow-->
                                    <div class="fRow">
                                        <!--fLine-->
                                        <div class="fLine <?php echo $_foreach["PERSON_NEWSLETTER"]["REQUIRED"]; ?>">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|person_special_offers_admin"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $_foreach["PERSON_NEWSLETTER"]["FIELD_PERSON_NEWSLETTER"]; ?>
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
                                <?php if(isset($data["EXPIRATION_DATE"]) && is_array($data["EXPIRATION_DATE"])){ foreach($data["EXPIRATION_DATE"] as $_foreach["EXPIRATION_DATE"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["EXPIRATION_DATE"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|expiration_date"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["EXPIRATION_DATE"]["FIELD_EXPIRATION_DATE"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["CONF_PAY_COLL"]) && is_array($data["CONF_PAY_COLL"])){ foreach($data["CONF_PAY_COLL"] as $_foreach["CONF_PAY_COLL"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["CONF_PAY_COLL"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|collateral_paid"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["CONF_PAY_COLL"]["FIELD_CONF_PAY_COLL"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["CONF_PAY_COST"]) && is_array($data["CONF_PAY_COST"])){ foreach($data["CONF_PAY_COST"] as $_foreach["CONF_PAY_COST"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["CONF_PAY_COST"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|cost_paid"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["CONF_PAY_COST"]["FIELD_CONF_PAY_COST"]; ?>
                                        </div>
                                        <div class="fHint">
                                            
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["CONF_PAY_DELIV"]) && is_array($data["CONF_PAY_DELIV"])){ foreach($data["CONF_PAY_DELIV"] as $_foreach["CONF_PAY_DELIV"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["CONF_PAY_DELIV"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|delivery_paid"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["CONF_PAY_DELIV"]["FIELD_CONF_PAY_DELIVERY"]; ?>
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
                                    <?php if(isset($data["SUBMIT"]) && is_array($data["SUBMIT"])){ foreach($data["SUBMIT"] as $_foreach["SUBMIT"]){ ?>

                                      <input type="button" value="<?php echo $_foreach["SUBMIT"]["BUTTON"]; ?>" onclick="javascript:doSubmit();" />
                                    <?php }} ?>

                                </div>
                                <!--/wButtons-->
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
                            <?php if(isset($data["SHOW_PIC"]) && is_array($data["SHOW_PIC"])){ foreach($data["SHOW_PIC"] as $_foreach["SHOW_PIC"]){ ?>

                                <img src="<?php echo $_foreach["SHOW_PIC"]["DATA_PIC"]; ?>" alt="" border="0" id="pic" />
                            <?php }} ?>

                            <?php if(isset($data["EDIT_PIC"]) && is_array($data["EDIT_PIC"])){ foreach($data["EDIT_PIC"] as $_foreach["EDIT_PIC"]){ ?>

                            <img src="<?php echo $_foreach["EDIT_PIC"]["DATA_PIC"]; ?>" alt="" border="0" width="<?php echo $_foreach["EDIT_PIC"]["MAX_WIDTH"]; ?>" id="cropImage" />
                            <div class="wButtons  wButtonsCenter">
                                <input type="submit" value="<?php echo $_foreach["EDIT_PIC"]["BUTTON"]; ?>" class="submit" />
                            </div>
                            <?php }} ?>

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

    </form>
