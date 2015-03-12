<?php
if($_GET['mod'] == 'home') {
?>

<table height="120" width="100%" border="0" cellpadding="0" cellspacing="0" class="header_table">
  <tr>
    <td>&nbsp;</td>
    <td width="250" valign="top"><?php
	if($_SESSION['login']['user_id']) {	#  Show user info
?>
      <table width="100%" border="0" cellpadding="1" cellspacing="1" class="login_box">
        <tr>
          <td class="title_3"><?= $_SESSION['login']['name']; ?></td>
        </tr>
        <tr>
          <td class="small_text">[<a href="<?= $conf_main_page; ?>?mod=user">
            <?= ucfirst(account_settings); ?>
            </a>]&nbsp;&nbsp;[<a href="<?= $conf_main_page; ?>?func=logout">
            <?= ucfirst(_exit); ?>
            </a>]</td>
        </tr>
        <tr>
          <td class="default_text"><img src="<?= $conf_images_path; ?>checkbox.gif" align="absmiddle">
            <?= ucfirst(next_booking); ?>
            : <a href="<?= $conf_main_page; ?>?mod=books&view=detail&detail=xxx">14/01/2012 17:00</a></td>
        </tr>
        <tr>
          <td class="default_text"><img src="<?= $conf_images_path; ?>info.png" align="absmiddle"> (<a href="<?= $conf_main_page; ?>?mod=books&view=alerts">3</a>) <a href="<?= $conf_main_page; ?>?mod=books&view=alerts">
            <?= ucfirst(pending_alerts); ?>
            </a></td>
        </tr>
      </table>
      <?php
		//------------------------- logged user info
		# Javier Cáceres 
		# [tus datos] [salir]
		# Próxima reserva: mañana 14:30		/	No tienes reservas
	}
	elseif($_GET['view'] != 'login') {		# not logged users
				# show login form, new user etc.
?>
      <form action="<?= $conf_main_page; ?>?action=login" method="post" name="login_form">
        <table width="100%" border="0" cellpadding="1" cellspacing="1" class="login_box">
          <tr>
            <td colspan="2" class="title_4"><?= ucfirst(user_access); ?></td>
          </tr>
          <?php if($wrong_login) { ?>
          <tr>
            <td colspan="2" align="center" class="error_message"><?= ucfirst(wrong_login); ?></td>
          </tr>
          <?php } ?>
          <tr>
            <td align="right" class="small_text">e&ndash;mail</td>
            <td><input name="user" type="text" class="inputnormal" id="user" maxlength="60" style="width:120px;" autofocus="autofocus" /></td>
          </tr>
          <tr>
            <td align="right" class="small_text"><?= password; ?></td>
            <td><input name="pass" type="password" class="inputnormal" id="pass" maxlength="30" style="width:120px;" /></td>
          </tr>
          <tr>
            <td align="center" colspan="2"><input name="Submit" type="submit" onClick="JavaScript:submit_login_form()" class="button" value="    <?php echo ucfirst(login); ?>    " /></td>
          </tr>
          <tr class="small_text">
            <td align="center"><a href="<?php echo $conf_main_page; ?>?mod=home&view=new_user"><?php echo ucfirst(new_user); ?></a></td>
            <td align="center"><a href="<?php echo $conf_main_page; ?>?mod=home&view=rec_pwd">&iquest;problemas?</a></td>
          </tr>
        </table>
	    <input type="hidden" name="url" id="url" value="" />
      </form>
      <?php
	
	}	// elseif($_GET['view'] != 'login') {
?></td>
  </tr>
</table>
<?php 	
}
else {	# show a compact header when not in mod=home
?>
<table width="100%" border="0" cellpadding="4" cellspacing="0" class="header_table">
  <tr>
    <?php
	if($_SESSION['login']['user_id']) {	#  Show user info
?>
    <td align="right" class="default_text"><?= $_SESSION['login']['name']; ?>
      &nbsp;&nbsp; <a href="<?= $conf_main_page; ?>?mod=user" title="<?= ucfirst(account_settings); ?>"><img src="<?= $conf_images_path; ?>settings.png" align="absmiddle" border="0"></a>&nbsp;&nbsp; <a href="<?= $conf_main_page; ?>?func=logout" title="<?= ucfirst(_exit); ?>"><img src="<?= $conf_images_path; ?>icon_logout.gif" align="absmiddle" border="0"></a>&nbsp;&nbsp; <a href="<?= $conf_main_page; ?>?mod=books&view=detail&detail=xxx" title="<?= ucfirst(next_booking); ?>: 14/01/2012 17:00"><img src="<?= $conf_images_path; ?>checkbox.gif" align="absmiddle" border="0"></a> (2)&nbsp;&nbsp;<a href="<?= $conf_main_page; ?>?mod=books&view=alerts" title="<?= ucfirst(pending_alerts); ?>"><img src="<?= $conf_images_path; ?>info.png" align="absmiddle" border="0"></a> (3) </td>
    <?php
	}
	elseif($_GET['view'] != 'login') {		# not logged users
?>
    <td align="right" class="default_text"><form action="<?= $conf_main_page; ?>?action=login" method="post" name="login_form">
        <span class="title_4">
        <?= ucfirst(associate_access); ?>
        :</span>&nbsp;&nbsp;&nbsp;
        <?php if($wrong_login) { ?>
        <span class="error_message">
        <?= ucfirst(wrong_login); ?>
        </span>
        <?php } ?>
        <span class="small_text">e&ndash;mail: </span>
        <input name="user" type="text" class="inputsmall" id="user" maxlength="60" style="width:120px;" autofocus="autofocus" />
        &nbsp;&nbsp;&nbsp; <span class="small_text">
        <?= password; ?>
        : </span>
        <input name="pass" type="password" class="inputsmall" id="pass" maxlength="30" style="width:120px;" />
        <a href="<?php echo $conf_main_page; ?>?mod=home&view=rec_pwd" title="<?php echo ucfirst(reset_pwd); ?>"><img src="<?= $conf_images_path; ?>help2.gif" align="absmiddle" border="0"></a>&nbsp;&nbsp;
        <input name="Submit" type="submit" onClick="JavaScript:submit_login_form()" class="button_small" value="   <?php echo ucfirst(login); ?>   "/>
        <input type="hidden" name="url" id="url" value="" />
        &nbsp;&nbsp;&nbsp; <span class="small_text">[<a href="<?php echo $conf_main_page; ?>?mod=home&view=new_user"><?php echo ucfirst(new_user); ?></a>]</span>
      </form></td>
    <?php
	}	// elseif($_GET['view'] != 'login') {
?>
  </tr>
</table>
<?php

}	// else 	// if($_GET['mod'] == 'home') {
?>
