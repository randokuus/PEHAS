<?php defined("MODERA_KEY")|| die(); ?>        <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

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
                            <form method="post" action="<?php echo $data["SELF"]; ?>" name="report_filter" class="jNice">
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_FIRST"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_LAST"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_id"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NUMBER"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|isic_number"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_ISIC_NUMBER"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|card_type"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_TYPE_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|school"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SCHOOL_ID"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|currency"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_CURRENCY"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|card_kind"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_KIND_ID"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons">
                                    <input type="submit" name="submit" value="<?php echo $this->getTranslate("module_isic_report|query"); ?>" />
                                </div>
                                <input type="hidden" name="filter" value="1">
                                <input type="hidden" name="detail" value="1">
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
                    <div class="heading">
                        <div class="control">
                            <?php if(isset($data["PRINT"]) && is_array($data["PRINT"])){ foreach($data["PRINT"] as $_foreach["PRINT"]){ ?>
<a href="<?php echo $_foreach["PRINT"]["URL"]; ?>" target="_blank" title="Print page" class="ico iprint">Print page</a><?php }} ?>

                        </div>
                        <h2><?php echo $data["TITLE"]; ?></h2>
                    </div>

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader2">
                                <th><?php echo $this->getTranslate("module_isic_card|pic"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|person_id"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|expiration_date"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|isic_number"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_report|payment_collateral"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_report|payment_cost"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|type"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|application_type"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|person_stru_unit"); ?></th>
                                <th><?php echo $this->getTranslate("module_isic_card|active"); ?></th>
                            </tr>
                            <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

                            <tr class="<?php echo $_foreach["DATA"]["STYLE"]; ?>">
                                <td><?php echo $_foreach["DATA"]["IMAGE"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["PERSON_NAME_FIRST"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["PERSON_NAME_LAST"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["PERSON_NUMBER"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["EXPIRATION_DATE"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["ISIC_NUMBER"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["CONFIRM_PAYMENT_COLLATERAL"]; ?><br /><?php echo $_foreach["DATA"]["COLLATERAL_SUM"]; ?><br /><?php echo $_foreach["DATA"]["COLLATERAL_PAYMENT_METHOD"]; ?> <?php echo $_foreach["DATA"]["COLLATERAL_BANK"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["CONFIRM_PAYMENT_COST"]; ?><br /><?php echo $_foreach["DATA"]["COST_SUM"]; ?><br /><?php echo $_foreach["DATA"]["COST_PAYMENT_METHOD"]; ?> <?php echo $_foreach["DATA"]["COST_BANK"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["CARD_TYPE_NAME"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["CARD_STATUS_NAME"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["PERSON_STRU_UNIT"]; ?></td>
                                <td><?php echo $_foreach["DATA"]["ACTIVE"]; ?></td>
                                </td>
                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                </div>
            </div>
              <?php if(isset($data["BACK_URL"]) && is_array($data["BACK_URL"])){ foreach($data["BACK_URL"] as $_foreach["BACK_URL"]){ ?>

              <div class="wButtons">
                <div class="jNiceButton grayButton">
                  <div>
                    <input type="button" class="jNiceButtonInput"  onclick="javascript:window:location = '<?php echo $_foreach["BACK_URL"]["URL"]; ?>'" value="<?php echo $this->getTranslate("module_isic_card|return_back"); ?>"/>
                  </div>
                </div>
              </div>
              <?php }} ?>

            <!--/box-->
        </div>
        <!--/singleCol-->