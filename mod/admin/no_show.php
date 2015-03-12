<?php

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$now = new date_time('now');

if($_POST) {

	$arr_upd = array('status' => 'noshow');
	if(update_array_db('bookings', 'booking_id', $_POST['booking_id'], $arr_upd)) {
		# insert no_show
		$arr_ins = array('user_id' => $_POST['user_id'], 'noshow_datetime' => $now->datetime, 'booking_id' => $_POST['booking_id'], 'comments' => $_POST['comments']);
		insert_array_db('noshows', $arr_ins);
		
		# increase user # of noshows
		$det_user = new user($_POST['user_id']);
		$det_user->increase_num_noshows();
		
		echo '<div class="title_3 indented">Se ha actualizado la reserva. y se ha registrado como "No presentado"</br>';
		echo '<a href="'. $conf_main_page .'?mod=admin&tab=bookings">&lt; Volver a las reservas</a></div>';
		exit();
	}

}

# get slot information
$sql = 'SELECT t.slot_starts, t.slot_ends, t.date_db, f.fare, f.fare_name, f.is_member, c.court_id, c.name
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
$sql = 'SELECT u.full_name, u.user_id, u.is_admin, b.user_is_member, b.booking_fare, b.book_placement_datetime, b.payment_method, b.booking_id
FROM bookings b
INNER JOIN users u on u.user_id = b.user_id
WHERE b.slot_id = \''. $_GET['detail'] .'\' AND b.status = \'confirmed\'';

$sel = my_query($sql, $conex);

$arr_book = my_fetch_array($sel);
if(!count($arr_book)) {
	exit();
}

$book_datetime = new date_time($arr_book['book_placement_datetime']);
//pa($arr_book);
?>

<div class="title_3"> Jugador
  <?= $arr_book['full_name']; ?>
  <strong>No presentado</strong><br />
  <?= $arr_slot['court_name'] .', '. $arr_slot['date']->format_date('med') .', '. $arr_slot['starts'] .' &ndash; '. $arr_slot['ends']; ?>
</div>
<div class="small_text">
  <?= '&nbsp;&nbsp;&nbsp;(Hizo la reserva el '. $book_datetime->odate->format_date('month_day') .', '. $book_datetime->hour .':'. $book_datetime->minute .')<br />'; ?>
</div>
<div style="background-color:#DDDDDD; padding:4px 15px;" class="default_text">
  <?= $start_str; ?>
</div>
<div class="default_text indented">
  <form name="no_show_form" id="no_show_form" action="" method="post">
    Comentarios (máx. 255)<br />
    <textarea name="comments" id="comments" class="inputnormal"></textarea><br />
      <input type="submit" name="confirm" class="button" id="confirm" value=" CONFIRMAR " />
      <input type="hidden" name="booking_id" id="booking_id" value="<?= $arr_book['booking_id']; ?>" />
      <input type="hidden" name="user_id" id="user_id" value="<?= $arr_book['user_id']; ?>" />
  </form>
</div>