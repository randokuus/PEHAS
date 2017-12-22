<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript">
var start_date_list = Array(<?php echo $data["PRESET_DATES_START"]; ?>);
var end_date_list = Array(<?php echo $data["PRESET_DATES_END"]; ?>);

function assignStartEndDateByPreseset() {
    filter_preset_date = document.getElementById("filter_preset_dates");
    filter_start_date = document.getElementById("filter_start_date");
    filter_end_date = document.getElementById("filter_end_date");
    filter_start_date.value = start_date_list[filter_preset_date.value];
    filter_end_date.value = end_date_list[filter_preset_date.value];
}

function submitForm(submitType) {
    if (submitType == 'export') {
        document.report_filter.export.value = 1;
    } else {
        document.report_filter.export.value = 0;
    }
    document.report_filter.submit();
}

</script>

        <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgAtt msgGray">
                <span><?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
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
                        <h2>Filter</h2>
                    </div>
                    <!--boxcontent-->
                    <div class="boxcontent">
                        <!--filterTable-->
                        <div class="filterTable fourCols">
                            <form method="post" action="<?php echo $data["SELF"]; ?>" id="report_filter" name="report_filter" class="jNice">
                                <input type="hidden" id="export" name="export" value="" />
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|preset_dates"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PRESET_DATES"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|start_date"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_START_DATE"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|end_date"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_END_DATE"]; ?>
                                    </div>
                                </div>

                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_LAST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_id"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NUMBER"]; ?>
                                    </div>
                                    <?php if(isset($data["JOINED"]) && is_array($data["JOINED"])){ foreach($data["JOINED"] as $_foreach["JOINED"]){ ?>

                                        <div class="tLine">
                                            <label for=""><?php echo $this->getTranslate("module_isic_report|joined_schools"); ?>:</label>
                                            <?php echo $_foreach["JOINED"]["FIELD_FILTER_SCHOOL_JOINED"]; ?>
                                        </div>
                                    <?php }} ?>

                                </div>

                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|isic_number"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_ISIC_NUMBER"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|sum_all_types"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SUM_ALL_TYPES"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|sum_all_schools"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SUM_ALL_SCHOOLS"]; ?>
                                    </div>
                                </div>

                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|card_type"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_TYPE_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|region"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_REGION_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|school"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SCHOOL_ID"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons" style="top: 25%;">
                                    <input type="button" name="query" value="<?php echo $this->getTranslate("module_isic_report|query"); ?>" onclick="javascript:submitForm('');" />
                                    <?php if(isset($data["EXPORT"]) && is_array($data["EXPORT"])){ foreach($data["EXPORT"] as $_foreach["EXPORT"]){ ?>

                                        <?php echo $_foreach["EXPORT"]["FIELD_EXPORT_BUTTON"]; ?>
                                    <?php }} ?>

                                </div>
                                <input type="hidden" name="filter" value="1">
                                <?php echo $data["HIDDEN"]; ?>
                            </form>
                        </div>
                        <!--/filterTable-->
                    </div>
                    <!--/boxcontent-->
                </div>
            </div>
            <!--/box-->

            <!--box-->
            <div class="box">
                <div class="inner">
                    <?php if(isset($data["SCHOOL"]) && is_array($data["SCHOOL"])){ foreach($data["SCHOOL"] as $_foreach["SCHOOL"]){ ?>

                    <div class="heading">
                        <div class="control">
                            <?php if(isset($_foreach["SCHOOL"]["PRINT"]) && is_array($_foreach["SCHOOL"]["PRINT"])){ foreach($_foreach["SCHOOL"]["PRINT"] as $_foreach["SCHOOL.PRINT"]){ ?>
<a href="<?php echo $_foreach["SCHOOL.PRINT"]["URL"]; ?>" target="_blank" title="Print page" class="ico iprint">Print page</a><?php }} ?>

                        </div>
                        <h2><?php echo $_foreach["SCHOOL"]["NAME"]; ?></h2>
                    </div>

                    <?php if(isset($_foreach["SCHOOL"]["CARD_TYPE"]) && is_array($_foreach["SCHOOL"]["CARD_TYPE"])){ foreach($_foreach["SCHOOL"]["CARD_TYPE"] as $_foreach["SCHOOL.CARD_TYPE"]){ ?>

                    <h3 class="tHeading"><b><?php echo $_foreach["SCHOOL.CARD_TYPE"]["NAME"]; ?></b></h3>
                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader1">
                                <th></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_KIND"]; ?>"><?php echo $this->getTranslate("module_isic_report|regular"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_KIND"]; ?>"><?php echo $this->getTranslate("module_isic_report|combined"); ?></th>
                            </tr>
                            <tr class="tHeader2">
                                <th></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|first"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|replace"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|prolong"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|total"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|first"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|replace"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|prolong"); ?></th>
                                <th colspan="<?php echo $_foreach["SCHOOL.CARD_TYPE"]["COLS_STAT"]; ?>"><?php echo $this->getTranslate("module_isic_report|total"); ?></th>
                            </tr>
                            <tr class="tHeader3">
                                <th><?php echo $this->getTranslate("module_isic_report|order_date"); ?></th>
                                <?php if(isset($_foreach["SCHOOL.CARD_TYPE"]["TITLE"]) && is_array($_foreach["SCHOOL.CARD_TYPE"]["TITLE"])){ foreach($_foreach["SCHOOL.CARD_TYPE"]["TITLE"] as $_foreach["SCHOOL.CARD_TYPE.TITLE"]){ ?>

                                <th><?php echo $_foreach["SCHOOL.CARD_TYPE.TITLE"]["TITLE"]; ?></th>
                                <?php }} ?>

                            </tr>
                            <?php if(isset($_foreach["SCHOOL.CARD_TYPE"]["DATA"]) && is_array($_foreach["SCHOOL.CARD_TYPE"]["DATA"])){ foreach($_foreach["SCHOOL.CARD_TYPE"]["DATA"] as $_foreach["SCHOOL.CARD_TYPE.DATA"]){ ?>

                            <tr class="<?php echo $_foreach["SCHOOL.CARD_TYPE.DATA"]["STYLE"]; ?>">
                                <td><?php echo $_foreach["SCHOOL.CARD_TYPE.DATA"]["DATE"]; ?></td>
                                <?php if(isset($_foreach["SCHOOL.CARD_TYPE.DATA"]["KIND"]) && is_array($_foreach["SCHOOL.CARD_TYPE.DATA"]["KIND"])){ foreach($_foreach["SCHOOL.CARD_TYPE.DATA"]["KIND"] as $_foreach["SCHOOL.CARD_TYPE.DATA.KIND"]){ ?>

                                <td><?php echo $_foreach["SCHOOL.CARD_TYPE.DATA.KIND"]["VAL"]; ?></td>
                                <?php }} ?>

                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                    <?php }} ?>

                <?php }} ?>

                </div>
            </div>
            <!--/box-->
        </div>
        <!--/singleCol-->