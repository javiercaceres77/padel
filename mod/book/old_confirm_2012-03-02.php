<?php

$now = new date_time('now');
# Get user available payment methods:
$arr_pms = $ob_user->get_payment_methods();
//pa($arr_pms);
if($_POST) {
	# double check that pre_books are not expired
	#------------------------------------------------------------------------------
	remove_expired_pre_books($now->timestamp);

	$arr_slots = array();
	foreach($_SESSION['pre_books'] as $slot_id => $book)
		$arr_slots[] = $slot_id;
	
	# double check that any of the timeslots have not been taken -------- This should never happen in theory
	#------------------------------------------------------------------------------
	$sql = 'SELECT slot_id FROM bookings b WHERE expire_datetime > \''. $now->datetime .'\' AND status IN (\'prebook\', \'confirmed\') 
			AND user_id <> \''. $_SESSION['login']['user_id'] .'\' AND slot_id IN ('. implode(', ', $arr_slots) .')';
	
	$sel_conflicts = my_query($sql, $conex);

	while($record = my_fetch_array($sel_conflicts)) {
		$sql = 'DELETE FROM bookings WHERE user_id = \''. $_SESSION['login']['user_id'] .'\' AND slot_id = \''. $record['slot_id'] .'\' AND expire_datetime > \''. $now->datetime .'\' AND status = \'prebook\'';
		$del_book = my_query($sql, $conex);
		if($del_book)
			unset($_SESSION['pre_books'][$record['slot_id']]);
		
		write_log_db('conflicts', 'double_booking', 'User: '. $_SESSION['login']['user_id'] .', slot: '. $record['slot_id']);
	}

	# if payment method = cash, check that number of pre-books is not over limit. check also with confirmed books on the DB.
	#------------------------------------------------------------------------------
	$num_books = count($_SESSION['pre_books']);

	if($_POST['payment_method'] == 'cash') {
		$available_books = $arr_pms['cash'];
/*		$sel_arr = simple_select('users', 'user_id', $_SESSION['login']['user_id'], 'available_books_num');
		$available_books = $sel_arr['available_books_num'];
		
		$sql = 'SELECT count(1) as num_books FROM bookings WHERE user_id = '. $_SESSION['login']['user_id'] .' AND status IN (\'confirmed\', \'prebook\')
				AND booking_datetime > \''. $now->datetime .'\' AND payment_method = 4';
		$sel_num_books = my_query($sql, $conex);
		$num_books_db = my_result($sel_num_books, 0, 'num_books');
	*/	
		if($num_books > $available_books) {
			$text = 'Solo puedes reservar '. $available_books .' pistas al pagar en efectivo. Selecciona otro medio de pago o cancela alguna de las pistas';
			add_alert('book', 'alert', 2, $text);
			
			jump_to($conf_main_page .'?mod=book&subtab=confirm');
			exit();
		}
	}
	
	if($_POST['payment_method'] != 'bonus') {
		# re-calculate total
		#------------------------------------------------------------------------------
		$total = 0; $arr_book_ids = array();
		foreach($_SESSION['pre_books'] as $slot_id => $book) {
			$total+= ($book['fare'] * 4);
			$arr_book_ids[] = $book['booking_id'];
		}
	
		# add to ledger entry for user
		#------------------------------------------------------------------------------
		$arr_ins = array('user_id'			=> $_SESSION['login']['user_id'],
						 'entry_status'		=> 'pending',
						 'payment_method'	=> $_POST['payment_method'],
						 'amount'			=> $total,
						 'entry_datetime'	=> $now->datetime,
						 'booking_ids'		=> implode(',', $arr_book_ids),
						 'entry_type'		=> 'booking');
		
		$ins_ledger = insert_array_db('ledger', $arr_ins, true);
		if(!$ins_ledger) {
			write_log_db('ledger', 'error_insert', 'User: '. $_SESSION['login']['user_id']);
			$text = 'Ha habido un error al insertar tu reserva en al base de datos. Contacta con nosotros para resolver el problema';
			add_alert('book', 'alert', 2, $text);
			
			exit();
		}
		else {
			write_log_db('ledger', 'booking', 'Amount: '. $total);
		}
	}	//	if($_POST['payment_method'] != 'bonus') {
	else {	# bonus, check if enough hours are available and if there are, discount the hours from the bonuses.
		$total = count($_SESSION['pre_books']) * 4;	# four hours per slot
		$bonus_hours = 0;
		foreach($arr_pms['bonus'] as $bonus)
			$bonus_hours += $bonus['hours'];
		
		if($total > $bonus_hours) {
			$text = 'No tienes horas suficientes en bonos. Te quedan '. $bonus_hours .' horas, recuerda que cada pista son cuatro horas de un bono';
			add_alert('book', 'alert', 2, $text);
			
			jump_to($conf_main_page .'?mod=book&subtab=confirm');
			exit();
		}
		else {
			foreach($arr_pms['bonus'] as $bonus_id => $bonus) {
				$ob_bonus = new bonus($bonus_id);

				if($total > $bonus['hours']) {
					$total-= $bonus['hours'];
					$ob_bonus->deactivate();
					$text = 'Bono "'. $bonus['name'] .'" consumido';
					add_alert('book', 'info', 2, $text);
				}
				else {
					$ob_bonus->discount_hours($total);
					$text = $total .' horas descontadas de bono "'. $bonus['name'] .'"';
					add_alert('book', 'info', 2, $text);
					$total = 0;
				}
				
				if($total == 0)
					break;
			}
		}
	}
		
	# if payment method = credit card jump to the TPV, we will set the bookings as confirmed on the exit of this
	#------------------------------------------------------------------------------

	if($_POST['payment_method'] == 'card') {
		jump_to($conf_main_page .'?mod=book&tab=tpv_input');
		exit();
	}
	
	# increase total booking for user
	#------------------------------------------------------------------------------
		# DO THIS WHEN THE BOOKING IS PLAYED.
/*	$sql = 'UPDATE users SET total_books_num = total_books_num + '. $num_books .' WHERE user_id = '. $_SESSION['login']['user_id'];
	$upd_num_books = my_query($sql, $conex);
	if(!$upd_num_books) {
		$text = 'Ha habido un error al insertar tu reserva en al base de datos. Contacta con nosotros para resolver el problema';
		add_alert('book', 'alert', 2, $text);
		
		exit();
	}
*/
	# udpate bookings to confirmed
	#------------------------------------------------------------------------------
	$sql = 'UPDATE bookings SET status = \'confirmed\', payment_method = \''. $_POST['payment_method'] .'\', expire_datetime = \'\'';
	if($_POST['payment_method'] == 'bonus')	$sql.=', booking_fare = 0, comments = \'Bono\' ';
	$sql.= 'WHERE user_id = '. $_SESSION['login']['user_id'] .' AND status = \'prebook\'
	  AND slot_id IN ('. implode(', ', $arr_slots) .') AND expire_datetime > \''. $now->datetime .'\'';

	$upd_bookings = my_query($sql, $conex);
	if($upd_bookings)
		write_log_db('books', 'confirmation', 'Bookings confirmed for user: '. $_SESSION['login']['user_id']);
	else {
		$text = 'Ha habido un error al insertar tu reserva en al base de datos. Contacta con nosotros para resolver el problema';
		add_alert('book', 'alert', 2, $text);
		
		exit();
	}
	
	# add alert with no. or bookings and total €
	#------------------------------------------------------------------------------
	$plural = $num_books > 1 ? 's' : '';
	$text = 'Tu reserva de '. $num_books .' pista'. $plural .' está confirmada';
	if($_POST['payment_method'] == 'cash' || $_POST['payment_method'] == 'ccc')
		$text.= ' con un importe total de '. print_money($total);
	elseif($_POST['payment_method'] == 'bonus') {
		$num_hours = count($_SESSION['pre_books']) * 4;
		$text.= '. '. $num_hours .' horas descontadas de bonos.';
	}
	
	add_alert('book', 'info', 2, $text);

	# send e-mail
	#------------------------------------------------------------------------------
	$to = $_SESSION['login']['email'];
	$subject = 'Reserva confirmada en Ponferrada Pádel Indor';
	
	$headers = 'To: "'. $_SESSION['login']['name'] .'" <'. $_SESSION['login']['email'] .'>' . "\r\n";
	$headers .= 'From: No Reply <no_replay@padelindoorponferrada.com>' . "\r\n";

	$message = "Tu reserva ha sido confirmada:\n\n";
	
	foreach($_SESSION['pre_books'] as $book) {
		$tmp_date = new my_date($book['date_db']);
		$message.= $book['court_name'] .". Fecha: ". $tmp_date->format_date('month_day') ." de ". $book['slot_starts'] ." a ". $book['slot_ends'] ."\n";
	}
	
	if($_POST['payment_method'] != 'bonus') 
		$message.= "\nTotal precio de la reserva: ". print_money($total);
	
	$message.= "\n\nWWW.PADELINDOORPONFERRADA.COM ". $conf_main_phone_contact;
					
	@mail($to, $subject, $message, $headers);

	# unset session pre-books
	#------------------------------------------------------------------------------
	unset($_SESSION['pre_books']);
	
	# forward to "your bookings"
	#------------------------------------------------------------------------------
	jump_to($conf_main_page .'?mod=book&tab=user_books');
	exit();
}

