<?php
# Check that the user actually has permissions to view this module

if($_SESSION['login']['modules'][$_GET['mod']]['read']) {
	
	if(!$_GET['tab']) $_GET['tab'] = 'personal';
	
	$arr_tabs = array('personal' => 'Datos personales', 
					  'fact' => 'Facturación');

	if($ob_user->has_valid_ccc() && $ob_user->is_member) {
		$arr_tabs['bonus'] = 'Bonos';
	}
					  
	if($ob_user->is_member) {
		$arr_tabs['member'] = 'Cuenta de socio';
	}
	
	# Creation of a member account shows a new tab
/*	if($_GET['tab'] == 'new_member') {
		$arr_tabs['new_member'] = 'Nueva cuenta de socio';
	}*/
	
	# Change password shows new tab
	if($_GET['tab'] == 'chg_pwd') {
		$arr_tabs['chg_pwd'] = 'Cambiar contraseña';
	}
	
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