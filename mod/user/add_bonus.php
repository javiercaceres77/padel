<?php

$arr_pms = $ob_user->get_payment_methods();

if($_POST) {
	//pa($_POST);
	
	$bonus_type = new bonus_type($_POST['bonus_type']);
	$bonus_price = $ob_user->is_member ? $bonus_type->price_members : $bonus_type->price;

	# check if the bonus is for members only and the user is not member
	if($bonus_type->members_only && !$ob_user->is_member) {
		write_log_db('hacking', 'member_bonus', $bonus_type->description);
	}
	else {
		switch($_POST['pay_meth']) {
			case 'user_ccc':			$pm = 'ccc';	break;
	//		case 'cash-cc': default:	$pm = 'cash';	break;
		}
		$bonus_id = bonus::add_bonus($ob_user->user_id, $_POST['bonus_type'], 'now', $ob_user->get_user_name(), 'active', $bonus_type->hours, $pm, $bonus_price);
		ledger::add_entry($ob_user->user_id, 'pending', $pm, $bonus_price, 'now', 'bonus bought by user', '', 'bonus', '');
	}
	
	if($bonus_id) {
		# everything went ok.
		//$bonus = new bonus($bonus_id);
		
		# send e-mail to user
		$to = $ob_user->get_email();
		$subject = 'Bono comprado en Padel Indoor Ponferrada';
				
		$headers = 'To: "'. $ob_user->get_user_name() .'" <'. $ob_user->get_email() .'>' . "\r\n";
		$headers .= 'From: Padel Indoor Ponferrada <no-reply@padelindoorponferrada.com>' . "\r\n";
			
		$message = "Este mensaje es para confirmar tu compra de un bono para reservar pistas en Padel Indoor Ponferrada:\n\n";
		$message.= $bonus_type->description ."\n";
		$message.= "Precio: ". print_money($bonus_price);
		$message.= "\n\nWWW.PADELINDOORPONFERRADA.COM ". $conf_main_phone_contact;
								
		@mail($to, $subject, $message, $headers);

		?>

<div class="alert_info default_text">
  <div align="center" class="title_4">Bono añadido correctamente</div>
  <?= $bonus_type->description .' '. print_money($bonus_price); ?>
  <br />
  El bono ya está disponible.</div>
<div class="default_text" style="width:95%;"><a href="<?= $conf_main_page; ?>?mod=user&tab=personal">&lt; Volver a tus datos personales</a></div>
<?php
		write_log_db('user_buy_bonus', 'bonus_created_ok', 'Bono agregado por '. $ob_user->get_user_name());
		unset($_POST);
		exit();
	}	//	if($bonus_id) {
	else {
		$text = 'Ha habido un error al insertar el bono en la base de datos';
		add_alert('admin', 'alert', 1, $text);
	}
}	//	if($_POST) {

?>
<div class="default_text" style="width:95%;"><a href="<?= $conf_main_page; ?>?mod=user&tab=personal">&lt; Volver a tus datos personales</a></div>
<div class="title_3" align="center" style="background-color:#DDDDDD; width:95%;">Comprar bono</div>
<div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Tipo de bono</div>
<form name="add_bonus_form" id="add_bonus_form" method="post" action="">
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <?php
	$sql = 'SELECT * FROM bonus_types WHERE active_ind = \'1\'';
	if(!$ob_user->is_member)
		$sql.= ' AND members_only_ind = \'0\'';
		
	$sel_bons = my_query($sql, $conex);
	
	$first = true;
	while($record = my_fetch_array($sel_bons)) {
		$checked = $first ? 'checked="checked"' : '';		if($first)	$first = false; 
		$price = $ob_user->is_member ? $record['price_members'] : $record['price'];
//		$disabled = !$ob_user->is_member && $record['members_only_ind'] == '1' ? 'disabled="disabled"' : '';
?>
    <tr>
      <td class="title_4"><label>
          <input type="radio" name="bonus_type" value="<?= $record['type_code']; ?>" <?= $checked; ?> />
          <?= $record['type_description']; ?>
        </label></td>
      <td class="title_3"><?= print_money($price); ?></td>
      <td class="small_text"><?= $record['conditions']; ?></td>
    </tr>
    <?php
	}
?>
  </table>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Forma de pago</div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td><?php		if($arr_pms['ccc']) {		?>
        <label>
          <input type="radio" name="pay_meth" id="pay_meth" value="user_ccc" checked="checked" />
          Cargo en cuenta corriente ******<?= substr($arr_pms['ccc']['ccc'], 19); ?>
        </label>
        <div align="center" style="padding:20px 0px 20px 0px;">
          <input type="button" name="save" class="button" value="  AÑADIR BONO  " onclick="JavaScript:save_data();" />
        </div>
		<?php		} 	else			{		?>
        Debes registrar un código de cuenta corriente para comprar bonos.
        <?php		}	?>
        </td>
    </tr>
  </table>
</form>
</div>
<script language="JavaScript">
function save_data() {
	document.add_bonus_form.submit();
}
</script> 
