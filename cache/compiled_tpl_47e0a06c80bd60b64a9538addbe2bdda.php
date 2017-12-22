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
//        setSelect: [<?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_WIDTH"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_HEIGHT"]; ?>],
        setSelect: [<?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["X2"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y2"]; ?>],
        allowSelect : false
    });
});

<?php }} ?>


jQuery(document).ready(function() {
    var initFieldsOnLoad = <?php echo $data["INIT_FIELDS_ON_LOAD"]; ?>;
    if (jQuery('#person_number').val() && initFieldsOnLoad) {
        generateBirthday();
    }
    refreshNewslettersList(false);
});

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

function pad(str, len, pad, dir) {
    if (typeof(len) == "undefined") { var len = 0; }
    if (typeof(pad) == "undefined") { var pad = ' '; }
    if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }

    if (len + 1 >= str.length) {
        switch (dir){
            case STR_PAD_LEFT:
                str = Array(len + 1 - str.length).join(pad) + str;
            break;
            case STR_PAD_BOTH:
                var right = Math.ceil((padlen = len - str.length) / 2);
                var left = padlen - right;
                str = Array(left+1).join(pad) + str + Array(right+1).join(pad);
            break;
            default:
                str = str + Array(len + 1 - str.length).join(pad);
            break;
        } // switch
    }

    return str;
}

// generating person's birthday from person number
function generateBirthday () {
    var p_code = document.vorm.elements['person_number'];
    var p_bday = document.vorm.elements['person_birthday'];
    var type_id = document.vorm.elements['type_id'].value;
    var school_id = document.vorm.elements['school_id'].value;
    var century = 0;
    var year = 0;
    var month = 0;
    var day = 0;
    var t_code = p_code ? p_code.value : '';
    var t_bday = '';

    // getting all the data from user profile via ajax-call
    do_getprofiledata(t_code, type_id, school_id);
    getUserStatusData();

    if (/*p_bday.value == '' &&*/ t_code != '') {

        // checking if this is person number
        if (t_code.length == 11) {
            century = parseInt(t_code.substring(0, 1), 10);
            year = parseInt(t_code.substring(1, 3), 10);
            month = parseInt(t_code.substring(3, 5), 10);
            day = parseInt(t_code.substring(5, 7), 10);

            if (century >= 1 && century <= 2) {
                century = 1800;
            } else if (century >= 3 && century <= 4) {
                century = 1900;
            } else if (century >= 5 && century <= 6) {
                century = 2000;
            } else {
                century = 0;
            }

            if (century > 0) {
                if (year >= 0 && year <= 99) {
                    year = century + year;
                    if (month >= 1 && month <= 12) {
                        if (day >= 1 && day <= 31) {
                            t_bday = pad(String(day), 2, '0', STR_PAD_LEFT) + '.' + pad(String(month), 2, '0', STR_PAD_LEFT) + '.' + year;
                            p_bday.value = t_bday;
                        }
                    }
                }
            }
        }
    }

}

// if bank account owner name field is empty and person's name is filled
// then using person name for bank-account name as well
function generateBankAccountName() {
    return;
    var p_name_first = document.vorm.elements['person_name_first'];
    var p_name_last = document.vorm.elements['person_name_last'];
    var p_bacc_name = document.vorm.elements['person_bankaccount_name'];
    if ((p_name_first.value != '' || p_name_last.value != '') && p_bacc_name.value == '') {
        p_bacc_name.value = p_name_first.value + ' ' + p_name_last.value;
    }
}


