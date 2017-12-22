<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript" charset="utf-8">

<?php if(isset($data["EDIT_PIC_JS"]) && is_array($data["EDIT_PIC_JS"])){ foreach($data["EDIT_PIC_JS"] as $_foreach["EDIT_PIC_JS"]){ ?>

// setup the callback function
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
        setSelect: [<?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["X1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_WIDTH"]; ?>, <?php echo $_foreach["EDIT_PIC_JS"]["Y1"]; ?> + <?php echo $_foreach["EDIT_PIC_JS"]["MIN_HEIGHT"]; ?>],
        allowSelect : false
    });
});
<?php }} ?>


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
    var p_code = document.vorm.elements['user_code'];
    var p_bday = document.vorm.elements['birthday'];
    var century = 0;
    var year = 0;
    var month = 0;
    var day = 0;
    var t_code = p_code ? p_code.value : '';
    var t_bday = '';

    // getting all the data from user profile via ajax-call
    do_getuserdata(t_code);

    if (p_bday.value == '' && t_code != '') {

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
    var name_first = document.vorm.elements['name_first'];
    var name_last = document.vorm.elements['name_last'];
    var bacc_name = document.vorm.elements['bankaccount_name'];
    if ((name_first.value != '' || name_last.value != '') && bacc_name.value == '') {
        bacc_name.value = name_first.value + ' ' + name_last.value;
    }
}

function do_getuserdata_cb(z) {
    if (z == false) {
        //couldnt find person
        document.vorm.elements['user_id'].value = '';
        document.vorm.elements['name_first'].value = '';
        document.vorm.elements['name_last'].value = '';
        document.vorm.elements['delivery_addr1'].value = '';
        document.vorm.elements['delivery_addr2'].value = '';
        document.vorm.elements['delivery_addr3'].value = '';
        document.vorm.elements['delivery_addr4'].value = '';
        document.vorm.elements['email'].value = '';
        document.vorm.elements['phone'].value = '';
        document.vorm.elements['bankaccount'].value = '';
        document.vorm.elements['bankaccount_name'].value = '';
        document.getElementById('person_pic').src = 'img/img_placeholder_big.png';
    } else {
        document.vorm.elements['user_id'].value = z.user;
        document.vorm.elements['name_first'].value = z.name_first;
        document.vorm.elements['name_last'].value = z.name_last;
        document.vorm.elements['delivery_addr1'].value = z.delivery_addr1;
        document.vorm.elements['delivery_addr2'].value = z.delivery_addr2;
        document.vorm.elements['delivery_addr3'].value = z.delivery_addr3;
        document.vorm.elements['delivery_addr4'].value = z.delivery_addr4;
        document.vorm.elements['email'].value = z.email;
        document.vorm.elements['phone'].value = z.phone;
        document.vorm.elements['bankaccount'].value = z.bankaccount;
        document.vorm.elements['bankaccount_name'].value = z.bankaccount_name;
        if (z.pic) {
            document.getElementById('person_pic').src = z.pic;
        } else {
            document.getElementById('person_pic').src = 'img/img_placeholder_big.png';
        }
    }
    getUserStatusData();
}

function do_getuserdata(person_number) {
    x_getuserdata(person_number, do_getuserdata_cb);
}


function getUserStatusData() {
    var p_user_id = document.vorm.elements['user_id'];
    var p_group_id = document.vorm.elements['group_id'];
    //var p_school_id = document.vorm.elements['school_id'];
    //var p_status_id = document.vorm.elements['status_id'];
    //if (p_user_id.value && p_group_id.value) {
        do_getuserstatusdata(p_group_id.value, p_user_id.value);
    //}
}

function do_getuserstatusdata_cb(z) {
    if (z == false) {
        //couldnt find user status
        document.vorm.elements['user_status_id'].value = '';
        document.vorm.elements['structure_unit'].value = '';
        document.vorm.elements['faculty'].value = '';
        document.vorm.elements['class'].value = '';
        document.vorm.elements['course'].value = '';
        document.vorm.elements['position'].value = '';
    } else {
        //document.vorm.elements['user_status_id'].value = z.id;
        document.vorm.elements['structure_unit'].value = z['structure_unit'];
        document.vorm.elements['faculty'].value = z['faculty'];
        document.vorm.elements['class'].value = z['class'];
        document.vorm.elements['course'].value = z['course'];
        document.vorm.elements['position'].value = z['position'];
    }
}

