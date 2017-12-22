<?php defined("MODERA_KEY")|| die(); ?><form method="post" action="<?php echo $data["FORM_ACTION"]; ?>" class="formpanel">

<?php if(isset($data["INFO"]) && is_array($data["INFO"])){ foreach($data["INFO"] as $_foreach["INFO"]){ ?>

<fieldset title="<?php echo $_foreach["INFO"]["TITLE"]; ?>">
<legend><?php echo $_foreach["INFO"]["TITLE"]; ?></legend>
    <table class="inputfield">
    <tr>
        <td><img src="pic/bullet_<?php echo $_foreach["INFO"]["TYPE"]; ?>.gif" alt="" border="0"></td>
        <td><label><?php echo $_foreach["INFO"]["INFO"]; ?></label></td>
    </tr>
    </table>
</fieldset>
<?php }} ?>


<fieldset id="fieldset1">
<legend><?php echo $data["LEGEND"]; ?></legend>

<table class="inputfield" id="translations-table">
    <tr>
        <td><label for="bunch_dir"><?php echo $data["BUNCH_DIR_LABEL"]; ?></label></td>
        <td>
            <select name="bunch_dir" id="bunch_dir">
                <?php echo $data["BUNCH_DIR_OPTIONS"]; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="process_subfolders"><?php echo $data["PROCESS_SUBFOLDERS"]; ?></label></td>
        <td><input type="checkbox" name="process_subfolders" id="process_subfolders" value="1"/></td>
    </tr>
    <tr>
        <td><label for="dest_dir"><?php echo $data["DEST_FOLDER_LABEL"]; ?></label></td>
        <td>
            <select name="dest_dir" id="dest_dir">
                <?php echo $data["DEST_FOLDER_OPTIONS"]; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="if_file_exists"><?php echo $data["IF_FILE_EXISTS_LABEL"]; ?></label></td>
        <td>
            <select name="if_file_exists" id="if_file_exists">
                <?php echo $data["IF_FILE_EXISTS_OPTIONS"]; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="2"><?php echo $data["IMAGE_SIZE_HEAD"]; ?></td>
    </tr>

    <tr>
        <td><label for="size_thumb"><?php echo $data["THUMBNAIL_SIZE_LABEL"]; ?></label></td>
        <td>
            <select name="size_thumb" id="size_thumb">
                <?php echo $data["THUMBNAIL_SIZE_OPTIONS"]; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="size_big"><?php echo $data["BIG_IMG_SIZE_LABEL"]; ?></label></td>
        <td>
            <select name="size_big" id="size_big">
                <?php echo $data["BIG_IMG_SIZE_OPTIONS"]; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="<?php echo $data["SIZE_ONLY_ID"]; ?>"><?php echo $data["SIZE_ONLY_LABEL"]; ?></label></td>
        <td><input type="checkbox" name="<?php echo $data["SIZE_ONLY_ID"]; ?>" id="<?php echo $data["SIZE_ONLY_ID"]; ?>" <?php echo $data["SIZE_ONLY_VALUE"]; ?>/></td>
    </tr>
    <tr>
        <td><label for="<?php echo $data["SIZE_CUST_X_ID"]; ?>"><?php echo $data["SIZE_CUST_X_LABEL"]; ?></label></td>
        <td><input type="text" name="<?php echo $data["SIZE_CUST_X_ID"]; ?>" id="<?php echo $data["SIZE_CUST_X_ID"]; ?>" value="<?php echo $data["SIZE_CUST_X_VALUE"]; ?>" size="5"/>
        </td>
    </tr>
    <tr>
        <td><label for="<?php echo $data["SIZE_CUST_Y_ID"]; ?>"><?php echo $data["SIZE_CUST_Y_LABEL"]; ?></label></td>
        <td><input type="text" name="<?php echo $data["SIZE_CUST_Y_ID"]; ?>" id="<?php echo $data["SIZE_CUST_Y_ID"]; ?>" value="<?php echo $data["SIZE_CUST_Y_VALUE"]; ?>" size="5"/>
        </td>
    </tr>
</table>

<br />
<div class="buttonbar">
    <button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SUMBIT_TXT"]; ?></button>
    <?php echo $data["ADDITIONAL_BUTTONS"]; ?>
</div>

</fieldset>

<?php if(isset($data["HIDDEN_FIELDS"]) && is_array($data["HIDDEN_FIELDS"])){ foreach($data["HIDDEN_FIELDS"] as $_foreach["HIDDEN_FIELDS"]){ ?>

    <input type="hidden" name="<?php echo $_foreach["HIDDEN_FIELDS"]["NAME"]; ?>" value="<?php echo $_foreach["HIDDEN_FIELDS"]["VALUE"]; ?>" />
<?php }} ?>


</form>
