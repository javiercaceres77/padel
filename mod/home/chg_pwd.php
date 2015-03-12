<div class="standard_container"> <span class="active_tab big_tab_text">Cambiar contraseña</span>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
  <?php
if($ob_user->user_id == 0)
	$ob_user = new user(decode($_GET['m']));
	

if($_POST) {
	$arr_upd = array('pasapalabra' => digest(substr($ob_user->get_email(), 0, 2) . $_POST['new_pwd']), 'required_change_pwd' => '0', 'change_pwd_code' => '');
	$old_pass = digest(substr($ob_user->get_email(), 0, 2) . decode($_POST['pass_enc']));
	
	if(update_array_db('users', 'user_id', $ob_user->user_id, $arr_upd, ' AND required_change_pwd = \'1\' AND pasapalabra = \''. $old_pass .'\'')) {
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
    Haz clic <a href="<?= $conf_main_page; ?>?mod=home&view=rec_pwd">aquí</a> para intentarlo de nuevo</div>
  <br />
  <?php
	}
	exit();

}
else {
	if(!$_GET['m'])
		jump_to($conf_main_page);
}



?>
<form name="pwd_form" id="pwd_form" method="post" action="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&view='. $_GET['view'] .'&action=chg_pwd&m='. $_GET['m']; ?>">
  <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
    <tr>
      <td align="right" width="33%">Introduce tu nueva contraseña para <?= $ob_user->get_email() .' ('. $ob_user->user_id .')'; ?>:</td>
      <td><input type="text" class="inputlarge" name="new_pwd" id="new_pwd" style="width:170px;" value="" maxlength="250" />
        <label>
          <input type="checkbox" name="hide_pwd" id="hide_pwd" onchange="JavaScript:show_hide()" />
          <input type="hidden" name="pass_enc" value="<?= $_GET['p']; ?>" />
          Ocultar contrase&ntilde;a</label></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="register" class="button" value="  GUARDAR  " /></td>
    </tr>
  </table>
</form>
  <br />
</div>
<script language="javascript">

function show_hide() {
	if(document.pwd_form.hide_pwd.checked)
		document.pwd_form.new_pwd.type = 'password';
	else
		document.pwd_form.new_pwd.type = 'text';
}

</script> 
