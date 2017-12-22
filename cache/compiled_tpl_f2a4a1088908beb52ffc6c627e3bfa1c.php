<?php defined("MODERA_KEY")|| die(); ?>            <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgError msgGray">
                    <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>

            
            

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2><h2><?php echo $data["MODULE_ISIC_CARD|USERS"]; ?></h2></h2>
                    </div>

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList">
                            <tr>
                                <th><?php echo $this->getTranslate("module_isic_user|row"); ?></th>
                                <?php if(isset($data["ROW_TITLE"]) && is_array($data["ROW_TITLE"])){ foreach($data["ROW_TITLE"] as $_foreach["ROW_TITLE"]){ ?>

                                <th><?php echo $_foreach["ROW_TITLE"]["TITLE"]; ?></th>
                                <?php }} ?>

                                <th></th>
                            </tr>
                            <?php if(isset($data["ROW"]) && is_array($data["ROW"])){ foreach($data["ROW"] as $_foreach["ROW"]){ ?>

                            <tr>
                              <td><?php echo $_foreach["ROW"]["ROW"]; ?></td>
                              <?php if(isset($_foreach["ROW"]["COL"]) && is_array($_foreach["ROW"]["COL"])){ foreach($_foreach["ROW"]["COL"] as $_foreach["ROW.COL"]){ ?>

                              <td><?php echo $_foreach["ROW.COL"]["DATA"]; ?></td>
                              <?php }} ?>

                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                </div>
            </div>
            <!--/box-->
