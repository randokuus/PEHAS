<?php defined("MODERA_KEY")|| die(); ?><div class="cal">

<div class="cal-body">
	<a href="<?php echo $data["URL_PREVIOUS"]; ?>" class="left"><img src="pic/cal_left.gif" alt="" /></a>
	<a href="<?php echo $data["URL_NEXT"]; ?>" class="right"><img src="pic/cal_right.gif" alt="" /></a>
	<div class="month"><?php echo $data["CURRENT_MONTH"]; ?></div>
	<div class="days">
		<br class="fix1" />
		<?php echo $data["WEEKDAYS"]; ?>
		<?php echo $data["DAYS"]; ?>
		<br class="fix2" />
	</div>
</div>
<form name="datevorm" method="get" action="">
<input type=hidden name="year" value="<?php echo $data["YEAR"]; ?>">
<input type=hidden name="month" value="<?php echo $data["MONTH"]; ?>">
<input type=hidden name="day" value="<?php echo $data["DAY"]; ?>">
<input type=hidden name="field" value="<?php echo $data["FIELD"]; ?>">			
<input type=hidden name="type" value="<?php echo $data["TYPE"]; ?>">		
<div class="cal-time">
	<?php echo $this->getTranslate("admin_general|time"); ?>:
	<select name="hour">
		<?php echo $data["HOURS"]; ?>
	</select>
	:
	<select name="minute">
		<?php echo $data["MINUTES"]; ?>
	</select>
	<button class="cal-btn" onClick="ChooseDate()">OK</button>
</div>
</form>
</div>