function do_getprofiledata_cb(z) {
    if (z == false) {
        //couldnt find person
        document.getElementById('person_pic').src = 'img/img_placeholder_big.png';
        document.vorm.elements['use_user_pic'].value = 0;
        document.vorm.elements['person_name_first'].value = '';
        document.vorm.elements['person_name_last'].value = '';
        document.vorm.elements['delivery_addr1'].value = '';
        document.vorm.elements['delivery_addr2'].value = '';
        //document.vorm.elements['delivery_addr3'].value = '';
        document.vorm.elements['delivery_addr4'].value = '';
        document.vorm.elements['person_email'].value = '';
        document.vorm.elements['person_phone'].value = '';
//        document.vorm.elements['person_bankaccount'].value = '';
//        document.vorm.elements['person_bankaccount_name'].value = '';
    } else {
        document.vorm.elements['person_name_first'].value = z.person_name_first;
        document.vorm.elements['person_name_last'].value = z.person_name_last;
        document.vorm.elements['delivery_addr1'].value = z.delivery_addr1;
        document.vorm.elements['delivery_addr2'].value = z.delivery_addr2;
        //document.vorm.elements['delivery_addr3'].value = z.delivery_addr3;
        document.vorm.elements['delivery_addr4'].value = z.delivery_addr4;
        document.vorm.elements['person_email'].value = z.person_email;
        document.vorm.elements['person_phone'].value = z.person_phone;
//        document.vorm.elements['person_bankaccount'].value = z.person_bankaccount;
//        document.vorm.elements['person_bankaccount_name'].value = z.person_bankaccount_name;

        if (z.person_pic) {
            document.getElementById('person_pic').src = z.person_pic;
            document.vorm.elements['use_user_pic'].value = 1;
        } else {
            document.getElementById('person_pic').src = 'img/img_placeholder_big.png';
            document.vorm.elements['use_user_pic'].value = 0;
        }

    }
}

function do_getprofiledata(person_number, type_id, school_id) {
    x_getprofiledata(person_number, type_id, school_id, do_getprofiledata_cb);
}

function getUserStatusData() {
    var p_user_code = document.vorm.elements['person_number'];
    var p_school_id = document.vorm.elements['school_id'];
    var p_type_id = document.vorm.elements['type_id'];
    do_get_user_status_data_by_school_cardtype(p_user_code.value, p_school_id.value, p_type_id.value);
}

function do_get_user_status_data_by_school_cardtype_cb(z) {
    if (z == false) {
        //couldnt find user status
        document.vorm.elements['person_stru_unit'].value = '';
        document.vorm.elements['person_stru_unit2'].value = '';
//        document.vorm.elements['person_class'].value = '';
        document.vorm.elements['person_position'].value = '';
    } else {
        document.vorm.elements['person_stru_unit'].value = z['structure_unit'];
        document.vorm.elements['person_stru_unit2'].value = z['faculty'];
//        document.vorm.elements['person_class'].value = z['class'];
        document.vorm.elements['person_position'].value = z['position'];
    }
}

function do_get_user_status_data_by_school_cardtype(user_code, school_id, type_id) {
    x_get_user_status_data_by_school_cardtype(user_code, school_id, type_id, do_get_user_status_data_by_school_cardtype_cb);
}

function refreshTypeList() {
    var p_school_id = document.vorm.elements['school_id'];
    do_get_card_types_by_school(p_school_id.value);
}

function refreshNewslettersList(unselect) {
    if (typeof(unselect) == 'undefined') unselect = true;
    if (unselect == true) jQuery('div#newsletterList div.newsletter input').removeAttr('checked');
    var p_type_id = document.vorm.elements['type_id'];
    do_get_newsletters_by_card_type(p_type_id.value);
}

function do_get_card_types_by_school_cb(types) {
    var typeList = document.vorm.elements["type_id"];
    typeList.clearOptions();
    if (types == false) {
        typeList.addOption(0, '');
    } else {
        for (var i in types) {
            typeList.addOption(types[i].id, types[i].name);
        }
    }
    typeList.clear();
//    refreshDeliveryList();
}

