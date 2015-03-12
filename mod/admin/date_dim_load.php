<?php

	# --------- Load date dimension ----------
	# 1  date_id          			int(11) PK		autonumeric
	# 2  date_db          			date			yyyy-mm-dd
	# 3  date_desc        			varchar(150)	en 'jS \of F Y'
	# 4  num_day_of_week  			tinyint(4)		1 Monday to 7 Sunday
	# 5  desc_day_of_week 			varchar(45)		en 'l' ! 0 sun to 6 sat
	# 6  num_day_of_month 			tinyint(4)		en 'd'
	# 7  num_month        			tinyint(4)		'm'
	# 8  desc_month       			varchar(45)		en 'F'
	# 9  num_week         			tinyint(4)		'W'
	# 10 first_day_week_date 		date			use function
	# 11 first_day_month_date 		date			use function
	# 12 last_day_week_date 		date			use function
	# 13 last_day_month_date 		date			use function
	# 14 first_day_week_ind 		char(1)			use function
	# 15 first_day_month_ind 		char(1)			use function
	# 16 last_day_week_ind 			char(1)			use function
	# 17 last_day_month_ind 		char(1)			use function
	# 18 week_day_ind     			char(1)			use function
	# 19 holiday_ind      			char(1)			NULL
	# 20 special_opening_time_ind 	char(1)			NULL
	# 21 year_month       			varchar(12)		substr from date_db
	# 22 day_month       	 		varchar(12)		substr from date_db
	# 23 year_month_date  			varchar(32)		date_db
		# New from 06/02/2012
	# 24 num_day_4month				smallint(4)		use function
	# 25 num_4month					smallint(6)		since begining of time
	# 26 num_4month_of_year			tinyint(4)		in the year
	# 27 first_day_4month_date		date			easy
	# 28 last_day_4month_date		date			easy
	# 29 num_day_of_year			smallint(6)		1 to 366. NOT 0 to 365 !

	//echo 'jelou'; exit();
$start_date = new my_date('2025-06-24');
$end_date = new my_date('2025-12-31');
/*
$current_date = $start_date;
	
$count = 0;

while($current_date->odate <= $end_date->odate) {
$sql = 'INSERT INTO date_dim(date_db, date_desc, num_day_of_week, desc_day_of_week, num_day_of_month, num_month, desc_month, num_week, 
							first_day_week_date, first_day_month_date, last_day_week_date, last_day_month_date, first_day_week_ind, 
							first_day_month_ind, last_day_week_ind, last_day_month_ind, week_day_ind, `year_month`, day_month, year_month_date)
		VALUES (\''. $current_date->odate .'\', \''. date('jS \of F Y', $current_date->get_mktime()) .'\', \''. $current_date->get_weekday() .'\', \''. $current_date->get_weekday_desc('en') .'\', 
				\''. $current_date->day .'\', \''. $current_date->month .'\', \''. date('F', $current_date->get_mktime()) .'\', \''. date('W', $current_date->get_mktime()) .'\', 
				\''. first_day_of_week($current_date) .'\', \''. first_day_of_month($current_date) .'\', \''. last_day_of_week($current_date) .'\', \''. last_day_of_month($current_date) .'\', 
				\''. is_first_day_of_week($current_date) .'\', \''. is_first_day_of_month($current_date) .'\', \''. is_last_day_of_week($current_date) .'\', 
				\''. is_last_day_of_month($current_date) .'\', \''. is_weekday($current_date) .'\', \''. $current_date->year .'-'. $current_date->month .'\', 
				\''. $current_date->day .'-'. $current_date->month .'\', \''. $current_date->year .'-'. $current_date->month .'-'. $current_date->day .'\');';

	$ins_sql = my_query($sql, $conex);
	
	if($ins_sql)
		echo 'inserted '. $current_date->odate .'<br>';
		
	$current_date = $current_date->plus_days(1);
	$count++;
	if($count > 10000) break;
}
*/

$arr_4mths = array(1 => array('01-01', '04-30'),
				   2 => array('05-01', '08-31'),
				   3 => array('09-01', '12-31'));

$sql = 'SELECT date_db, date_id FROM date_dim WHERE date_db BETWEEN \''. $start_date->odate .'\' AND \''. $end_date->odate .'\' ORDER BY date_db;';

