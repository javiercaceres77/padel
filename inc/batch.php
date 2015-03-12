<?php

# Includes  ----------------------------------
	
include 'config.php';
include 'comm.php';
include 'connect.php';
include 'oops_comm.php';
include 'oops_sc.php';

date_default_timezone_set($conf_timezone);

# Sanitize get and post  ----------------------------------
sanitize_input();
unset($_POST, $_GET);

# some general use objects --------------------------------
$today = new my_date('today');

# -------------------------------------------------------------------------- #
#                        PREPARE PARAMETERS TABLE                            #
# -------------------------------------------------------------------------- #

$days_in_advance = get_config_value('days_advance_booking_available');
$new_fares_date = $today->plus_days($days_in_advance);

# ------------------------------ F A R E S --------------------------------- #

update_array_db('configuration', 'config_code', 'batch_fares_table', array('config_value' => $new_fares_date->odate));

# ---------------------- O P E N I N G   T I M E S ------------------------- #

update_array_db('configuration', 'config_code', 'batch_opening_times_table', array('config_value' => $new_fares_date->odate));

# ------------------------- T I M E   S L O T S ---------------------------- #

update_array_db('configuration', 'config_code', 'batch_time_slots_table', array('config_value' => $new_fares_date->odate));

# ------------------- M E M B E R S H I P   B I L L S ---------------------- #

$days_in_advance_member = get_config_value('days_advance_membership_bills');
$new_member_date = $today->plus_days($days_in_advance_member);

update_array_db('configuration', 'config_code', 'batch_time_slots_table', array('config_value' => $new_fares_date->odate));

# -------------------------------------------------------------------------- #
#                     M E M B E R S H I P   B I L L S                        #
# -------------------------------------------------------------------------- #
$today_plus_7 = $today->plus_days(7);

$users_renew = member::get_members_up_for_renewal($today_plus_7->odate);

if(count($users_renew)) {
	foreach($users_renew as $user_id) {
		$ob_member = new member($user_id);
		if($ob_member->renew_membership())
			write_log_db('batch', 'renew_membership', 'User: '. $ob_member->user_id);
		else
			write_log_db('batch', 'renew_membership_error', 'User: '. $ob_member->user_id);
		
		echo "Renew membership ". $ob_member->user_id ."<br>\n";
	}
}

# --------------------------- PURGE PRE-BOOKS ------------------------------ #

$sql = 'DELETE FROM bookings WHERE expire_datetime < now() AND status = \'prebook\'';
$del_prebooks = my_query($sql, $conex);
if($del_prebooks)
	write_log_db('batch', 'purge_prebooks', 'Prebooks deleted');
else
	write_log_db('batch', 'purge_prebooks', 'Error - Prebooks deleted');


# -------------------------------------------------------------------------- #
#                                F A R E S                                   #
# -------------------------------------------------------------------------- #

# extract date_parameter from batch_parameters table.
$param_date = new my_date(get_config_value('batch_fares_table'));

# get newest date on fares table
$sel_max_date = my_query('SELECT max(date_db) as date_db FROM fares', $conex);
$max_date = new my_date(my_result($sel_max_date, 0, 'date_db'));

# if it doesn't have any values or is previous to today
if($max_date->odate == '0000-00-00' || $max_date->odate < $today->odate)
	$current_date = $today;
else
	$current_date = $max_date->plus_days(1);

while($current_date->odate <= $param_date->odate) {
	$sql = 'INSERT INTO fares (date_id, date_db, week_day_ind, holiday_ind, time_starts, time_ends, is_member, fare, fare_name)
SELECT dd.date_id, dd.date_db, dd.week_day_ind, dd.holiday_ind, f.time_starts, f.time_ends, f.is_member, f.fare, f.fare_name
FROM date_dim dd
INNER JOIN fares_conf f
   ON f.holiday_ind = dd.holiday_ind
  AND f.week_day_ind = dd.week_day_ind
WHERE dd.date_db = \''. $current_date->odate .'\'';

//echo $sql .'<br><br>';
	$insert_fares = my_query($sql, $conex);
	if($insert_fares)
		write_log_db('batch', 'fares', 'Fares inserted for '. $current_date->odate);
	else
		write_log_db('batch', 'fares', 'Error - Fares inserted for '. $current_date->odate);

	echo 'Fares: '. $current_date->odate ."<br>\n";
	$current_date = $current_date->plus_days(1);
}


# -------------------------------------------------------------------------- #
#                        O P E N I N G   T I M E S                           #
# -------------------------------------------------------------------------- #

# extract date_parameter from batch_parameters table.
$param_date = new my_date(get_config_value('batch_opening_times_table'));

