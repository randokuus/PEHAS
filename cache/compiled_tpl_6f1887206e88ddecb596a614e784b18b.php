<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title>Modules navi</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script type="text/javascript" language="JavaScript"><!--

        function $()
        {
        	var elements = new Array();
        	for (var i = 0; i < arguments.length; i++) {
        		var element = arguments[i];
        		if (typeof element == 'string')
        			element = document.getElementById(element);
        		if (arguments.length == 1)
        			return element;
        		elements.push(element);
        	}
        	return elements;
        }

        /**
         * Select specified menu item and load page in right frame
         *
         * @param int parent_id
         * @param int child_id
         * @param string url item_type
         */
        function open_link(parent_id, child_id, url)
        {
            select_item(parent_id, child_id);
            if (url && parent.frames['right']) parent.frames['right'].document.location = url;
        }

        /**
         * Deselect menu items by type
         *
         * @param string type items type: 'parents', 'children', all
         */
        function deselect_items(type)
        {
            for (var i = 1; ; i++) {
                if (!$('parent-' + i.toString())) break;

                if ('parents' == type || 'all' == type) {
                    switch ($('parent-' + i.toString()).className) {
                        case 'parent opened highlighted':
                            $('parent-' + i.toString()).className = 'parent opened';
                            break;

                        case 'parent closed highlighted':
                            $('parent-' + i.toString()).className = 'parent closed';
                            break;

                        case 'parent highlighted':
                            $('parent-' + i.toString()).className = 'parent';
                            break;
                    }
                }

                if  ('children' == type || 'all' == type) {
                    for (var j = 1; ; j++) {
                        if (!$('child-' + i.toString() + '-' + j.toString())) break;
                        $('child-' + i.toString() + '-' + j.toString()).className = 'child';
                    }
                }
            }
        }

        /**
         * Select specified menu item
         *
         * @param int parent_id
         * @param int child_id
         * @param string item_type
         */
        function select_item(parent_id, child_id)
        {
            if (null == child_id) {
                // parent
                switch ($('parent-' + parent_id).className) {
                    case 'parent opened highlighted':
                        if ($('sub-' + parent_id)) {
                            $('parent-' + parent_id).className = 'parent closed highlighted';
                            $('sub-' + parent_id).style.display = 'none';
                        }
                        break;

                    case 'parent opened':
                        // close group
                        if ($('sub-' + parent_id)) {
                            $('parent-' + parent_id).className = 'parent closed';
                            $('sub-' + parent_id).style.display = 'none';
                        } else {
                            // highlight item
                            deselect_items('all');
                            $('parent-' + parent_id).className = 'parent opened highlighted';
                        }
                        break;

                    case 'parent closed highlighted':
                        if ($('sub-' + parent_id)) {
                            $('parent-' + parent_id).className = 'parent opened highlighted';
                            $('sub-' + parent_id).style.display = 'block';
                        }
                        break;

                    case 'parent closed':
                        if ($('sub-' + parent_id)) {
                            $('parent-' + parent_id).className = 'parent opened';
                            $('sub-' + parent_id).style.display = 'block';
                        }
                        break;
                }
            } else {
                //child
                deselect_items('all');
                $('parent-' + parent_id).className = 'parent opened highlighted';
                if ($('sub-' + parent_id)) $('sub-' + parent_id).style.display = 'block';
                $('child-' + parent_id + '-' + child_id).className = 'child active';
            }
        }

        /**
         * Select menu item by specified url
         *
         * @param string link
         */
        function select_by_link(link)
        {
            for (var i = 1; ; i++) {
                if (!$('parent-' + i.toString())) break;

                if (-1 != $('parent-' + i.toString()).childNodes[0].href.indexOf(link)) {
                    select_item(i, null);
                    break;
                }

                for (var j = 1; ; j++) {
                    if (!$('child-' + i.toString() + '-' + j.toString())) break;

                    if (-1 != $('child-' + i.toString() + '-' + j.toString()).childNodes[0].href.indexOf(link)) {
                        select_item(i, j);
                        return;
                    }
                }
            }
        }

    	/**
    	 * On page load event handler
    	 */
        function on_load()
    	{
    	    var preselected = '<?php echo $data["PRESELECTED_LINK"]; ?>';
    	    if (preselected) select_by_link(preselected);
    	}

	--></script>

	<link rel="stylesheet" href="main.css" type="text/css" media="all" />
	<style type="text/css" media="all">
        .children { display: none; }
    </style>
</head>
<body id="leftmenu-frame" onLoad="on_load()">
	<div class="verticalmenu">
		<?php if(isset($data["PARENT"]) && is_array($data["PARENT"])){ foreach($data["PARENT"] as $_foreach["PARENT"]){ ?>

			<div id="parent-<?php echo $_foreach["PARENT"]["ID"]; ?>" class="parent<?php echo $_foreach["PARENT"]["STYLE"]; ?>"><a href="javascript:open_link(<?php echo $_foreach["PARENT"]["ID"]; ?>, null, <?php echo $_foreach["PARENT"]["URL"]; ?>);"><?php echo $_foreach["PARENT"]["NAME"]; ?></a></div>

			<?php if(isset($_foreach["PARENT"]["CHILDREN"]) && is_array($_foreach["PARENT"]["CHILDREN"])){ foreach($_foreach["PARENT"]["CHILDREN"] as $_foreach["PARENT.CHILDREN"]){ ?>

				<div class="children" id="sub-<?php echo $_foreach["PARENT.CHILDREN"]["PARENT"]; ?>">

					<?php if(isset($_foreach["PARENT.CHILDREN"]["CHILD"]) && is_array($_foreach["PARENT.CHILDREN"]["CHILD"])){ foreach($_foreach["PARENT.CHILDREN"]["CHILD"] as $_foreach["PARENT.CHILDREN.CHILD"]){ ?>

						<div id="child-<?php echo $_foreach["PARENT.CHILDREN.CHILD"]["PARENT_ID"]; ?>-<?php echo $_foreach["PARENT.CHILDREN.CHILD"]["ID"]; ?>" class="child<?php echo $_foreach["PARENT.CHILDREN.CHILD"]["STYLE"]; ?>"><a href="javascript:open_link(<?php echo $_foreach["PARENT.CHILDREN.CHILD"]["PARENT_ID"]; ?>, <?php echo $_foreach["PARENT.CHILDREN.CHILD"]["ID"]; ?>, '<?php echo $_foreach["PARENT.CHILDREN.CHILD"]["URL"]; ?>');"><?php echo $_foreach["PARENT.CHILDREN.CHILD"]["NAME"]; ?></a></div>
					<?php }} ?>


				</div>
			<?php }} ?>


		<?php }} ?>

	</div>
</body>
</html>