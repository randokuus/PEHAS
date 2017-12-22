<?php defined("MODERA_KEY")|| die(); ?><SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function fieldsetInit(maxfs) {
    for (i = 0; i < maxfs; i++) {
        if ((i+1) != 1) {
            document.getElementById("fieldset"+(i+1)).style.display = "none";
        }
    }
}

function fieldJump(current, maxtab, jumpto) {
    for (i = 0; i < maxtab; i++) {
        document.getElementById("tabset"+(i+1)).className = "";
    }
    document.getElementById("tabset"+current).className = "active";

    document.location = jumpto;

    return;
}

function valueCheck(objToTest) {
    if (null == objToTest) {
        return false;
    }
    if ("undefined" == typeof(objToTest) ) {
        return false;
    }
    return true;

}

</SCRIPT>

<div class="formpanel">
    <fieldset title="Node properties">
    <legend><?php echo $data["FILTER"]; ?></legend>
        <table class="inputfield">
        <form action="<?php echo $this->getGlobals("PHP_SELF"); ?>" method="get">
        <tr>
            <td><label for="filter" class="left"><?php echo $data["FILTER"]; ?></label></td>
            <td><input type="text" id="filter" name="filter" value="<?php echo $data["VAL_FILTER"]; ?>" size="16" /></td>
                <?php if(isset($data["EXTRAFILTER"]) && is_array($data["EXTRAFILTER"])){ foreach($data["EXTRAFILTER"] as $_foreach["EXTRAFILTER"]){ ?>

                <td><label for="<?php echo $_foreach["EXTRAFILTER"]["ID"]; ?>" class="left"><?php echo $_foreach["EXTRAFILTER"]["LABEL"]; ?></label></td>
                <td><?php echo $_foreach["EXTRAFILTER"]["FIELD"]; ?></td>
                <?php }} ?>

            <td align="right"><label for="max_entries" class="left"><?php echo $data["DISPLAY"]; ?></label></td>
            <td><select id="max_entries" name="max_entries" onChange="this.form.submit()">
                <?php if(isset($data["ENTRIES"]) && is_array($data["ENTRIES"])){ foreach($data["ENTRIES"] as $_foreach["ENTRIES"]){ ?>

                    <option value="<?php echo $_foreach["ENTRIES"]["VALUE"]; ?>" <?php echo $_foreach["ENTRIES"]["SEL"]; ?>><?php echo $_foreach["ENTRIES"]["NAME"]; ?></option>
                <?php }} ?>

                </select></td>
            <td>&nbsp;</td>
            <td><button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SUBMIT"]; ?></button></td>
        </tr>
            <?php echo $data["HIDDEN"]; ?>
            <input type="hidden" name="sort" value="<?php echo $data["VAL_SORT"]; ?>">
            <input type="hidden" name="sort_type" value="<?php echo $data["VAL_SORT_TYPE"]; ?>">
            <input type="hidden" name="structure" value="<?php echo $data["VAL_STRUCTURE"]; ?>">
        </form>
        </table>
    </fieldset>
</div>

<!-- begin list -->

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
<!-- <caption><?php echo $data["CAPTION"]; ?>&nbsp;</caption> -->
<tr>
    <?php if(isset($data["HEADER"]) && is_array($data["HEADER"])){ foreach($data["HEADER"] as $_foreach["HEADER"]){ ?>

    <th class="<?php echo $_foreach["HEADER"]["STYLE"]; ?>"><a href="<?php echo $_foreach["HEADER"]["URL"]; ?>"><?php echo $_foreach["HEADER"]["NAME"]; ?></a></th>
    <?php }} ?>

    <th>&nbsp;</th>
    <th>&nbsp;</th>
</tr>
<?php if(isset($data["ROWS"]) && is_array($data["ROWS"])){ foreach($data["ROWS"] as $_foreach["ROWS"]){ ?>

<tr>
    <?php echo $_foreach["ROWS"]["COLUMNS"]; ?>
    <td align="right">&nbsp;<a href="<?php echo $this->getGlobals("PHP_SELF"); ?>?show=add&copyto=<?php echo $_foreach["ROWS"]["ID"]; ?>"><img src="pic/copy.gif" width="9" height="10" border="0" alt="Copy?"></a></td>
    <td align="right">&nbsp;<a href="javascript:del('<?php echo $this->getGlobals("PHP_SELF"); ?>?do=delete&id=<?php echo $_foreach["ROWS"]["ID"]; ?><?php echo $_foreach["ROWS"]["HIDDEN1"]; ?>')"><img src="pic/delete.gif" width="9" height="11" border="0" alt="Delete?"></a></td>
</tr>
<?php }} ?>

</table>

<?php if(isset($data["PAGES"]) && is_array($data["PAGES"])){ foreach($data["PAGES"] as $_foreach["PAGES"]){ ?>

    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
    <tr><td><br /><?php echo $_foreach["PAGES"]["PAGES"]; ?>: <?php echo $_foreach["PAGES"]["LINKS"]; ?></td></tr>
    </table>
<?php }} ?>


<p></p>
<!-- end list -->
