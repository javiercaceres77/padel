<?php

if(!$_GET['usr']) {
	jump_to($conf_main_page . '?mod=admin&tab=users');
	exit();
}

$bonus_user = new user($_GET['usr']);
$member_ind = $bonus_user->is_member ? 'socio' : 'no socio';
//$today = new my_date('today');

if($_POST) {
	# Double check that the logged in user is administrator.
	if($ob_user->is_admin()) {
		$bonus_type = new bonus_type($_POST['bonus_type']);
		$bonus_price = $bonus_user->is_member ? $bonus_type->price_members : $bonus_type->price;
		
		switch($_POST['pay_meth']) {
			case 'user_ccc':			$pm = 'ccc';	break;
			case 'cash-cc': default:	$pm = 'cash';	break;
		}
		
		# add the bonus		
		$bonus_id = bonus::add_bonus($bonus_user->user_id, $_POST['bonus_type'], 'now', $ob_user->get_user_name(), 'active', $bonus_type->hours, $pm);
	
		if($pm == 'cash') {	# if it's paid with cash or card, write it on the ledger as paid
			ledger::add_entry($bonus_user->user_id, 'paid', $pm, $bonus_price, 'now', 'bonus added by '. $ob_user->get_user_name(), '', 'bonus', '');
		}
		else {				# if it's paid with ccc, write on the ledger as pending.
			ledger::add_entry($bonus_user->user_id, 'pending', $pm, $bonus_price, 'now', 'bonus added by '. $ob_user->get_user_name(), '', 'bonus', '');
		}
		
	}
	
	if($bonus_id) {
		# everything went ok.
		//$bonus = new bonus($bonus_id);
		
		# send e-mail to user
		$to = $bonus_user->get_email();
		$subject = 'Bono comprado en Padel Indoor Ponferrada';
				
		$headers = 'To: "'. $bonus_user->get_user_name() .'" <'. $bonus_user->get_email() .'>' . "\r\n";
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
  El bono ya está disponible para el usuario.</div>
<div class="default_text indented"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=user_detail&detail=<?= $bonus_user->user_id; ?>">Ver los detalles del usuario</a><br />
  <a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=add_bonus&usr=<?= $bonus_user->user_id; ?>">Agregar nuevo bono para <?= $bonus_user->get_user_name(); ?></a><br />
  <a href="<?= $conf_main_page; ?>?mod=admin&tab=users">&lt; Volver a la lista de usuarios</a></div>
<?php
		write_log_db('Admin_create_bonus', 'bonus_created_ok', 'Bono agregado por '. $_SESSION['login']['name']);
		unset($_POST);
		exit();
	}	//	if($user_id) {
	else {
		$text = 'Ha habido un error al insertar el bono en la base de datos';
		add_alert('admin', 'alert', 1, $text);
	}
}	//	if($_POST) {

?>
<div class="default_text" style="width:95%;"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users">&lt; Volver a la lista de usuarios</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; <a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=user_detail&detail=<?= $bonus_user->user_id; ?>">Ver detalle del usuario</a></div>
<div class="title_3" align="center" style="background-color:#DDDDDD; width:95%;">Añadir bono para
  <?= $bonus_user->get_user_name() .' ('. $bonus_user->user_id .', '. $member_ind .')'; ?>
</div>
<div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Tipo de bono</div>
<form name="add_bonus_form" id="add_bonus_form" method="post" action="">
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <?php
	$sql = 'SELECT * FROM bonus_types WHERE active_ind = \'1\'';
	$sel_bons = my_query($sql, $conex);
	
	$first = true;
	while($record = my_fetch_array($sel_bons)) {
		$checked = $first ? 'checked="checked"' : '';		if($first)	$first = false; 
		$price = $bonus_user->is_member ? $record['price_members'] : $record['price'];
		$disabled = !$bonus_user->is_member && $record['members_only_ind'] == '1' ? 'disabled="disabled"' : '';
?>
    <tr>
      <td class="title_4"><label>
        <input type="radio" name="bonus_type" value="<?= $record['type_code']; ?>" <?= $checked .' '. $disabled; ?> />
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
  <?php
  $user_ccc = '******'. substr($bonus_user->get_ccc(), 19);
  ?>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td><label>
        <input type="radio" name="pay_meth" value="cash-cc" checked="checked" />
        Efectivo o tarjeta de crédito</label>
        &nbsp;&nbsp;&nbsp; <span class="small_text">(Añadir el bono después de cobrar)</span></td>
    </tr>
    <tr>
      <td><label>
        <?php
	  $disabled = $user_ccc == '' ? 'disabled="disabled"' : '';
	  ?>
        <input type="radio" name="pay_meth" value="user_ccc" <?= $disabled; ?> />
        Cargo en cuenta corriente del usuario:
        <?= $user_ccc; ?>
        </label>
      </td>
    </tr>
  </table>
  <div align="center" style="padding:20px 0px 20px 0px;">
    <input type="button" name="save" class="button" value="  AÑADIR BONO  " onclick="JavaScript:save_data();" />
  </div>
</form>
</div>
<script language="JavaScript">
function save_data() {
	document.add_bonus_form.submit();
}
</script>
