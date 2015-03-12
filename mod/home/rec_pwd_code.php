<div class="standard_container"> <span class="active_tab big_tab_text">Asistente para restaurar contraseña</span>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
  <?php

if($_GET['m']) {
	$email = decode($_GET['m'])	;
}
else {
	jump_to($conf_main_page . '?mod=home&view=rec_pwd');
}


if($_POST) {
	$arr_upd = array('pasapalabra' => digest(substr($email, 0, 2) . $_POST['new_pwd']), 'change_pwd_code' => '');
	if(update_array_db('users', 'email', $email, $arr_upd)) {
		write_log_db('login', 'pwd_changed', 'password changed for: '. $email);
/*		$change_user = new user($email);
		
		$to = $email;
		$subject = 'Recuperación de contraseña en Padel Indoor Ponferrada';
		
		$headers = 'To: "'. $change_user->get_user_name() .'" <'. $email .'>' . "\r\n";
		$headers .= 'From: Padel Indoor Ponferrada <info@padelindoorponferrada.com>' . "\r\n";
	
		$message = "Tu contraseña en Pádel Indoor Ponferrada ha sido cambiada.

Utiliza el siguiente código: ". $check_code ."\n

o bien ve directamente a ". $conf_main_url . $conf_main_page ."?mod=home&view=rec_pwd_code&code=". $check_code ."\n\n
Si no has solicitado el cambio de contraseña símplemente ignora este mensaje.\n
No dudes en contactar con nosotros si tienes cualquier problema.\n\n
Un saludo\n\nWWW.PADELINDOORPONFERRADA.COM ". $conf_main_phone_contact;
										
		@mail($to, $subject, $message, $headers);*/
		?>
  <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
    <tr>
      <td>Hemos actualizado tu contraseña correctamente.
        Ya puedes acceder a tu cuenta utilizando tu dirección de e-mail y tu nueva contraseña</td>
    </tr>
  </table>
  <?php
	}	//	if(update_array_db('users', 'email', $email, $arr_upd)) {
	else {
		?>
  <div class="indented error_message default_text">Ha habido un error al actualizar tu contraseña.<br />
    Haz clic <a href="<?= $conf_main_page; ?>?mod=home&view=rec_pwd">aquí</a> para intentarlo de nuevo</div>
  <br />
  <?php
	}
	exit();

}



?>
  <form name="reg_form" id="reg_form" method="post" action="">
    <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
      <tr>
        <td colspan="2">Hemos enviado un mensaje a la dirección <strong>
          <?= $email; ?>
          </strong> con un código para crear una contraseña nueva. </td>
      </tr>
      <tr>
        <td align="right" width="33%">Copia y pega el código recibido:</td>
        <td><input type="text" class="inputlarge" name="reg_code" id="reg_code" style="width:300px;" value="<?= $_GET['code']; ?>" maxlength="250" /></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><input type="button" name="register" class="button" value="  CONTINUAR  " onclick="JavaScript:check_data();" /></td>
      </tr>
    </table>
  </form>
  <br />
  <div id="new_pwd_container"></div>
</div>
<script language="javascript">

function check_data() {
	if(document.reg_form.reg_code.value == '')
		alert("Debes introducir el código\nenviado a tu dirección de e-mail");
	else {
		url = 'inc/ajax.php?content=check_code&code='+ document.reg_form.reg_code.value +'&m=<?= $_GET['m']; ?>';
		getData(url, 'new_pwd_container');
	}
}

function show_hide() {
	if(document.pwd_form.hide_pwd.checked)
		document.pwd_form.new_pwd.type = 'password';
	else
		document.pwd_form.new_pwd.type = 'text';
}

</script> 
