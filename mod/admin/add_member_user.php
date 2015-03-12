<?php

if(!$ob_user->is_admin() || !$_GET['detail']) {
	jump_to($conf_main_page);
	exit();
}

$det_user = new user($_GET['detail']);

if($_POST) {
	# select the number of users with a member and the max number of users available
	$sql = 'SELECT count(mu.user_id) + 1 as num_member_with, u.user_id, u.full_name, u.email, u.is_member, m.member_type, mt.max_num_members, mt.type_name
 FROM users u
 LEFT JOIN members_users mu
   ON mu.member_id = u.user_id
INNER JOIN members m ON u.user_id = m.user_id
INNER JOIN member_types mt ON m.member_type = mt.type_id
WHERE m.member_type IN (\'fam\', \'mat\') AND (u.full_name like \'%'. $_POST['search_input'] .'%\' OR u.email like \'%'. $_POST['search_input'] .'%\') 
  AND u.deleted_ind = \'0\' AND u.blocked_ind = \'0\'
GROUP BY u.user_id, u.full_name, u.email, u.is_member, m.member_type, mt.max_num_members, mt.type_name';

	$sel = my_query($sql, $conex);
	
	$arr_users = array();
	while($record = my_fetch_array($sel)) {
		if($record['max_num_members'] > $record['num_member_with'])
			$arr_users[$record['user_id']] = $record;
	}
}

?>

<div class="title_3 indented">Agregar a <strong>
  <?= $det_user->get_user_name() .'</strong> ('. $det_user->user_id .')'; ?>
  como socio con otro usuario</div>
<form name="search_users" id="search_users" method="post" action="">
  <table border="0" cellspacing="3" cellpadding="3" class="default_text">
    <tr>
      <td bgcolor="#DDDDDD">Escribe parte de un nombre o dirección de e-mail que quires buscar:</td>
    </tr>
    <tr>
      <td>Nombre/email:
        <input type="text" class="inputnormal" name="search_input" id="search_input" style="width:170px;" value="<?= $_POST['search_input']; ?>" maxlength="250" autofocus="autofocus" />
        <input type="submit" class="button_small" name="search" id="search" value=" BUSCAR "></td>
    </tr>
    <tr>
      <td><?php
	if(count($arr_users)) {
		?>
        <table width="100%" border="0" cellpadding="2" cellspacing="2" class="default_text">
          <tr>
            <td colspan="3" class="small_text bottomborderdotted"><?= count($arr_users); ?>
              resultados</td>
          </tr>
          <?php	foreach($arr_users as $user_id => $user) {		?>
          <tr>
            <td class="bottomborderthin"><a href="JavaScript:select_user('<?= $user_id; ?>')">
              <?= $user['full_name']; ?>
              </a> (
              <?= $user_id; ?>
              )</td>
            <td class="bottomborderthin"><?= $user['email']; ?></td>
            <td class="bottomborderthin">Tipo socio:
              <?= $user['type_name']; ?></td>
          </tr>
          <?php		}	?>
        </table>
        <?php
	}
	elseif($_POST) {	# there is a search but no results
		echo '<span class="error_message">No se han encontrado resultados para '. $_POST['search_input'] .'</span>';
	}
	?>
      </td>
    </tr>
    <?php	if($_POST) {	?>
    <tr>
      <td>Selecciona el usuario de la lista de arriba que quieres agregar con <strong>
        <?= $det_user->get_user_name(); ?>
        </strong></td>
    </tr>
    <?php	}	?>
    <tr>
      <td><div id="join_users"> </div></td>
    </tr>
  </table>
</form>
<script language="javascript">

function select_user(member_id) {
	document.getElementById('join_users').setAttribute("class", "standard_container");
	url = 'inc/ajax.php?content=join_users&user=<?= $_GET['detail']; ?>&member=' + member_id;
	getData(url, 'join_users');
}

function confirm_join(member_id, user_id) {
	url = 'inc/ajax.php?content=confirm_join&user='+ user_id +'&member=' + member_id;
	getData(url, 'join_users');
	window.setTimeout(get_out, 5000);
}

function get_out() {
	document.location = '<?php $conf_main_page; ?>?mod=admin&tab=users';
}

</script>