function do_getuserstatusdata(group_id, user_id) {
    x_getuserstatusdata(group_id, user_id, do_getuserstatusdata_cb);
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
                            <h2><?php echo $this->getTranslate("module_isic_user|change_profile"); ?></h2>
                        </div>

                        <!--formTable-->
                        <div class="formTable">

                                <!--fRow-->
                                <div class="fRow">
                                <?php if(isset($data["USER_CODE"]) && is_array($data["USER_CODE"])){ foreach($data["USER_CODE"] as $_foreach["USER_CODE"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["USER_CODE"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|user_code"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["USER_CODE"]["FIELD_USER_CODE"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["BIRTHDAY"]) && is_array($data["BIRTHDAY"])){ foreach($data["BIRTHDAY"] as $_foreach["BIRTHDAY"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["BIRTHDAY"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|birthday"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["BIRTHDAY"]["FIELD_BIRTHDAY"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["NAME_FIRST"]) && is_array($data["NAME_FIRST"])){ foreach($data["NAME_FIRST"] as $_foreach["NAME_FIRST"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["NAME_FIRST"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|name_first"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["NAME_FIRST"]["FIELD_NAME_FIRST"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["NAME_LAST"]) && is_array($data["NAME_LAST"])){ foreach($data["NAME_LAST"] as $_foreach["NAME_LAST"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["NAME_LAST"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|name_last"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["NAME_LAST"]["FIELD_NAME_LAST"]; ?>
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
                                            <?php echo $this->getTranslate("module_isic_user|photo"); ?>:
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
                                <?php if(isset($data["BANKACCOUNT"]) && is_array($data["BANKACCOUNT"])){ foreach($data["BANKACCOUNT"] as $_foreach["BANKACCOUNT"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["BANKACCOUNT"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|bankaccount"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["BANKACCOUNT"]["FIELD_BANKACCOUNT"]; ?>
                                        </div>
                                        <div class="fHint">
                                            <p><span><?php echo $_foreach["BANKACCOUNT"]["TOOLTIP"]; ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                               <?php }} ?>


                               <?php if(isset($data["BANKACCOUNT_NAME"]) && is_array($data["BANKACCOUNT_NAME"])){ foreach($data["BANKACCOUNT_NAME"] as $_foreach["BANKACCOUNT_NAME"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["BANKACCOUNT_NAME"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|bankaccount_name"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["BANKACCOUNT_NAME"]["FIELD_BANKACCOUNT_NAME"]; ?>
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
                                <?php if(isset($data["DELIVERY_ADDR1"]) && is_array($data["DELIVERY_ADDR1"])){ foreach($data["DELIVERY_ADDR1"] as $_foreach["DELIVERY_ADDR1"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR1"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|delivery_addr1"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR1"]["FIELD_DELIVERY_ADDR1"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["DELIVERY_ADDR2"]) && is_array($data["DELIVERY_ADDR2"])){ foreach($data["DELIVERY_ADDR2"] as $_foreach["DELIVERY_ADDR2"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["DELIVERY_ADDR2"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|delivery_addr2"); ?>:
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
                                            <?php echo $this->getTranslate("module_isic_user|delivery_addr3"); ?>:
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
                                            <?php echo $this->getTranslate("module_isic_user|delivery_addr4"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["DELIVERY_ADDR4"]["FIELD_DELIVERY_ADDR4"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["EMAIL"]) && is_array($data["EMAIL"])){ foreach($data["EMAIL"] as $_foreach["EMAIL"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["EMAIL"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|email"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["EMAIL"]["FIELD_EMAIL"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["PHONE"]) && is_array($data["PHONE"])){ foreach($data["PHONE"] as $_foreach["PHONE"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["PHONE"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|phone"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["PHONE"]["FIELD_PHONE"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>

                                </div>
                                <!--/fRow-->

                                <?php if(isset($data["SPECIAL_OFFERS"]) && is_array($data["SPECIAL_OFFERS"])){ foreach($data["SPECIAL_OFFERS"] as $_foreach["SPECIAL_OFFERS"]){ ?>

                                <!--fRow-->
                                <div class="fRow">
                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["SPECIAL_OFFERS"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|special_offers"); ?>:
                                        </div>

                                        <div class="fCell">
                                            <?php echo $_foreach["SPECIAL_OFFERS"]["FIELD_SPECIAL_OFFERS"]; ?>
                                        </div>
                                        <div class="fHint">
                                            <p><span><?php echo $_foreach["SPECIAL_OFFERS"]["TOOLTIP"]; ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                </div>
                                <!--/fRow-->
                                <?php }} ?>


                                <div id="newsletter_block">
                                <!--fRow-->
                                <div class="fRow">
                                    <!--fLine-->
                                    <div class="fLine <?php echo $data["REQUIRED"]; ?>">
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
                                            <p><span><?php echo $this->getTranslate("module_isic_user|newsletter_help"); ?></span></p>
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
                                    <div class="fLine <?php echo $_foreach["APPL_CONFIRMATION_MAILS"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|appl_confirmation_mails"); ?>:
                                        </div>

                                        <div class="fCell">
                                            <?php echo $_foreach["APPL_CONFIRMATION_MAILS"]["FIELD_APPL_CONFIRMATION_MAILS"]; ?>
                                        </div>
                                        <div class="fHint">
                                            <p><span><?php echo $this->getTranslate("module_isic_user|appl_confirmation_mails_tooltip"); ?></span></p>
                                        </div>
                                    </div>
                                    <!--/fLine-->
                                </div>
                                <!--/fRow-->
                                <?php }} ?>


                                <!--fRow-->
                                <div class="fRow">

                                <?php if(isset($data["GROUP_ID"]) && is_array($data["GROUP_ID"])){ foreach($data["GROUP_ID"] as $_foreach["GROUP_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["GROUP_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_isic_user|group"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["GROUP_ID"]["FIELD_GROUP_ID"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["USER_ID"]) && is_array($data["USER_ID"])){ foreach($data["USER_ID"] as $_foreach["USER_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["USER_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|user"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["USER_ID"]["FIELD_USER_ID"]; ?>
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
                                            <?php echo $this->getTranslate("module_user_status|school"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["SCHOOL_ID"]["FIELD_SCHOOL_ID"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["STATUS_ID"]) && is_array($data["STATUS_ID"])){ foreach($data["STATUS_ID"] as $_foreach["STATUS_ID"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["STATUS_ID"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|status"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["STATUS_ID"]["FIELD_STATUS_ID"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["STRUCTURE_UNIT"]) && is_array($data["STRUCTURE_UNIT"])){ foreach($data["STRUCTURE_UNIT"] as $_foreach["STRUCTURE_UNIT"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["STRUCTURE_UNIT"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|structure_unit"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["STRUCTURE_UNIT"]["FIELD_STRUCTURE_UNIT"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["FACULTY"]) && is_array($data["FACULTY"])){ foreach($data["FACULTY"] as $_foreach["FACULTY"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["FACULTY"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|faculty"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["FACULTY"]["FIELD_FACULTY"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["CLASS"]) && is_array($data["CLASS"])){ foreach($data["CLASS"] as $_foreach["CLASS"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["CLASS"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|class"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["CLASS"]["FIELD_CLASS"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["COURSE"]) && is_array($data["COURSE"])){ foreach($data["COURSE"] as $_foreach["COURSE"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["COURSE"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|course"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["COURSE"]["FIELD_COURSE"]; ?>
                                        </div>
                                        <div class="fHint">

                                        </div>
                                    </div>
                                    <!--/fLine-->
                                <?php }} ?>


                                <?php if(isset($data["POSITION"]) && is_array($data["POSITION"])){ foreach($data["POSITION"] as $_foreach["POSITION"]){ ?>

                                    <!--fLine-->
                                    <div class="fLine <?php echo $_foreach["POSITION"]["REQUIRED"]; ?>">
                                        <div class="fHead">
                                            <?php echo $this->getTranslate("module_user_status|position"); ?>:
                                        </div>
                                        <div class="fCell">
                                            <?php echo $_foreach["POSITION"]["FIELD_POSITION"]; ?>
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
                                <?php if(isset($data["BACK"]) && is_array($data["BACK"])){ foreach($data["BACK"] as $_foreach["BACK"]){ ?>

                                  <input type="button" onClick="document.location='<?php echo $_foreach["BACK"]["URL"]; ?>'" value="<?php echo $_foreach["BACK"]["TITLE"]; ?>" class="grayButton" />
                                <?php }} ?>

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
                            <div class="wButtons  wButtonsCenter">
                                <input type="submit" value="<?php echo $_foreach["EDIT_PIC"]["BUTTON"]; ?>" />
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