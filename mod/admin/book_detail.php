<?php

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$now = new date_time('now');

if($_POST) {
//	pa($_POST);
	
	# get the booking info
	$arr_book = simple_select('bookings', 'booking_id', $_POST['booking_id'], array('payment_method', 'status', 'comments', 'slot_id'));
	$arr_upd = array('status' => 'cancelled', 'comments' => $arr_book['comments'] .'<br>'. $now->odate->odate .' '. $now->otime .'. Cancelado por admin '. $_POST['comments']);
	$slot = new time_slot($arr_book['slot_id']);
	$slot_hours = $slot->get_time_slot_min() / 60;
	
	if(update_array_db('bookings', 'booking_id', $_POST['booking_id'], $arr_upd)) {
		if($arr_book['payment_method'] == 'bonus') {
			# get an active bonus for the user
			$arr_bonus = simple_select('bonuses', 'user_id', $_POST['user_id'] ,'bonus_id', ' AND status = \'active\'');
			if($arr_bonus['bonus_id']) {
				$ob_bonus = new bonus($arr_bonus['bonus_id']);
				$ob_bonus->discount_hours(-$slot_hours);
			}
			else {
				bonus::add_bonus($_POST['user_id'], 'bon10', '', 'return', 'active', $slot_hours, 'cash');
				write_log_db('bonus', 'admin_create_bonus', 'By cancellation of booking');
			}
		}	
							
		$text = 'La reserva ha sido cancelada. ';
		if($arr_book['payment_method'] == 'bonus')
			$text.= 'Se ha agregado '. $slot_hours .' hora a los bonos del usuario';
		/*elseif($arr_book['payment_method'] != 'cash')
			$text.= 'Se descontarán '. print_money($arr_book['booking_fare']) .' en el mismo medio de pago que usaste para hacer la reserva.';
		*/
		add_alert('admin', 'info', 1, $text);
		write_log_db('books', 'booking_cancelled', 'User: '. $ob_user->user_id);
	}
	else {
		$text = 'Ha habido un error al cancelar la reserva en la base de datos.';
		add_alert('admin', 'alert', 1, $text);
	}
	
	print_alerts('admin');
	echo '<div class="default_text"><a href="'. $conf_main_page .'?mod=admin&tab=bookings">&lt; volver a las reservas </a></div>';
	exit();
}

# get slot information
$sql = 'SELECT t.slot_starts, t.slot_ends, t.date_db, (f.fare / 60 * t.time_slot_min) as fare, f.fare_name, f.is_member, c.court_id, c.name
FROM time_slots t
INNER JOIN fares f ON f.date_id = t.date_id AND t.slot_starts >= f.time_starts	AND t.slot_starts < f.time_ends
INNER JOIN courts c ON t.court_id = c.court_id
WHERE t.slot_id = '. $_GET['detail'];

$sel = my_query($sql, $conex);

$arr_slot = array();
while($record = my_fetch_array($sel)) {
	$arr_slot['starts'] = $record['slot_starts'];
	$arr_slot['ends']   = $record['slot_ends'];
	$arr_slot['date']   = new my_date($record['date_db']);
	$arr_slot['fare'][$record['is_member']]   = array('fare' => $record['fare'], 'name' => $record['fare_name']);
	$arr_slot['court']  = $record['court_id'];
	$arr_slot['court_name'] = $record['name'];
}

$slot_starts = new date_time($arr_slot['date']->odate, $arr_slot['starts']);
$mins_to_start = round(($slot_starts->timestamp - $now->timestamp) / 60);
$start_str = $mins_to_start >= 0 ? 'La reserva comienza en '. $mins_to_start .' minutos' : 'La reserva comenzó hace '. abs($mins_to_start) .' minutos';

# get booking information
$sql = 'SELECT u.full_name, u.user_id, u.is_admin, b.user_is_member, b.booking_fare, b.book_placement_datetime, b.payment_method, b.booking_id, b.status, pm.method_desc
FROM bookings b
INNER JOIN users u ON u.user_id = b.user_id
LEFT JOIN payment_methods pm ON b.payment_method = pm.method_code
WHERE b.slot_id = \''. $_GET['detail'] .'\' AND b.status IN (\'confirmed\', \'paid\')';

$sel = my_query($sql, $conex);

$arr_book = my_fetch_array($sel);
if(!count($arr_book)) {
	exit();
}

$book_datetime = new date_time($arr_book['book_placement_datetime']);
//pa($arr_book);
?>

<div class="title_3"> Reserva en
  <?= $arr_slot['court_name'] .', '. $arr_slot['date']->format_date('med') .', '. $arr_slot['starts'] .' &ndash; '. $arr_slot['ends']; ?>
