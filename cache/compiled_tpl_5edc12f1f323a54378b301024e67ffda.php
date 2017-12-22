<?php defined("MODERA_KEY")|| die(); ?><?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

    <!--msgWrap-->
    <div class="msgWrap">
        <p class="msg msgError msgGray">
            <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
        </p>
    </div>
    <!--/msgWrap-->
<?php }} ?>


<!--col1-->
<div class="col1">
    <!--colInner-->
    <div class="colInner">
        <!--box-->
        <div class="box">
            <div class="inner">
                <div class="heading">
                    <h2><?php echo $this->getTranslate("module_messages|confirm"); ?></h2>
                </div>

                <!--formTable-->
                <div class="formTable">
                    <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
                        <?php echo $data["HIDDEN"]; ?>
                            <!--fRow-->
                            <div class="fRow">
                                <?php if(isset($data["FIELD"]) && is_array($data["FIELD"])){ foreach($data["FIELD"] as $_foreach["FIELD"]){ ?>

                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $_foreach["FIELD"]["TITLE"]; ?>:
                                    </div>
                                    <div class="fCell">
                                        <?php echo $_foreach["FIELD"]["DATA"]; ?>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                                <?php }} ?>

                            </div>
                            <!--/fRow-->

                            <!--fRow-->
                            <div class="fRow">
                                <!--fLine-->
                                <div class="fLine">
                                    <div class="fHead">
                                        <?php echo $this->getTranslate("module_messages|recipients"); ?>:
                                    </div>
                                    <div class="fCell">
                                        <table>
                                        <?php if(isset($data["RECIPIENT"]) && is_array($data["RECIPIENT"])){ foreach($data["RECIPIENT"] as $_foreach["RECIPIENT"]){ ?>

                                            <tr>
                                                <td><?php echo $_foreach["RECIPIENT"]["CHECKED"]; ?></td>
                                                <td><?php echo $_foreach["RECIPIENT"]["NAME"]; ?></td>
                                            </tr>
                                        <?php }} ?>

                                        </table>
                                    </div>
                                    <div class="fHint">

                                    </div>
                                </div>
                                <!--/fLine-->
                            </div>
                            <!--/fRow-->

                            <!--wButtons-->
                            <div class="wButtons">
                                <!--<input type="button" class="grayButton" onClick="document.location='<?php echo $data["URL_BACK_CONFIRM"]; ?>'" value="<?php echo $this->getTranslate("module_messages|back"); ?>" />-->
                                <input type="submit" value="<?php echo $data["BUTTON"]; ?>" />
                            </div>
                            <!--/wButtons-->
                    </form>
                </div>
                <!--/formTable-->
            </div>
            <!--/inner-->
        </div>
        <!--/box-->
    </div>
    <!--/colInner-->
</div>
<!--/col1-->
