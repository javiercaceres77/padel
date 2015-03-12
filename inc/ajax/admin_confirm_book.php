<?php

$ob_user = new user($_SESSION['login']['user_id']);

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$det_user = new user($_GET['user']);
$arr_pms = $det_user->get_payment_methods();
$now = new date_time('now');
$num_pms = $arr_pms['cash'] + count($arr_pms['bonus']) + count($arr_pms['ccc']);

if($_GET['booking'] && $_GET['pm'] && $num_pms) {
	# double check that pre_books are not expired
	#------------------------------------------------------------------------------
	$arr_book = simple_select('bookings', 'booking_id', $_GET['booking'], array('expire_datetime', 'slot_id', 'slots_list', 'booking_datetime', 'booking_ends_datetime', 'booking_fare'));
	$expire_time = new date_time($arr_book['expire_datetime']);
	
	if($expire_time->timestamp < $now->timestamp) {
		echo 'La pre-reserva ha caducado, vuelve a la pantalla de reservas para intentarlo otra vez';
		exit();
	}

	$arr_slots = explode_keys(',', $arr_book['slots_list']);
	# double check that any of the timeslots have not been taken -------- This should never happen in theory
	#------------------------------------------------------------------------------
	$sql = 'SELECT slot_id FROM bookings b WHERE expire_datetime > \''. $now->datetime .'\' AND status IN (\'prebook\', \'confirmed\') 
			AND user_id <> \''. $det_user->user_id .'\' AND slot_id IN ('. implode(', ', $arr_slots) .') AND booking_id <> \''. $_GET['booking'] .'\'';
	
	$sel_conflicts = my_query($sql, $conex);

	while($record = my_fetch_array($sel_conflicts)) {
		$sql = 'DELETE FROM bookings WHERE user_id = \''. $det_user->user_Id .'\' AND slot_id = \''. $record['slot_id'] .'\' AND expire_datetime > \''. $now->datetime .'\' AND status = \'prebook\'';
		$del_book = my_query($sql, $conex);
		if($del_book)
			unset($_SESSION['pre_books'][$record['slot_id']]);
		
		write_log_db('conflicts', 'double_booking', 'User: '. $_SESSION['login']['user_id'] .', slot: '. $record['slot_id']);
	}

	# if payment method = cash, check that number of pre-books is not over limit. check also with confirmed books on the DB.
	#------------------------------------------------------------------------------
	if($_GET['pm'] == 'cash' || $_GET['pm'] == 'ccc') {
		# Update status of the booking
		$arr_upd = array('status' => 'confirmed', 'payment_method' => $_GET['pm'], 'expire_datetime' => '');
		if(update_array_db('bookings', 'booking_id', $_GET['booking'], $arr_upd)) {
			write_log_db('books', 'confirmation', 'Book confirmed for user: '. $det_user->user_id);
		}
		else {
			echo 'Ha habido un error al actualizar la reserva en la base de datos';
			write_log_db('books', 'error_updte', 'user: '. $det_user->user_id .'; book: '. $_GET['booking']);
		}
	}
	elseif($_GET['pm'] == 'bonus') {
		# calculate number of hours
		$book_starts = new date_time($arr_book['booking_datetime']);
		$book_ends = new date_time($arr_book['booking_ends_datetime']);
		$total = ($book_ends->timestamp - $book_starts->timestamp) / 3600;

		$bonus_hours = 0;
		foreach($arr_pms['bonus'] as $bonus)
			$bonus_hours += $bonus['hours'];
		
		if($total > $bonus_hours) {
//			$text = 'No tienes horas suficientes en bonos. Te quedan '. $bonus_hours .' horas, recuerda que cada pista son cuatro horas de un bono';
			echo 'El usuario no tiene horas suficientes en bonos. Le quedan en total '. $bonus_hours .' horas';
			
			# Allow to book and leave the hours on credit
			
/*			jump_to($conf_main_page .'?mod=book&subtab=confirm');
			exit();*/
		}
		else {
			foreach($arr_pms['bonus'] as $bonus_id => $bonus) {
				$ob_bonus = new bonus($bonus_id);

				if($total > $bonus['hours']) {
					$total-= $bonus['hours'];
					$ob_bonus->deactivate();
					$text = 'Bono "'. $bonus['name'] .'" (#'. $bonus_id .') consumido';
					add_alert('book', 'info', 2, $text);
				}
				else {
					$ob_bonus->discount_hours($total);
					$plural = $total > 1 ? 's' : '';
					$text = $total .' hora'. $plural .' descontada'. $plural .' de bono "'. $bonus['name'] .'" (#'. $bonus_id .')';
					add_alert('book', 'info', 2, $text);
					$total = 0;
				}
				
				if($total == 0)
					break;
			}	//	foreach($arr_pms['bonus'] as $bonus_id => $bonus) {
		}	//	else {
	}	//	elseif($_GET['pm'] == 'bonus') {
	
	#	send e-mail
	$to = $det_user->get_email();
	$subject = 'Reserva confirmada en Ponferrada Pádel Indor';
	
	$headers = 'To: "'. $det_user->get_user_name() .'" <'. $to .'>' . "\r\n";
	$headers .= 'From: No Reply <no_replay@padelindoorponferrada.com>' . "\r\n";

	$message = "Tu reserva ha sido confirmada:\n\n";
	$message.= "Fecha: ". $book_starts->odate->format_date('month_day') ." de ". $book_starts->otime ." a ". $book_ends->otime ."\n";
	
	if($_POST['payment_method'] != 'bonus') 
		$message.= "\nPrecio de la reserva: ". print_money($arr_book['booking_fare']);
	
	$message.= "\n\nWWW.PADELINDOORPONFERRADA.COM ". $conf_main_phone_contact;
					
	@mail($to, $subject, $message, $headers);
		
}	//	if($_GET['booking'] && $_GET['pm'] && $num_pms) {