function do_get_newsletters_by_card_type_cb(newsletters) {
    if (newsletters == false) {
    } else {
        jQuery('div#newsletterList div.newsletter input').removeAttr('style');
        jQuery('div#newsletterList div.newsletter').css('display', 'none');
        for (var i in newsletters) {
            curNewslleter = document.vorm.elements["person_newsletter_" + newsletters[i].id];
            if (curNewslleter != false) {
                curNewslleter.parentNode.parentNode.style.display = 'block';
            }
        }
    }
    if (jQuery('div#newsletterList div.newsletter input:visible').length == 0) {
        jQuery('#newsletter_block').css('display', 'none');
    }
}

function do_get_newsletters_by_card_type(type_id) {
    x_get_newsletters_by_card_type(type_id, do_get_newsletters_by_card_type_cb);
}

function do_get_card_types_by_school(school_id) {
    x_get_card_types_by_school(school_id, do_get_card_types_by_school_cb);
}

function refreshDeliveryList() {
    var p_school_id = document.vorm.elements['school_id'];
    var p_type_id = document.vorm.elements['type_id'];
    do_get_card_deliveries_by_school_card_type(p_school_id.value, p_type_id.value);
}

function do_get_card_deliveries_by_school(school_id) {
    x_get_card_deliveries_by_school(school_id, do_get_card_deliveries_by_school_cb);
}

function do_get_card_deliveries_by_school_card_type(school_id, type_id) {
    x_get_card_deliveries_by_school_card_type(school_id, type_id, do_get_card_deliveries_by_school_cb);
}

function do_get_card_deliveries_by_school_cb(deliveries) {
    var deliveryList = document.vorm.elements["delivery_id"];
    deliveryList.clearOptions();

    if (deliveries != false) {
        for (var i in deliveries) {
            deliveryList.addOption(deliveries[i].id, deliveries[i].name);
        }
    } else {
        deliveryList.addOption(0, '');
    }
    deliveryList.clear();
}
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




    <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice" id="application_add_admin">
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
                            <h2><?php echo $this->getGlobals("PAGETITLE"); ?></h2>
                        </div>

                        <!--formTable-->
                        <div class="formTable">

                                <!--fRow-->
                                <div class="fRow">
                                <?php if(isset($data["LANGUAGE_ID"]) && is_array($data["LANGUAGE_ID"])){ foreach($data["LANGUAGE_ID"] as $_foreach["LANGUAGE_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["LANGUAGE_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|language"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["LANGUAGE_ID"]["FIELD_LANGUAGE_ID"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["KIND_ID"]) && is_array($data["KIND_ID"])){ foreach($data["KIND_ID"] as $_foreach["KIND_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["KIND_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|kind"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["KIND_ID"]["FIELD_KIND_ID"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                <?php if(isset($data["BANK_ID"]) && is_array($data["BANK_ID"])){ foreach($data["BANK_ID"] as $_foreach["BANK_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["BANK_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_card|bank"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["BANK_ID"]["FIELD_BANK_ID"]; ?>
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
                                            <p><span><?php echo $_foreach["DELIVERY_ID"]["TOOLTIP"]; ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                </div>
                                <!--/fRow-->

                                <!--fRow-->
                                <div class="fRow">
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

                                <!--wButtons-->
                                <div class="wButtons">
                                    <?php if(isset($data["SUBMIT"]) && is_array($data["SUBMIT"])){ foreach($data["SUBMIT"] as $_foreach["SUBMIT"]){ ?>

                                      <input type="submit" value="<?php echo $_foreach["SUBMIT"]["BUTTON"]; ?>" />
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

                                <img src="<?php echo $_foreach["SHOW_PIC"]["DATA_PIC"]; ?>" alt="" border="0" id="person_pic" />
                            <?php }} ?>

                            <?php if(isset($data["EDIT_PIC"]) && is_array($data["EDIT_PIC"])){ foreach($data["EDIT_PIC"] as $_foreach["EDIT_PIC"]){ ?>

                            <img src="<?php echo $_foreach["EDIT_PIC"]["DATA_PIC"]; ?>" alt="" border="0" width="<?php echo $_foreach["EDIT_PIC"]["MAX_WIDTH"]; ?>" id="cropImage" />
                            <div class="wButtons wButtonsCenter">
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