$in_5_min = $now->plus_mins(5);

$arr_my_slots = array();
$arr_times = array();

$min_advance_booking = get_config_value('min_advance_booking');

remove_expired_pre_books($now->timestamp);

foreach($_SESSION['pre_books'] as $slot_id => $book) {
	# remove pre-books that are close to the booking time -------------------------------------
	$slot_starts = new date_time($book['date_db'], $book['slot_starts']);
	if($slot_starts->timestamp - $now->timestamp < ($min_advance_booking * 60)) {
		$text = 'La <i>pre_reserva</i> para '. $book['court_name'] .' a las '. $book['slot_starts'] .' se ha eliminado por haber sobrepasado la hora límite para reservar.';
		add_alert('book', 'alert', 1, $text);
		
		remove_pre_book($slot_id, $_SESSION['login']['user_id']);
		unset($_SESSION['pre_books'][$slot_id]);
	}

	# extend expire time to a min of 5 minutes ------------------------------------------------
	$expire = new date_time($book['expire_date'], $book['expire_time']);
	
	if(($expire->timestamp < $in_5_min->timestamp) && ($expire->timestamp > $now->timestamp)) {
		$_SESSION['pre_books'][$slot_id]['expire_date'] = $in_5_min->odate->odate;
		$_SESSION['pre_books'][$slot_id]['expire_time'] = $in_5_min->otime;
		
		$arr_upd = array('expire_datetime' => $in_5_min->datetime);
		
		update_array_db('bookings', 'booking_id', $book['booking_id'], $arr_upd);
	}
	
	$arr_my_slots[] = $slot_id;
	$arr_times[$book['date_db']][$slot_id] = array('start' => $book['slot_starts'], 'end' => $book['slot_ends'], 'court_name' => $book['court_name']);
}


