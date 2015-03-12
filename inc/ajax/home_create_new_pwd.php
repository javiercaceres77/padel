<?php

# check if the provided code matches the e-mail on users table.
$email = decode($_GET['m'])	;
$result = simple_select('users', 'email', $email, 'user_id', ' AND change_pwd_code = \''. $_GET['code'] .'\'');

if($result['user_id']) {

?>

<form name="pwd_form" id="pwd_form" method="post" action="">
  <table width="80%" align="center" cellpadding="4" cellspacing="4" class="default_text">
    <tr>
      <td align="right" width="33%">Introduce tu nueva contraseña:</td>
      <td><input type="text" class="inputlarge" name="new_pwd" id="new_pwd" style="width:170px;" value="" maxlength="250" />
        <label>
          <input type="checkbox" name="hide_pwd" id="hide_pwd" onchange="JavaScript:show_hide()" />
          Ocultar contrase&ntilde;a</label></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="register" class="button" value="  GUARDAR  " /></td>
    </tr>
  </table>
</form>
<?php
}
else {
?>
<div class="indented error_message default_text">El código proporcionado es incorrecto, asegúrate de que coincide con el recibido en tu correo.<br />
  Haz clic <a href="<?= $conf_main_page; ?>?mod=home&view=rec_pwd">aquí</a> para enviar un nuevo código</div>
<br />
<?php
}
?>
