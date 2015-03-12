<?php

$now = new date_time('now');
$expire_time = $now->plus_mins(get_config_value('min_expiry_pre_books'));

if(!$_SESSION['pre_books']) {
	# Load the $_SESSION array if there are pre-books on the database
/*	$sql = 'SELECT b.booking_fare, f.fare_name, f.fare_id, f.time_starts, f.time_ends, b.booking_datetime, b.court_id, c.name as court_name, t.slot_id, t.slot_starts, t.slot_ends, b.expire_datetime
			FROM bookings b
			INNER JOIN fares f ON b.fare_id = f.fare_id
			INNER JOIN courts c ON b.court_id = c.court_id
			INNER JOIN time_slots t on b.slot_id = t.slot_id
			WHERE b.user_id = \''. $_SESSION['login']['user_id'] .'\'
			  AND b.expire_datetime > \''. $now->datetime .'\'
			  AND b.status = \'prebook\'';

	$sel_prebs = my_query($sql, $conex);
	while($record = my_fetch_array($sel_prebs)) {
		
	}
*/
	$_SESSION['pre_books'] = array();
}
 
#review the $_SESSION and remove expired pre_books
remove_expired_pre_books($now->timestamp);

if($_GET['detail']) {
	# Check for conflicts with other users on this slot:
	$ob_slot = new time_slot($_GET['detail']);
	$booking_datetime = $ob_slot->get_date_time();
	$booking_ends_datetime = $booking_datetime->plus_mins($_GET['duration']);
		
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
		//echo 'solapa!!';
	
		# do nothing, maybe an alert?
	}
	else {
		
		
	
		$other_slots = $ob_slot->get_following_slots_db($_GET['duration']);
		$arr_slots = $other_slots;
		$arr_slots[] = $ob_slot->slot_id;
		
		$sql = 'SELECT f.fare, f.fare_name, f.fare_id, f.time_starts, f.time_ends, f.date_db, c.court_id, c.name as court_name, t.slot_starts, t.slot_ends, t.slot_id
				FROM fares f
				INNER JOIN time_slots t ON f.date_id = t.date_id AND t.slot_starts >= f.time_starts	AND t.slot_starts < f.time_ends
				INNER JOIN courts c ON t.court_id = c.court_id
				WHERE f.is_member = \''. $_SESSION['login']['is_member'] .'\'
				AND t.slot_id IN ('. implode(',', $arr_slots) .')';//'. $_GET['detail'];
				
		$sel_fare = my_query($sql, $conex);
		
		$max_num_books = get_config_value('max_number_books_pre');
		
		if(count($_SESSION['pre_books']) >= $max_num_books) {
			$text = ucfirst(you_can_book_only) .' '. $max_num_books .' '. each_time .'.';
			add_alert('book', 'info', 1, $text);
		}
		else{
			$fare = 0;
			while($record = my_fetch_array($sel_fare)) {
				$fare += ($record['fare']) / 2;
				
				$_SESSION['pre_books'][$ob_slot->slot_id]['court_id'] = $record['court_id'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['court_name'] = $record['court_name'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['book_starts'] = $ob_slot->get_time()->time;
				$_SESSION['pre_books'][$ob_slot->slot_id]['book_date'] = $record['date_db'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['book_ends'] = $booking_ends_datetime->otime;
				$_SESSION['pre_books'][$ob_slot->slot_id]['expire_date'] = $expire_time->odate->odate;
				$_SESSION['pre_books'][$ob_slot->slot_id]['expire_time'] = $expire_time->otime;
				$_SESSION['pre_books'][$ob_slot->slot_id]['slot_ids'] = $arr_slots;
					
				$_SESSION['pre_books'][$ob_slot->slot_id]['fares'][$record['fare_id']]['fare_name'] = $record['fare_name'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['fares'][$record['fare_id']]['time_starts'] = $record['time_starts'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['fares'][$record['fare_id']]['time_ends'] = $record['time_ends'];
				$_SESSION['pre_books'][$ob_slot->slot_id]['fares'][$record['fare_id']]['fare'] = $record['fare'] / 2;
				
			}
			$_SESSION['pre_books'][$ob_slot->slot_id]['fare'] = $fare;

			# insert the pre-book in the database
		//	$booking_datetime = new date_time($_SESSION['pre_books'][$ob_slot->slot_id]['book_date'], $_SESSION['pre_books'][$ob_slot->slot_id]['book_starts']);
			$cancel_period = get_config_value('min_advance_cancel_booking') * 60;
			$cancel_datetime = $booking_datetime->plus_mins(-$cancel_period);
	//		$booking_ends_datetime = $booking_datetime->plus_mins($_GET['duration']);
			# ---- booking ends datetime
			
			$arr_ins = array('user_id' 					=> $_SESSION['login']['user_id'],
							 'user_is_member' 			=> $_SESSION['login']['is_member'],
							 'slot_id' 					=> $ob_slot->slot_id,
							 'slots_list'				=> implode(',', $arr_slots),
							 'booking_datetime' 		=> $booking_datetime->datetime,
							 'booking_ends_datetime'	=> $booking_ends_datetime->datetime,
							 'book_placement_datetime' 	=> $now->datetime,
							 'status'					=> 'prebook',
							 'booked_by'				=> 'web',
							 'channel'					=> 'web',
							 'court_id'					=> $_SESSION['pre_books'][$ob_slot->slot_id]['court_id'],
							 'booking_fare'				=> $_SESSION['pre_books'][$ob_slot->slot_id]['fare'],
							 'cancel_until_datetime'	=> $cancel_datetime->datetime,
							 'expire_datetime'			=> $expire_time->datetime,
							 'fare_id'					=> implode_keys(',',$_SESSION['pre_books'][$ob_slot->slot_id]['fares'])
							 );
			
			$book_id = insert_array_db('bookings', $arr_ins, true);
			
			if(!$book_id) {
				$text = 'Error al insertar la reserva en nuestra base de datos. Inténtalo otra vez o contacta con nosotros para informarnos del problema.';
				add_alert('book', 'alert', 1, $text);
			}
			else
				$_SESSION['pre_books'][$_GET['detail']]['booking_id'] = $book_id;
		}
	}
}	// if($_GET['detail']) {

if(count($_SESSION['pre_books'])) {
?>
<table border="0" cellpadding="4" cellspacing="3" width="100%" class="default_text">
  <tr>
    <th class="bottomborderthin" colspan="5" align="left">Pistas seleccionadas:</th>
  </tr>
  <?php
	foreach($_SESSION['pre_books'] as $slot_id => $pre_book) {
		$book_date = new my_date($pre_book['book_date']);
		$book_time = new my_time($pre_book['book_starts']);
		$book_end_time = new my_time($pre_book['book_ends']);
?>
  <tr>
    <td class="bottomborderthin"><?= $pre_book['court_name']; ?></td>
    <td class="bottomborderthin"><?= $book_date->get_weekday_desc() .', '. $book_date->format_date('long'); ?></td>
    <td class="bottomborderthin"><?= $book_time->hour .':'. $book_time->minute .' a '. $book_end_time->hour .':'. $book_end_time->minute; ?></td>
    <td class="bottomborderthin"><?= print_money($pre_book['fare']); ?></td>
    <td class="bottomborderthin small_text">[<a href="JavaScript:delete_pre_book('<?= $slot_id; ?>')">borrar</a>]</td>
  </tr>
  <?php
	}
?>
  <tr>
    <td colspan="3" class="small_text">La &ldquo;pre-reserva&rdquo; sobre estas pistas caduca a los 15 minutos.<br />
Los precios son por persona y hora. En reservas de 90 min. la tarifa es &times;1.5</td>
    <td height="50" colspan="2" align="center"><input type="button" name="confirm" id="confirm" value="  Confirmar &gt;  " onclick="JavaScript:confirm_bookings();" class="button" /></td>
  </tr>
</table>
<?php
}
?>