</div>
<div class="small_text">
  <?= '&bull; Reserva hecha el '. $book_datetime->odate->format_date('month_day') .', '. $book_datetime->hour .':'. $book_datetime->minute .'<br />'; ?>
  <?= '&bull; Tarifa aplicable: &ldquo;'. $arr_slot['fare'][$arr_book['is_admin']]['name'] .'&rdquo;, '. print_money($arr_slot['fare'][$arr_book['is_admin']]['fare']) .'<br />'; ?>
  <?= '&bull; '. $start_str; ?>
</div>
<?php
if($arr_book['status'] == 'confirmed') {
	$book_user = new user($arr_book['user_id']);
	
	$member_img_str = $book_user->is_member ? '<img src="'. $conf_images_path .'user16.png" height="14" width="14" title="Socio" align="absmiddle" />' : '';
	//pa($book_user);
?>
<div class="title_3"> Jugador:
  <?= $book_user->get_user_name() .' ('. $book_user->user_id .') '. $member_img_str; ?>
  <br />
  Forma de pago:
  <?= $arr_book['method_desc'] .', '. print_money($arr_book['booking_fare']); ?>
</div>
<div class="default_text indented standard_container"> Cancelar reserva
  <form name="no_show_form" id="no_show_form" action="" method="post">
    <div class="small_text">Comentarios (máx. 255)</div>
    <textarea name="comments" id="comments" class="inputnormal"></textarea>
    <br />
    <input type="button" name="confirm" class="button" id="confirm" value=" CANCELAR " onclick="JavaScript:cancel_book();" />
    <input type="hidden" name="booking_id" id="booking_id" value="<?= $arr_book['booking_id']; ?>" />
    <input type="hidden" name="user_id" id="user_id" value="<?= $arr_book['user_id']; ?>" />
  </form>
</div>
<?php
}
elseif($arr_book['status'] == 'paid') {
?>
<div class="default_text indented">Jugadores:<br />
  <table border="0" cellpadding="6" cellspacing="2">
    <?php	
	$arr_pms = dump_table('payment_methods', 'method_code', 'method_desc');
	$arr_pms['bonus'] = 'Bono';

	$sql = 'SELECT 
mp.user_id1, u1.full_name as name1, u1.email as email1, mp.payment_method1, mp.amount1, u1.is_member as is_m1,
mp.user_id2, u2.full_name as name2, u2.email as email2, mp.payment_method2, mp.amount2, u2.is_member as is_m2,
mp.user_id3, u3.full_name as name3, u3.email as email3, mp.payment_method3, mp.amount3, u3.is_member as is_m3, 
mp.user_id4, u4.full_name as name4, u4.email as email4, mp.payment_method4, mp.amount4, u4.is_member as is_m4
FROM mp_games mp
LEFT JOIN users u1 ON u1.user_id = mp.user_id1
LEFT JOIN users u2 ON u2.user_id = mp.user_id2
LEFT JOIN users u3 ON u3.user_id = mp.user_id3
LEFT JOIN users u4 ON u4.user_id = mp.user_id4
WHERE mp.booking_id = '. $arr_book['booking_id'];
//echo $sql;
	$sel = my_query($sql, $conex);
	$arr_mp = my_fetch_array($sel);

	$total_paid = 0;
	for($i = 1; $i <= 4; $i++) {
		$member_img_str = $arr_mp['is_m'. $i] ? '<img src="'. $conf_images_path .'user16.png" height="14" width="14" title="Socio" align="absmiddle" />' : '';
		echo '<tr><td class="hours_table_cell">'. $arr_mp['name'. $i] .' ('. $arr_mp['user_id'. $i] .') '. $member_img_str .'</td><td class="hours_table_cell">'. $arr_mp['email'. $i] .'</td><td class="hours_table_cell">'. $arr_pms[$arr_mp['payment_method'. $i]] .'</td><td class="hours_table_cell" align="right">'. print_money($arr_mp['amount'. $i]) .'</td></tr>';
		$total_paid+= $arr_mp['amount'. $i];
	}
//	pa($arr_mp);
//	$mp_game_arr = simple_select('mp_games', 'booking_id', $arr_book['booking_id'], array('user_id1'

?>
    <tr>
      <td colspan="3" align="right" class="hours_table_cell"><strong>Total pagado: </strong></td>
      <td align="right" class="hours_table_cell"><strong><?= print_money($total_paid); ?></strong></td>
    </tr>
  </table>
</div>
<?php
}
?>
<div class="default_text"><a href="<?= $conf_main_page; ?>?mod=admin&tab=bookings">&lt; volver a las reservas </a></div>
<script language="javascript">
function cancel_book() {
	if(confirm('¿Estás seguro de que quieres cancelar esta reserva?'))
		document.no_show_form.submit();
}
</script>