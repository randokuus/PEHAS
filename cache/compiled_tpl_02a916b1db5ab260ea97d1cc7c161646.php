<?php defined("MODERA_KEY")|| die(); ?>        <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

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
                            <div class="col3">
                                <!--controlTable-->
                                <div class="formTable controlTable">
                                    <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
                                    <?php echo $data["HIDDEN"]; ?>
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_user|import_type"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_IMPORT_TYPE"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_user|group"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_GROUP"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_user|datafile"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_DATAFILE"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_user|separator"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_SEPARATOR"]; ?>
                                            </div>
                                        </div>
                                        <!--/fLine-->
                                        <!--fLine-->
                                        <div class="fLine">
                                            <div class="fHead">
                                                <?php echo $this->getTranslate("module_isic_user|title_row"); ?>:
                                            </div>
                                            <div class="fCell">
                                                <?php echo $data["FIELD_TITLE_ROW"]; ?>
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
                            <div class="col4 content">
                                <?php echo $this->getTranslate("module_isic_user|csv_import_description"); ?>
                            </div>
                            <!--/col4-->
                        </div>
                        <!--/boxcontent-->
                    </div>
                </div>
                <!--/box-->
        </div>
        <!--/singleCol-->
