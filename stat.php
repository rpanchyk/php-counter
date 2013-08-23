<?php

include_once dirname(__FILE__) . '/counter.php';
$ftc = new FireTrotCounter();

function ShowMain()
{
	// Type handling
	$type = addslashes(trim(strtolower($_REQUEST['type'])));
	if (!in_array($type, array('hours', 'days', 'months', 'years')))
		$type = 'hours';
	$type = ucfirst($type);
	
	// Show data
	ShowHeader($type);
	ShowChartHtmlBlock($type);
	ShowFooter();
}

function ShowHeader($type)
{
	if (empty($type))
		return;
	
	?>
		<div class="section">
		<ul class="tabs">
			<li class="<? echo ($type == 'Hours') ? 'current' : ''; ?>"><a href="stat.php?type=Hours" style="text-decoration:none; color:#777;">Hours</a></li>
			<li class="<? echo ($type == 'Days') ? 'current' : ''; ?>"><a href="stat.php?type=Days" style="text-decoration:none; color:#777;">Days</a></li>
			<li class="<? echo ($type == 'Months') ? 'current' : ''; ?>"><a href="stat.php?type=Months" style="text-decoration:none; color:#777;">Months</a></li>
			<li class="<? echo ($type == 'Years') ? 'current' : ''; ?>"><a href="stat.php?type=Years" style="text-decoration:none; color:#777;">Years</a></li>
		</ul>
	<?
}
function ShowFooter()
{
	?></div><?
}

function ShowChartHtmlBlock($statType)
{
	global $ftc;
	
	$dateStart = !empty($_REQUEST['date_start']) ? $_REQUEST['date_start'] : null;
	$dateEnd = !empty($_REQUEST['date_end']) ? $_REQUEST['date_end'] : null;
	
	// Write stat files
	$ftc->GetStatatistics($statType, $dateStart, $dateEnd);
	
	$dateStart = !is_null($dateStart) ? $dateStart : $ftc->dateStart[$statType];
	$dateEnd = !is_null($dateEnd) ? $dateEnd : $ftc->dateEnd[$statType];
	
	?>
	<div class="box">
		<table width="100%" border="0">
			<tr>
				<td width="70%"><? ShowChart($statType); ?></td>
				<td align="center"><? ShowChartForm($statType, $dateStart, $dateEnd); ?></td>
			</tr>
			<tr>
				<td width="70%"><? ShowTableIP($statType); ?></td>
				<td></td>
			</tr>
		</table>
	</div>
	<?
}
function ShowChart($statType, $width = 700, $height = 400)
{
	?>
	<div id="ftc_chart_<?=$statType?>">GRAPH</div>
	<script type="text/javascript">
	  var chart = new FusionCharts("chart/FCF_MSLine.swf", "ChId1", "<?=$width?>", "<?=$height?>");
	  chart.setDataURL("data/Stat<?=$statType?>.xml");
	  chart.render("ftc_chart_<?=$statType?>");
	</script>
	<?
}
function ShowChartForm($statType, $dateStart = '', $dateEnd = '')
{
	?>
	<script type="text/javascript">
		$(function() {
			$("#datepicker<?=$statType?>Start").datepicker({
				dateFormat: 'yy-mm-dd 00:00:00',
				changeMonth: true,
				changeYear: true
			});
			$("#datepicker<?=$statType?>End").datepicker({
				dateFormat: 'yy-mm-dd 00:00:00',
				changeMonth: true,
				changeYear: true
			});
		});
	</script>
	
	<div style="font-weight:bold;">Period by <?=$statType?></div>
	<div style="width:70%; height:2px; background-color:#AAA"></div>
	<form id="stat" method="POST" action="">
		<div class="">
			<table width="100%" border="0">
				<tr>
					<td align="center" width="50%" style="padding:5px;">Start</td>
					<td align="center" style="padding:5px;">End</td>
				</tr>
				<tr>
					<td align="center" style="padding:5px;"><input type="text" id="datepicker<?=$statType?>Start" value="<?=$dateStart?>" class="input_text" style="" /></td>
					<td align="center"><input type="text" id="datepicker<?=$statType?>End" value="<?=$dateEnd?>" class="input_text" style="" /></td>
				</tr>
				<tr>
					<td align="center" style="padding:5px;" colspan="2">
						<input type="hidden" name="type" value="<?=$statType?>" />
						<input type="submit" name="dosend" value="Show" style="width:50%" />
						<p><a href="stat.php?type=<?=$statType?>">Reset</a></p>
					</td>
				</tr>
			</table>
		</div>
	</form>
	<?
}
function ShowTableIP($statType)
{
	?><div style="font-size:20px; text-align:center; line-height:50px;">IP Statistics</div><?
	echo file_get_contents(ROOT_DIR . DS . DATA_DIR . DS . 'IpStat'.$statType.'.html');
	?><br /><?
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
	<meta http-equiv="Content-Language" content="ru-RU" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	
	<title>FireTrotCounter :: Web Design FireTrot Studio</title>
	
	<script language="javascript" src="chart/FusionCharts.js"></script>
	<script type="text/javascript" src="js/jquery.js"></script>
	
	<link rel="stylesheet" href="css/jquery.ui.all.css">
	<script type="text/javascript" src="js/jquery.ui.core.js"></script>
	<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
	
	<link rel="stylesheet" href="css/tablesorter.style.css" type="text/css" media="print, projection, screen" />
	<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
	
<style type="text/css">
/* <![CDATA[ */

* {margin: 0; padding: 0;}
body {margin: 30px; font: 13px/1.5 "Trebuchet MS", Tahoma, Arial;}
a {color: #0094D6;}
p {padding: 7px 0;}
h1 {font-size: 21px; font-weight: normal; margin: 0 0 30px;}

.section {
	width: 90%;
	background: #EFEFEF;
	margin: 0 5% 30px;
}
ul.tabs {
	height: 28px;
	line-height: 25px;
	list-style: none;
	border-bottom: 1px solid #DDD;
	background: #FFF;
}
.tabs li {
	float: left;
	display: inline;
	margin: 0 1px -1px 0;
	padding: 0 13px 1px;
	color: #777;
	cursor: pointer;
	background: #EFEFEF;
	border: 1px solid #E4E4E4;
	border-bottom: 1px solid #F9F9F9;
	position: relative;
}
.tabs li:hover,
.tabs li.current {
	color: #444;
	background: #FFF;
	padding: 0 13px 2px;
	border: 1px solid #D4D4D4;
	border-bottom: 1px solid #EFEFEF;
}
.box {
	/*display: none;*/
	border: 1px solid #D4D4D4;
	border-width: 0 1px 1px;
	background: #FFF;
	padding: 0 12px;
}
.box.visible {
	display: block;
}

/* ]]> */
</style>

<style type="text/css">
.input_text {
	border: 1px solid #AAA;
	background-color:#EFEFEF;
}
</style>

</head>
<body>

<div style="text-align:center; font-size:28px; font-weight:bold;">Statistics</div>
<? ShowMain(); ?>

</body>
</html>
