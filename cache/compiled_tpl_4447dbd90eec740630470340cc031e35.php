<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript" charset="utf-8">
function assignConfirmAdmin() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_admin_msg"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.vorm.action.value = 'confirm_admin';
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

function assignConfirmAdminConfirm() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_admin_confirm_msg"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.vorm.action.value = 'confirm_admin';
                document.vorm.confirm_admin_confirm.value = 'true';
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

function assignReject() {
    document.vorm.action.value = 'reject';
    document.vorm.write.value = 'false';
    document.vorm.submit();
}

function assignPayment(paymentType) {
    document.vorm.action.value = 'payment';
    document.vorm.payment_type.value = paymentType;
    document.vorm.write.value = 'false';
    document.vorm.submit();
}

function assignConfirmPayment(paymentType, confirmMessage) {
    var html = confirmMessage + '<br/><br/>';
    html += '<div class="formTable completeTable">';
    html += '<form method="" action="" name="" enctype="" class="jNice">';
    html += '<div class="fRow">';
    html += '<div class="fLine">';
    html += '<div class="fHead">';
    html += '<?php echo $this->getTranslate("module_isic_card|actual_payment_date"); ?>:';
    html += '</div>';
    html += '<div class="fCell">'
    html += '<input type="text" size="10" name="t_date" class="datePicker" />';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</form';
    html += '</div>';
    var $dialog = $('<div></div>')
    .html(html)
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                $(this).find('.datePicker').datepicker('destroy');
                document.vorm.action.value = paymentType;
//                document.vorm.actual_payment_date.value = $(this).find('.datePicker').val();
                document.vorm.submit();
            },
            "<?php echo $this->getTranslate("module_isic_card|cancel"); ?>": function() {
                $(this).dialog("close");
            }
        }
    });
    $dialog.find('.datePicker').change(function() {
        if (this.value != '' && validDate(this.value) == true) {
            jQuery('.ui-dialog-buttonset').find('button:first').removeAttr('disabled');
            document.vorm.actual_payment_date.value = this.value;
        }
        else {
            jQuery('.ui-dialog-buttonset').find('button:first').attr('disabled', 'disabled');
        }
    });
    $dialog.find('.datePicker').datepicker({dateFormat: 'dd.mm.yy', maxDate: '+0d'}).click(function(){
        $(this).datepicker('show');
    });
    $dialog.find('.datePicker').hide();
    $('div.ui-dialog').append('<i class="ll"><i></i></i><i class="rr"><i></i></i><i class="tt"><i></i></i><i class="bb"><i></i></i><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');
    $('div.ui-dialog div.ui-dialog-buttonpane span.ui-button-text').wrap('<span></span>');
    $dialog.dialog('open');
    $('form.jNice input[type=text]').addClass('jNiceInput').wrap('<div class="jNiceInputWrapper"><div class="jNiceInputInner"/></div>');
    $('form.jNice .jNiceInputWrapper:has(input.datePicker)').addClass('datePicker');
    $dialog.find('.datePicker').show();
    $('.ui-dialog-buttonset').find('button:first').attr('disabled', 'disabled');
    $('#ui-datepicker-div').hide();
}

function validDate(s) {
    var m = /(\d{1,2})[-\.](\d{1,2})[-\.](\d{4})/.exec(s);
    if (!m) {
        return false;
    }
    m[2] = m[2] - 1;
    var d = new Date(m[3],m[2],m[1]);
    if ((m[3] == d.getFullYear()) && (m[2] == d.getMonth()) && (m[1] == d.getDate())) {
        return true;
    }
    return false;
}

