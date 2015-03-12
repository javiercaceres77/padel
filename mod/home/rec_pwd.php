<?php

if($_POST) {
	# Check captcha
	if($_POST['reg_captcha'] == $_SESSION['misc']['captcha']) {
		# Check email already exists
		$ob_user = new user($_POST['reg_email']);
		
		$m = encode($_POST['reg_email']);
		if($ob_user->user_id != 0) {
			
			//$user_name = my_result($sel_usr, 0, 'full_name');
			$check_code = md5(rand() . date('his'));
			$arr_upd = array('change_pwd_code' => $check_code);
			
			if(update_array_db('users', 'user_id', $ob_user->user_id, $arr_upd)) {
				write_log_db('login', 'pwd_recovery', 'password recovery requested for: '. $_POST['reg_email']);
				
				if($_POST['pwd_user'] == 'user') {
					$arr_vars = array('user_id' => $ob_user->user_id, 'site' => $conf_main_url);
			  
					if($_SERVER['SERVER_NAME'] != 'localhost')
						mail_templates::send_mail($ob_user->get_email(), 'send_user_id', $arr_vars);					
				}
				else {
					$arr_vars = array('site' => $conf_main_url
						 ,'code' => $check_code
						 ,'url' => $conf_main_url . $conf_main_page .'?mod=home&view=rec_pwd_code&code='. $check_code .'&m='. $m);
			  
					if($_SERVER['SERVER_NAME'] != 'localhost')
						mail_templates::send_mail($ob_user->get_email(), 'send_rec_pwd_code', $arr_vars);
				}

			/*			
			
				$to = $_POST['reg_email'];
				$subject = 'Recuperación de contraseña en Padel Indoor Ponferrada';
				
				$headers = 'To: "'. $user_name .'" <'. $_POST['reg_email'] .'>' . "\r\n";
				$headers .= 'From: Padel Indoor Ponferrada <info@padelindoorponferrada.com>' . "\r\n";
			
				$message = "Hemos recibido una solicitud para restablecer la contraseña asociada a esta\n
dirección de e-mail en Pádel Indoor Ponferrada.

Utiliza el siguiente código: ". $check_code ."\n

o bien ve directamente a ". $conf_main_url . $conf_main_page ."?mod=home&view=rec_pwd_code&code=". $check_code ."&m=". $m ."\n\n
Si no has solicitado el cambio de contraseña símplemente ignora este mensaje.\n
No dudes en contactar con nosotros si tienes cualquier problema.\n\n
Un saludo\n\nwww.padelindoorponferrada.com ". $conf_main_phone_contact;
												
				@mail($to, $subject, $message, $headers);*/
			}	//if(update_array_db('users', 'email', $_POST['reg_email'], $arr_upd)) {

		}	//	if($ob_user->user_id != 0) {
		
		$visibility_error_captcha = 'hidden';
		
		if($_POST['pwd_user'] == 'user') {
			$alert = 'hemos enviado el número de socio a la dirección que nos has indicado';
			add_alert('home', 'info', 1, $alert);	
		}
		else {
			jump_to($conf_main_page .'?mod=home&view=rec_pwd_code&m='. $m);
			exit();
		}
	}
	else {
		$visibility_error_captcha = 'visible';		
	}
}
else
	$visibility_error_captcha = 'hidden';

$email = $_POST['reg_email'] ? $_POST['reg_email'] : decode($_GET['m']);
if($_GET['m'])
	$text = 'Debes crear una contraseña nueva en Pádel Indoor Ponferrada<br>';

?>

<div class="standard_container"> <span class="active_tab big_tab_text">Asistente para restaurar contraseña</span>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
  <form name="reg_form" id="reg_form" method="post" action="">
    <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
      <tr>
        <td colspan="3"><?= $text; ?>Escribe tu dirección de correo para recibir instrucciones para crear una contraseña nueva o tu número de socio.</td>
      </tr>
      <tr>
        <td align="right" width="25%"> Dirección de e-mail:</td>
        <td><input type="text" class="inputlarge" name="reg_email" id="reg_email" style="width:170px;" value="<?= $email; ?>" maxlength="250" /></td>
        <td><div id="error_email" class="error_message" style="visibility:hidden">Escribe una dirección de correo electrónico válida</div></td>
      </tr>
      <tr>
        <td colspan="3">Para evitar spam, por favor, escribe el resultado de la siguiente operación en número</td>
      </tr>
      <tr>
        <td align="right"><div id="captcha_container"></div></td>
        <td width="20%"><input type="text" class="inputlarge" name="reg_captcha" id="reg_captcha" style="width:80px;" maxlength="4" />
        <input type="hidden" name="pwd_user" />
          <a href="JavaScript:reload_captcha();"><img src="<?= $conf_images_path; ?>reload.png" alt="Recargar" title="Recargar" width="16" height="16" border="0" align="absmiddle" /></a></td>
        <td><div id="error_captcha" class="error_message" style="visibility:<?= $visibility_error_captcha; ?>">Error. Escribe el resultado en número, ej.:<br />
            diez por tres = 30.</div></td>
      </tr>
      <tr>
        <td colspan="3" align="center"><input type="button" name="register" class="button" value="  RESTAURAR CONTRASEÑA  " onclick="JavaScript:check_data('pwd');" /><br />
<br />
<input type="button" name="register" class="button" value="  ENVIAR NÚMERO DE SOCIO  " onclick="JavaScript:check_data('user');" /></td>
      </tr>
    </table>
  </form>
</div>
<script language="javascript">

document.onload = reload_captcha();

function reload_captcha() {
	url = 'inc/ajax.php?content=captcha';
	getData(url, 'captcha_container');
}

function check_data(p_u) {
	var error = false;
	var error_email = false;
	var error_captcha = false;
	
	with(document.reg_form) {
		error_email = !simple_check_email(reg_email.value);
		error_captcha = reg_captcha.value == '';
	}
	error = error_email || error_captcha;
	
	if(error) {
		if(error_email)		document.getElementById('error_email').style.visibility = 'visible';	else 	document.getElementById('error_email').style.visibility = 'hidden';
		if(error_captcha)	document.getElementById('error_captcha').style.visibility = 'visible';	else 	document.getElementById('error_captcha').style.visibility = 'hidden';
	}
	else {
		document.reg_form.pwd_user.value = p_u;
		document.reg_form.submit();
	}
}

function simple_check_email(str) {
   return (str.indexOf(".") > 2) && (str.indexOf("@") > 0);
}

</script> 