# get newest date on OT´s table
$sel_max_date = my_query('SELECT max(date_db) as date_db FROM opening_times', $conex);
$max_date = new my_date(my_result($sel_max_date, 0, 'date_db'));

# if it doesn't have any values or is previous to today
if($max_date->odate == '0000-00-00' || $max_date->odate < $today->odate)
	$current_date = $today;
else
	$current_date = $max_date->plus_days(1);

$sql = 'INSERT INTO opening_times (date_id, date_db, open_from_1, open_to_1, open_from_2, open_to_2, description)
SELECT dd.date_id, dd.date_db, ot.open_from_1, ot.open_to_1, ot.open_from_2, ot.open_to_2, ot.description
FROM date_dim dd
INNER JOIN opening_times_conf ot
   ON ot.holiday_ind = dd.holiday_ind
  AND ot.week_day_ind = dd.week_day_ind
WHERE dd.date_db BETWEEN \''. $current_date->odate .'\' AND \''. $param_date->odate .'\'';

$insert_ots = my_query($sql, $conex);
if($insert_ots)
	write_log_db('batch', 'opening_times', 'Opening times inserted from '. $current_date->odate .' to '. $param_date->odate);
else
	write_log_db('batch', 'opennig_times', 'Error -  to insert Opening times from '. $current_date->odate .' to '. $param_date->odate);

echo 'Opening Times: '. $current_date->odate ."<br>\n";

# ------------------------ SPECIAL OPENING TIMES --------------------------- #
# this overwrites the previous opening times.
$sql = 'SELECT * FROM opening_times_special WHERE date_db BETWEEN \''. $current_date->odate .'\' AND \''. $param_date->odate .'\'';
$sel_special_ots = my_query($sql, $conex);

while($record = my_fetch_array($sel_special_ots)) {
	print_array($record);
	$arr_upd = array('open_from_1' => $record['open_from_1'],
					 'open_to_1' => $record['open_to_1'],
					 'open_from_2' => $record['open_from_2'],
					 'open_to_2' => $record['open_to_2'],
					 'description' => $record['description']);
	$upd_ots = update_array_db('opening_times', 'date_id', $record['date_id'], $arr_upd);
	if($upd_ots)
		write_log_db('batch', 'opening_times', 'Special Opening time inserted for '. $record['date_db']);
	else
		write_log_db('batch', 'opening_times', 'Error - Special Opening time inserted for '. $record['date_db']);
	
	echo 'Special Opening time: '. $record['date_db'] ."<br>\n";
}



# -------------------------------------------------------------------------- #
#                          T I M E   S L O T S                               #
# -------------------------------------------------------------------------- #

# extract date_parameter from batch_parameters table.
$param_date = new my_date(get_config_value('batch_time_slots_table'));

# get newest date on TS´s table
$sel_max_date = my_query('SELECT max(date_db) as date_db FROM time_slots', $conex);
$max_date = new my_date(my_result($sel_max_date, 0, 'date_db'));

# if it doesn't have any values or is previous to today
if($max_date->odate == '0000-00-00' || $max_date->odate < $today->odate)
	$current_date = $today;
else
	$current_date = $max_date->plus_days(1);

$sql = 'SELECT c.court_id, c.time_slot_min, ot.date_id, ot.date_db, ot.open_from_1, ot.open_to_1, ot.open_from_2, ot.open_to_2
FROM opening_times ot
CROSS JOIN courts c
WHERE ot.date_db BETWEEN \''. $current_date->odate .'\' AND \''. $param_date->odate .'\'
ORDER BY ot.date_db, c.court_id';

$sel_dates = my_query($sql, $conex); 

while($record = my_fetch_array($sel_dates)) {
//	$arr_slots = calculate_slots($record['open_from_1'], $record['open_to_2'], $record['time_slot_min']);
//	$arr_slots = calculate_slots($record['open_from_1'], $record['open_to_1'], $record['open_from_2'], $record['open_to_2'], $record['time_slot_min']);
	# 3rd version of calculate_slots function now marks slots as midday as needed
	$arr_slots = calculate_slots($record['open_from_1'], $record['open_to_1'], $record['open_from_2'], $record['open_to_2']);

	$sql = 'INSERT INTO time_slots (date_id, date_db, slot_starts, slot_ends, court_id, time_slot_min, status) VALUES ';
	$first = true;
	foreach($arr_slots as $slot) {
		$slot_starts = new date_time(date('Y-m-d'), $slot['start']);
		$slot_ends = new date_time(date('Y-m-d'), $slot['end']);
		$time_slot_min = ($slot_ends->timestamp - $slot_starts->timestamp) / 60;
		
		if($first)
			$first = false;
		else
			$sql.= ', ';
		
		$sql.= '(\''. $record['date_id'] .'\', \''. $record['date_db'] .'\', \''. $slot['start'] .'\', \''. $slot['end'] .'\', \''. $record['court_id'] .'\', '. $time_slot_min .', \''. $slot['period'] .'\')';
	}
	
	$ins_tss = my_query($sql, $conex);
	if($ins_tss)
		write_log_db('batch', 'time_slots', 'Time slots inserted for '. $record['date_db'] .' on court '. $record['court_id']);
	else
		write_log_db('batch', 'time_slots', 'Error - Time slots inserted for '. $record['date_db'] .' on court '. $record['court_id']);
	
	echo 'Time Slots: '. $record['date_db'] ."<br>\n";

}


