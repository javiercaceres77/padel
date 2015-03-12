<?php

$ob_user = new user($_SESSION['login']['user_id']);

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}
$det_user = new user($_GET['user']);
$arr_fare = $det_user->get_slot_fare($_GET['slot_id']);
$arr_pms = $det_user->get_payment_methods();
//$arr_user = $det_user->get_all_details();
$now = new date_time('now');

$_SESSION['multiplayer'][$_GET['ply']]['user_id'] = $det_user->user_id;
$_SESSION['multiplayer'][$_GET['ply']]['user_name'] = $det_user->get_user_name();
$_SESSION['multiplayer'][$_GET['ply']]['is_member'] = $det_user->is_member;

/*
$sql = 'SELECT b.booking_id, b.booking_datetime, b.booking_fare, b.payment_method, c.name, f.fare_name, t.slot_ends, t.slot_starts, p.method_name
FROM bookings b
INNER JOIN courts c ON c.court_id = b.court_id
LEFT JOIN payment_methods p ON p.method_code = b.payment_method
INNER JOIN fares f ON f.fare_id = b.fare_id
INNER JOIN time_slots t ON b.slot_id = t.slot_id
WHERE b.booking_datetime >= \''. $now->datetime .'\'
  AND b.user_id = \''. $det_user->user_id .'\'
  AND b.status = \'confirmed\'
ORDER BY b.booking_datetime DESC';

$sel = my_query($sql, $conex);
$arr_books = array();
while($record = my_fetch_array($sel)) {
	$pm_name = $record['payment_method'] == 'bonus' ? 'Bono' : $record['method_name'];
	$arr_books[$record['booking_id']] = array('id' => $record['booking_id'], 'datetime' => $record['booking_datetime'], 'fare' => $record['booking_fare'],
											 'fare_name' => $record['fare_name'], 'starts' => $record['slot_starts'], 'ends' => $record['slot_ends'],
											 'pm' => $record['payment_method'], 'court' => $record['name'], 'pm_name' => $pm_name);
}

$member_str = $arr_user['is_member'] == '1' ? 'socio' : 'no socio';
?>

<div class="title_4">Reserva para
  <?= $arr_user['full_name'] .' ('. $det_user->user_id .', '. $member_str .')' ; ?>
</div>
<?php	
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
*/

echo '<br /><span class="player_name">'. $det_user->get_user_name() .' ('. $det_user->user_id .')</span>&nbsp;&nbsp;';
if($det_user->is_member)
	echo '<img src="'. $conf_images_path .'user16.png" title="'. $det_user->get_user_name().' es socio." align="absmiddle" />';


echo '<div class="title_4">Pago por adelantado</div>';
if(count($arr_pms)) {
	if($arr_pms['ccc'] || $arr_pms['bonus'] || $arr_pms['card']) {
		$checked = '';
		if($arr_pms['ccc']) {
			$checked = '';
			$desc = 'Cargo en cuenta corriente&nbsp;&nbsp;CCC: ******'. substr($arr_pms['ccc']['ccc'], 19) .'. <strong>'. print_money($arr_fare['fare']) .'</strong>';
			echo '<label><input name="payment_method_'. $_GET['ply'] .'" type="radio" value="ccc" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'ccc\', \''. $_GET['ply'] .'\');" />'. $desc .'</label><br>';
		}
		
		if($arr_pms['bonus']) {
			$plural = count($arr_pms['bonus']) > 1 ? 's' : '';
			$hours = 0;
			foreach($arr_pms['bonus'] as $bonus) $hours+= $bonus['hours'];
			$desc = 'Bono: '. count($arr_pms['bonus']) .' bono'. $plural .', total '. $hours .' horas disponibles<br />';
//					$desc.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="small_text">(una hora por jugador, cuatro horas por pista)</span>';
			echo '<label><input name="payment_method_'. $_GET['ply'] .'" type="radio" value="bonus" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'bonus\', \''. $_GET['ply'] .'\');" />'. $desc .'</label><br>';
		}
		
		if($arr_pms['card']) {
			$desc = 'Tarjeta de crédito &nbsp;&nbsp;<img src="'. $conf_images_path .'credit_cards.png" align="absmiddle" />';
			echo '<label><input name="payment_method_'. $_GET['ply'] .'" type="radio" value="card" '. $checked . $disabled .' onchange="JavaScript:select_pm(\'card\', \''. $_GET['ply'] .'\');" />'. $desc .'</label><br>';
		}
	}	//	if($arr_pms['ccc'] || $arr_pms['bonus'] || $arr_pms['card']) {
	else {
		echo '&nbsp;&nbsp;El usuario no tiene registrado ningún medio de pago con antelación.<br />';
		echo '&nbsp;&nbsp;<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=add_bonus&usr='. $det_user->user_id .'">Agregar bono</a> o <a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $det_user->user_id .'"> registrar número de cuenta corriente</a> para domiciliar los pagos.';
	}
}	//	if(count($arr_pms)) {
# admin can override the max number of cash bookings.
?>
<div class="title_4">Pagar en ventanilla</div>
<label>
<input name="payment_method_<?= $_GET['ply']; ?>" type="radio" value="cash" checked="checked" onchange="JavaScript:select_pm('cash', '<?= $_GET['ply']; ?>');" />
Efectivo / tarjeta (pago en ventanilla) <strong><?= print_money($arr_fare['fare']); ?></strong></label>