<?php defined("MODERA_KEY")|| die(); ?>        <!--col1-->
        <div class="col1">
            <!--colInner-->
            <div class="colInner">
                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $this->getTranslate("module_news|news"); ?></h2>
                        </div>
                        
                        <ul class="newslist">
                        <?php if(isset($data["ARTICLE"]) && is_array($data["ARTICLE"])){ foreach($data["ARTICLE"] as $_foreach["ARTICLE"]){ ?>

                            <li>
                                <div>
                                    <h4><a href="<?php echo $_foreach["ARTICLE"]["URL"]; ?>"><?php echo $_foreach["ARTICLE"]["TITLE"]; ?></a></h4>
                                    <small><?php echo $_foreach["ARTICLE"]["DATE"]; ?></small>
                                    <p><?php echo $_foreach["ARTICLE"]["LEAD"]; ?></p>
                                </div>
                            </li>
                        <?php }} ?>

                        </ul>

                        <!--bpanel-->
                        <div class="bpanel">
                            <div class="bpinner">
                                <div class="bpleft">
                                    <a href="<?php echo $data["ARCHIVE_URL"]; ?>" class="icow inext"><?php echo $this->getTranslate("module_news|archive"); ?></a>
                                </div>
                                <div class="bpright">
                                </div>
                            </div>
                        </div>
                        <!--/bpanel-->

                    </div>
                </div>
                <!--/box-->

            </div>
            <!--/colInner-->
        </div>
        <!--/col1-->
