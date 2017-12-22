<?php defined("MODERA_KEY")|| die(); ?><script type="text/javascript"><!--
/**
 * Perform action with all checkboxed at 'frm-search-results' form
 *
 * @param string action wether 'check' or 'uncheck'
 */
var checkboxes = function(action)
{
    var form, el;

    form = document.getElementById('frm-search-results');
    for (i = 0; i < form.elements.length; i++) {
        el = form.elements[i];
        if ('checkbox' == el.type) {
            if ('check' == action) {
                el.checked = true;
            } else if ('uncheck' == action) {
                el.checked = false;
            }
        }
    }

    on_checkbox_change();
}

var on_checkbox_change = function()
{
    var form, el, replace_btn, is_disabled;

    is_disabled = true;

    form = document.getElementById('frm-search-results');
    replace_btn = document.getElementById('replace-btn');
    approve_btn = document.getElementById('approve-btn');
    decline_btn = document.getElementById('decline-btn');

    for (i = 0; i < form.elements.length; i++) {
        el = form.elements[i];
        if ('checkbox' == el.type) {
            if (el.checked) {
                is_disabled = false;
                break;
            }
        }
    }

    if (approve_btn && decline_btn) {
        if (approve_btn.disabled) {
            if (!is_disabled) {
                approve_btn.disabled = false;
                decline_btn.disabled = false;
            }
        } else {
            if (is_disabled) {
                approve_btn.disabled = true;
                decline_btn.disabled = true;
            }
        }
    } else if (replace_btn) {
        if (replace_btn.disabled) {
            if (!is_disabled) replace_btn.disabled = false;
        } else {
            if (is_disabled) replace_btn.disabled = true;
        }
    }
}

/**
 * Load right frame content
 *
 * @param string location relative uri
 */
var load_right = function(location)
{
    var right = parent.frames[1].document;
    right.location.href = location;
}

/**
 * Load left navigation menu
 *
 * @param string location relative uri
 * @param int parent_id parent item id
 * @param int|NULL child_id child item id
 */
var load_left_navi = function(location)
{
    var left = parent.frames[0].document;
    left.location.href = location;
}

/**
 * Load left content navigation menu
 *
 */
var load_left_content = function()
{
    var left = parent.frames[0].document;
}

/**
 * Select specified tab on top menu
 *
 * @param int tab_num
 */
var select_top_tab = function(tab_num)
{
    var top = parent.parent.frames[0].document;
    top.getElementById('menu1').className = '';
    top.getElementById('menu' + tab_num).className = 'active';
}

