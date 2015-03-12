<?php
	if($ob_user->is_member) {
		if($ob_user->is_member_member()) {
			$sql = 'SELECT m.payment_freq, m.member_type, mt.type_name, m.registration_date, m.last_bill_date, m.last_bill_amount, m.next_bill_date
				FROM members m INNER JOIN member_types mt ON m.member_type = mt.type_id
				WHERE m.user_id = \''. $ob_user->user_id .'\' AND m.member_account_status = \'active\'';
			
			$sel_member = my_query($sql, $conex);
			$member_data = my_fetch_array($sel_member);
			
			$date_member_from = new my_date($member_data['registration_date']);
			$date_last_bill = new my_date($member_data['last_bill_date']);
			$date_next_bill = new my_date($member_data['next_bill_date']);
	
			$freq_str = array('mth' => 'mensual', '4mt' => 'cuatrimestral', 'yrl' => 'anual');
	?>

<table width="80%" align="center" cellpadding="6" cellspacing="4" border="0" class="default_text">
  <tr>
    <td align="right" bgcolor="#DDDDDD" width="32%">Tipo:</td>
    <td><?= $member_data['type_name']; ?></td>
  </tr>
  <?php
            # Check if there are other members in this account.
			$member = new member($ob_user->user_id);
			//echo 'jelou '. $member->get_num_other_users();
			if($member->get_num_other_users()) {
				echo '<td align="right" bgcolor="#DDDDDD">Otros usuarios en esta cuenta: </td><td>';
				$other_users_names = $member->get_other_users_names();
				$first = true;
				foreach($other_users_names as $user_id => $user_name) {
					if($first)	$first = false;
					else		echo '<br>';
						
					echo $user_name .' ('. $user_id .')';
				}				echo '</td></tr>';
			}	//	if($other_users) {

              ?>
  <tr>
    <td align="right" bgcolor="#DDDDDD">Socio desde:</td>
    <td><?= $date_member_from->format_date('med'); ?></td>
  </tr>
  <tr>
    <td align="right" bgcolor="#DDDDDD">Cuota:</td>
    <td><?= ucfirst($freq_str[$member_data['payment_freq']]); ?></td>
  </tr>
  <tr>
    <td align="right" bgcolor="#DDDDDD">Fecha pr&oacute;xima cuota:</td>
    <td><?= $date_next_bill->format_date('med'); ?></td>
  </tr>
  <tr>
    <td align="right" bgcolor="#DDDDDD">Importe &uacute;ltima cuota:</td>
    <td><?= print_money($member_data['last_bill_amount']); ?></td>
  </tr>
  <tr>
    <td align="right" bgcolor="#DDDDDD">Fecha &uacute;ltima cuota:</td>
    <td><?= $date_last_bill->format_date('med'); ?></td>
  </tr>
</table>
<br />
<?php
		}	//	if($ob_user->is_member_member()) {
		else {
			# the user is member by proxy. Get the name and ID of the member is member with.
			$sql = 'SELECT m.user_id, m.user_name, m.member_type, mt.type_name
				FROM members m 
				INNER JOIN members_users mu ON mu.member_id = m.user_id 
				INNER JOIN member_types mt ON m.member_type = mt.type_id
				WHERE mu.user_id = \''. $ob_user->user_id .'\' and m.member_account_status = \'active\'';
			
			$sel = my_query($sql, $conex);
			$member_data = my_fetch_array($sel);
	?>
<table width="80%" align="center" cellpadding="6" cellspacing="4" border="0" class="default_text">
  <tr>
    <td align="right" bgcolor="#DDDDDD" width="38%">Asociado a la cuenta
      <?= $member_data['type_name']; ?>
      de:</td>
    <td><?= $member_data['user_name']; ?>
      (
      <?= $member_data['user_id']; ?>
      ) </td>
  </tr>
</table><br />
<?php		}
	}	//	if($ob_user->is_member) {
?>