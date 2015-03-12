<?php

	if(!$_GET['date']) $_GET['date'] = date('Y-m-d');
	
	$current_date = new my_date($_GET['date']);	# not the system date
	$now = new date_time('now');
	$now_plus_15 = $now->plus_mins(15);
	
	$arr_ots = simple_select('opening_times', 'date_db', $current_date->odate, array('open_from_1', 'open_to_2'));
	
	$opening_time = new date_time($current_date->odate, $arr_ots['open_from_1']);
	$closing_time = new date_time($current_date->odate, $arr_ots['open_to_2']);

	# Generate the hours array for this day
	$arr_hours = array();
	for($i = $opening_time->hour; $i < $closing_time->hour; $i++) {
		$arr_hours[$i + 0] = ($i + 0) .':00';
	}
	
//	pa($arr_hours);
	
	# Adjust the widths of all columns. The whole table must be 910 px.
	$first_col_w = 70; 	# this value may chage slightly after
	$full_table_w = 900;
	$space_4_hours = $full_table_w - $first_col_w;//		echo 'space_4_hours: '. $space_4_hours .'<br>';
	$hour_w = round($space_4_hours / count($arr_hours));//echo 'hour_w: '. $hour_w .'<br>';
	$space_4_hours = $hour_w * count($arr_hours);//		echo 'space_4_hours: '. $space_4_hours .'<br>';
	$first_col_w = $full_table_w - $space_4_hours;//		echo 'first_col_w: '. $first_col_w .'<br>';
	$pix_min = $space_4_hours / (($closing_time->timestamp - $opening_time->timestamp) / 60);//	echo 'pix_min: '. $pix_min .'<br>';
?>