# check that the slot is not taken by another user ----------------------------------------
# in theory this should never be run.
$sql = 'SELECT slot_id FROM bookings b WHERE expire_datetime > \''. $now->datetime .'\' AND status IN (\'prebook\', \'confirmed\') 
		AND user_id <> \''. $_SESSION['login']['user_id'] .'\' AND slot_id IN ('. implode(', ', $arr_my_slots) .')';

$sel_conflicts = my_query($sql, $conex);
while($record = my_fetch_array($sel_conflicts)) {
	$sql = 'DELETE FROM bookings WHERE user_id = \''. $_SESSION['login']['user_id'] .'\' AND slot_id = \''. $record['slot_id'] .'\' AND expire_datetime > \''. $now->datetime .'\' AND status = \'prebook\'';
	$del_book = my_query($sql, $conex);
	if($del_book)
		unset($_SESSION['pre_books'][$record['slot_id']]);
	
	write_log_db('conflicts', 'double_booking', 'User: '. $_SESSION['login']['user_id'] .', slot: '. $record['slot_id']);
}

# check that two courts have been booked at the same time ----------------------------------
foreach($arr_times as $date => $arr_slots)
	rec_check_conflicts($arr_slots);

# !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! THIS DOESN'T WORK ---------------------------------------
function rec_check_conflicts(&$my_arr) {
	$compare = array_pop($my_arr);
	
	foreach($my_arr as $slot_id => $times) {
		if(($compare['start'] > $times['start'] && $compare['start'] < $times['end']) || ($compare['end'] > $times['start'] && $compare['end'] < $times['end'])) {
			$text = 'Has hecho dos reservas para la misma hora: '. $compare['court_name'] .' a las '. $compare['start'] .' y '. $times['court_name'] .' a las '. $times['start'];
			add_alert('book', 'info', 1, $text);
		}
	}
	if(count($my_arr))
		rec_check_conflicts($my_arr);
}

