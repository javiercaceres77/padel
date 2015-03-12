<?php 

if($_SESSION['login']['modules'][$_GET['mod']]['write'])  {
	$now = new date_time('now');
	
	if($_POST['date_from']) 
		$_SESSION['filters']['user_books']['date_from'] = $_POST['date_from'];
	else
		$_SESSION['filters']['user_books']['date_from'] = '';
		
	if($_POST['date_to'])
		$_SESSION['filters']['user_books']['date_to'] = $_POST['date_to'];
	else
		$_SESSION['filters']['user_books']['date_to'] = '';
	
	# Cancel bookings ----------------------------------
	if($_POST['book_to_cancel']) {
		$sql = 'SELECT booking_id, status, booking_fare, cancel_until_datetime, expire_datetime, payment_method, slot_id
		FROM bookings WHERE booking_id = '. $_POST['book_to_cancel'] .' AND user_id = '. $_SESSION['login']['user_id'] .' AND status IN (\'prebook\', \'confirmed\')';
		
		$sel_book = my_query($sql, $conex);
		$arr_book = my_fetch_array($sel_book);
		
		if(count($arr_book)) {
			switch($arr_book['status']) {
				case 'confirmed':
					# Check that indeed it is within the cancelation period.
					$cancel_datetime = new date_time($arr_book['cancel_until_datetime']);
					if($cancel_datetime->timestamp < $now->timestamp) {	# it's too late
						$text = 'La reserva no se puede cancelar por que la hora para cancelar esta pista ('. $cancel_datetime->format_time() .') ha pasado.';
						add_alert('book', 'alert', 1, $text);
					}
					else {
						# Mark booking as cancelled
						$sql = 'UPDATE 	bookings SET status = \'cancelled\', comments = \''. $now->odate->odate .' '. $now->otime .'. Reserva cancelada por usuario\'
								WHERE booking_id = '. $arr_book['booking_id'];
					
						$upd_book = my_query($sql, $conex);
						if(!$upd_book) {
							$text = 'Ha habido un error al cancelar la reserva en nuestra base de datos. Contacta con nosotros para resolver el problema.';
							add_alert('book', 'alert', 1, $text);
						}
						
						# if the booking was paid with a bonus, find an active bonus and add 1 hour to it. if there isn´t any, issue a new one with 4 hours.
						if($arr_book['payment_method'] == 'bonus') {
							# get an active bonus for the user
							$ob_slot = new time_slot($arr_book['slot_id']);
							$slot_hours = $ob_slot->get_time_slot_min() / 60;
							$arr_bonus = simple_select('bonuses', 'user_id', $_SESSION['login']['user_id'] ,'bonus_id', ' AND status = \'active\'');
							if($arr_bonus['bonus_id']) {
								$ob_bonus = new bonus($arr_bonus['bonus_id']);
								$ob_bonus->discount_hours(-$slot_hours);
							}
							else {
								bonus::add_bonus($_SESSION['login']['user_id'], 'bon10', '', 'return', 'active', $slot_hours, 'cash');
								write_log_db('bonus', 'system_create_bonus', 'By cancellation of booking');
							}
						}
						else {	
						/*	# if the booking was paid with cash or CCC or credit card, put it in the ledger.
							$arr_ins = array('user_id'			=> $_SESSION['login']['user_id'],
											 'entry_status'		=> 'pending',
											 'payment_method'	=> $arr_book['payment_method'],
											 'amount'			=> -($arr_book['booking_fare']),
											 'entry_datetime'	=> $now->datetime,
											 'booking_ids'		=> $arr_book['booking_id'],
											 'comments'			=> $now->odate->odate .' '. $now->otime .'. Cancelación de pista',
											 'entry_type'		=> 'booking');
			
							$ins_ledger = insert_array_db('ledger', $arr_ins, true);
							if(!$ins_ledger) {
								write_log_db('ledger', 'error_insert', 'User: '. $_SESSION['login']['user_id']);
								$text = 'Ha habido un error al cancelar la reserva en nuestra base de datos. Contacta con nosotros para resolver el problema.';
								add_alert('book', 'alert', 1, $text);
								
								exit();
							}*/
						}
						
						$text = 'La reserva ha sido cancelada. ';
						if($arr_book['payment_method'] == 'bonus')
							$text.= 'Se ha agregado '. $slot_hours. ' hora a tus bonos';
						elseif($arr_book['payment_method'] != 'cash')
							$text.= 'Se descontarán '. print_money($arr_book['booking_fare']) .' en el mismo medio de pago que usaste para hacer la reserva.';
						
						add_alert('book', 'info', 1, $text);	
						
						
					}	// else {	//	if($cancel_datetime->timestamp < $now->timestamp) {	
				break;
				case 'prebook':
					# remove from DB and from $_SESSION
					$sql = 'DELETE FROM bookings WHERE user_id = \''. $_SESSION['login']['user_id'] .'\' AND booking_id = \''. $arr_book['booking_id'] .'\' AND expire_datetime > \''. $now->datetime .'\' AND status = \'prebook\'';
					$del_book = my_query($sql, $conex);
					if($del_book) {
						unset($_SESSION['pre_books'][$arr_book['slot_id']]);
						$text = 'La reserva ha sido cancelada.';
						add_alert('book', 'info', 1, $text);	
					}
				break;
			}	//	switch($arr_book['status']) {
		}	//	if(count($arr_book)) {
	}
		if(!$_SESSION['filters']['user_books']['date_from'])
		$_SESSION['filters']['user_books']['date_from'] = $now->odate->odate;
?>
<form name="date_filters" id="date_filters" method="post" action="">
  <table width="100%" border="0" cellpadding="5" cellspacing="2" class="default_text">
    <tr>
      <td bgcolor="#DDDDDD"><span class="title_4">Buscar reservas </span>&nbsp;&nbsp;&nbsp;&nbsp;Desde:
        <input type="text" id="date_from" name="date_from" class="inputnormal" value="<?= $_SESSION['filters']['user_books']['date_from']; ?>" onblur="JavaScript:construct_date('date_from');" />
        &nbsp;&nbsp;Hasta:
        <input type="text" id="date_to" name="date_to" class="inputnormal" value="<?= $_SESSION['filters']['user_books']['date_to']; ?>" onblur="JavaScript:construct_date('date_to');"/>
        &nbsp;&nbsp;
        <input type="button" name="search" value="  Buscar  " class="button" onclick="JavaScript:search_books();" /></td>
    </tr>
  </table>
</form>
<?php

	$conditions = array();
	if($_SESSION['filters']['user_books']['date_from'])		$conditions[] = 'b.booking_datetime >= \''. $_SESSION['filters']['user_books']['date_from'] .' 00:00:00\'';
	if($_SESSION['filters']['user_books']['date_to'])		$conditions[] = 'b.booking_datetime <= \''. $_SESSION['filters']['user_books']['date_to'] .' 23:59:59\'';
	$conditions[] = 'b.user_id = '. $_SESSION['login']['user_id'];

	$sql = 'SELECT b.booking_id, b.booking_datetime, b.booking_ends_datetime, b.booking_fare, b.cancel_until_datetime, b.status, b.cancel_until_datetime, b.payment_method,
	p.method_name, c.name, b.expire_datetime/*, f.fare_name, t.slot_ends, t.slot_starts*/
	FROM bookings b
	INNER JOIN courts c ON c.court_id = b.court_id
	LEFT JOIN payment_methods p ON p.method_code = b.payment_method
	/*INNER JOIN fares f ON f.fare_id = b.fare_id
	INNER JOIN time_slots t ON b.slot_id = t.slot_id*/
	WHERE '. implode(' AND ', $conditions) .'
	  AND (b.status <> \'prebook\' OR (b.status = \'prebook\' AND b.expire_datetime > \''. $now->datetime .'\'))
	ORDER BY b.booking_datetime DESC';
	
	$sel_books = my_query($sql, $conex);
	
?>
<form name="cancel_books" id="cancel_books" method="post" action="">
  <table width="100%" cellpadding="2" cellspacing="1" border="0" class="default_text">
    <?php	
	while($record = my_fetch_array($sel_books)) {
		$booking_datetime = new date_time($record['booking_datetime']);
		$booking_ends_datetime = new date_time($record['booking_ends_datetime']);
		$cancel_datetime = new date_time($record['cancel_until_datetime']);
		$expire_datetime = new date_time($record['expire_datetime']);
		
?>
    <tr>
      <td><div class="title_4">
          <?= $booking_datetime->odate->format_date('long');  ?>
        </div>
        <div class="default_text">
          <?= $booking_datetime->hour .':'. $booking_datetime->minute .' - '. $booking_ends_datetime->hour .':'. $booking_ends_datetime->minute; ?>
        </div></td>
      <td align="center" class="title_3"><?= $record['name']; ?></td>
      <td align="center"><?php
	  if($record['payment_method'] != 'bonus') {
	  ?>
	  <div class="title_3" title="Tarifa <?= $record['fare_name']; ?>">
          <?= print_money($record['booking_fare']); ?>
        </div>
        <div class="small_text">
          <?= $record['method_name']; ?>
        </div><?php
	  }
	  else
	  	echo '<div class="title_3">Bono</div>';
	  ?></td>
      <td align="center"><?php
	switch($record['status']) {
		case 'prebook':
			$str = 'pre-reserva';
			$class = 'ts_prebook';
		break;
		case 'confirmed':
			if($booking_datetime->timestamp > $now->timestamp) {
				$str = 'confirmada';
				$class = 'ts_confirmed';
			}
			else {
				$str = '';
				$class = '';
			}
		break;
		case 'paid':
			$str = 'pagada';
			$class = 'ts_confirmed';
		break;
		case 'cancelled':
			$str = 'cancelada';
			$class = 'ts_cancelled';
		break;
		
	}
	 ?>
        <div class="round_borders_3 <?= $class; ?>" style="padding:4px; width:80px;">
          <?= $str; ?>
        </div></td>
      <td align="center"><?php
	if($cancel_datetime->timestamp > $now->timestamp) {
		if($record['status'] == 'prebook')
			$title = 'Caduca a las '. $expire_datetime->format_time();
		elseif($record['status'] == 'confirmed')
			$title = 'Cancelación disponible hasta las '. $cancel_datetime->format_time();
		else
			$title = '';
	?>
        <input type="button" name="cancel_<?= $record['booking_id']; ?>" value="Cancelar" title="<?= $title; ?>" class="button_small" onclick="JavaScript:cancel_book('<?= $record['booking_id']; ?>');" />
   <?php	
	}		//	if($cancel_datetime->timestamp > $now->timestamp) {
	?></td>
    </tr>
    <tr>
      <td colspan="5" height="3" bgcolor="#DDDDDD"></td>
    </tr>
    <?php
	}
?>
  </table>
  <input type="hidden" name="book_to_cancel" id="book_to_cancel" value="" />
</form>
<?php
}	//if($_SESSION['login']['modules'][$_GET['mod']]['write'])  {
?>
<script language="javascript">
function search_books() {
	document.date_filters.submit();
}

function cancel_book(book_id) {
	document.cancel_books.book_to_cancel.value = book_id;
	document.cancel_books.submit();
}

show_alerts();
</script>
