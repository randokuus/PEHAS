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
                        <div class="filterTable threeCols">
                            <form method="post" action="<?php echo $data["SELF"]; ?>" name="report_filter" class="jNice">
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|user_code"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_USER_CODE"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|school"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SCHOOL_ID"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|status"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_STATUS_ID"]; ?>
                                    </div>
                                </div>
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
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|mod_action"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_MOD_ACTION"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|mod_origin"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_MOD_ORIGIN"]; ?>
                                    </div>
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|mod_user"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_MOD_USER"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons">
                                    <input type="submit" name="submit" value="<?php echo $this->getTranslate("module_isic_report|query"); ?>" />
                                </div>
                                <input type="hidden" name="filter" value="1">
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
                    <?php if(isset($data["RESULT"]) && is_array($data["RESULT"])){ foreach($data["RESULT"] as $_foreach["RESULT"]){ ?>

                    <div class="heading">
                        <div class="control">
                            <?php if(isset($_foreach["RESULT"]["PRINT"]) && is_array($_foreach["RESULT"]["PRINT"])){ foreach($_foreach["RESULT"]["PRINT"] as $_foreach["RESULT.PRINT"]){ ?>
<a href="<?php echo $_foreach["RESULT.PRINT"]["URL"]; ?>" target="_blank" title="Print page" class="ico iprint">Print page</a><?php }} ?>

                        </div>
                        <h2><?php echo $_foreach["RESULT"]["TITLE"]; ?></h2>
                    </div>
                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader1">
								<th><?php echo $this->getTranslate("module_isic_user|user_code"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_user|name_first"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_user|name_last"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_user|status"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_user|school"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_report|mod_action"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_report|mod_origin"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_report|mod_date"); ?></th>
								<th><?php echo $this->getTranslate("module_isic_report|mod_user"); ?></th>
                            </tr>
							<?php if(isset($_foreach["RESULT"]["RECORD"]) && is_array($_foreach["RESULT"]["RECORD"])){ foreach($_foreach["RESULT"]["RECORD"] as $_foreach["RESULT.RECORD"]){ ?>

							<tr>
							    <td><?php echo $_foreach["RESULT.RECORD"]["USER_CODE"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["USER_NAME_FIRST"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["USER_NAME_LAST"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["STATUS_NAME"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["SCHOOL_NAME"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["MOD_ACTION"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["MOD_ORIGIN"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["MOD_DATE"]; ?></td>
							    <td><?php echo $_foreach["RESULT.RECORD"]["MOD_USER_NAME"]; ?></td>
							</tr>
							<?php }} ?>

                            </tr>
                        </table>
                    </div>
                    <!--/tableWrap-->
                    <?php }} ?>

                    
					<?php if(isset($data["PAGES"]) && is_array($data["PAGES"])){ foreach($data["PAGES"] as $_foreach["PAGES"]){ ?>

                    <!--pagin-->
                    <div class="pagin">
                        <div>
						    <?php if(isset($_foreach["PAGES"]["PAGE"]) && is_array($_foreach["PAGES"]["PAGE"])){ foreach($_foreach["PAGES"]["PAGE"] as $_foreach["PAGES.PAGE"]){ ?>

						        <?php if(isset($_foreach["PAGES.PAGE"]["CURRENT"]) && is_array($_foreach["PAGES.PAGE"]["CURRENT"])){ foreach($_foreach["PAGES.PAGE"]["CURRENT"] as $_foreach["PAGES.PAGE.CURRENT"]){ ?>

						            <span><?php echo $_foreach["PAGES.PAGE.CURRENT"]["NUMBER"]; ?></span>
						        <?php }} ?>

						        <?php if(isset($_foreach["PAGES.PAGE"]["NOT_CURRENT"]) && is_array($_foreach["PAGES.PAGE"]["NOT_CURRENT"])){ foreach($_foreach["PAGES.PAGE"]["NOT_CURRENT"] as $_foreach["PAGES.PAGE.NOT_CURRENT"]){ ?>

						            <a href="<?php echo $_foreach["PAGES.PAGE.NOT_CURRENT"]["URL"]; ?>"><?php echo $_foreach["PAGES.PAGE.NOT_CURRENT"]["NUMBER"]; ?></a>
						        <?php }} ?>

						    <?php }} ?>

                        </div>
                    </div>
                    <!--/pagin-->
					<?php }} ?>

                </div>
            </div>
            <!--/box-->
        </div>
        <!--/singleCol-->

