<?php defined("MODERA_KEY")|| die(); ?><style>.edit {cursor:pointer;} </style>
<link rel="stylesheet" type="text/css" href="../js/ext/resources/css/ext-all.css" />
<script type="text/javascript" src="../js/ext/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="../js/ext/builds/formeditor.js"></script>
<script type="text/javascript" src="../js/editor.js"></script>

<script type="text/javascript">
Ext.onReady(function(){
    Ext.EditorFieldAjax.prototype
    var editor = new Ext.EditorFieldAjax(
             new Ext.form.TextArea({allowBlank: true, alignment: 'l', grow: true, growMax:250}),
             false, {autosize:true,
                     emptyText:'<?php echo $this->getTranslate("admin_langfiles|untranslated"); ?>',
                     buttons: {
                        accept: '<?php echo $this->getTranslate("admin_langfiles|btn_edit_translation"); ?>',
                        cancel: '<?php echo $this->getTranslate("admin_langfiles|btn_cancel"); ?>'
                    }});

    editor.addListener('beforrequest', function(editor, value){
        url = editor.boundEl.getAttributeNS('','name');
        editor.setUrl(url);
    });


    Ext.select('.edit').on('click',function (e){
        el = Ext.get(e.target);
        editor.startEditing(el, el.dom.innerHTML);
    });
});

</script>

<div class="formpanel">
<fieldset>
<table cellpadding="10" cellspacing="0" border="0" width="100%">
<tr>
    <td valign="top">
        <!-- default button -->
        <button style="width:0; height:0; margin: 0; padding: 0; visibility: hidden;">default button</button>
        <button onClick="javascript:popup('./settings_translator_popup.php?do=list_lang_columns', 450, 250);"><?php echo $data["MANAGE_LANG_COLS"]; ?></button> &nbsp;
        <button onClick="javascript:popup('./settings_translator_popup.php?do=compile', 450, 250);"><?php echo $data["COMPILE_FILES"]; ?></button>
    </td>
</tr>
</table>

<script type="text/javascript"><!--
    var clear_filters = function(form) {
        var el;
        for (i = 0; i < form.elements.length; i++) {
            el = form.elements[i];
            if ('text' == el.type) {
                el.value = '';
            } else if ('select-one' == el.type) {
                el.selectedIndex = 0;
            }
        }
    }
    changeFilterDomain = function(domain){
        var df = document.forms["vorm"]["filters[domain]"];
        for(var i=0; i < df.length; i++) {
            if (df[i].value == domain) {
                df.selectedIndex = i;
                document.forms["vorm"].submit();
            }
        }
    }
--></script>

</fieldset>
</div>

<form action="<?php echo $data["FORM_ACTION"]; ?>" method="post" style="display: inline;" name="vorm">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="datatable">

<thead>
<tr>
    <th width="1"><select name="filters[domain]" onChange="this.form.submit();" style="width: 150px;"><?php echo $data["DOMAINS_OPTIONS"]; ?></select></th>
    <th width="<?php echo $data["TOKEN_COL_WIDTH"]; ?>" class="<?php echo $data["TOKEN_CLASS"]; ?>"><a href="<?php echo $data["URL_SORT_BY_TOKEN"]; ?>"><?php echo $data["TOKEN"]; ?></a></th>
    <?php if(isset($data["LANG_HEADERS"]) && is_array($data["LANG_HEADERS"])){ foreach($data["LANG_HEADERS"] as $_foreach["LANG_HEADERS"]){ ?>

        <th width="<?php echo $_foreach["LANG_HEADERS"]["LANG_COL_WIDTH"]; ?>"><?php echo $_foreach["LANG_HEADERS"]["LANGUAGE"]; ?> <?php echo $_foreach["LANG_HEADERS"]["REMOVE_LANG"]; ?></th>
    <?php }} ?>

    <th>&nbsp;</th>
</tr>
<tr>
    <th><input type="reset" name="clear_filter" value="<?php echo $data["CLEAR_FILTERS"]; ?>" onClick="clear_filters(this.form); this.form.submit();" />
        <input type="submit" name="run_filter" value="<?php echo $data["RUN_FILTER"]; ?>" /></th>
    <td><input type="text" name="filters[token]" size="50" style="width: 100%;" value="<?php echo $data["FILTER_TOKEN_VALUE"]; ?>" /></td>
    <?php if(isset($data["LANG_FILTERS"]) && is_array($data["LANG_FILTERS"])){ foreach($data["LANG_FILTERS"] as $_foreach["LANG_FILTERS"]){ ?>

        <td><input type="text" name="filters[lang_<?php echo $_foreach["LANG_FILTERS"]["LANG_CODE"]; ?>]" size="50" style="width: 100%;" value="<?php echo $_foreach["LANG_FILTERS"]["FILTER_LANG_VALUE"]; ?>"/></td>
    <?php }} ?>

    <td>&nbsp;</td>
