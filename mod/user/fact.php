<?php 

if($_SESSION['login']['modules'][$_GET['mod']]['write'])  {
	$now = new date_time('now');
	
	if($_POST['date_from']) 
		$_SESSION['filters']['fact']['date_from'] = $_POST['date_from'];
	else
		$_SESSION['filters']['fact']['date_from'] = '';
		
	if($_POST['date_to'])
		$_SESSION['filters']['fact']['date_to'] = $_POST['date_to'];
	else
		$_SESSION['filters']['fact']['date_to'] = '';
	
?>

<form name="date_filters" id="date_filters" method="post" action="">
  <table width="100%" border="0" cellpadding="5" cellspacing="2" class="default_text">
    <tr>
      <td bgcolor="#DDDDDD"><span class="title_4">Buscar pagos </span>&nbsp;&nbsp;&nbsp;&nbsp;Desde:
        <input type="text" id="date_from" name="date_from" class="inputdate" value="<?= $_SESSION['filters']['user_books']['date_from']; ?>" onblur="JavaScript:construct_date('date_from');" />
        &nbsp;&nbsp;Hasta:
        <input type="text" id="date_to" name="date_to" class="inputdate" value="<?= $_SESSION['filters']['user_books']['date_to']; ?>" onblur="JavaScript:construct_date('date_to');"/>
        &nbsp;&nbsp;
        <input type="button" name="search" value="  Buscar  " class="button" onclick="JavaScript:search_books();" /></td>
    </tr>
  </table>
</form>
<?php
	$conditions = array();
	if($_SESSION['filters']['fact']['date_from'])		$conditions[] = 'entry_datetime >= \''. $_SESSION['filters']['fact']['date_from'] .' 00:00:00\'';
	if($_SESSION['filters']['fact']['date_to'])		$conditions[] = 'entry_datetime <= \''. $_SESSION['filters']['fact']['date_to'] .' 23:59:59\'';
	$conditions[] = 'user_id = '. $ob_user->user_id;

	$sql = 'SELECT entry_status, payment_method, amount, entry_datetime, booking_ids, entry_type, bill_id FROM ledger
	WHERE '. implode(' AND ', $conditions) .'
	ORDER BY entry_datetime DESC';
	
	$sel_books = my_query($sql, $conex);
	
?>
<table width="100%" cellpadding="2" cellspacing="1" border="0" class="default_text">
  <tr>
    <th align="left">Fecha &ndash; Hora</th>
    <th align="left">Descripción</th>
    <th align="left">Estado</th>
    <th align="left">Medio pago</th>
    <th align="right">Cantidad</th>
  </tr>
  <?php	
	while($record = my_fetch_array($sel_books)) {
		$entry_datetime = new date_time($record['entry_datetime']);
		switch($record['entry_type']) {
			case 'booking':		$type = 'Reserva pista'; 	break;
			case 'bonus':		$type = 'Bono';				break;
			case 'membership':	$type = 'Cuota socio';		break;
		}
		
		switch($record['entry_status']) {
			case 'pending':		$status = 'Pendiente pago';	break;
			case 'paid':		$status = 'Pagado';			break;
			case 'cancelled':	$status = 'Cancelado';		break;
			case 'unpaid':		$status = 'Impagado';		break;
		}
		
		switch($record['payment_method']) {
			case 'ccc':			$method = 'Cargo en cuenta';break;
			case 'cash':		$method = 'Efectivo';		break;
		}
		
?>
  <tr>
    <td colspan="5" height="3" class="bg_ddd"></td>
  </tr>
  <tr>
    <td><?= $entry_datetime->odate->format_date('med') .' &ndash; '. $entry_datetime->format_time(); ?></td>
    <td class="title_4"><?= $type; ?></td>
    <td><?= $status; ?></td>
    <td><?= $method; ?></td>
    <td align="right" class="title_4"><?= print_money($record['amount']); ?></td>
  </tr>
  <?php
	}
?>
</table>
<input type="hidden" name="book_to_cancel" id="book_to_cancel" value="" />
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