?>

<span class="button" onclick="JavaScript:document.location='<?= $conf_main_page; ?>?mod=book&tab=bookings'">&lt; Cambiar tu reserva</span><br />
<br />
<table border="0" cellpadding="4" cellspacing="3" width="100%" class="default_text">
  <tr>
    <th class="hours_table_cell">Pista</th>
    <th class="hours_table_cell">Fecha-Hora</th>
    <th class="hours_table_cell">precio / persona</th>
    <th class="hours_table_cell">precio / pista</th>
  </tr>
  <?php
$total = 0;
foreach($_SESSION['pre_books'] as $slot_id => $book) {
	$book_date = new my_date($book['date_db']);
	$court_price = $book['fare'] * 4;
	$total+= $court_price;
?>
  <tr>
    <td class="hours_table_cell"><?= $book['court_name']; ?></td>
    <td class="hours_table_cell"><?= $book_date->get_weekday_desc() .', '. $book_date->format_date('long') .' - '. $book['slot_starts'] .' a '. $book['slot_ends']; ?></td>
    <td class="hours_table_cell" align="right"><?= print_money($book['fare']); ?></td>
    <td class="hours_table_cell" align="right"><?= print_money($court_price); ?></td>
  </tr>
  <?php
}
?>
  <tr>
    <td colspan="3" class="hours_table_cell" align="right">Total a pagar: </td>
    <td class="hours_table_cell title_4" align="right"><?= print_money($total); ?></td>
  </tr>
</table>
<br />
<form name="conf_pre_books" method="post" action="" id="conf_pre_books">
  <table cellspacing="10" cellpadding="10" align="center" border="0">
    <tr>
      <td class="default_text standard_container"><div class="title_4">Elige forma de pago</div>
        <?php

# Select payment methods for this user.

//pa($ob_user);

	if(count($arr_pms)) {
		$first = true;
		foreach($arr_pms as $id => $pm) {
			if($first) {
				$first = false;
				$checked = ' checked="checked"';
			}
			else
				$checked = '';
			
			$disabled = '';
			
			switch($id) {
				case 'card':
					$desc = 'Tarjeta de crédito &nbsp;&nbsp;<img src="'. $conf_images_path .'credit_cards.png" align="absmiddle" />';
				break;
				case 'ccc':
					$checked = ' checked="checked"';
					$desc = 'Cargo en cuenta corriente&nbsp;&nbsp;CCC: ******'. substr($pm['ccc'], 19);
				break;
				case 'cash':
					$disabled = $pm == 0 ? ' disabled="disabled"' : '';
					$plural = $pm == 1 ? '' : 's';
					$desc = 'Efectivo (pago en ventanilla), máximo '. $pm .' reserva'. $plural;
				break;
				case 'bonus':
					$plural = count($pm) > 1 ? 's' : '';
					$hours = 0;
					foreach($pm as $bonus) $hours+= $bonus['hours'];
					$desc = 'Bono: '. count($pm) .' bono'. $plural .', total '. $hours .' horas disponibles<br />';
					$desc.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="small_text">(una hora por jugador, cuatro horas por pista)</span>';
				break;
				default:
					$desc = '';
				break;
			}
	
			echo '<label><input name="payment_method" type="radio" value="'. $id .'" '. $checked . $disabled .' />'. $desc .'</label><br>';
		}	//	foreach($arr_pms as $id => $pm) {
	}	//	if(count($arr_pms)) {
	else {
		echo '<div class="error_message" style="width:100%"><div class="indented">No tienes ningún medio de pago disponible.<br />Ve a <a href="'. $conf_main_page .'?mod=user">tu cuenta</a> para añadir un número de cuenta o comprar un bono</div></div>';
	}
?></td>
    </tr>
    <tr>
      <td class="small_text">&bull; Una vez pagada la reserva quedar&aacute; confirmada<br />
        &bull; Las reservas se pueden cancelar hasta
        <?= get_config_value('min_advance_cancel_booking'); ?>
        horas antes de comenzar </td>
    </tr>
    <tr>
      <td align="center" class="small_text"><?php	if(count($arr_pms)) {	?><input type="submit" name="pay" id="pay" class="button" value="   PAGAR   " /><?php	}	?></td>
    </tr>
  </table>
</form>
<script language="javascript">
show_alerts();
</script>
