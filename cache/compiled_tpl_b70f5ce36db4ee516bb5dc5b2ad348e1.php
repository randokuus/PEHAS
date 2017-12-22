<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<head>
    <title>Content Admin</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <link rel="stylesheet" href="main.css" type="text/css" media="all" />

        <SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
        <!--
        function del(urliMeez) {
            conf = window.confirm('<?php echo $data["CONFIRMATION"]; ?>');
            if (conf) top.main.right.document.location = urliMeez;
        }
        function submitTo() {
            document.forms["vorm"].elements['submit_to'].value = '1';
            document.forms["vorm"].submit();
        }
        function navigateTo(urliMeez) {
            top.main.right.document.location = urliMeez;
        }
        // Open new window for the selection
        function newWindow(myurl, sizex, sizey) {
            var newWindow;
            var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=no,location=no,directories=no,width='+sizex+',height='+sizey;
            newWindow = window.open(myurl, "window", props);
            newWindow.focus();
        }
        // Disable main form
        function disableForm()
        {
            var l = document.forms['vorm'].elements.length;
            for (var i = 0; i < l; i++) {
                document.forms['vorm'].elements[i].disabled = true;
                document.forms['vorm'].elements[i].style.color = 'gray';
            }
        }

        //-->
        </SCRIPT>

        <script language="JavaScript" type="text/javascript" src="img/aliases.js"></script>

</head>

<body id="body-frame" onload="<?php echo $data["BODY_ONLOAD"]; ?>">

<div class="infopanel">
    <h1><?php echo $data["TITLE"]; ?></h1>
</div>

<div class="tabmenu-dark">
    <ul class="tabmenu-dark">
    <?php if(isset($data["TABS"]) && is_array($data["TABS"])){ foreach($data["TABS"] as $_foreach["TABS"]){ ?>

        <li id="tabset<?php echo $_foreach["TABS"]["ID"]; ?>" <?php echo $_foreach["TABS"]["CLASS"]; ?>><a href="<?php echo $_foreach["TABS"]["URL"]; ?>"><?php echo $_foreach["TABS"]["NAME"]; ?></a></li>
    <?php }} ?>

    </ul>
</div>

<?php echo $data["CONTENT"]; ?>

<script>
    <?php echo $data["FOOTER_JS"]; ?>
</script>

</body>
</html>
