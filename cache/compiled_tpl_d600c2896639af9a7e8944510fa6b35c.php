<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript"><!--

function getElementsByClass(searchClass,node,tag) {
    var i, j;
    var classElements = new Array();
    if ( node == null )
        node = document;
    if ( tag == null )
        tag = '*';
    var els = node.getElementsByTagName(tag);
    var elsLen = els.length;
    var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
    for (i = 0, j = 0; i < elsLen; i++) {
        if ( pattern.test(els[i].className) ) {
            classElements[j] = els[i];
            j++;
        }
    }
    return classElements;
}

var toggle_plurals = function(plurals_enabled) {
    var els, i;

    els = getElementsByClass('plural-ctrl', document.getElementById('translations-table'));
    for (i = 0; i < els.length; i++) {
       if (plurals_enabled) {
           els[i].style.display = '';
       } else if ('none' != els[i].style.display){
           els[i].style.display = 'none';
       }
    }

}

var process_domain = function(preserve_domain) {
    var domain_sel = document.getElementById('domain-sel');
    if ('_new-domain' == domain_sel.value) {
        // enable new domain text input
        var domain_txt = document.getElementById('domain');
        domain_txt.style.color = '#000000';
        domain_txt.disabled = false;
        if (!preserve_domain) domain_txt.value = '';
    } else {
        // disable new domain text input
        var domain_txt = document.getElementById('domain');
        domain_txt.style.color = '#999999';
        domain_txt.disabled = true;
        domain_txt.value = domain_sel.value;
    }
}

--></script>

<form method="post" action="<?php echo $data["FORM_ACTION"]; ?>" class="formpanel">

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
        <td><label for="token"><?php echo $data["TOKEN"]; ?></label></td>
        <td><input type="text" name="token" id="token" size="30" maxlength="50" value="<?php echo $data["TOKEN_VALUE"]; ?>" /></td>
    </tr>
    <tr>
        <td><label for="domain"><?php echo $data["DOMAIN"]; ?></label></td>
        <td>
            <select name="domain-sel" id="domain-sel" onChange="process_domain();">
                <option value="_new-domain"><?php echo $data["NEW_DOMAIN"]; ?></option>
                <?php echo $data["DOMAIN_OPTIONS"]; ?>
            </select>
            <input type="text" name="domain" id="domain" size="25" maxlength="50" value="<?php echo $data["DOMAIN_VALUE"]; ?>" />
        </td>
    </tr>
    <tr>
        <td><label for="plural"><?php echo $data["HAS_PLURAL_FORMS"]; ?></label></td>
        <td><input type="checkbox" name="plural" id="plural" value="1" <?php echo $data["PLURAL_CHECKED"]; ?> onClick="toggle_plurals(this.checked);" /></td>
    </tr>

    <?php if(isset($data["LANGUAGE"]) && is_array($data["LANGUAGE"])){ foreach($data["LANGUAGE"] as $_foreach["LANGUAGE"]){ ?>

    <tr>
        <td><label for="<?php echo $_foreach["LANGUAGE"]["LANGUAGE_CODE"]; ?>"><strong><?php echo $_foreach["LANGUAGE"]["LANGUAGE_NAME"]; ?></strong><br />
            <span class="plural-ctrl"><?php echo $_foreach["LANGUAGE"]["PLURAL_FORM_DESCR"]; ?></span></label></td>
        <td><textarea name="<?php echo $_foreach["LANGUAGE"]["LANGUAGE_CODE"]; ?>" id="<?php echo $_foreach["LANGUAGE"]["LANGUAGE_CODE"]; ?>" cols="40" rows="2"><?php echo $_foreach["LANGUAGE"]["TRANSLATION"]; ?></textarea></td>
    </tr>
        <?php if(isset($_foreach["LANGUAGE"]["LANGUAGE_PLURALS"]) && is_array($_foreach["LANGUAGE"]["LANGUAGE_PLURALS"])){ foreach($_foreach["LANGUAGE"]["LANGUAGE_PLURALS"] as $_foreach["LANGUAGE.LANGUAGE_PLURALS"]){ ?>

        <tr class="plural-ctrl">
            <td><label for="<?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["PLURAL_FORM_ID"]; ?>"><strong><?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["LANGUAGE_NAME"]; ?></strong><br />
                <span class="plural-ctrl"><?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["PLURAL_FORM_DESCR"]; ?></span></label></td>
            <td><textarea name="<?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["PLURAL_FORM_ID"]; ?>" id="<?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["PLURAL_FORM_ID"]; ?>" cols="40" rows="2"><?php echo $_foreach["LANGUAGE.LANGUAGE_PLURALS"]["TRANSLATION"]; ?></textarea></td>
        </tr>
        <?php }} ?>

    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <?php }} ?>


</table>

<br />
<div class="buttonbar">
    <button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SUMBIT_TXT"]; ?></button>
    <?php echo $data["ADDITIONAL_BUTTONS"]; ?>
</div>

<script type="text/javascript"><!--
    /* form initialization */
    toggle_plurals('' != '<?php echo $data["PLURAL_CHECKED"]; ?>' ? true : false);
    process_domain(true);
--></script>

</fieldset>

<TPLSUB:HIDDEN_FIELDS>
    <input type="hidden" name="<?php echo $data["NAME"]; ?>" value="<?php echo $data["VALUE"]; ?>" />
</TPLSUB:HIDDEN_FIELDS>

</form>
