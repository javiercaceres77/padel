<div class="standard_container"> <span class="active_tab big_tab_text">Registro de nuevo usuario</span>
  <?php

if($_POST['user_id']) {
	if($ob_user->user_id == 0) {
		$ob_user = new user($_POST['user_id']);	
	}

	if($ob_user->activate_user($_POST['check_code'])) {
			?>
  <table cellpadding="10" cellspacing="4" border="0" class="default_text" align="center" width="75%">
    <tr>
      <td><div class="title_3" align="center">¡Enhorabuena!</div>
        Has completado el registro con éxito.<br />
        Ya puedes empezar a hacer reservas.<br /></td>
    </tr>
  </table>
  <?php	
			echo '</div>'; 	// this is for the standard_container div
			exit();	
	}
	else {
		$text = 'El código insertado ( '. $_POST['check_code'] .' ) no es válido.<br />Copia y pega el código enviado a tu correo electrónico e inténtalo otra vez.<br />Si sigues teniendo problemas, contacta con nosotros.<br /><a href="'. $conf_main_page .'?mod=home&view='. $_GET['view'] .'&func=send_code' .'">&gt; Enviar nuevo código</a>';
		add_alert('home', 'alert', 1, $text);
	}
		

/*	
//	$today = new my_date('today');
	$minus_30_days = new my_date(date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 30, date('Y'))));

	$sql = 'SELECT user_id FROM users WHERE control_code = \''. $_POST['check_code'] .'\' AND date_registered > \''. $minus_30_days->odate .'\'';

	$sel_usr = my_query($sql, $conex);
	$user_id = my_result($sel_usr, 0, 'user_id');
	if($user_id) {
		$user = new user($user_id);
			
		if($user->activate_user()) {
			?>
  <table cellpadding="10" cellspacing="4" border="0" class="default_text" align="center" width="75%">
    <tr>
      <td><div class="title_3" align="center">¡Enhorabuena!</div>
        Has completado el registro con éxito.<br />
        Ya puedes empezar a hacer reservas.<br /></td>
    </tr>
  </table>
  <?php	
			echo '</div>'; 	// this is for the standard_container div
			exit();	
		}
	}
	else {
		$text = 'El código insertado ( '. $_POST['check_code'] .' ) no es válido.<br />Copia y pega el código enviado a tu correo electrónico e inténtalo otra vez.<br />Si sigues teniendo problemas, contacta con nosotros.';
		add_alert('home', 'alert', 1, $text);
	}*/
}

# Send e-mail from here
if($_GET['func'] == 'send_code') {
	$user_email = $ob_user->get_email();
	$user_det = $ob_user->get_all_details();
	
	$arr_vars = array('site' => $conf_main_url
					 ,'code' => $user_det['control_code']
					 ,'url' => $conf_main_url . $conf_main_page .'?mod=home&view=check_code&code='. $user_det['control_code']);
		  
	if($_SERVER['SERVER_NAME'] != 'localhost')
		mail_templates::send_mail($user_email, 'send_mig_code', $arr_vars);

	?>
	<table border="0" style="margin-top:20px; margin-left:255px;" width="400" class="default_text">
    <tr><td>
    <h5>Hemos enviado un e-mail a la dirección que nos proporcionaste.<br />
Revisa tu bandeja de entrada y la bandeja de spam.<br />
Si no lo recibes contacta con nosotrs en <?= $conf_main_phone_contact; ?></h5>
    </td></tr>
    </table>
    <?php
}

?>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
  <form name="check_form" id="check_form" method="post" action="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&view='. $_GET['view']; ?>">
    <table cellpadding="10" cellspacing="4" border="0" class="default_text" align="center" width="75%">
      <tr>
        <td valign="top"><img src="<?= $conf_images_path; ?>email_big.gif" /></td>
        <td colspan="2">Para validar tu dirección de correo, te hemos enviado un mensaje con un código.
          Comprueba tu correo (revisa la bandeja de spam) y pega el código aquí para completar tu registro.<br />
<br />
<a href="<?= $conf_main_page .'?mod=home&view='. $_GET['view'] .'&func=send_code'; ?>">&gt; Enviar nuevo código</a></td>
      </tr>
<?php
	if($ob_user->user_id == 0) {
?>
      <tr>
        <td></td>
        <td align="right">Número de socio:&nbsp;&nbsp;</td>
        <td><input type="text" class="inputlarge" name="user_id" value="" id="user_id" /></td>
      </tr>
<?php
	}
	else {
?>
	<input type="hidden" name="user_id" id="user_id" value="<?= $ob_user->user_id; ?>" />
<?php
	}
?>
    
      <tr>
        <td></td>
        <td align="right">Código:&nbsp;&nbsp;</td>
        <td><input type="text" class="inputlarge" name="check_code" value="<?= $_GET['code']; ?>" id="check_code" />
&nbsp;&nbsp;</td>
      </tr>
      <tr>
        <td></td>
        <td colspan="2" align="center"><input type="submit" name="send_code" class="button" value="  ACTIVAR CUENTA  " /></td>
      </tr>
    </table>
  </form>
</div>
