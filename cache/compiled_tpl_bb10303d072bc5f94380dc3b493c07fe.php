<?php defined("MODERA_KEY")|| die(); ?><?php if(isset($data["LOGO"]) && is_array($data["LOGO"])){ foreach($data["LOGO"] as $_foreach["LOGO"]){ ?>

<img src="<?php echo $_foreach["LOGO"]["PIC"]; ?>" border="0"/>
<?php }} ?>
