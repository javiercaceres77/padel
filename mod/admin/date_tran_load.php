<?php

	# --------- Load date translation ----------
	# 1 date_id          			int(11) PK		FK
	# 2 date_desc       			varchar(150)	en 'jS \of F Y'; es use function
	# 3 week_day_desc  				varchar(45)		en 'l' ! 0 sun to 6 sat; es use funciton
	# 4 month_desc     				varchar(45)		en 'F'; es use function
	# 5 language       				char(2) PK		en / es
	# 6 holiday_desc   				varchar(45)		NULL
	# 7 date_db          			date			Y-m-d

$start_date = '2015-12-13';
$end_date = '2016-12-31';


$sql = 'SELECT date_id, date_db FROM date_dim WHERE date_db BETWEEN \''. $start_date .'\' AND \''. $end_date .'\' ORDER BY date_db;';

$sel_dates = my_query($sql, $conex);


$count = 0;

while($record = my_fetch_array($sel_dates)) {
	$current_date = new my_date($record['date_db'], 'es');
	
	$sql_es = 'INSERT INTO date_translations(date_id, language, date_desc, week_day_desc, month_desc, date_db) VALUES 
			('. $record['date_id'] .', \'es\', \''. $current_date->format_date('long') .'\', \''. $current_date->get_weekday_desc('es') .'\', 
			\''. $current_date->get_month_desc('es') .'\', \''. $record['date_db'] .'\');';
			
	$current_date = new my_date($record['date_db'], 'en');
	
	$sql_en = 'INSERT INTO date_translations(date_id, language, date_desc, week_day_desc, month_desc, date_db) VALUES 
			('. $record['date_id'] .', \'en\', \''. $current_date->format_date('long') .'\', \''. $current_date->get_weekday_desc('en') .'\', 
			\''. $current_date->get_month_desc('en') .'\', \''. $record['date_db'] .'\');';


	$ins_sql = my_query($sql_es, $conex);
	if($ins_sql)
		echo 'inserted es  '. $current_date->odate .'<br>';

	$ins_sql = my_query($sql_en, $conex);
	if($ins_sql)
		echo 'inserted en '. $current_date->odate .'<br>';
}

?>