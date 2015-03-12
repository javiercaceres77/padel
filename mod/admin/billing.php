<?php

if($_POST['amount'] && $_POST['user_id']) {
	# reconciliate what the user owes
	$sql = 'SELECT sum(amount) as owed FROM ledger WHERE user_id = \''. $_POST['user_id'] .'\' AND entry_status = \'pending\'';
	$sel = my_query($sql, $conex);
	$amount = my_result($sel, 0, 'owed');
	
	if($_POST['amount'] == $amount) {
		$sql = 'UPDATE ledger SET entry_status = \'paid\' WHERE user_id = \''. $_POST['user_id'] .'\'';
		$upd = my_query($sql, $conex);
		if($upd) {
			$notice_text = 'Los pagos del usuario se han marcado como "pagados". Se deben cargar '. print_money($amount) .' a la cuenta del usuario';
			add_alert('admin', 'info', 1, $notice_text);
		}
		else {
			$notice_text = 'Ha habido un error al actualizar los pagos en la base de datos';
			add_alert('admin', 'alert', 1, $notice_text);
		}
		unset($_POST);
	}
}
elseif($_POST) $_SESSION['last_search'] = $_POST;

print_alerts($_GET['mod']);
?>
<table width="100%" border="0" cellpadding="3" cellspacing="2">
  <tr>
    <td class="title_3"><?= ucfirst(search_users); ?></td>
    <td class="default_text" align="right"></td>
  </tr>
  <tr>
    <td colspan="2" class="default_text" bgcolor="#DDDDDD"><form name="form_search_users" id="form_search_users" method="post" action="">
        <?= ucfirst(name); ?>
        :
        <input type="text" class="inputnormal" name="user_name" id="user_name" autofocus="autofocus" maxlength="150" value="<?= $_SESSION['last_search']['user_name']; ?>" />
        &nbsp;&nbsp;&nbsp;Número de usuario :
        <input type="text" class="inputdate" name="user_id" id="user_id" maxlength="10" value="<?= $_SESSION['last_search']['user_id']; ?>" />
        &nbsp;&nbsp;&nbsp;
        e-mail:
        <input type="text" class="inputnormal" name="email" id="email" maxlength="250" value="<?= $_SESSION['last_search']['email']; ?>" />
        &nbsp;&nbsp;&nbsp;
        <input type="submit" value=" <?= ucfirst(search); ?> " name="submit" class="button" />
      </form></td>
  </tr>
  <tr>
    <td colspan="2"><?php

$now = new date_time('now');

$conditions = array();
if($_SESSION['last_search']['user_name'])		$conditions[] = 'u.full_name like \'%'. $_SESSION['last_search']['user_name'] .'%\'';
if($_SESSION['last_search']['email'])			$conditions[] = 'u.email like \'%'. $_SESSION['last_search']['email'] .'%\'';
if($_SESSION['last_search']['user_id'])			$conditions[] = 'u.user_id = '. $_SESSION['last_search']['user_id'];

$sql = 'SELECT u.full_name, u.user_id, u.email, u.is_member,
p.details, p.ccc_name,
l.entry_id, l.entry_status, l.payment_method, l.amount, l.entry_datetime,
l.comments, l.entry_type, l.bill_id
FROM users u
INNER JOIN ledger l ON l.user_id = u.user_id
LEFT JOIN payment_user_details p ON p.user_id = u.user_id
WHERE l.entry_status = \'pending\'';
if(count($conditions)) $sql.= ' AND '. implode(' AND ', $conditions);
$sql.= ' ORDER BY l.entry_datetime DESC';

$sel = my_query($sql, $conex);

$num_results = my_num_rows($sel);

$initial_row = 0;
$final_row = 0;

if($_GET['pag']) $_SESSION['login']['modules'][$_GET['mod']]['nav_page'] = $_GET['pag'];

if(!$_SESSION['login']['modules'][$_GET['mod']]['nav_page']) $_SESSION['login']['modules'][$_GET['mod']]['nav_page'] = 1;

if($_GET['nrows']) $_SESSION['login']['modules'][$_GET['mod']]['nrows'] = $_GET['nrows'];
if(!$_SESSION['login']['modules'][$_GET['mod']]['nrows']) $_SESSION['login']['modules'][$_GET['mod']]['nrows'] = 25;

