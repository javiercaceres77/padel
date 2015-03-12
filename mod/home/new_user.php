<?php

if($_POST) {
	# Check captcha
	if($_POST['reg_captcha'] == $_SESSION['misc']['captcha']) {
		# Check email already exists
		$sql = 'SELECT 1 FROM users WHERE email = \''. $_POST['reg_email'] .'\' AND deleted_ind <> \'1\'';
		$sel_usr = my_query($sql, $conex);
		
		if(my_num_rows($sel_usr)) {
			$text = 'Ya existe un usuario con el correo '. $_POST['reg_email'] .'. <a href="'. $conf_main_page .'?mod=home&view=rec_pwd">Click aquí</a> si has olvidado tu contraseña';
			add_alert('home', 'alert', 1, $text);
		}
		else {
			# Create user
			$check_code = md5(rand() . date('his'));
			
			$arr_ins = array('full_name' 			=> $_POST['reg_name'],
							 //'nick_name'			=> substr($_POST['reg_email'], 0, strpos($_POST['reg_email'], '@')),
							 'pasapalabra'			=> digest(substr($_POST['reg_email'],0,2) . $_POST['reg_pwd']),
							 'email'				=> $_POST['reg_email'],
							 'added_by'				=> 'web',
							 'control_code'			=> $check_code,
							 'date_registered'		=> date('Y-m-d'),
							 'available_books_num'	=> get_config_value('num_books_user_default'));
							 
			$user_id = insert_array_db('users', $arr_ins, true);
			
			if($user_id) {
				# add read permissions on the default modules.
				$user = new user($user_id);
				$user->add_default_modules();
				
				# Send e-mail
				$to = $_POST['reg_email'];
				$subject = 'Registro en Padel Indoor Ponferrada';
				
				$headers = 'To: "'. $_POST['reg_name'] .'" <'. $_POST['reg_email'] .'>' . "\r\n";
				$headers .= 'From: Padel Indoor Ponferrada <info@padelindoorponferrada.com>' . "\r\n";
			
				$message = "Ya casi estás registrado como usuario en Padel Indoor Ponferrada.\n
Tu número de socio es: ". $user_id ." \n
Para completar el registro, copia y pega el siguiente código: ". $check_code ." \n
en ". $conf_main_url . $conf_main_page ."?mod=home&view=check_code \n
o bien, ve directamente a 
". $conf_main_url . $conf_main_page ."?mod=home&view=check_code&code=". $check_code ." \n\n
contacta con nosotros si tienes cualquier problema.";
								
				@mail($to, $subject, $message, $headers);
				
				jump_to($conf_main_page .'?mod=home&view=check_code');
				exit();
			}
			else {
				$text = 'Ha habido un error al insertar el usuario en la base de datos. Contacta con nosotros para resolver el problema';
				add_alert('home', 'alert', 1, $text);
			}
		}

		$visibility_error_captcha = 'hidden';
	}
	else {
		$visibility_error_captcha = 'visible';		
	}
}
else
	$visibility_error_captcha = 'hidden';

?>