--></script>
<style type="text/css">
    .datatable td a { color: #0000ff; }
</style>
<form method="post" action="<?php echo $data["FORM_SEARCH_ACTION"]; ?>" class="formpanel">
	<fieldset id="fieldset1" title="Search" >
	<legend><?php echo $data["SEARCH_FORM_TITLE"]; ?></legend>
		<table class="inputfield">
		<tr><td><label for="search" class="left"><?php echo $data["SEARCH_LABEL"]; ?>:&nbsp;</label>
		<input type="text" id="search" name="search" value="<?php echo $data["SEARCH_VAL"]; ?>" class="" maxlength="255" size="30">
		<label><?php echo $data["IN_LABEL"]; ?></label> <?php echo $data["OBJECTS_SELECT"]; ?>
		<button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["GO"]; ?></button></td></tr>
		</table>
    </fieldset>
</form>

<form method="post" action="<?php echo $data["FORM_REPLACE_ACTION"]; ?>" class="formpanel" name="frm-search-results" id="frm-search-results">
	<fieldset>
	<legend><?php echo $data["FORM_TITLE"]; ?></legend>

	    <input type="hidden" name="search" value="<?php echo $data["SEARCH_VAL"]; ?>" />
	    <input type="hidden" name="do" id="do" value="replace" />

        <div style="background-color: #ccc; padding: 5px; color: #999;">
          <?php if(isset($data["REPLACE_CTRLS"]) && is_array($data["REPLACE_CTRLS"])){ foreach($data["REPLACE_CTRLS"] as $_foreach["REPLACE_CTRLS"]){ ?>

              <label for="replace" class="left"><?php echo $_foreach["REPLACE_CTRLS"]["REPLACE_LABEL"]; ?>:&nbsp;</label>
              <input type="text" id="replace" name="replace" value="<?php echo $_foreach["REPLACE_CTRLS"]["REPLACE_VAL"]; ?>" class="" maxlength="255" size="30">
              <button id="replace-btn" disabled="disabled" type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $_foreach["REPLACE_CTRLS"]["REPLACE_CHECKED"]; ?></button>
              <br />
          <?php }} ?>

          <button type="button" onClick="checkboxes('check')"><?php echo $data["CHECK_ALL"]; ?></button>
          <button type="button" onClick="checkboxes('uncheck')"><?php echo $data["UNCHECK_ALL"]; ?></button>
          <?php if(isset($data["WORKFLOW_CTRLS"]) && is_array($data["WORKFLOW_CTRLS"])){ foreach($data["WORKFLOW_CTRLS"] as $_foreach["WORKFLOW_CTRLS"]){ ?>

            &nbsp;
            <button id="approve-btn" disabled="disabled" onclick="getElementById('do').value='approve';" type="submit">Approve</button>
            <button id="decline-btn" disabled="disabled" onclick="getElementById('do').value='decline';" type="submit">Decline</button>
          <?php }} ?>

        </div>

        <br />

        <?php if(isset($data["SEARCH_TBL"]) && is_array($data["SEARCH_TBL"])){ foreach($data["SEARCH_TBL"] as $_foreach["SEARCH_TBL"]){ ?>

    		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
    		<tr>
                <th><?php echo $_foreach["SEARCH_TBL"]["COL1_HDR"]; ?></th>
    			<th><?php echo $_foreach["SEARCH_TBL"]["COL2_HDR"]; ?></th>
    			<th><?php echo $_foreach["SEARCH_TBL"]["COL3_HDR"]; ?></th>
    			<th><?php echo $_foreach["SEARCH_TBL"]["COL4_HDR"]; ?></th>
    		</tr>
            <?php if(isset($_foreach["SEARCH_TBL"]["RESULT_ROW"]) && is_array($_foreach["SEARCH_TBL"]["RESULT_ROW"])){ foreach($_foreach["SEARCH_TBL"]["RESULT_ROW"] as $_foreach["SEARCH_TBL.RESULT_ROW"]){ ?>

    		<tr>
                <td><?php echo $_foreach["SEARCH_TBL.RESULT_ROW"]["COL1"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["SEARCH_TBL.RESULT_ROW"]["COL2"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["SEARCH_TBL.RESULT_ROW"]["COL3"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["SEARCH_TBL.RESULT_ROW"]["COL4"]; ?>&nbsp;</td>
    		</tr>
            <?php }} ?>

        <?php }} ?>


        <?php if(isset($data["WORKFLOW_TBL"]) && is_array($data["WORKFLOW_TBL"])){ foreach($data["WORKFLOW_TBL"] as $_foreach["WORKFLOW_TBL"]){ ?>

    		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
    		<tr>
                <th><?php echo $_foreach["WORKFLOW_TBL"]["COL1_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL2_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL3_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL4_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL5_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL6_HDR"]; ?></th>
    			<th><?php echo $_foreach["WORKFLOW_TBL"]["COL7_HDR"]; ?></th>
    		</tr>
            <?php if(isset($_foreach["WORKFLOW_TBL"]["RESULT_ROW"]) && is_array($_foreach["WORKFLOW_TBL"]["RESULT_ROW"])){ foreach($_foreach["WORKFLOW_TBL"]["RESULT_ROW"] as $_foreach["WORKFLOW_TBL.RESULT_ROW"]){ ?>

    		<tr>
                <td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL1"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL2"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL3"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL4"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL5"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL6"]; ?>&nbsp;</td>
    			<td><?php echo $_foreach["WORKFLOW_TBL.RESULT_ROW"]["COL7"]; ?>&nbsp;</td>
    		</tr>
            <?php }} ?>

        <?php }} ?>


		</table>
	</fieldset>
</form>