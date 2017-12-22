<?php
require_once('../class/config.php');
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

require_once(SITE_PATH . '/class/Ehis/EhisUser.php');

$db = new DB();
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$data = array();
$ehisUser = new EhisUser();

$queryLines = explode("\n", $_POST['query']);
foreach($queryLines as $queryLine)
{
	$parts = explode(';', $queryLine);
	
	$isik = new EYL_IsikParing;
	$tmp = trim(@$parts[0]);
	$isik->isikukood = $tmp == '' ? null : $tmp;
	$tmp = trim(@$parts[1]);
	$isik->eesnimi = $tmp == '' ? null : $tmp;
	$tmp = trim(@$parts[2]);
	$isik->perenimi = $tmp == '' ? null : $tmp;
	$tmp = trim(@$parts[3]);
	$isik->synni_kp = $tmp == '' ? null : $tmp;
	
	$data[] = $isik;
}

$isSearch = @$_POST['search']=='Otsi';
$result->data = array();

if ($isSearch) {
    foreach ($data as $user) {
        $idList = $ehisUser->getStatusListByUser($user->isikukood);
        echo 'User: ' . $user->isikukood . ': ' . print_r($idList, true) . "\n";
    }
	
    $result = $ehisUser->getQueryResult();
	$requestHeaders = $ehisUser->getEhisClient()->getLastRequestHeaders();
	$request = $ehisUser->getEhisClient()->getLastRequest();
	$responseHeaders = $ehisUser->getEhisClient()->getLastResponseHeaders();
	$response = $ehisUser->getEhisClient()->getLastResponse();
}
?><html>
	<head>
			<title>EhisClient()</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<style>
				.resultsTable { border-collapse: collapse; }
				.resultsTable, .resultsTable td, .resultsTable th 
				{
					border: 1px solid darkgray;	
				}
			</style>
	</head>
	<body>
		<h2>Isikute otsing</h2>
		
		<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td colspan="2">Otsingu tingimused:</td>
					<tr>
						<td valign="top">							
							<textarea name="query" cols="40" rows="7"><?php echo @$_POST['query'] ?></textarea><br />
							<input type="submit" value="Otsi" name="search" />
						</td>
						<td valign="top" style="padding-left:20px">
								<p>Sisesta üks või mitu rida kujul:</p>
								<p>isikukood;eesnimi;perenimi;synni_kp</p>
								<p>Isikukoodide korral piisab ainult isikukoodi sisestamisest,<br/>mitme isikukoodi korral kõik eraldi ridadele.</p>
						</td>
					</tr>
				</table>
		</form>
		
		<?php 
			if($isSearch)
			{
				echo 'Leiti <strong>'.count($result->data).'</strong> tulemust';		
		?>
			<table class="resultsTable">
				<tr>
					<th rowspan="2">Isikukood</th>
					<th rowspan="2">Eesnimi</th>
					<th rowspan="2">Perenimi</th>
					<th rowspan="2">Sünnipäev</th>
					<th colspan="4">Õpetamine</th>
					<th colspan="9">Õppimine</th>
				</tr>
				<tr>
					<th>Kool</th>
					<th>ID</th>
					<th>Reg. nr</th>
					<th>Ametikohad</th>
					<th>Kool</th>
					<th>ID</th>
					<th>Reg. nr</th>
					<th>Klass</th>
					<th>ok_kood</th>
					<th>ok_nimetus</th>
					<th>Õppevorm</th>
					<th>Koormus</th>
					<th>Kursus</th>
				</tr>
				<?php foreach($result->data as $value) { 
						$opetamisi = count(@$value->opetamine);
						$oppimisi = count(@$value->oppimine_yld);
						$oppimisi2 = count(@$value->oppimine_korg);
						$span = max($opetamisi, $oppimisi, $oppimisi2);
						
						$rowSpan='';
						if($span > 1)
							$rowSpan = 'rowspan="' . $span . '"';
						if($span == 0)
							$span = 1;
						//var_dump($value->opetamine);

						

						for($i=0; $i < $span; $i++) {
							$opetamine =@ $value->opetamine[$i];
							if(is_array(@$value->oppimine_yld))
								$oppimine = $value->oppimine_yld[$i];
							else if(is_array(@$value->oppimine_korg))
								$oppimine = $value->oppimine_korg[$i];
							else if($i == 0 && $oppimine = @$value->oppimine_yld != null)
								$oppimine = @$value->oppimine_yld;
							else if($i == 0 && $oppimine = @$value->oppimine_korg != null)
								$oppimine = @$value->oppimine_korg;
							else 
								$oppimine = array();
					?>						
						<tr>
						<?php if($i==0) { ?>
							<td valign="top" <?php echo $rowSpan ?>><?php echo @$value->isikukood ?></td>
							<td valign="top" <?php echo $rowSpan ?>><?php echo @$value->eesnimi ?></td>
							<td valign="top" <?php echo $rowSpan ?>><?php echo @$value->perenimi ?></td>
							<td valign="top" <?php echo $rowSpan ?>><?php echo @$value->synni_kp ?></td>
						<?php } ?>
							<td><?php echo @$opetamine->oas_nimetus ?></td>
							<td><?php echo @$opetamine->oas_id ?></td>
							<td><?php echo @$opetamine->oas_regnr ?></td>
							<td><?php echo @$opetamine->ametikohad->ametikoht ?></td>
							<td><?php echo @$oppimine->oas_nimetus ?></td>
							<td><?php echo @$oppimine->oas_id ?></td>
							<td><?php echo @$oppimine->oas_regnr ?></td>
							<td><?php echo @$oppimine->klass ?></td>
							<td><?php echo @$oppimine->ok_kood ?></td>
							<td><?php echo @$oppimine->ok_nimetus ?></td>
							<td><?php echo @$oppimine->oppevorm ?></td>
							<td><?php echo @$oppimine->koormus ?></td>
							<td><?php echo @$oppimine->kursus ?></td>
						</tr>
					<?php } ?>
				<?php } ?>
			</table>
			
			<table width="100%">
				<tr>
					<th>Päringu päised</th>
					<th>Vastuse päised</th>
				</tr>
				<tr>
					<td width="50%"><textarea rows="10" style="width:100%"><?php echo $requestHeaders ?></textarea></td>
					<td width="50%"><textarea rows="10" style="width:100%"><?php echo $responseHeaders ?></textarea></td>
				</tr>
				<tr>
					<th>Päringu keha</th>
					<th>Vastuse keha</th>
				</tr>
				<tr>
					<td><textarea style="width:100%" rows="50"><?php echo $request ?></textarea></td>
					<td><textarea style="width:100%" rows="50"><?php echo $response ?></textarea></td>
				</tr>
			</table>
		<?php } ?>
	</body>
</html>