</tr>
<tr class="plinks" >
    <td colspan="<?php echo $data["TOTALCOLS"]; ?>" style="padding-top: 10px; padding-bottom: 5px;">
        <div style="float: left;"><?php echo $data["PAGER_INFO"]; ?></div>
        <div style="float: right; white-space: nowrap;"><?php echo $data["PAGER"]; ?></div>
    </td>
</tr>
<tr class="plinks" >
    <td colspan="<?php echo $data["TOTALCOLS"]; ?>" style="padding-top: 10px; padding-bottom: 5px;">
        <div style="float: left;"><?php echo $data["MENU_ROWS_ON_PAGE"]; ?></div>
        <div style="float: right; white-space: nowrap;"><?php echo $data["MENU_SHOW_TRANSLATIONS_TYPE"]; ?></div>
    </td>
</tr>
</thead>

<tbody>
<?php if(isset($data["TRANSLATION_ROW"]) && is_array($data["TRANSLATION_ROW"])){ foreach($data["TRANSLATION_ROW"] as $_foreach["TRANSLATION_ROW"]){ ?>

<tr class="<?php echo $_foreach["TRANSLATION_ROW"]["CLASS"]; ?>">
    <td><span style="color: #666; margin-right: 10px;"><?php echo $_foreach["TRANSLATION_ROW"]["NUM"]; ?>.</span>
        <a href="#" onclick="changeFilterDomain('<?php echo $_foreach["TRANSLATION_ROW"]["DOMAIN"]; ?>'); return false;"><?php echo $_foreach["TRANSLATION_ROW"]["DOMAIN"]; ?></a>
    </td>
    <td class="token"><?php echo $_foreach["TRANSLATION_ROW"]["TOKEN_ICON"]; ?>&nbsp;&nbsp;<a href="<?php echo $_foreach["TRANSLATION_ROW"]["URL_TOKEN"]; ?>"><?php echo $_foreach["TRANSLATION_ROW"]["TOKEN"]; ?></a></td>
    <?php if(isset($_foreach["TRANSLATION_ROW"]["LANGUAGES"]) && is_array($_foreach["TRANSLATION_ROW"]["LANGUAGES"])){ foreach($_foreach["TRANSLATION_ROW"]["LANGUAGES"] as $_foreach["TRANSLATION_ROW.LANGUAGES"]){ ?>

        <td class="<?php echo $_foreach["TRANSLATION_ROW.LANGUAGES"]["CLASS"]; ?>"><?php echo $_foreach["TRANSLATION_ROW.LANGUAGES"]["TD"]; ?></td>
    <?php }} ?>

    <td align="right"><a href="<?php echo $_foreach["TRANSLATION_ROW"]["URL_DELETE"]; ?>" onClick="return(confirm('<?php echo $_foreach["TRANSLATION_ROW"]["CONFIRM_DELETE"]; ?>'));"><img
        src="pic/delete.gif" width="9" height="11" border="0" alt="<?php echo $_foreach["TRANSLATION_ROW"]["DELETE"]; ?>" /></a></td>
</tr>
<?php }} ?>

</tbody>

<tfoot>
<tr class="plinks" >
    <td colspan="<?php echo $data["TOTALCOLS"]; ?>" style="padding-top: 10px; padding-bottom: 5px;">
        <div style="float: left;"><?php echo $data["MENU_ROWS_ON_PAGE"]; ?></div>
        <div style="float: right; white-space: nowrap;"><?php echo $data["MENU_SHOW_TRANSLATIONS_TYPE"]; ?></div>
    </td>
</tr>
<tr class="plinks">
    <td colspan="<?php echo $data["TOTALCOLS"]; ?>">
        <div style="float: left;"><?php echo $data["PAGER_INFO"]; ?></div>
        <div style="float: right; white-space: nowrap;"><?php echo $data["PAGER"]; ?></div>
    </td>
</tr>
</tfoot>

</table>
<form>