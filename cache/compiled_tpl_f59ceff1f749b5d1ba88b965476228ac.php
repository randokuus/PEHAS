<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<head>
	<title>Content Admin</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<link rel="stylesheet" href="main.css" type="text/css" media="all" />
	<style type="text/css">
        .datatable input, .datatable select { font-size: 100%; }
        .datatable th { color: #cc1100; }
        .datatable .token { white-space: nowrap; }
        .datatable .domain { white-space: nowrap; }
        .datatable a.delete { padding: 0 5px 0 5px; color: #000; }
        .datatable a.delete:hover { text-decoration: none; background-color: #ff0000; color: #fff; }
        .datatable td.untr{ background-color: #ffeeee; }
        .datatable td.plural-untr { background-color: #eeeeff; }
        .formpanel a { color: #0000ff; }
        .formpanel a.delete { padding: 0 3px 0 3px; color: #000; }
        .formpanel ul { margin: 0; list-style-type: square; }
        .formpanel li { list-style-position: inside; }
    </style>
	<script type="text/javascript"><!--
	    /**
	     * @param string url
	     * @param int width
	     * @param int height
	     */
        var popup = function(url, width, height)
        {
            var new_window, window_name;
            var now = new Date();

            window_name = Math.round(Math.random()*10000) + '_' + now.getHours()
                + '_' + now.getMinutes() + '_' + now.getSeconds();
            new_window = window.open(url, window_name, 'resizable=yes,scrollbars=yes,status=yes,toolbar=no,menubar=no,location=no,top='
                +(screen.availHeight - width) / 2+',left='+(screen.availWidth - width) / 2+',width='+width
                +',height='+height);
            if (window.focus) new_window.focus();
        }
	--></script>
</head>

<body id="body-frame">

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

</body>
</html>