function assignConfirmUseDeposit() {
    var $dialog = $('<div></div>')
    .html('<?php echo $this->getTranslate("module_isic_card|confirm_use_deposit"); ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.vorm.action.value = 'deposit';
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
    if (jQuery('div#newsletterList div.newsletter').length == 0) {
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
                            <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" class="jNice">
                            <input type="hidden" name="actual_payment_date" value=""/>
                            <input type="hidden" name="payment_type" value=""/>
                            <?php echo $data["HIDDEN"]; ?>

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
                                        <?php echo $this->getTranslate("module_isic_card|type"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_TYPE_NAME"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|application_type"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_APPL_TYPE_NAME"]; ?>
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

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|delivery"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_DELIVERY_NAME"]; ?>
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
                                        <?php echo $this->getTranslate("module_isic_card|person_position"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_POSITION"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_structure_unit"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_STRU_UNIT"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|person_structure_unit2"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $data["DATA_PERSON_STRU_UNIT2"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <!--fRow-->
                            <!--<div class="fRow">-->
                                <!--&lt;!&ndash;fLine&ndash;&gt;-->
                                <!--<div class="fLine">-->
                                    <!--<div class="fHead">-->
                                        <!--<?php echo $this->getTranslate("module_isic_card|person_special_offers_admin"); ?>:-->
                                    <!--</div>-->
                                    <!--<div class="fCell">-->
                                        <!--<?php echo $data["DATA_PERSON_NEWSLETTER"]; ?>-->
                                    <!--</div>-->
                                    <!--<div class="fHint">-->

                                    <!--</div>-->
                                <!--</div>-->
                                <!--&lt;!&ndash;/fLine&ndash;&gt;-->
                            <!--</div>-->
                            <!--/fRow-->

                            <!--fRow-->
                            <div class="fRow">
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
                            </div>
                            <!--/fRow-->

                            <?php if(isset($data["PAYMENT"]) && is_array($data["PAYMENT"])){ foreach($data["PAYMENT"] as $_foreach["PAYMENT"]){ ?>

                            <!--fRow-->
                            <div class="fRow">

                                <?php if(isset($_foreach["PAYMENT"]["COLL"]) && is_array($_foreach["PAYMENT"]["COLL"])){ foreach($_foreach["PAYMENT"]["COLL"] as $_foreach["PAYMENT.COLL"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|collateral_paid"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["PAYMENT.COLL"]["DATA_CONFIRM_PAYMENT_COLLATERAL"]; ?><?php if(isset($_foreach["PAYMENT.COLL"]["SUM"]) && is_array($_foreach["PAYMENT.COLL"]["SUM"])){ foreach($_foreach["PAYMENT.COLL"]["SUM"] as $_foreach["PAYMENT.COLL.SUM"]){ ?>
, <?php echo $_foreach["PAYMENT.COLL.SUM"]["SUM"]; ?> <?php echo $_foreach["PAYMENT.COLL.SUM"]["CURRENCY"]; ?><?php }} ?>

                                        <?php if(isset($_foreach["PAYMENT.COLL"]["ACTUAL_PAYMENT_DATE"]) && is_array($_foreach["PAYMENT.COLL"]["ACTUAL_PAYMENT_DATE"])){ foreach($_foreach["PAYMENT.COLL"]["ACTUAL_PAYMENT_DATE"] as $_foreach["PAYMENT.COLL.ACTUAL_PAYMENT_DATE"]){ ?>
, <?php echo $_foreach["PAYMENT.COLL.ACTUAL_PAYMENT_DATE"]["DATA"]; ?><?php }} ?>

                                    </div>
                                    <div class="fHint">
                                        <?php if(isset($_foreach["PAYMENT.COLL"]["CONF_PAY_COLL"]) && is_array($_foreach["PAYMENT.COLL"]["CONF_PAY_COLL"])){ foreach($_foreach["PAYMENT.COLL"]["CONF_PAY_COLL"] as $_foreach["PAYMENT.COLL.CONF_PAY_COLL"]){ ?>

                                        <input type="button" id="confirm_payment_collateral_button" value="<?php echo $_foreach["PAYMENT.COLL.CONF_PAY_COLL"]["BUTTON"]; ?>" onclick="javascript:assignPayment('collateral');" />
                                        <?php }} ?>

                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($_foreach["PAYMENT"]["DEPOSIT"]) && is_array($_foreach["PAYMENT"]["DEPOSIT"])){ foreach($_foreach["PAYMENT"]["DEPOSIT"] as $_foreach["PAYMENT.DEPOSIT"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|deposit_amount"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["PAYMENT.DEPOSIT"]["AMOUNT"]; ?>
                                    </div>
                                    <div class="fHint">
                                        <?php if(isset($_foreach["PAYMENT.DEPOSIT"]["CONF_USE_DEPOSIT"]) && is_array($_foreach["PAYMENT.DEPOSIT"]["CONF_USE_DEPOSIT"])){ foreach($_foreach["PAYMENT.DEPOSIT"]["CONF_USE_DEPOSIT"] as $_foreach["PAYMENT.DEPOSIT.CONF_USE_DEPOSIT"]){ ?>

                                        <input type="button" id="confirm_payment_deposit_button" value="<?php echo $_foreach["PAYMENT.DEPOSIT.CONF_USE_DEPOSIT"]["BUTTON"]; ?>" onclick="javascript:assignConfirmUseDeposit();" />
                                        <?php }} ?>

                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($_foreach["PAYMENT"]["COST"]) && is_array($_foreach["PAYMENT"]["COST"])){ foreach($_foreach["PAYMENT"]["COST"] as $_foreach["PAYMENT.COST"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|cost_paid"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["PAYMENT.COST"]["DATA_CONFIRM_PAYMENT_COST"]; ?><?php if(isset($_foreach["PAYMENT.COST"]["SUM"]) && is_array($_foreach["PAYMENT.COST"]["SUM"])){ foreach($_foreach["PAYMENT.COST"]["SUM"] as $_foreach["PAYMENT.COST.SUM"]){ ?>
, <?php echo $_foreach["PAYMENT.COST.SUM"]["SUM"]; ?> <?php echo $_foreach["PAYMENT.COST.SUM"]["CURRENCY"]; ?><?php }} ?>

                                        <?php if(isset($_foreach["PAYMENT.COST"]["ACTUAL_PAYMENT_DATE"]) && is_array($_foreach["PAYMENT.COST"]["ACTUAL_PAYMENT_DATE"])){ foreach($_foreach["PAYMENT.COST"]["ACTUAL_PAYMENT_DATE"] as $_foreach["PAYMENT.COST.ACTUAL_PAYMENT_DATE"]){ ?>
, <?php echo $_foreach["PAYMENT.COST.ACTUAL_PAYMENT_DATE"]["DATA"]; ?><?php }} ?>

                                    </div>
                                    <div class="fHint">
                                        <?php if(isset($_foreach["PAYMENT.COST"]["CONF_PAY_COST"]) && is_array($_foreach["PAYMENT.COST"]["CONF_PAY_COST"])){ foreach($_foreach["PAYMENT.COST"]["CONF_PAY_COST"] as $_foreach["PAYMENT.COST.CONF_PAY_COST"]){ ?>

                                            <input type="button" id="confirm_payment_cost_button" value="<?php echo $_foreach["PAYMENT.COST.CONF_PAY_COST"]["BUTTON"]; ?>" onclick="javascript:assignPayment('cost');" />
                                        <?php }} ?>

                                    </div>
                                </div>
                                <!--/fLine-->
                               <?php }} ?>


                                <?php if(isset($_foreach["PAYMENT"]["DELIVERY"]) && is_array($_foreach["PAYMENT"]["DELIVERY"])){ foreach($_foreach["PAYMENT"]["DELIVERY"] as $_foreach["PAYMENT.DELIVERY"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_isic_card|delivery_paid"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["PAYMENT.DELIVERY"]["DATA_CONFIRM_PAYMENT_DELIVERY"]; ?><?php if(isset($_foreach["PAYMENT.DELIVERY"]["SUM"]) && is_array($_foreach["PAYMENT.DELIVERY"]["SUM"])){ foreach($_foreach["PAYMENT.DELIVERY"]["SUM"] as $_foreach["PAYMENT.DELIVERY.SUM"]){ ?>
, <?php echo $_foreach["PAYMENT.DELIVERY.SUM"]["SUM"]; ?> <?php echo $_foreach["PAYMENT.DELIVERY.SUM"]["CURRENCY"]; ?><?php }} ?>

                                        <?php if(isset($_foreach["PAYMENT.DELIVERY"]["ACTUAL_PAYMENT_DATE"]) && is_array($_foreach["PAYMENT.DELIVERY"]["ACTUAL_PAYMENT_DATE"])){ foreach($_foreach["PAYMENT.DELIVERY"]["ACTUAL_PAYMENT_DATE"] as $_foreach["PAYMENT.DELIVERY.ACTUAL_PAYMENT_DATE"]){ ?>
, <?php echo $_foreach["PAYMENT.DELIVERY.ACTUAL_PAYMENT_DATE"]["DATA"]; ?><?php }} ?>

                                    </div>
                                    <div class="fHint">
                                        <?php if(isset($_foreach["PAYMENT.DELIVERY"]["CONF_PAY_DELIVERY"]) && is_array($_foreach["PAYMENT.DELIVERY"]["CONF_PAY_DELIVERY"])){ foreach($_foreach["PAYMENT.DELIVERY"]["CONF_PAY_DELIVERY"] as $_foreach["PAYMENT.DELIVERY.CONF_PAY_DELIVERY"]){ ?>

                                            <input type="button" id="confirm_payment_delivery_button" value="<?php echo $_foreach["PAYMENT.DELIVERY.CONF_PAY_DELIVERY"]["BUTTON"]; ?>" onclick="javascript:assignPayment('delivery');" />
                                        <?php }} ?>

                                    </div>
                                </div>
                                <!--/fLine-->
                               <?php }} ?>

                            </div>
                            <!--/fRow-->
                            <?php }} ?>


                            <!--wButtons-->
                            <div class="wButtons">
                                <?php if(isset($data["BACK"]) && is_array($data["BACK"])){ foreach($data["BACK"] as $_foreach["BACK"]){ ?>
<input type="button" class="grayButton" onClick="document.location='<?php echo $_foreach["BACK"]["URL_BACK"]; ?>'" value="<?php echo $_foreach["BACK"]["BACK"]; ?>" /><?php }} ?>

                                <?php if(isset($data["MODIFY"]) && is_array($data["MODIFY"])){ foreach($data["MODIFY"] as $_foreach["MODIFY"]){ ?>
<input type="button" onClick="document.location='<?php echo $_foreach["MODIFY"]["URL_MODIFY"]; ?>'" value="<?php echo $_foreach["MODIFY"]["MODIFY"]; ?>" /><?php }} ?>

                                <?php if(isset($data["CONFIRM_ADMIN"]) && is_array($data["CONFIRM_ADMIN"])){ foreach($data["CONFIRM_ADMIN"] as $_foreach["CONFIRM_ADMIN"]){ ?>

                                <input type="button" id="confirm_admin_button" value="<?php echo $_foreach["CONFIRM_ADMIN"]["BUTTON"]; ?>" onclick="javascript:assignConfirmAdmin();" />
                                <?php }} ?>

                                <?php if(isset($data["CONFIRM_ADMIN_CONFIRM"]) && is_array($data["CONFIRM_ADMIN_CONFIRM"])){ foreach($data["CONFIRM_ADMIN_CONFIRM"] as $_foreach["CONFIRM_ADMIN_CONFIRM"]){ ?>

                                <input type="button" id="confirm_admin_confirm_button" value="<?php echo $_foreach["CONFIRM_ADMIN_CONFIRM"]["BUTTON"]; ?>" onclick="javascript:assignConfirmAdminConfirm();" />
                                <?php }} ?>

                                <?php if(isset($data["REJECT"]) && is_array($data["REJECT"])){ foreach($data["REJECT"] as $_foreach["REJECT"]){ ?>

                                <input type="button" id="reject_button" value="<?php echo $_foreach["REJECT"]["BUTTON"]; ?>" onclick="javascript:assignReject();" />
                                <?php }} ?>

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