if(!is_numeric($_SESSION['login']['modules'][$_GET['mod']]['nrows']) || $_SESSION['login']['modules'][$_GET['mod']]['nrows'] < 0 || $_SESSION['login']['modules'][$_GET['mod']]['nrows'] > 500)
	$_SESSION['login']['modules'][$_GET['mod']]['nrows'] = 25;
	
	$parameters = array('page' => $_SESSION['login']['modules'][$_GET['mod']]['nav_page']
					   ,'num_rows' => $num_results ,'num_rows_page' => $_SESSION['login']['modules'][$_GET['mod']]['nrows'], 'class' => 'border_bottom_dotted');

	draw_pages_navigator($parameters);

	$arr_bills = array();
	while($record = my_fetch_array($sel)) {
		$arr_bills[$record['user_id']]['ccc'] = decode($record['details']);
		$arr_bills[$record['user_id']]['name'] = $record['full_name'];
		$arr_bills[$record['user_id']]['email'] = $record['email'];
		$arr_bills[$record['user_id']]['is_member'] = $record['is_member'];
		$arr_bills[$record['user_id']]['ccc_name'] = decode($record['ccc_name']);
		$arr_bills[$record['user_id']]['entries'][$record['entry_id']] = array('pm' => $record['payment_method'],
																			   'amount' => $record['amount'],
																			   'date_time' => $record['entry_datetime'],
																			   'comments' => $record['comments'],
																			   'type' => $record['entry_type']);
	}
?>
      <table border="0" cellpadding="3" cellspacing="2" width="100%" class="default_text">
        <?php
if($num_results > 0) {
	$row = 0;
	foreach($arr_bills as $user_id => $all_details) {
		$img_str = $all_details['is_member'] ? '<img src="'. $conf_images_path .'user16.png" title="Socio" align="absmiddle" />' : '';
		?>
        <tr>
          <td bgcolor="#DDDDDD"><table border="0" cellpadding="1" cellspacing="1" width="90%">
              <tr>
                <td class="title_3" width="33%"><?= $all_details['name'] .', '.  $user_id .' '. $img_str; ?></td>
                <td width="33%"><?= $all_details['email']; ?></td>
                <td width="33%"><?= $all_details['ccc'] .' ('. $all_details['ccc_name'] .')'; ?></td>
              </tr>
            </table></td>
        </tr>
        <tr>
          <td class="sideborderthin bottomborderthin"><table border="0" cellpadding="2" cellspacing="2" width="100%">
              <?php
		$subtotal = 0;
		foreach($all_details['entries'] as $entry_id => $details) {
			$subtotal+= $details['amount'];
			$en_date = new date_time($details['date_time']);
			
			switch($details['type']) {
				case 'booking':		$type = 'Reserva pista'; 	break;
				case 'bonus':		$type = 'Bono';				break;
				case 'membership':	$type = 'Cuota socio';		break;
			}

			?>
              <tr>
                <td class="bottomborderthin" width="25%"><?= $en_date->odate->format_date('med') .'&nbsp;&nbsp;&nbsp;'. $en_date->format_time(); ?></td>
                <td class="bottomborderthin" width="25%"><?= $type; ?></td>
                <td align="right" class="bottomborderthin" width="25%"><?= print_money($details['amount']); ?></td>
                <td align="center" class="bottomborderthin" width="25%"></td>
              </tr>
              <?php
		}
		?>
              <tr>
                <td colspan="2" class="title_4">Total a pagar</td>
                <td class="title_4" align="right"><?= print_money($subtotal); ?></td>
                <td align="center"><input type="button" name="charge_<?= $entry_id; ?>" id="charge_<?= $entry_id; ?>" class="button" value=" COBRAR " onclick="JavaScript:charge_amount('<?= $user_id; ?>', '<?= $subtotal; ?>')" /></td>
              </tr>
            </table></td>
        <tr>
        <tr>
          <td height="8"></td>
        </tr>
        <?php
	}	//	foreach($arr_bills as $user_id => $all_details) {
}	//if($num_results > 0) {
else {
?>
        <tr>
          <td colspan="10" align="center" class="error_message"><?= ucfirst(no_results_found); ?></td>
        </tr>
        <?php
}
			?>
      </table>
      <?php
      $parameters['class'] = 'border_top_dotted';
	  draw_pages_navigator($parameters);
      ?></td>
  </tr>
</table>
<form name="form_bill" id="form_bill" method="post" action="">
  <input type="hidden" name="user_id" id="user_id" />
  <input type="hidden" name="amount" id="amount" />
</form>
<script language="javascript">

function charge_amount(user_id, amount) {
	document.form_bill.user_id.value = user_id;
	document.form_bill.amount.value = amount;
	document.form_bill.submit();
}

</script>