<div class="standard_container"> <span class="active_tab big_tab_text">Registro de nuevo usuario</span>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
  <form name="reg_form" id="reg_form" method="post" action="">
    <table cellpadding="10" cellspacing="4" border="0" class="default_text" align="center">
      <tr>
        <td bgcolor="#DDDDDD" align="right" class="title_4">Nombre y apellidos: </td>
        <td align="left"><input type="text" class="inputlarge" name="reg_name" id="reg_name" style="width:170px;" value="<?= $_POST['reg_name']; ?>" maxlength="250" />
        </td>
        <td align="left" width="300"><div id="error_name" class="error_message" style="visibility:hidden">Debes escribir tu nombre</div></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" align="right" class="title_4">email: </td>
        <td align="left"><input type="text" class="inputlarge" name="reg_email" id="reg_email" style="width:170px;" value="<?= $_POST['reg_email']; ?>" maxlength="250" />
        </td>
        <td align="left"><div id="error_email" class="error_message" style="visibility:hidden">Escribe una dirección de correo electrónico válida</div></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" align="right" class="title_4">Contraseña: </td>
        <td align="left"><input type="password" class="inputlarge" name="reg_pwd" id="reg_pwd" style="width:170px;" maxlength="32" />
        </td>
        <td align="left"><div id="error_pwd" class="error_message" style="visibility:hidden">La contraseña debe tener al menos 6 caractéres</div></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" align="right" class="title_4">Repetir contraseña: </td>
        <td align="left"><input type="password" class="inputlarge" name="reg_pwd2" id="reg_pwd2" style="width:170px;" maxlength="32" />
        </td>
        <td align="left"><div id="error_pwd2" class="error_message" style="visibility:hidden">Las contraseñas no coinciden</div></td>
      </tr>
      <tr>
        <td colspan="3">Para evitar spam, por favor, escribe el resultado de la siguiente operación en <strong>número</strong>:</td>
        </td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" align="right"><div id="captcha_container"></div></td>
        <td align="left"><input type="text" class="inputlarge" name="reg_captcha" id="reg_captcha" style="width:80px;" maxlength="4" />
          <a href="JavaScript:reload_captcha();"><img src="<?= $conf_images_path; ?>reload.png" alt="Recargar" title="Recargar" width="16" height="16" border="0" align="absmiddle" /></a> </td>
        <td align="left"><div id="error_captcha" class="error_message" style="visibility:<?= $visibility_error_captcha; ?>">Error. Escribe el resultado en número, ej.:<br />
            diez por tres = 30.</div></td>
      </tr>
      <tr>
        <td colspan="3" class="small_text"><label><input type="checkbox" name="tycs" id="tycs" checked="checked" />
          He leído y aceptado los <a href="JavaScript:show_tycs();">términos y condiciones</a> de registro en Padelindoorponferrada.com</label><br />
          <br />
          &middot; Al registrarte como usuario en Padel Indoor Ponferrada podrás <strong>reservar pistas online</strong>.<br />
          &middot; El regsitro como usuario es <strong>gratuíto</strong>.<br />
          &middot; Una vez registrado como usuario puedes convertirte en <strong>socio</strong><br />
          &middot; Los socios estos deben abonar una <strong>cuota mensual</strong> y las tarifas son más bajas para ellos.</td>
      </tr>
      <tr>
        <td colspan="3" align="center"><input type="button" name="register" class="button" value="  REGISTRARSE  " onclick="JavaScript:check_data();" /></td>
      </tr>
    </table>
  </form>
</div>
<script language="javascript">
function show_tycs() {
	// Stores the resgistration info in the $_SESSION so that when user comes back it's already there.
	//	<?= $conf_main_page; ?>?mod=home&view=tycs
}

document.onload = reload_captcha();

function reload_captcha() {
	url = 'inc/ajax.php?content=captcha';
	getData(url, 'captcha_container');
}

function check_data() {
	var error = false;
	var error_name = false;
	var error_email = false;
	var error_pwd = false;
	var error_pwd2 = false;
	var error_captcha = false;
	var error_tycs = false;
	
	with(document.reg_form) {
		error_name = reg_name.value == '';
		error_email = !simple_check_email(reg_email.value);
		error_pwd = reg_pwd.value.length < 6;
		error_pwd2 = reg_pwd.value != reg_pwd2.value;
		error_captcha = reg_captcha.value == '';
		error_tycs = !tycs.checked;
	}
	error = error_name || error_email || error_pwd || error_pwd2 || error_captcha || error_tycs;
	
	if(error) {
		if(error_name)		document.getElementById('error_name').style.visibility = 'visible';		else 	document.getElementById('error_name').style.visibility = 'hidden';
		if(error_email)		document.getElementById('error_email').style.visibility = 'visible';	else 	document.getElementById('error_email').style.visibility = 'hidden';
		if(error_pwd)		document.getElementById('error_pwd').style.visibility = 'visible';		else 	document.getElementById('error_pwd').style.visibility = 'hidden';
		if(error_pwd2)		document.getElementById('error_pwd2').style.visibility = 'visible';		else 	document.getElementById('error_pwd2').style.visibility = 'hidden';
		if(error_captcha)	document.getElementById('error_captcha').style.visibility = 'visible';	else 	document.getElementById('error_captcha').style.visibility = 'hidden';
		if(error_tycs)		alert('Debes aceptar los términos y condiciones para registrarte en Padel Indoor Ponferrada');
	}
	else {
		document.reg_form.submit();
	}
}

function simple_check_email(str) {
   return (str.indexOf(".") > 2) && (str.indexOf("@") > 0);
}

</script>