<table border="0" cellpadding="4" cellspacing="0" width="<?= $full_table_w; ?>">
  <tr>
    <td align="right" width="<?= $first_col_w - 9; ?>" class="hours_table_cell" id="first_col"><div id="slot_container" style="position:relative;">
        <?php
	if($now->timestamp > $opening_time->timestamp && $now->timestamp < $closing_time->timestamp); {
		$mins_since_open = ($now->timestamp - $opening_time->timestamp) / 60;
		$now_pos = round($mins_since_open * $pix_min) + $first_col_w - 30;		// 	
	    echo '<div id="now_container" class="now_container_class" style="left:'. $now_pos .'px">'. $now->hour .':'. $now->minute .'</div>';
	}
	?>
      </div></td>
    <?php
		  # hours loop
		  $hour_w -= 9;	# remove 9 pixels to account for the cellpadding: 4 + 4 and the border width: 1
		  foreach($arr_hours as $key => $value) {
		  	echo '<td class="hours_table_cell" width="'. $hour_w .'" id="hour_col_'. $key .'" colspan="2">'. $value .'</td>';
		  }
		  ?>
  </tr>
  <?php
	# select courts
	$sql = 'SELECT c.court_id, c.name, c.court_type_desc, c.time_slot_min, t.slot_id, t.slot_starts, t.slot_ends, t.status, b.user_id, b.status as book_status, b.booking_id, b.slots_list
		FROM courts c
		INNER JOIN time_slots t	ON c.court_id = t.court_id
		LEFT JOIN (
			SELECT slot_id, user_id, status, booking_id, slots_list
			FROM bookings 
			WHERE (status IN (\'prebook\', \'waiting_players\') AND expire_datetime > \''. $now->datetime .'\')
			   OR status IN (\'confirmed\', \'paid\')
			  AND booking_datetime > \''. $current_date->odate .' 00:00:00\'
		) b ON b.slot_id = t.slot_id
		WHERE t.date_db = \''. $current_date->odate .'\'
		ORDER BY c.court_id, t.slot_id';
//	echo $sql; 
	$sel_courts = my_query($sql, $conex);
	$arr_courts_slots = array(); $arr_slots = array();

	if($current_date->odate == $now->odate->odate) {
		$arr_min_to_book = simple_select('configuration', 'config_code', 'min_advance_booking', array('config_value', 'value_type'));
		$last_time_to_book = $now->plus_mins($arr_min_to_book['config_value']);
	}

	$slots_list_arr = array();
	while($record = my_fetch_array($sel_courts)) {
		$slot_duration = $_GET['dur'];
		
		if($record['slots_list'])
			$slots_list_arr = explode(',', $record['slots_list']);
		
		if(in_array($record['slot_id'], $slots_list_arr)) {
			# these variables will keep the value through the loops if the slot id is in the last slots list
			$user_id = $record['user_id'] ? $record['user_id'] : $user_id;
			$book_status = $record['book_status'] ? $record['book_status'] : $book_status;
			$booking_id = $record['booking_id'] ? $record['booking_id'] : $booking_id;
		}
		else {
			$user_id = '';
			$book_status = '';
			$booking_id = '';
		}
		
		$arr_courts_slots[$record['court_id']]['info'] = array('name' => $record['name'],  'desc' => $record['court_type_desc']);
		$slot_starts = new date_time($current_date->odate, $record['slot_starts']);
		
		$arr_courts_slots[$record['court_id']]['times'][$record['slot_starts']] = array('slot_id' => $record['slot_id']
																					   ,'book_id' => $booking_id
																					   ,'slot_status' => $record['status']
																					   ,'user_id' => $user_id
																					   ,'book_status' => $book_status
																					  // ,'slots_list' => $record['slots_list']
																					   );
		
/*		if($last_time_to_book->timestamp < $slot_starts->timestamp)
			$arr_courts_slots[$record['court_id']]['slots'][$record['slot_id']] = array('slot_starts' => $record['slot_starts'],
																					   'slot_ends' => $record['slot_ends'],
																					   'status' => $record['status'],
																					   'user_id' => $record['user_id'],
																					   'book_status' => $record['book_status']);*/
	}
	
//pa($arr_courts_slots);
	foreach($arr_courts_slots as $court_id => $arr_slots) {
	?>
  <tr height="30">
    <td align="right" title="<?= $arr_slots['info']['desc']; ?>" class="hours_table_cell"><?= $arr_slots['info']['name']; ?></td>
    <?php
//		$slot_length = $arr_slots['info']['length'];
		if(!$arr_slots['times']) $arr_slots['times'] = array();
		$arr_times = $arr_slots['times'];
		
		foreach($arr_slots['times'] as $time_starts => $slot) {
			$ob_slot = new time_slot($slot['slot_id'], $time_starts);
			$arr_folowing_slots = $ob_slot->get_following_slots($_GET['dur'], $arr_times);
			$ob_time_slot = new date_time($current_date->odate, $time_starts);

			if($now_plus_15->timestamp >= $ob_time_slot->timestamp) {
				$slot['slot_status'] = 'md';	
			}
				
			$next_blocked = new my_time($ob_slot->get_next_blocked($arr_times));
			if(!isset($next_blocked->time))
				$next_blocked = new my_time($closing_time->otime);
			
			$ot_minus30 = $opening_time->plus_mins(-30);
			$prev_blocked = new my_time($ob_slot->get_prev_blocked($arr_times));
			if(!isset($prev_blocked->time))
				$prev_blocked = new my_time($ot_minus30->otime);
			
			$remaining_mins = $next_blocked->total_minutes - $ob_slot->get_time()->total_minutes;
			$time_before =    $ob_slot->get_time()->total_minutes - $prev_blocked->total_minutes - 30;

			$blocked_after = ($remaining_mins - $_GET['dur'] == 30) || ($remaining_mins - $_GET['dur'] < 0);
			$blocked_before = $time_before == 30;
			
			$is_blockded = $blocked_after || $blocked_before || $slot['book_id'] || $slot['slot_status'] == 'md';
//			$is_active = ($ob_slot->get_time()->total_minutes + $_GET['dur'] + 30) > $next_blocked->total_minutes;

			$half_h_w = round($hour_w / 2);
			$slots_str = implode(',', $arr_folowing_slots);

			$onclick = ''; $onmouseout = ''; $onmouseover = '';
			
			
			if($slot['slot_status'] == 'md')
				$class = 'ts_midday';
			else {
				switch($slot['book_status']) {
					case 'prebook':
						if($slot['user_id'] == $_SESSION['login']['user_id'])
							$class = 'ts_prebook';
						else
							$class = 'ts_unavailable';
					break;
					case 'confirmed': case 'paid':
						if($slot['user_id'] == $_SESSION['login']['user_id'])
							$class = 'ts_confirmed';
						else
							$class = 'ts_unavailable';
					break;
					default:
						if($_SESSION['login']['modules']['book']['write'] && !$is_blockded) {
							$onclick     = ' onclick="JavaScript:pre_book(\''. $slot['slot_id'] .'\')"';
							$onmouseover = ' onmouseover="JavaScript:activate_slots(\''. $slots_str .'\')"';
							$onmouseout  = ' onmouseout="JavaScript:deactivate_slots(\''. $slots_str .'\')"';
							$class = 'ts_free';
						}
						else
							$class = 'ts_free_read_only';
					break;
				}
			}
			
			$aval_str = $is_blockded ? 'no disponible' : 'Reservar '. $_GET['dur'] .' min. desde las '. $time_starts;
?>
    <td class="hours_table_cell <?= $class; ?>" valign="top" width="<?= $half_h_w; ?>" title="<?= $aval_str; ?>" id="<?= $slot['slot_id']; ?>"  <?= $onclick . $onmouseover . $onmouseout; ?>>
       	<input type="hidden" id="mark_<?= $slot['slot_id']; ?>" name="mark_<?= $slot['slot_id']; ?>" value="<?= $is_blockded ? 0 : 1; ?>" />
    </td>
<?php
		}	//	foreach($arr_slots['times'] as $time_starts => $slot) {
?>
  </tr>
<?php
	}	//	foreach($arr_courts_slots as $court_id => $arr_slots) {
	# now come the fares cells
?>
  <tr>
    <td class="hours_table_cell" align="right">Tarifas</td>
    <?php

	$is_member = $_SESSION['login']['is_member'] ? '1' : '0';
	$sql = 'SELECT time_starts, time_ends, fare, fare_name FROM fares WHERE date_db = \''. $current_date->odate .'\' AND is_member = \''. $is_member .'\' ORDER BY time_starts';

	$sel_fares = my_query($sql, $conex);
	while($record = my_fetch_array($sel_fares)) {
		$fare_starts = new date_time($current_date->odate, $record['time_starts']);
		$fare_ends = new date_time($current_date->odate, $record['time_ends']);
		
		$num_hours_fare = ($fare_ends->hour - $fare_starts->hour) * 2;
		
		echo '<td class="hours_table_cell" align="center" colspan="'. $num_hours_fare .'" title="'. ucfirst($record['fare_name']) .' (precio por persona y hora)">'. number_format($record['fare'], 2, '.', ' ') .' €</td>';
	}
?>
  </tr>
</table>
