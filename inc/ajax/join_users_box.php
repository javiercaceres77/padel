<?php

$member = new user($_GET['member']);
$user   = new user($_GET['user']);

$member_member = new member($member->user_id);

$other_users = $member_member->get_num_other_users();
$member_props = $member_member->get_props();
$member_type_props = $member_member->get_member_type_props();

if($member_type_props['max_num_members'] > $other_users) {
	
?>
<table width="100%" border="0" cellpadding="3" cellspacing="4">
  <tr>
    <td colspan="2">Vas a agregar el usuario <strong><?= $user->get_user_name(); ?></strong> a la cuenta de socio &quot;<?= $member_type_props['type_name']; ?>&quot; de <strong><?= $member->get_user_name(); ?></strong></td>
  </tr>
  <tr>
    <td width="50%" bgcolor="#DDDDDD"><?= $member->get_user_name() .' ('. $member->user_id .')'; ?></td>
    <td width="50%" bgcolor="#DDDDDD"><?= $user->get_user_name() .' ('. $user->user_id .')'; ?></td>
  </tr>
  <tr>
    <td valign="top"><?php
	echo 'Socio '. $member_type_props['type_name'] .'<br>';
	if($other_users) {
		echo 'Otros usuarios en esta cuenta: ';
		$other_users_names = $member_member->get_other_users_names();
		$first = true;
		foreach($other_users_names as $user_id => $user_name) {
			if($first)
				$first = false;
			else
				echo ', ';
				
			echo '<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $user_id .'">'. $user_name .'</a>';
		}
		echo '<br>';
	}
	$ob_date = new my_date($member_props['registration_date']);
	echo 'Socio desde: '. $ob_date->format_date('med') .'<br>';
	echo 'Cuota: '. print_money($member_props['last_bill_amount']) .'<br>';
	$ob_date = new my_date($member_props['next_bill_date']);
	echo 'Próxima cuota: '. $ob_date->format_date('med') .'<br>';
	
	?></td>
    <td valign="top">No socio</td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input type="button" name="confirm" id="confirm" class="button" value=" CONFIRMAR " onclick="confirm_join('<?= $member->user_id; ?>','<?= $user->user_id; ?>')" /></td>
  </tr>
</table>
<?php
}
else {
	echo $member->user_name .' ya tiene asignado el número máximo de usuarios en su cuenta de socio.';
}
?>