# ------------------------ BLOCKS AND AUTO-BOOKS --------------------------- #





# ------------------------ AUXILIARY FUNCTIONS --------------------------- #


function calculate_slots($start1, $end1, $start2, $end2, $length = 30) {
	$ot1 = new date_time(date('Y-m-d'), $start1);
	$ct1 = new date_time(date('Y-m-d'), $end1);
	$ot2 = new date_time(date('Y-m-d'), $start2);
	$ct2 = new date_time(date('Y-m-d'), $end2);

	$num_slots_1 = floor((($ct1->timestamp - $ot1->timestamp) / 60) / $length);
	$num_slots_2 = floor((($ct2->timestamp - $ot2->timestamp) / 60) / $length);
	$num_slots_md = floor((($ot2->timestamp - $ct1->timestamp) / 60) / $length);

	$ret_arr = array();
	for($i = 0; $i < $num_slots_1; $i++) {
		$slot_starts = $ot1->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime, 'period' => '');
	}

	for($i = 0; $i < $num_slots_md; $i++) {
		$slot_starts = $ct1->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime, 'period' => 'md');
	}

	
	for($i = 0; $i < $num_slots_2; $i++) {
		$slot_starts = $ot2->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime, 'period' => '');
	}

	return $ret_arr;
}

function calculate_slots_old2($start1, $end1, $start2, $end2, $length) {
	# this new version calculates the time slots with two periods.
	# back to the previous version
	$ot1 = new date_time(date('Y-m-d'), $start1);
	$ct1 = new date_time(date('Y-m-d'), $end1);
	$ot2 = new date_time(date('Y-m-d'), $start2);
	$ct2 = new date_time(date('Y-m-d'), $end2);
	
	$num_slots_1 = floor((($ct1->timestamp - $ot1->timestamp) / 60) / $length);
	$num_slots_2 = floor((($ct2->timestamp - $ot2->timestamp) / 60) / $length);
	
	$ret_arr = array();
	for($i = 0; $i < $num_slots_1; $i++) {
		$slot_starts = $ot1->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime);
	}

	# if there is at least one hour for the ending of the period, insert a 1 hour slot at the end.
	if($ct1->timestamp - $slot_ends->timestamp >= 3600) {
		$new_slot_ends = $slot_ends->plus_mins(60);
		$ret_arr[] = array('start' => $slot_ends->otime, 'end' => $new_slot_ends->otime);
	}
	
	for($i = 0; $i < $num_slots_2; $i++) {
		$slot_starts = $ot2->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime);
	}
	
	# if there is at least one hour for the ending of the period, insert a 1 hour slot at the end.
	if($ct2->timestamp - $slot_ends->timestamp >= 3600) {
		$new_slot_ends = $slot_ends->plus_mins(60);
		$ret_arr[] = array('start' => $slot_ends->otime, 'end' => $new_slot_ends->otime);
	}
	
	return $ret_arr;
}

function calculate_slots_old($start, $end, $length) {
	$opening_time = new date_time(date('Y-m-d'), $start);
	$closing_time = new date_time(date('Y-m-d'), $end);
	$num_slots = floor((($closing_time->timestamp - $opening_time->timestamp) / 60) / $length);
	
	$ret_arr = array();
	for($i = 0; $i < $num_slots; $i++) {
		$slot_starts = $opening_time->plus_mins($i * $length);
		$slot_ends = $slot_starts->plus_mins($length);
		
		$ret_arr[$i] = array('start' => $slot_starts->otime, 'end' => $slot_ends->otime);
	}
	
	# if there is at least one hour for the ending of the period, insert a 1 hour slot at the end.
	if($closing_time->timestamp - $slot_ends->timestamp >= 3600) {
		$new_slot_ends = $slot_ends->plus_mins(60);
		$ret_arr[] = array('start' => $slot_ends->otime, 'end' => $new_slot_ends->otime);
	}
	
	return $ret_arr;
}

?>