<?php

$start_date = '2012-01-01';
$end_date = '2012-12-31';


$sql = 'SELECT date_id, date_db, week_day_ind FROM date_dim WHERE date_db BETWEEN \''. $start_date .'\' AND \''. $end_date .'\' ORDER BY date_db;';

$sel_dates = my_query($sql, $conex);

while($record = my_fetch_array($sel_dates)) {
	if($record['week_day_ind']) {
		$ot = '10:00';
		$ct = '23:00';
	}
	else {
		$ot = '09:00';
		$ct = '21:00';
	}
	
	$sql = 'INSERT INTO opening_times (date_id, date_db, open_from_1, open_to_2) VALUES ('. $record['date_id'] .', \''. $record['date_db'] .'\', \''. $ot .'\', \''. $ct .'\');';
	
	$ins_sql = my_query($sql, $conex);
	if($ins_sql)
		echo $sql. '<br>';
}

?>