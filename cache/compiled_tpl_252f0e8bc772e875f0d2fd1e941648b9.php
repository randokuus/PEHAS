<?php defined("MODERA_KEY")|| die(); ?><script language="JavaScript" type="text/javascript">
<!--
var del_url = '';

function del(url) {
    del_url = url;
    var $dialog = $('<div></div>')
    .html('<?php echo $data["CONFIRMATION"]; ?>')
    .dialog({
        autoOpen: false,
        title: '<?php echo $this->getTranslate("module_isic_card|question"); ?>',
        resizable: false,
        modal: true,
        width: 450,
        buttons: {
            "<?php echo $this->getTranslate("module_isic_card|ok"); ?>": function() {
                $(this).dialog("close");
                document.location = del_url;
            },
            "<?php echo $this->getTranslate("module_isic_card|cancel"); ?>": function() {
                $(this).dialog("close");
            }
        }
    });
    $('div.ui-dialog').append('<i class="ll"><i></i></i><i class="rr"><i></i></i><i class="tt"><i></i></i><i class="bb"><i></i></i><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');
    $('div.ui-dialog div.ui-dialog-buttonpane span.ui-button-text').wrap('<span></span>');
    $dialog.dialog('open');
}
//-->
</script>

                <!--box-->
                <div class="box">
                    <div class="inner">
                        <div class="heading">
                            <h2><?php echo $this->getTranslate("module_isic_card|title_applications"); ?></h2>
                        </div>
                        <ul class="userlist cardlist">
                            <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

                            <li>
                                <div class="img">
                                    <a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["IMAGE"]; ?></a>
                                </div>
                                <div class="control">
                                    <?php if(isset($_foreach["DATA"]["MOD"]) && is_array($_foreach["DATA"]["MOD"])){ foreach($_foreach["DATA"]["MOD"] as $_foreach["DATA.MOD"]){ ?>
<a href="<?php echo $_foreach["DATA.MOD"]["URL"]; ?>" title="Edit" class="ico iedit">Edit</a><?php }} ?>

                                    <?php if(isset($_foreach["DATA"]["DEL"]) && is_array($_foreach["DATA"]["DEL"])){ foreach($_foreach["DATA"]["DEL"] as $_foreach["DATA.DEL"]){ ?>
<a href="<?php echo $_foreach["DATA.DEL"]["URL"]; ?>" title="Delete" class="ico idel">Delete</a><?php }} ?>

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
                                    <a href="<?php echo $data["URL_ALL"]; ?>" class="icow inext"><?php echo $this->getTranslate("module_isic_card|all_applications"); ?></a>
                                </div>
                                <div class="bpright">
                                    <a href="<?php echo $data["URL_ADD"]; ?>" class="icow iadd"><?php echo $this->getTranslate("module_isic_card|add_new_application"); ?></a>
                                </div>
                            </div>

                        </div>
                        <!--/bpanel-->

                    </div>
                </div>
                <!--/box-->
