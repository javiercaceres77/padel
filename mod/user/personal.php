<?php

if($_POST) {

	$address = $_POST['pers_addr'] .'<br>'. $_POST['pers_postcode'] .'<br>'. $_POST['pers_city'] .'<br>'. $_POST['pers_province'];
	$upd_array = array('full_name' => $_POST['pers_name'], 'phone1' => $_POST['pers_phone'], 'phone2' => $_POST['pers_phone2'], 'address' => $address, 'DOB' => $_POST['pers_DOB']);
	if(update_array_db('users', 'user_id', $_SESSION['login']['user_id'], $upd_array)) {
		$upd_array = array('user_name' => $_POST['pers_name']);
		update_array_db('members', 'user_id', $_SESSION['login']['user_id'], $upd_array);
		
		$text = 'Datos actualizados correctamente';
		add_alert('user', 'info', 1, $text);
//		print_alerts($_GET['mod']);
	}

	
	if($_POST['CCC_ent'] && $_POST['CCC_suc'] && $_POST['CCC_DC'] && $_POST['CCC_acc'] && $_POST['CCC_titular']) {
		if(digito_control($_POST['CCC_ent'] . $_POST['CCC_suc'], $_POST['CCC_acc']) == $_POST['CCC_DC']) {
		
			$ccc = encode($_POST['CCC_ent'] .'/'. $_POST['CCC_suc'] .'/'. $_POST['CCC_DC'] .'/'. $_POST['CCC_acc']);
			$ccc_name = encode($_POST['CCC_titular']);
	
			$ins_array = array('user_id' => $_SESSION['login']['user_id'], 'details' => $ccc, 'ccc_name' => $ccc_name);
			insert_array_db('payment_user_details', $ins_array);
		}
		else {
			$text = 'El número de cuenta no es válido';
			add_alert('user', 'alert', 1, $text);
		}
	}
}

# SELECT personal data
$sql = 'SELECT full_name, phone1, phone2, email, address, available_books_num, last_login_datetime, is_member, DOB
		FROM users WHERE user_id = '. $_SESSION['login']['user_id'] .' AND deleted_ind <> \'1\'';
		
$sel_usr = my_query($sql, $conex);
$usr_array = my_fetch_array($sel_usr);

$arr_addr = explode('<br>', $usr_array['address']);

# Check if the user has CCC as a payment method
$sql = 'SELECT * FROM payment_user_details WHERE user_id = \''. $_SESSION['login']['user_id'] .'\'';

$sel_ccc = my_query($sql, $conex);
$exist_ccc = my_num_rows($sel_ccc);

if($exist_ccc)
	$pay_array = my_fetch_array($sel_ccc);

//pa($pay_array);

?>

<div align="right" class="default_text" style="width:95%">
  <?php	if(!$ob_user->is_account_active()) {	?>
  <a href="<?= $conf_main_page ?>?mod=home&view=check_code " title="Activa tu cuenta para poder hacer reservas.">Activar cuenta</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
  <?php	}	
  		if($exist_ccc) {						?>
  <a href="<?= $conf_main_page ?>?mod=user&tab=bonus&subtab=add_bonus">Comprar Bono</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
  <?php	}	?>
  <a href="<?= $conf_main_page ?>?mod=book&tab=user_books">Ver tus reservas</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
  <a href="<?= $conf_main_page; ?>?mod=user&tab=chg_pwd" >Cambiar contraseña</a> </div>
