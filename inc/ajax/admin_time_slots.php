<?php

	$ob_user = new user($_SESSION['login']['user_id']);
	if(!$ob_user->is_admin()) {
		jump_to($conf_main_page);
		exit();
	}

	if(!$_GET['date']) $_GET['date'] = date('Y-m-d');
	
	$current_date = new my_date($_GET['date']);	# not the system date
	$now = new date_time('now');
	
	$arr_ots = simple_select('opening_times', 'date_db', $current_date->odate, array('open_from_1', 'open_to_2'));
	
	$opening_time = new date_time($current_date->odate, $arr_ots['open_from_1']);
	$closing_time = new date_time($current_date->odate, $arr_ots['open_to_2']);

	# Generate the hours array for this day
	$arr_hours = array();
	for($i = $opening_time->hour; $i < $closing_time->hour; $i++) {
		$arr_hours[$i + 0] = ($i + 0) .':00';
	}

	$sql = 'SELECT c.court_id, c.name, c.court_type_desc, c.time_slot_min, t.slot_id, t.slot_starts, t.status, /*t.slot_ends, */
				   b.user_id, b.status as book_status, b.full_name as user_name, b.booking_id, b.booking_fare, 
				   b.payment_method, b.is_member, b.expire_datetime, b.booking_datetime, b.booking_datetime, b.booking_ends_datetime, b.slots_list
		FROM courts c
		INNER JOIN time_slots t	ON c.court_id = t.court_id
		LEFT JOIN (
			SELECT b.slot_id, b.user_id, b.status, u.full_name, b.booking_id, b.booking_fare, b.payment_method, u.is_member, b.expire_datetime,
				   b.booking_datetime, b.booking_ends_datetime, b.slots_list
			FROM bookings b INNER JOIN users u ON u.user_id = b.user_id
			WHERE (status IN (\'prebook\', \'waiting_players\') AND expire_datetime > \''. $now->datetime .'\')
			   OR status IN (\'confirmed\', \'paid\')
			  AND booking_datetime > \''. $current_date->odate .' 00:00:00\'
		) b ON b.slot_id = t.slot_id
		WHERE t.date_db = \''. $current_date->odate .'\'
		ORDER BY c.court_id, t.slot_id';
//	echo $sql; 
	$sel_courts = my_query($sql, $conex);
	$arr_courts_slots = array(); $arr_slots = array();

/*	if($current_date->odate == $now->odate->odate) {
		$arr_min_to_book = simple_select('configuration', 'config_code', 'min_advance_booking', array('config_value', 'value_type'));
		$last_time_to_book = $now->plus_mins($arr_min_to_book['config_value']);
	}
*/
	while($record = my_fetch_array($sel_courts)) {
		$arr_courts[$record['court_id']] = array('name' => $record['name'],  'desc' => $record['court_type_desc']);//,  'length' => $record['time_slot_min']);
		
//		$slot_starts = new date_time($current_date->odate, $record['slot_starts']);
		
//		if($last_time_to_book->timestamp < $slot_starts->timestamp)
		$arr_courts_slots[$record['court_id']][$record['slot_id']] = array('booking_datetime' => $record['booking_datetime']
																		  ,'booking_ends_datetime' => $record['booking_ends_datetime']
																		  ,'slots_list' => $record['slots_list']
																		  ,'user_id' => $record['user_id']
																		  ,'user_name' => $record['user_name']
																		  ,'book_status' => $record['book_status']
																		  ,'booking_id' => $record['booking_id']
																		  ,'price' => $record['booking_fare']
																		  ,'payment_method' => $record['payment_method']
																		  ,'is_member' => $record['is_member']
																		  ,'expire' => $record['expire_datetime']
																		  ,'slot_starts' => $record['slot_starts']);
																		  
		$arr_courts_slots[$record['court_id']]['times'][$record['slot_starts']] = array('slot_id' => $record['slot_id']
																					   ,'book_id' => $record['booking_id']
																					   //,'slot_status' => $record['status'] # do not block at midday
																					   ,'user_id' => $record['user_id']
																					   ,'book_status' => $record['book_status']
																					  // ,'slots_list' => $record['slots_list']
																					   );

	}
	$num_courts = count($arr_courts_slots);

	$first_col_w = 50; 	# this value may chage slightly after
	$full_table_w = 900;
	$space_4_courts = $full_table_w - $first_col_w;		//echo 'space_4_courts: '. $space_4_courts .'<br>';
	$court_w = floor($space_4_courts / $num_courts);	//echo 'court_w: '. $court_w .'<br>';
	$space_4_courts = $court_w * $num_courts;			//echo 'space_4_courts: '. $space_4_courts .'<br>';
	$first_col_w = $full_table_w - $space_4_courts;		//echo 'first_col_w: '. $first_col_w .'<br>';

?>

<table border="0" cellpadding="4" cellspacing="0" width="100%">
  <tr>
    <td class="hours_table_cell" width="<?= $first_col_w; ?>">&nbsp;</td>
<?php	foreach($arr_courts as $court_id => $court) {	?>    
    <th class="hours_table_cell" width="<?= $court_w; ?>"><?= $court['name']; ?></th>
<?php	}	?>
  </tr>
  <tr>
    <td height="4"><div id="time_marker_container" style="position:relative;"><?php
	if($now->timestamp > $opening_time->timestamp && $now->timestamp < $closing_time->timestamp) {
		$mins_since_open = ($now->timestamp - $opening_time->timestamp) / 60;
		$now_top = round($mins_since_open) - 11;
	    echo '<div id="now_container" class="now_container_horizontal" style="top:'. $now_top .'px;">'. $now->hour .':'. $now->minute;
		$marker_width = $space_4_courts - 15;
		$marker_left = $first_col_w;
		echo '<div id="now_marker" style="position:absolute; left:'. $marker_left .'px; top:12px; width:'. $marker_width .'px; height:2px; background-color:#FF3300;"></div>';
		echo '</div>';
	}?></div></td>
<?php	foreach($arr_courts_slots as $court_id => $slots) {	?>    
    <td height="4"><div id="slots_containter" style="position:relative;">
    
<?php
			if(!$slots['times']) $slots['times'] = array();
			$arr_times = $slots['times'];

			foreach($slots as $slot_id => $slot) {
				if($slot['booking_id']) {
					# if there is a booking, don't show its other slots.
					$arr_other_slots = explode_keys(',', $slot['slots_list']);
					// ------------------------------
					
					$slot_starts = new date_time($slot['booking_datetime']);
					$slot_ends = new date_time($slot['booking_ends_datetime']);
					$slot_mins = ($slot_ends->timestamp - $slot_starts->timestamp) / 60;
					
					$slot_height = $slot_mins - 9;										//echo 'slot_height: '. $slot_height .'<br>';
					$slot_width  = $court_w - 13;													//echo 'slot_width: '. $slot_width .'<br>';
					$mins_since_open = ($slot_starts->timestamp - $opening_time->timestamp) / 60;	//echo 'hours_since_open: '. $hours_since_open .'<br>';
					$slot_top    = $mins_since_open + 8;
					
					$div_class = '';
					$div_text = '';
					
					if($slot['book_status'] == 'confirmed') {
						$div_class = 'ad_ts_unpaid';
							$div_text = '<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $slot['user_id'] .'">'. shorten_str($slot['user_name'], 18) .'</a>';
							$div_text.= ' ('. $slot['user_id'] .')';
							if($slot['is_member'])
								$div_text.= ' <img src="'. $conf_images_path .'user16.png" height="12" width="12" title="Socio" align="absmiddle" />';
	
							if($slot['payment_method'] == 'ccc' || $slot['payment_method'] == 'card' || $slot['payment_method'] == 'paypal' || $slot['payment_method'] == 'google')
								$div_text.='<br><span class="small_text">Pagado '. print_money($slot['price']) .' (pre-pago)</span>';
							elseif($slot['payment_method'] == 'bonus')
								$div_text.= '<br><span class="small_text">Pagado BONO (1 h.)</span>';
							elseif($slot['payment_method'] == 'cash')
								$div_text.='<br><span class="small_text">Pagado '. print_money(0) .' (efectivo)</span>';
							
							
							//$div_text.= '<br><span class="small_text" style="color:#CC3333;"><strong>'. print_money($slot['price']) .'</strong></span>';
							$div_text.= '<div align="center">
							<input type="button" class="button_tiny" name="edit_'. $slot_id .'" onclick="JavaScript:see_booking(\''. $slot_id .'\');" value="Cambiar" title="Cambiar / cancelar" />&nbsp;&nbsp;
							<input type="button" class="button_tiny" name="no_show_'. $slot_id .'" onclick="JavaScript:add_no_show(\''. $slot_id .'\');" value="NP" title="No presentado" />&nbsp;&nbsp;
							<input type="button" class="button_tiny" name="paid_'. $slot_id .'" onclick="JavaScript:pay_booking(\''. $slot_id .'\');" value="PAGAR" title="Pagar reserva" />
							</div>';
					}
					elseif($slot['book_status'] == 'prebook') {
						$time_expires = new date_time($slot['expire']);
						$time_to_expire = round(($time_expires->timestamp - $now->timestamp) / 60);
						
						$div_class = 'ad_ts_free';
						$div_text = 'PRE-RESERVA<br />';
						$div_text.= '<span class="small_text"><a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $slot['user_id'] .'">'. shorten_str($slot['user_name'], 18) .'</a>';
							$div_text.= ' ('. $slot['user_id'] .')';
							if($slot['is_member'])
								$div_text.= ' <img src="'. $conf_images_path .'user16.png" height="12" width="12" title="Socio" align="absmiddle" />';
						$div_text.= '<br>Caduca en: '. $time_to_expire .' min.</span>';
					}
					elseif($slot['book_status'] == 'paid') {
						# Confirmed and paid
						$div_class = 'ad_ts_paid';
						$div_text = '<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $slot['user_id'] .'">'. shorten_str($slot['user_name'], 18) .'</a>';
						$div_text.= ' ('. $slot['user_id'] .')';
						if($slot['is_member'])
							$div_text.= ' <img src="'. $conf_images_path .'user16.png" height="12" width="12" title="Socio" align="absmiddle" />';
						
						$div_text.= '<br><span class="small_text">'. print_money($slot['price']) .'&nbsp;&nbsp;PAGADO</span>';
						$div_text.= '<div align="center"><input type="button" class="button_tiny" name="see_'. $slot_id .'" onclick="JavaScript:see_booking(\''. $slot_id .'\');" value="Ver Reserva" /></div>';
	//					$div_text.= '<br><a href="'. $conf_main_page .'?mod=admin&tab=bookings&subtab=book_detail&detail='. $slot['booking_id'] .'" title="Editar, cancelar reserva"><img src="'. $conf_images_path .'edit.gif" border="0" /></a>';
	
					}

					echo '<div id="'. $slot_id .'" align="center" class="time_slot '. $div_class .'" style="height:'. $slot_height .'px; padding:1px; top:'. $slot_top .'px; overflow:auto; width:'. $slot_width .'px;">'. $div_text .'</div>';
				}
				elseif(!$arr_other_slots[$slot_id]) {
					
					$slot_starts = new date_time($current_date->odate, $slot['slot_starts']);
					$slot_ends = $slot_starts->plus_mins(30); //new date_time($slot['booking_ends_datetime']);
					$slot_mins = 30;//($slot_ends->timestamp - $slot_starts->timestamp) / 60;
					
					$slot_height = $slot_mins - 9;										//echo 'slot_height: '. $slot_height .'<br>';
					$slot_width  = $court_w - 13;													//echo 'slot_width: '. $slot_width .'<br>';
					$mins_since_open = ($slot_starts->timestamp - $opening_time->timestamp) / 60;	//echo 'hours_since_open: '. $hours_since_open .'<br>';
					$slot_top    = $mins_since_open + 8;
					
					$div_class = '';
					$div_text = '';
					
					# booking is free
					$div_class = 'ad_ts_free';
					if($now->timestamp < $slot_ends->timestamp) {
						$ob_slot = new time_slot($slot_id, $slot['slot_starts']);
						$next_time_blocked = $ob_slot->get_next_blocked($arr_times);
						if(!$next_time_blocked)
							$next_time_blocked = $closing_time->otime;

						$next_blocked = new my_time($next_time_blocked);
						$slot_time = new my_time($slot['slot_starts']);
						
						$remaining_hours = $next_blocked->total_hours - $slot_time->total_hours;
					
						$div_text = '<input type="button" class="button_tiny" name="book_'. $slot_id .'" onclick="JavaScript:place_booking(\''. $slot_id .'\', \''. $remaining_hours .'\');" value=" RESERVAR " />';
					}

					echo '<div id="'. $slot_id .'" align="center" class="time_slot '. $div_class .'" style="height:'. $slot_height .'px; padding:1px; top:'. $slot_top .'px; overflow:auto; width:'. $slot_width .'px;">'. $div_text .'</div>';
				}
				
			}	//	foreach($slots as $slot_id => $slot) {
?>          
      </div></td>
<?php	}	?>
  </tr>
<?php	foreach($arr_hours as $hour) {	?>  
  <tr>
    <td height="51" align="right" valign="top" class="hours_table_cell" id="hours_cells" rowspan="2"><?= $hour; ?></td>
    <?=		str_repeat('<td class="hours_table_cell" height="16">&nbsp;</td>', $num_courts);	?>
    </tr><tr>
    <?=		str_repeat('<td class="hours_table_cell" height="17">&nbsp;</td>', $num_courts);	?>
  </tr>
<?php	}	?>
</table>