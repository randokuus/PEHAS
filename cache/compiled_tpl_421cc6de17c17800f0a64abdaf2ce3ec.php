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
                        <div class="filterTable threeCols">
                            <form method="post" action="<?php echo $data["SELF"]; ?>" name="report_filter" class="jNice">
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|start_date"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_START_DATE"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|end_date"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_END_DATE"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_report|school"); ?>:</label>
                                        <?php echo $data["FIELD_FILTER_SCHOOL_ID"]; ?>
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
                    <?php if(isset($data["SEND_LIST"]) && is_array($data["SEND_LIST"])){ foreach($data["SEND_LIST"] as $_foreach["SEND_LIST"]){ ?>

                    <div class="tableWrap">
                        <table class="tList tCard">
                            <tr class="tHeader1">
                                <th><?php echo $this->getTranslate("module_messages_send_log|sendtime"); ?></th>
                                <th><?php echo $this->getTranslate("module_messages_send_log|sender"); ?></th>
                                <th><?php echo $this->getTranslate("module_messages_send_log|school"); ?></th>
                                <th><?php echo $this->getTranslate("module_messages_send_log|to"); ?></th>
                                <th><?php echo $this->getTranslate("module_messages_send_log|text"); ?></th>
                                <!--<th><?php echo $this->getTranslate("module_messages_send_log|cost"); ?></th>-->
                            </tr>
                            <?php if(isset($_foreach["SEND_LIST"]["DATA"]) && is_array($_foreach["SEND_LIST"]["DATA"])){ foreach($_foreach["SEND_LIST"]["DATA"] as $_foreach["SEND_LIST.DATA"]){ ?>

                            <tr class="<?php echo $_foreach["SEND_LIST.DATA"]["STYLE"]; ?>">
                                <td><?php echo $_foreach["SEND_LIST.DATA"]["SENDTIME"]; ?></td>
                                <td><?php echo $_foreach["SEND_LIST.DATA"]["SENDER"]; ?></td>
                                <td><?php echo $_foreach["SEND_LIST.DATA"]["SCHOOL"]; ?></td>
                                <td><?php echo $_foreach["SEND_LIST.DATA"]["TO"]; ?></td>
                                <td><?php echo $_foreach["SEND_LIST.DATA"]["TEXT"]; ?></td>
                                <!--<td><?php echo $_foreach["SEND_LIST.DATA"]["COST"]; ?></td>-->
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
