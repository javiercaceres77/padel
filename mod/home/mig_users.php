<?php

if($_POST) {
	$user_id = decode($_POST['user_id']);
	
	$ob_user = new user($user_id);
	
	if($ob_user->acitvate_migrated_user($_POST['pass'])) {
	?>
	<table border="0" style="margin-top:20px; margin-left:255px;" width="400" class="default_text">
    <tr><td>
    <h5>¡Ya está!<br />
	tu usuario ha sido activado correctamente. Ya puedes entrar con tu número de usuario y tu contraseña y empezar a hacer reservas :-)</h5>
    </td></tr>
    </table>
    <?php
		# send ocnfirmation e-mail
		$user_email = $ob_user->get_email();
			
		$arr_vars = array('site' => $conf_main_url);
				  
		if($_SERVER['SERVER_NAME'] != 'localhost')
			mail_templates::send_mail($user_email, 'migrated_ok', $arr_vars);
	}
	else {
		echo 'ha habido un error al activar el usuario, contacta con nosotros para resolver el problema';	
		write_log_db('new_user', 'user_migrated_error', 'User: '. $user_id);
	}
	exit();
}

# Send unser code
if($_GET['func'] == 'send_code' && $_GET['m']) {
	$user_id = decode($_GET['m']);
	$ob_user = new user($user_id);

	$user_email = $ob_user->get_email();
	$user_det = $ob_user->get_all_details();

	$arr_vars = array('site' => $conf_main_url
					 ,'code' => $user_det['migration_code']
					 ,'url' => $conf_main_url . $conf_main_page .'?mod=home&view=mig_users&m='. $_GET['m']);
			  
	if($_SERVER['SERVER_NAME'] != 'localhost')
		mail_templates::send_mail($user_email, 'send_mig_code', $arr_vars);

	?>
	<table border="0" style="margin-top:20px; margin-left:255px;" width="400" class="default_text">
    <tr><td>
    <h5>Hemos enviado un e-mail a la dirección que nos proporcionaste.<br />
Revisa tu bandeja de entrada y la bandeja de spam.<br />
Si no lo recibes contacta con nosotrs en <?= $conf_main_phone_contact; ?><br />
<br />
<a href="<?= $conf_main_page .'?mod=home&view=mig_users&m='. $_GET['m']; ?>">< Volver</a></h5>
    </td></tr>
    </table>
    <?php
	exit();
}

$user_id = decode($_GET['m']);
$user_status = user::validate_login($user_id, 'x');

if($user_status == 'MIGRATED') {
?>
<form action="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&view='. $_GET['view'] .'&action=validate'; ?>" method="post" name="mig_form">
  <table border="0" style="margin-top:20px; margin-left:255px;" width="400" class="default_text">
  <tr>
  <td>
  <h5>¡Bienvenido!</h5>
  <p>Padel Indoor Ponferrada ha cambiado.<br>
Para acceder a tu cuenta debes introducir el código que te hemos proporcionado y crear una nueva contraseña.<br />
Si no tienes este código te enviaremos otro a tu dirección de correo electrónico: <a href="<?= $conf_main_page .'?mod=home&view=mig_users&m='. $_GET['m'] .'&func=send_code'; ?>">Enviar nuevo código</a></p>
  </td>
  </tr>
    <tr>
      <td><div class="small_text">código de activación</div>
        <input name="mig_code" type="text" class="input_normal" id="mig_code" maxlength="10" autofocus="autofocus" tabindex="4" style="width: 150px;" /></td>
    </tr>
    <tr>
      <td style="padding-top:15px;"><div class="small_text">
          nueva contraseña (6 a 32 caractéres)
        </div>
        <input name="pass" type="text" class="input_normal" id="pass" maxlength="30" tabindex="5" style="width: 150px;" />
        <input name="user_id" type="hidden" value="<?= $_GET['m']; ?>" />
        <label>
            <input type="checkbox" name="hide_pwd" id="hide_pwd"  onchange="JavaScript:show_hide()"/>
            ocultar contraseña
          </label>
        </td>
    </tr>
     <!--  <tr>
      <td style="padding-top: 15px;" class="small_text">Para evitar spam, por favor, escribe el resultado de la siguiente operación en <strong>número</strong>:</td>
    </tr>
    <tr>
      <td ><table border="0">
          <tr>
            <td width="170" align="right"><div id="captcha_container"></div></td>
            <td><div style="position:relative;">
                <input name="captcha" type="text" class="input_normal" id="captcha" maxlength="3" style="width: 125px;" />
                <a href="JavaScript:reload_captcha();"><img src="<?= $conf_images_path; ?>reload.png" alt="<?= ucfirst(reload); ?>" title="Recargar" width="24" height="24" border="0" align="absmiddle" /></a>
              </div></td>
          </tr>
        </table></td>
    </tr>-->

    <tr>
      <td align="center" style="padding-top:15px;"><input name="Submit" type="submit" class="button" value="   Activar Cuenta   " tabindex="3" style="width:140px;" /></td>
    </tr>

  </table>

</form>
<div style="height:120px;"></div>
<?php
}
else {
	jump_to($conf_main_page);
}
?>
<script language="javascript">

//document.onload = reload_captcha();

function reload_captcha() {
	url = 'inc/ajax.php?content=captcha';
	getData(url, 'captcha_container');
}


function show_hide() {
	if(document.mig_form.hide_pwd.checked)
		document.mig_form.pass.type = 'password';
	else
		document.mig_form.pass.type = 'text';
}

</script>