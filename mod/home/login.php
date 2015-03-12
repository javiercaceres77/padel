<form name="login_form" method="post" action="<?= $conf_main_page; ?>?action=login" id="login_form">
  <div class="standard_container"><span class="active_tab big_tab_text">Iniciar Sesión</span>
    <div align="right" class="indented">
      <input type="button" class="button" onclick="JavaScript:jumpto('<?= $conf_main_page; ?>?mod=home&view=new_user');" value="Regístrate como nuevo usuario" name="new_user" />
    </div>
    <table align="center" border="0" cellpadding="6" cellspacing="6" class="default_text">
      <tr>
        <td colspan="2" class="title_3">Identifícate en Pádel Indoor Ponferrada</td>
      </tr>
      <?php if($wrong_login) { ?>
      <tr>
        <td colspan="2" class="error_message">e-mail o contraseña incorrectos</td>
      </tr>
      <?php } ?>
      <tr>
        <td align="right" width="150">e-mail:</td>
        <td><input type="email" class="inputlarge" name="user" id="user" placeholder="ejemplo@email.com" value="<?= $_POST['email']; ?>" autofocus="autofocus" maxlength="250"/>
          <br />
          <span class="error_message small_text" id="error_email" style="display:none; padding:3px;">Debes escribir una dirección de correo válida</span></td>
      </tr>
      <tr>
        <td align="right">contraseña:</td>
        <td><input type="text" class="inputlarge" name="pass" id="pass"  maxlength="32"/>
          <label>
            <input type="checkbox" value="OK" id="hide_pwd_check" name="hide_pwd_check" onchange="JavaScript:hide_pwd()" />
            Ocultar contraseña</label>
          <br />
          <span class="error_message small_text" id="error_pwd" style="display:none; padding:3px;">Debes escribir tu contraseña</span></td>
      </tr>
      <tr>
        <td></td>
        <td class="small_text"><a href="<?php echo $conf_main_page; ?>?mod=home&view=rec_pwd"><?php echo ucfirst(reset_pwd); ?></a></td>
      </tr>
      <?php if($show_captcha) {	// $show_captcha comes from index.php	?>
      <tr>
        <td colspan="2" class="default_text">Para evitar spam, por favor, escribe el resultado de la siguiente operación en <strong>número</strong>:</td>
      </tr>
      <tr>
        <td align="right"><div id="captcha_container"></div></td>
        <td><input type="number" class="inputlarge" style="width:75px;" name="captcha" id="captcha" maxlength="5" />
          <input type="hidden" name="there_is_captcha" id="there_is_captcha" value="1" />
          <a href="JavaScript:reload_captcha();"><img src="<?= $conf_images_path; ?>reload.png" alt="Recargar" title="Recargar" width="16" height="16" border="0" align="absmiddle" /></a><br />
          <?php if($wrong_captcha) { // Also comes from index.php 	?>
          <span class="error_message small_text" id="error_captcha" style="display:none; padding:3px;">Escribe el resultado de la operación en número</span>
          <?php	}	?></td>
      </tr>
      <?php	}	?>
      <tr>
        <td colspan="2" align="center"><div class="indented">
            <input type="submit" class="button" name="go" id="go" value=" ENTRAR " onclick="JavaScript:check_values();" />
          </div></td>
      </tr>
    </table>
  </div>
  <input type="hidden" name="url" id="url" value="<?= $conf_main_page; ?>" />
</form>
<script language="javascript">
<?php if($show_captcha) {	// $show_captcha comes from index.php	?>
document.onload = reload_captcha();
<?php	}	?>
function reload_captcha() {
	url = 'inc/ajax.php?content=captcha';
	getData(url, 'captcha_container');
}

function hide_pwd() {
	if(document.login_form.hide_pwd_check.checked)
		document.login_form.pass.type = 'password';
	else
		document.login_form.pass.type = 'text';
}

function check_values() {
	error = 0;
	if(document.login_form.user.value == '') {
		document.getElementById('error_email').style.display = 'block';
		error = 1;
	}
	if(document.login_form.pass.value == '') {
		document.getElementById('error_pwd').style.display = 'block';
		error = 1;
	}
	if(document.login_form.captcha.value == '') {
		document.getElementById('error_captcha').style.display = 'block';
		error = 1;
	}
	
	if(error == 0) {
		document.login_form.submit();
	}
}

</script>