<?php

/*$other_users_arr = array();
foreach($_SESSION['multiplayer'] as $ply => $player)
	$other_users_arr[$player['user_id']] = 1;	# info on the keys
*/

$sql = 'SELECT user_id, full_name, email, is_member
FROM users ';
if(is_numeric($_GET['search']))
	$sql.= 'WHERE user_id = '. $_GET['search'];
else
	$sql.= 'WHERE (full_name like \'%'. $_GET['search'] .'%\' OR email like \'%'. $_GET['search'] .'%\')';
$sql.= ' AND deleted_ind = \'0\'';// AND user_id NOT IN ('. implode_keys(', ', $other_users_arr) .')';

$sel = my_query($sql, $conex);

if(my_num_rows($sel)) {
	?>
	<table width="100%" border="0" cellpadding="2" cellspacing="2" class="default_text">
	  <tr>
		<td colspan="3" class="small_text bottomborderdotted"><?= my_num_rows($sel); ?>
		  resultados</td>
	  </tr>
	  <?php	while($record = my_fetch_array($sel)) {		?>
	  <tr>
		<td class="bottomborderthin"><a href="JavaScript:select_user('<?= $record['user_id']; ?>', '<?= $_GET['ply']; ?>')">
		  <?= $record['full_name']; ?>
		  </a> (
		  <?= $record['user_id']; ?>
		  )</td>
<!--		<td class="bottomborderthin"><?= $record['email']; ?></td>-->
		<td class="bottomborderthin"><?php
			if($record['is_member'] == '1') {
		  ?>
			<img src="<?= $conf_images_path; ?>user16.png" title="<?= $record['full_name']; ?> es socio." />
			<?php
		  }			 ?></td>
	  </tr>
	  <?php		}	?>
	</table>
	 <?php
}	//if(my_num_rows($sel)) {
elseif($_POST) {	# there is a search but no results
	echo '<span class="error_message">No se han encontrado resultados para '. $_GET['search'] .'</span>';
}
?>