<?php

$ob_user = new user($_SESSION['login']['user_id']);

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$det_user = new user($_GET['user']);
$arr_pms = $det_user->get_payment_methods();
$arr_user = $det_user->get_all_details();
$now = new date_time('now');
$ob_slot = new time_slot($_GET['slot']);
$booking_datetime = $ob_slot->get_date_time();
$booking_ends_datetime = $booking_datetime->plus_mins($_GET['dur'] * 60);

# search for conflicts
	
$sql = 'SELECT booking_id FROM bookings 
WHERE expire_datetime > \''. $now->datetime .'\' 
  AND (
       (\''. $booking_datetime->datetime .'\' >= booking_datetime AND \''. $booking_datetime->datetime .'\' < booking_ends_datetime) 
  		OR
	   (\''. $booking_ends_datetime->datetime .'\' > booking_datetime AND \''. $booking_ends_datetime->datetime .'\' <= booking_ends_datetime)
	    OR
	   (\''. $booking_datetime->datetime .'\' <= booking_datetime AND \''. $booking_ends_datetime->datetime .'\' >= booking_ends_datetime)
	  )	
  AND status IN (\'prebook\', \'confirmed\')
  AND court_id = '. $ob_slot->get_court_id() .'
  ';
  
$sel_conflict = my_query($sql, $conex);
if(my_num_rows($sel_conflict)) {
	echo '<div id="alert_0" class="alert_alert default_text">Existe un conflicto con la hora, <a href="'. $conf_main_page .'?mod=admin&tab=bookings">vuleve al panel</a> para seleccionar una nueva hora</div>';
	$log_msg = 'Admin booking as found a conflict, book_date = '. $booking_datetime->datetime .'; ends = '. $booking_ends_datetime->datetime .'; court = '. $ob_slot->get_court_id();
	write_log_db('books', 'conflict_error', $log_msg);
}
else {
	# Get other slot ids
	$other_slots = $ob_slot->get_following_slots_db($_GET['dur'] * 60);
	$arr_slots = $other_slots;
	$arr_slots[] = $ob_slot->slot_id;
	
	$sql = 'SELECT sum(f.fare) as fare, f.fare as fare_val, f.fare_name, f.fare_id, count(1) as num_slots/*, f.time_starts, f.time_ends, f.date_db, c.court_id, c.name as court_name, t.slot_starts, t.slot_ends, t.slot_id*/
			FROM fares f
			INNER JOIN time_slots t ON f.date_id = t.date_id AND t.slot_starts >= f.time_starts	AND t.slot_starts < f.time_ends
			INNER JOIN courts c ON t.court_id = c.court_id
			WHERE f.is_member = \''. $det_user->is_member .'\'
			AND t.slot_id IN ('. implode(',', $arr_slots) .')
			GROUP BY f.fare, f.fare_name, f.fare_id';
			
	$sel_fare = my_query($sql, $conex);
	
	$fare = 0; $first = true; $arr_fares = array();
	while($record = my_fetch_array($sel_fare)) {
		$fare += ($record['fare']) / 2;
		$duration = $record['num_slots'] / 2;
		if($first) 
			$first = false;
		else
			$add = ' + ';
		$text_fares.= $add . $duration .' h. &times; '. print_money($record['fare_val']) .' (tarifa "'. $record['fare_name'] .'")';
		$arr_fares[$record['fare_id']] = 1;
	}
	
	echo '<div class="default_text">Precio reserva: <strong>'. print_money($fare) .'</strong>&nbsp;&nbsp;&nbsp;= '. $text_fares .'</div>';
	
	# insert pre-book in database.
	$expire_time = $now->plus_mins(5);
	$cancel_period = get_config_value('min_advance_cancel_booking') * 60;
	$cancel_datetime = $booking_datetime->plus_mins(-$cancel_period);
				 
	$arr_ins = array('user_id' 					=> $det_user->user_id,
					 'user_is_member' 			=> $det_user->is_member,
					 'slot_id' 					=> $ob_slot->slot_id,
					 'slots_list'				=> implode(',', $arr_slots),
					 'booking_datetime' 		=> $booking_datetime->datetime,
					 'booking_ends_datetime'	=> $booking_ends_datetime->datetime,
					 'book_placement_datetime' 	=> $now->datetime,
					 'status'					=> 'prebook',
					 'booked_by'				=> 'admin ('. $_SESSION['login']['user_id'] .')',
					 'channel'					=> 'web',
					 'court_id'					=> $ob_slot->get_court_id(),
					 'booking_fare'				=> $fare,
					 'cancel_until_datetime'	=> $cancel_datetime->datetime,
					 'expire_datetime'			=> $expire_time->datetime,
					 'fare_id'					=> implode_keys(',',$arr_fares)
					 );
					 
	$book_id = insert_array_db('bookings', $arr_ins, true);

	if($book_id) {
		echo '<div class="default_text">Pre-reserva para <strong>'. $arr_user['full_name'] .'</strong>; núm. reserva: <strong>'. $book_id .'</strong>; Expira: '. $expire_time->datetime .'</div>';
		# get other bookings for the user
		$sql = 'SELECT b.booking_id, b.booking_datetime, b.booking_ends_datetime, b.booking_fare, b.payment_method, c.name, p.method_name
				FROM bookings b
				INNER JOIN courts c ON c.court_id = b.court_id
				LEFT JOIN payment_methods p ON p.method_code = b.payment_method
				WHERE b.booking_datetime >= \''. $now->datetime .'\'
				  AND b.user_id = \''. $det_user->user_id .'\'
				  AND b.status = \'confirmed\'
				ORDER BY b.booking_datetime DESC';
		
		$sel = my_query($sql, $conex);
		$arr_books = array();
		while($record = my_fetch_array($sel)) {
			$pm_name = $record['payment_method'] == 'bonus' ? 'Bono' : $record['method_name'];
			$arr_books[$record['booking_id']] = array('id' => $record['booking_id'], 'datetime' => $record['booking_datetime'], 'fare' => $record['booking_fare'],
											 'fare_name' => $record['fare_name'], 'starts' => $record['booking_datetime'], 'ends' => $record['booking_ends_datetime'],
											 'pm' => $record['payment_method'], 'court' => $record['name'], 'pm_name' => $pm_name);
		}

		if(count($arr_books)) {	
			$plural = count($arr_books) > 1 ? 's' : '';
			echo '<div class="small_text indented">'. $arr_user['full_name'] .' tiene otra'. $plural .' '. count($arr_books) .' reserva'. $plural .' conrimada'. $plural .':<br>';
			foreach($arr_books as $book) {
				$ob_date = new date_time($book['datetime']);
				$fare_str = $book['fare'] > 0 ? print_money($book['fare']) : '';
				echo '&bull; '. $ob_date->odate->format_date('month_day') .' de '. $book['starts'] .' a '. $book['ends'] .', '. $book['court'] .'; '. $fare_str .' '. $book['pm_name'] .'<br>';
			}
			echo '</div>';
		}	
		
		# Show payment methods
		echo '<div class="default_text"><div class="title_3">Pago por adelantado</div>';
		if(count($arr_pms)) {
			if($arr_pms['ccc'] || $arr_pms['bonus'] || $arr_pms['card']) {
				$checked = '';
				if($arr_pms['ccc']) {
					$checked = ' checked="checked"';
					$desc = 'Cargo en cuenta corriente&nbsp;&nbsp;CCC: ******'. substr($arr_pms['ccc']['ccc'], 19);
					echo '<label><input name="payment_method" type="radio" value="ccc" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'ccc\');" />'. $desc .'</label><br>';
				}
				
				if($arr_pms['bonus']) {
					$plural = count($arr_pms['bonus']) > 1 ? 's' : '';
					$hours = 0;
					foreach($arr_pms['bonus'] as $bonus) $hours+= $bonus['hours'];
					$desc = 'Bono: '. count($arr_pms['bonus']) .' bono'. $plural .', total '. $hours .' horas disponibles<br />';
		//					$desc.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="small_text">(una hora por jugador, cuatro horas por pista)</span>';
					echo '<label><input name="payment_method" type="radio" value="bonus" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'bonus\');" />'. $desc .'</label><br>';
				}
				
				if($arr_pms['card']) {
					$desc = 'Tarjeta de crédito &nbsp;&nbsp;<img src="'. $conf_images_path .'credit_cards.png" align="absmiddle" />';
					echo '<label><input name="payment_method" type="radio" value="card" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'card\');" />'. $desc .'</label><br>';
				}
			}	//	if($arr_pms['ccc'] || $arr_pms['bonus'] || $arr_pms['card']) {
			else {
				echo '&nbsp;&nbsp;El usuario no tiene registrado ningún medio de pago con antelación.<br />';
				echo '&nbsp;&nbsp;<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=add_bonus&usr='. $det_user->user_id .'">Agregar bono</a> o <a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $det_user->user_id .'"> registrar número de cuenta corriente</a> para domiciliar los pagos.';
			}
		}	//	if(count($arr_pms)) {
		# admin can override the max number of cash bookings.
		?>
		<div class="title_3">Pagar en ventanilla</div>
		<label>
		<input name="payment_method" type="radio" value="cash" checked="checked" onchange="JavaScript:select_pm('cash');" />
		Efectivo / tarjeta (pago en ventanilla)</label>
		<br />
		<div align="center" class="indented">
		  <input type="button" name="confirm" value=" CONFIRMAR " id="confirm" onclick="JavaScript:confirm_book('<?= $det_user->user_id; ?>', '<?= $book_id; ?>');" class="button" />
		</div></div>
<?php
	}
	else {
		echo '<div id="alert_0" class="alert_alert default_text">Ha habido un error al insertar la reserva en la base de datos.</div>';
		$log_msg = 'Admin booking error when inserting in db. book_date = '. $booking_datetime->datetime .'; ends = '. $booking_ends_datetime->datetime .'; court = '. $ob_slot->get_court_id();
		write_log_db('books', 'insert_db_error', $log_msg);
	}
}