<div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Datos personales</div>
<form name="pers_form" id="pers_form" method="post" action="">
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td bgcolor="#DDDDDD" align="right">Nombre: </td>
      <td align="left"><input type="text" class="inputlarge" name="pers_name" id="pers_name" style="width:170px;" value="<?= $usr_array['full_name']; ?>" maxlength="250" /></td>
      <td bgcolor="#DDDDDD" align="right">Teléfono: </td>
      <td align="left"><input type="text" class="inputlarge" name="pers_phone" id="pers_phone" style="width:170px;" value="<?= $usr_array['phone1']; ?>" maxlength="250" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">e-mail:</td>
      <td align="left"><input type="text" class="inputlarge" disabled="disabled" name="pers_email" id="pers_email" style="width:170px;" value="<?= $usr_array['email']; ?>" maxlength="250" /></td>
      <td bgcolor="#DDDDDD" align="right">Tel&eacute;fono 2:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_phone2" id="pers_phon2e" style="width:170px;" value="<?= $usr_array['phone2']; ?>" maxlength="45" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Fecha de nacimiento:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_DOB" id="pers_DOB" style="width:90px;" value="<?= $usr_array['DOB']; ?>" maxlength="250" onblur="JavaScript:construct_date('pers_DOB');"/>
        <span class="small_text"> &nbsp;(aaaa-mm-dd)</span></td>
      <td align="right">&nbsp;</td>
      <td align="left">&nbsp;</td>
    </tr>
  </table>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Dirección</div>
   <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td bgcolor="#DDDDDD" align="right">Calle, n&ordm;, piso, puerta: </td>
      <td colspan="5" align="left"><input type="text" class="inputlarge" name="pers_addr" id="pers_addr" style="width:450px;" value="<?= $arr_addr[0]; ?>" maxlength="250" /></td>
     </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">C&oacute;digo Postal:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_postcode" id="pers_postcode" style="width:70px;" value="<?= $arr_addr[1]; ?>" maxlength="10" /></td>
      <td bgcolor="#DDDDDD" align="right">Ciudad:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_city" id="pers_city" style="width:170px;" value="<?= $arr_addr[2]; ?>" maxlength="75" /></td>
      <td bgcolor="#DDDDDD" align="right">Provincia: </td>
      <td align="left"><input type="text" class="inputlarge" name="pers_province" id="pers_province" style="width:170px;" value="<?= $arr_addr[3]; ?>" maxlength="10" /></td>
    </tr>
  </table> 
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Datos bancarios</div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td align="right" bgcolor="#DDDDDD" width="30%">C&oacute;digo Cuenta Corriente: </td>
      <td align="left"><?php
      if($exist_ccc) {
	  	echo '******'. substr(decode($pay_array['details']), 18) .' <img src="'. $conf_images_path .'info.png" title="Para cambiar el número de cuenta debes solicitarlo en persona en nuestro club." align="absmiddle" />';
		
/*	  	if(!$pay_array['authorized_method_ind']) {
			echo '<br />El código de tu cuenta corriente no ha sido autorizado todavía.<br />Debes esperar a que lo autoricemos para poder hacer reservas con este medio de pago.';
		}*/
	  }
	  else {
	  ?><table cellpadding="1" cellspacing="1" border="0">
          <tr class="small_text">
            <td>Entidad</td>
            <td>Sucursal</td>
            <td>D.C.</td>
            <td>Nº Cuenta</td>
          </tr>
          <tr>
            <td><input type="text" class="inputlarge" name="CCC_ent" id="CCC_ent" style="width:60px;" value="" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_suc" id="CCC_suc" style="width:60px;" value="" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_DC" id="CCC_DC" style="width:30px;" value="" maxlength="2" /></td>
            <td><input type="text" class="inputlarge" name="CCC_acc" id="CCC_acc" style="width:120px;" value="" maxlength="10" /></td>
          </tr>
      </table><?php
      }
	  ?></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Nombre del titular de la cuenta: </td>
      <td align="left"><?php
	  if($exist_ccc) {
	  	echo decode($pay_array['ccc_name']);
	  }
	  else {
	  ?><input type="text" class="inputlarge" name="CCC_titular" id="CCC_titular" style="width:170px;" value="" maxlength="250" /><?php	}	?></td>
    </tr>
    <tr>
      <td colspan="2">Puedes hacer hasta <strong><?= $usr_array['available_books_num']; ?></strong> reservas a la vez con pago en efectivo.</td>
    </tr>
  </table>
  <div align="center" style="padding:20px 0px 20px 0px;"><input type="button" name="save" class="button" value="  GUARDAR  " onclick="JavaScript:save_data();" /></div>
</form>
</div>
<script language="JavaScript">
function save_data() {
	var error = '';
	with(document.pers_form) {
		if(pers_name.value == '')	error+= 'El nombre no puede estar vacío.\n';
	}
	
	if(error == '')
		document.pers_form.submit();
	else
		alert(error);
}

show_alerts();
</script>