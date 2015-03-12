<div id="alerts_box" style="top:-10px; position:relative;">
  <?php
	print_alerts($_GET['mod']);
?>
</div>
<?php
/*if($ob_user->user_id == 0)
	jump_to($conf_main_page);
*/
if($_POST) {

	$arr_upd = array('pasapalabra' => digest(substr($ob_user->get_email(), 0, 2) . $_POST['new_pwd']));

	$old_pass = digest(substr($ob_user->get_email(), 0, 2) . $_POST['old_pwd']);
	
	if(update_array_db('users', 'user_id', $ob_user->user_id, $arr_upd, ' AND pasapalabra = \''. $old_pass .'\'')) {
		write_log_db('login', 'pwd_changed', 'password changed for: '. $ob_user->get_email());
		
		$arr_vars = array('site' => $conf_main_url);
			  
		if($_SERVER['SERVER_NAME'] != 'localhost')
			mail_templates::send_mail($ob_user->get_email(), 'changed_pwd', $arr_vars);

		?>
<table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
  <tr>
    <td>Hemos actualizado tu contraseña correctamente.
      Ya puedes acceder a tu cuenta utilizando tu número de socio y tu nueva contraseña</td>
  </tr>
</table>
<?php
	}	//	if(update_array_db('users', 'email', $email, $arr_upd)) {
	else {
		?>
<div class="indented error_message default_text">Ha habido un error al actualizar tu contraseña.<br />
  <a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab']; ?>">Inténtalo de nuevo</a></div>
<br />
<?php
	}
	exit();
}

?>
<form name="pwd_form" id="pwd_form" method="post" action="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&action=chg_pwd'; ?>">
  <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
        <tr>
      <td align="right" width="33%">Introduce tu <strong>antigua</strong> contraseña</td>
      <td><input type="text" class="inputlarge" name="old_pwd" id="old_pwd" style="width:170px;" value="" maxlength="36" />
        <label>
          <input type="checkbox" name="hide_old" id="hide_old" onchange="JavaScript:show_hide_old()" />
          Ocultar contrase&ntilde;a</label></td>
    </tr>

    <tr>
      <td align="right" width="33%">Introduce tu <strong>nueva</strong> contraseña</td>
      <td><input type="text" class="inputlarge" name="new_pwd" id="new_pwd" style="width:170px;" value="" maxlength="250" />
        <label>
          <input type="checkbox" name="hide_new" id="hide_new" onchange="JavaScript:show_hide_new()" />
          Ocultar contrase&ntilde;a</label></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="register" class="button" value="  GUARDAR  " /></td>
    </tr>
  </table>
</form>
<br />
<script language="javascript">

function show_hide_new() {
	if(document.pwd_form.hide_new.checked)
		document.pwd_form.new_pwd.type = 'password';
	else
		document.pwd_form.new_pwd.type = 'text';
}

function show_hide_old() {
	if(document.pwd_form.hide_old.checked)
		document.pwd_form.old_pwd.type = 'password';
	else
		document.pwd_form.old_pwd.type = 'text';
}
</script> 
