<?php

$now = new date_time('now');
# update booking as paid
$arr_upd = array('status' => 'paid');
if(update_array_db('bookings', 'booking_id', $_POST['booking_id'], $arr_upd)) {
	# generate multiplayer game and add ledger entries for each player (except bonuses)
	$arr_ins_mp = array('booking_id' => $_POST['booking_id'], 'opened_datetime' => $now->datetime, 'closed_datetime' => $now->datetime, 'generated_by' => 'admin'); 
	$arr_ins_ledger = array();
	foreach($_SESSION['multiplayer'] as $ply => $game) {
		# $game['pm'] and $game['amount'] don't have valid information
		$det_user = new user($game['user_id']);
		$user_fare = $det_user->get_slot_fare($_POST['slot_id']);
		if($_POST['pm'. $ply] == 'bonus' && $ply != 1) {
			$arr_ins_mp['amount'. $ply] = 0;//$user_fare['fare'];
			# bonus, check if enough hours are available and if there are, discount the hours from the bonuses.
			$ob_book = new booking($_POST['booking_id']);
			$ob_slot = new time_slot($ob_book->get_slot_id());
			$slot_hours = $ob_slot->get_time_slot_min() / 60;

			$total = $slot_hours;

			$arr_bonus = $det_user->get_user_bonuses();
			
			$bonus_hours = 0;
			foreach($arr_bonus as $bonus)
				$bonus_hours += $bonus['hours'];
			
			if($total > $bonus_hours) {
	//			$text = 'No tienes horas suficientes en bonos. Te quedan '. $bonus_hours .' horas, recuerda que cada pista son cuatro horas de un bono';
				$text = 'El usuario no tiene horas suficientes en bonos.';
				add_alert('admin', 'alert', 2, $text);
				
				jump_to($conf_main_page .'?mod=book&subtab=confirm');
				exit();
			}
			else {	
				foreach($arr_bonus as $bonus_id => $bonus) {
					$ob_bonus = new bonus($bonus_id);
	
					$ob_bonus->discount_hours($total);
					$plural = $total > 1 ? 's' : '';
					$text = $total .' hora'. $plural .' descontada'. $plural .' de bono "'. $bonus['name'] .'"';
					add_alert('admin', 'info', 2, $text);
					$total = 0;
					
					if($total == 0)
						break;
				}
			}
		}
		elseif($_POST['pm'. $ply] == 'cash' || $_POST['pm'. $ply] == 'ccc') {
			$arr_ins_ledger = array('user_id' => $det_user->user_id,  'amount' => $user_fare['fare'], 'payment_method' => $_POST['pm'. $ply], 
									'entry_datetime' => $now->datetime, 'booking_ids' => $_POST['booking_id'], 'entry_type' => 'booking');
			if($_POST['pm'. $ply] == 'cash')
				$arr_ins_ledger['entry_status'] = 'paid';
			elseif($_POST['pm'. $ply] == 'ccc')
				$arr_ins_ledger['entry_status'] = 'pending';
				
			$ins_ledger = insert_array_db('ledger', $arr_ins_ledger, true);
			if(!$ins_ledger) {
				write_log_db('ledger', 'error_insert', 'User: '. $_SESSION['login']['user_id']);
				$text = 'Ha habido un error al insertar la reserva en al base de datos.';
				add_alert('admin', 'alert', 2, $text);
			}
			
			$arr_ins_mp['amount'. $ply] = $user_fare['fare'];
		}	//	elseif($_POST['pm'. $ply] == 'cash' || $_POST['pm'. $ply] == 'ccc') {

		$arr_ins_mp['user_id'. $ply] = $det_user->user_id;
		$arr_ins_mp['payment_method'. $ply] = $_POST['pm'. $ply];
		$det_user->increase_num_books();
		
	}	//	foreach($_SESSION['multiplayer'] as $ply => $game) {

	$ins_mp = insert_array_db('mp_games', $arr_ins_mp, true);
	if($ins_mp) {
?>

<div class="title_3 indented">La reserva se ha actualizado correctamente:</div>
<div class="default_text indented">
  <?php
		foreach($_SESSION['multiplayer'] as $ply => $game) {
			switch($_POST['pm'. $ply]) {
				case 'cash':	$pm_str = 'Efectivo / tarjeta'; 	break;
				case 'ccc':		$pm_str = 'Cargo en cuenta';		break;
				case 'bonus':	$pm_str = 'Bono (-'. $slot_hours .' hora)';			break;
			}
			
			echo '&bull; Jugador '. $ply .': <strong>'. $game['user_name'] .'</strong> '. print_money($arr_ins_mp['amount'. $ply]) .' ('. $pm_str .')<br>';
		}	//	foreach($_SESSION['multiplayer'] as $ply => $game) {
	}
	unset($_SESSION['multiplayer']);
	unset($_POST);
}
?>
  <br />
  <br />
  <a href="<?= $conf_main_page; ?>?mod=admin&tab=bookings">&lt; Volver a las reservas</a></div>
