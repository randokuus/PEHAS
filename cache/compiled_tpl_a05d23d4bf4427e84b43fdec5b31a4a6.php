<?php defined("MODERA_KEY")|| die(); ?>                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $this->getTranslate("module_isic_card|title_cards"); ?></h2>
                        </div>
                        <ul class="userlist cardlist">
                            <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

                            <li>
                                <div class="img">
                                    <a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["IMAGE"]; ?></a>
                                </div>
                                <div class="control">
                                </div>
                                <div class="lead">
                                    <h4><?php echo $_foreach["DATA"]["PERSON_NAME_FIRST"]; ?> <?php echo $_foreach["DATA"]["PERSON_NAME_LAST"]; ?></h4>
                                    <small><?php echo $_foreach["DATA"]["CARD_TYPE_NAME"]; ?></small>
                                    <p><?php echo $this->getTranslate("module_isic_card_status|status"); ?>: <b class="ok"><?php echo $_foreach["DATA"]["STATE_NAME"]; ?></b></p>
                                </div>
                            </li>
                            <?php }} ?>

                        </ul>
                        <!--bpanel-->
                        <div class="bpanel">
                            <div class="bpinner">
                                <div class="bpleft">
                                    <a href="<?php echo $data["URL"]; ?>" class="icow inext"><?php echo $this->getTranslate("module_isic_card|all_cards"); ?></a>
                                </div>
                                <div class="bpright">
                                </div>
                            </div>
                        </div>
                        <!--/bpanel-->
                    </div>
                </div>
                <!--/box-->