$sel_dates = my_query($sql, $conex);


$count = 0;

while($record = my_fetch_array($sel_dates)) {

	$current_date = new my_date($record['date_db'], 'es');
	
	$arr_upd = array('num_day_of_4month'	=> num_day_4month($current_date),
					 'num_4month'			=> num_4month($current_date),
					 'num_4month_of_year'	=> num_4month_of_year($current_date),
					 'first_day_4month_date'=> first_day_4month_date($current_date),
					 'last_day_4month_date'	=> last_day_4month_date($current_date),
					 'num_day_of_year'		=> num_day_of_year($current_date)
					 );
	
	update_array_db('date_dim', 'date_id', $record['date_id'], $arr_upd);

		 
	$count++;
	if($count > 2000)
		break;
}

function last_day_of_month($obj_date) {
	return $obj_date->year .'-'. $obj_date->month .'-'. date('t', $obj_date->get_mktime());
}

function is_last_day_of_month($obj_date) {
	return (date('j', $obj_date->get_mktime()) == date('t', $obj_date->get_mktime())) ? '1' : '0';
}

function first_day_of_month($obj_date) {
	return $obj_date->year .'-'. $obj_date->month .'-01';
}

function is_first_day_of_month($obj_date) {
	return ($obj_date->day == '01') ? '1' : '0';
}

function first_day_of_week($obj_date) {	# Monday, 1
	$week_day = $obj_date->get_weekday();
	$ret_obj = $obj_date->plus_days(-$week_day + 1);
	return $ret_obj->odate;
}

function is_first_day_of_week($obj_date) {	# Monday, 1
	return ($obj_date->get_weekday() == 1) ? '1' : '0';
}

function last_day_of_week($obj_date) {	# Sunday, 7
	$week_day = $obj_date->get_weekday();
	$ret_obj = $obj_date->plus_days(7 - $week_day);
	return $ret_obj->odate;
}

function is_last_day_of_week($obj_date) {	# Sunday, 7
	return ($obj_date->get_weekday() == 7) ? '1' : '0';
}

function is_weekday($obj_date) {
	return ($obj_date->get_weekday() == 6 || $obj_date->get_weekday() == 7) ? '0' : '1';
}

function week_number($obj_date) {
	return date('W', $obj_date->get_mktime());
}

function count_days( $a, $b )
{
	$gd_a = explode('-', $a);
	$gd_b = explode('-', $b);

    $a_new = mktime( 12, 0, 0, $gd_a[1], $gd_a[2], $gd_a[0] );
    $b_new = mktime( 12, 0, 0, $gd_b[1], $gd_b[2], $gd_b[0] );

    return round( abs( $a_new - $b_new ) / 86400 );
} 


function num_day_4month($current_date) {
	global $arr_4mths;
	# day number within a 4 month period
	$num_4month = num_4month_of_year($current_date);

	return count_days($current_date->odate, $current_date->year .'-'. $arr_4mths[$num_4month][0]) + 1;
}

function num_4month($current_date) {
	# since 2000
	$num_4month = num_4month_of_year($current_date);

	return (($current_date->year - 2000) * 3) + $num_4month;
}

function num_4month_of_year($current_date) {
	global $arr_4mths;

	$mth_day = $current_date->month .'-'. $current_date->day;
	//$year = $current_date->year;
	
	if($mth_day >= $arr_4mths[1][0] && $mth_day <= $arr_4mths[1][1]) {
		return 1;
	}
	elseif($mth_day >= $arr_4mths[2][0] && $mth_day <= $arr_4mths[2][1]) {
		return 2;
	}
	elseif($mth_day >= $arr_4mths[3][0] && $mth_day <= $arr_4mths[3][1]) {
		return 3;
	}
}

function first_day_4month_date($current_date) {
	global $arr_4mths;
	$num_4month = num_4month_of_year($current_date);

	return $current_date->year .'-'. $arr_4mths[$num_4month][0];
}

function last_day_4month_date($current_date) {
	global $arr_4mths;
	$num_4month = num_4month_of_year($current_date);

	return $current_date->year .'-'. $arr_4mths[$num_4month][1];
}

function num_day_of_year($current_date) {
	return count_days($current_date->odate, $current_date->year .'-01-01') + 1;
}

?>