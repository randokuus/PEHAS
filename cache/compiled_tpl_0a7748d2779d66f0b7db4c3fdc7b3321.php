<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript">
function refreshTypeList() {
    var p_school_id = document.vorm.elements['school_id'];
    do_get_card_types_by_school(p_school_id.value);
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

function do_get_card_types_by_school(school_id) {
    x_get_card_types_by_school(school_id, do_get_card_types_by_school_cb);
}

function refreshDeliveryList() {
    var p_school_id = document.vorm.elements['school_id'];
    var p_type_id = document.vorm.elements['type_id'];
    do_get_card_deliveries_by_school_card_type(p_school_id.value, p_type_id.value, 0);
}

function do_get_card_deliveries_by_school(school_id, show_home_delivery) {
    x_get_card_deliveries_by_school(school_id, show_home_delivery, do_get_card_deliveries_by_school_cb);
}

function do_get_card_deliveries_by_school_card_type(school_id, type_id, show_home_delivery) {
    x_get_card_deliveries_by_school_card_type(school_id, type_id, show_home_delivery, do_get_card_deliveries_by_school_cb);
}

function do_get_card_deliveries_by_school_cb(deliveries) {
    var deliveryList = document.vorm.elements["delivery_id"];
//    deliveryList.updateOptions(deliveries);

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
                            <div class="col3" style="width: 35%;">
                                <!--controlTable-->
                                <div class="formTable controlTable">
                                    <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
                                    <?php echo $data["HIDDEN"]; ?>
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|school"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_SCHOOL_ID"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|type"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_TYPE_ID"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|datafile"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_DATAFILE"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|delivery"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_DELIVERY_ID"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|separator"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_SEPARATOR"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->

                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|title_row"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_TITLE_ROW"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->

                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_card|missing_data_from_profile"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_MISSING_DATA_FROM_PROFILE"]; ?>
                                            </div>
                                        </div>

                                        <!--/fLine-->

                                        <!--fSubmit-->
                                        <div class="fSubmit">
                                            <div class="fSubmitInner">
                                                <input type="submit" value="<?php echo $data["BUTTON"]; ?>" />
                                            </div>
                                        </div>
                                        <!--/fSubmit-->
                                    </form>
                                </div>
                                <!--/controlTable-->
                            </div>
                            <!--/col3-->
                            <!--col4-->
                            <div class="col4 content" style="width: 62%;">
                                <?php echo $this->getTranslate("module_isic_card|csv_import_description"); ?>
                            </div>
                            <!--/col4-->
                        </div>
                        <!--/boxcontent-->
                    </div>
                </div>
                <!--/box-->
        </div>
        <!--/singleCol-->
