<?php
# Check that the user actually has permissions to view this module

if($_SESSION['login']['modules'][$_GET['mod']]['read']) {
	
	if(!$_GET['tab']) $_GET['tab'] = 'bookings';
	
	$arr_tabs = array('bookings' => ucfirst(book_a_court), 
					  'user_books' => ucfirst(your_bookings));
	
?>

<div class="standard_container">
  <?php
	foreach($arr_tabs as $key => $value) {
		$class = $key == $_GET['tab'] ? 'active_tab' : 'inactive_tab';
	
		if(!($_SESSION['login']['modules'][$_GET['mod']]['write'] == '0' && $key == 'user_books'))
			echo '<span class="'. $class .' big_tab_text" onclick="JavaScript:jump_to(\''. $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $key .'\')">'. $value .'</span>';
	}
?>
  <div id="alerts_box" style="top:-10px; position:relative;">
    <?php
	print_alerts($_GET['mod']);
?>
  </div>
<?php
# include alert for not loged-in users or not active users.
	if($_SESSION['login']['user_id'] == '0') {
?>
  <div id="alert_not_loggedin" class="notice_notice default_text" style="top:-10px; position:relative;">Debes identificarte como usuario para hacer reservas. Si todavía no eres usuario, <a href="<?= $conf_main_page; ?>?mod=home&view=new_user" title="Registro gratuito">regístrate</a> gratuítamete y empieza a hacer reservas en minutos</div>
  <?php		
	}
	elseif($_SESSION['login']['modules'][$_GET['mod']]['write'] == '0') {
?>
  <div id="alert_not_loggedin" class="notice_notice default_text" style="top:-10px; position:relative;">Tu cuenta de usuario no ha sido activada y no puedes hacer reservas todavía.<br />
    Comprueba tu correo ( 
    <?= $_SESSION['login']['email']; ?>
     ) y sigue las instrucciones para activar tu cuenta. <br />
Haz clic <a href="<?= $conf_main_page; ?>?mod=home&view=check_code&se=1">aquí</a> para recibir de nuevo el código de activación</div>
  <?php
	}


	if($_GET['subtab'])
		include 'mod/'. $_GET['mod'] .'/'. $_GET['subtab'] .'.php';
	else
		include 'mod/'. $_GET['mod'] .'/'. $_GET['tab'] .'.php';
?>
</div>
<?php
}	//	if($_SESSION['login']['modules'][$_GET['mod']]['read']) {
else {
	jumpt_to($conf_main_page);
	exit();
}

?>
