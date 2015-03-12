<?php
if(!$_GET['tab']) $_GET['tab'] = 'users';

$arr_tabs = array('users' => ucfirst(users), 
				  'bookings' => 'Gestionar reservas',
				  'club' => ucfirst(club),
				  'billing' => 'Facturación',
				  'news'	=> 'Noticias');

?>
<div class="standard_container">
  <?php
foreach($arr_tabs as $key => $value) {
	$class = $key == $_GET['tab'] ? 'active_tab' : 'inactive_tab';

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
<script language="javascript">

show_alerts();

</script>
