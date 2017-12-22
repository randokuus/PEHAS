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
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|isic_number"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_ISIC_NUMBER"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_id"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NUMBER"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_FIRST"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_PERSON_NAME_LAST"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons">
                                    <input type="submit" name="submit" value="<?php echo $this->getTranslate("module_isic_report|query"); ?>" />
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
                    <div class="heading">
                        <div class="control">
                            <?php if(isset($data["PRINT"]) && is_array($data["PRINT"])){ foreach($data["PRINT"] as $_foreach["PRINT"]){ ?>
<a href="<?php echo $_foreach["PRINT"]["URL"]; ?>" target="_blank" title="Print page" class="ico iprint">Print page</a><?php }} ?>

                        </div>
                        <h2><?php echo $data["TITLE"]; ?></h2>
                    </div>
                
                    <?php if(isset($data["CARD_LIST"]) && is_array($data["CARD_LIST"])){ foreach($data["CARD_LIST"] as $_foreach["CARD_LIST"]){ ?>

                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader1">
								<th><?php echo $this->getTranslate("module_isic_card|person_name_first"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|person_name_last"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|person_id"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|expiration_date"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|isic_number"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|type"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_card|active"); ?></th>
                            </tr>
							<?php if(isset($_foreach["CARD_LIST"]["DATA"]) && is_array($_foreach["CARD_LIST"]["DATA"])){ foreach($_foreach["CARD_LIST"]["DATA"] as $_foreach["CARD_LIST.DATA"]){ ?>

							<tr class="<?php echo $_foreach["CARD_LIST.DATA"]["STYLE"]; ?>">
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["PERSON_NAME_FIRST"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["PERSON_NAME_LAST"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["PERSON_NUMBER"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["EXPIRATION_DATE"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["ISIC_NUMBER"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["CARD_TYPE_NAME"]; ?></a></td>
							    <td><a href="<?php echo $_foreach["CARD_LIST.DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["CARD_LIST.DATA"]["ACTIVE"]; ?></a></td>
							</tr>
							<?php }} ?>
 
                        </table>
                    </div>
                    <!--/tableWrap-->
                    <?php }} ?>


				    <?php if(isset($data["CARD_LOG"]) && is_array($data["CARD_LOG"])){ foreach($data["CARD_LOG"] as $_foreach["CARD_LOG"]){ ?>

                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader1">
						        <th rowspan="2"><?php echo $this->getTranslate("module_isic_report|log_date"); ?></th>
						        <th rowspan="2"><?php echo $this->getTranslate("module_isic_report|log_type"); ?></th>
						        <th rowspan="2"><?php echo $this->getTranslate("module_isic_report|log_user"); ?></th>
						        <th colspan="3"><?php echo $this->getTranslate("module_isic_report|log_body"); ?></th>
        				    </tr>
						    <tr class="tHeader2">
						        <th><?php echo $this->getTranslate("module_isic_report|log_field"); ?></th>
						        <th><?php echo $this->getTranslate("module_isic_report|log_old"); ?></th>
						        <th><?php echo $this->getTranslate("module_isic_report|log_new"); ?></th>
						    </tr>
						    <?php if(isset($_foreach["CARD_LOG"]["DATA"]) && is_array($_foreach["CARD_LOG"]["DATA"])){ foreach($_foreach["CARD_LOG"]["DATA"] as $_foreach["CARD_LOG.DATA"]){ ?>

						    <tr class="<?php echo $_foreach["CARD_LOG.DATA"]["STYLE"]; ?>">
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["DATE"]; ?></td>
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["TYPE"]; ?></td>
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["USER"]; ?></td>
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["BODY_NAME"]; ?></td>
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["BODY_OLD"]; ?></td>
						        <td><?php echo $_foreach["CARD_LOG.DATA"]["BODY_NEW"]; ?></td>
						        <?php echo $_foreach["CARD_LOG.DATA"]["DUMMY"]; ?>
						    </tr>
				            <?php }} ?>
 
                        </table>
                    </div>
                    <!--/tableWrap-->
				    <?php }} ?>

                </div>
            </div>
            <!--/box-->
        </div>
        <!--/singleCol-->
				    