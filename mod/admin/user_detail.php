<?php

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$det_user = new user($_GET['detail']);
$member_ind = $det_user->is_member ? 'socio' : 'no socio';

$user_data = $det_user->get_all_details();
$ccc = $det_user->get_ccc();
$ccc_name = $det_user->get_ccc_name();
//$ccc_data = $det_user->get_all_payment_details();

if($_POST) {
	$notice_text = '';
	$notice_type = 'notice';	// 'alert'

	# ------------ PERSONAL DETAILS
	if($_POST['pers_name']) {
		$changed_name = $user_data['full_name'] != $_POST['pers_name'];
		$changed_pwd = $_SESSION['new_user']['pasapalabra'];
		
		$address = $_POST['pers_addr'] .'<br>'. $_POST['pers_postcode'] .'<br>'. $_POST['pers_city'] .'<br>'. $_POST['pers_province'];
		
		$upd_array = array('full_name' => $_POST['pers_name'], 'phone1' => $_POST['pers_phone1'], 'phone2' => $_POST['pers_phone2'],
						   'address' => $address, 'DOB' => $_POST['DOB'], 'admin_comments' => $_POST['comments']);
			
		if($changed_pwd) {
			$upd_array['pasapalabra'] = digest(substr($user_data['email'],0,2) . $changed_pwd);
			$upd_array['required_change_pwd'] = '1';
		}
		
		if(update_array_db('users', 'user_id', $det_user->user_id, $upd_array)) {
			if($changed_name && $det_user->is_member) {
				$upd_array = array('user_name' => $_POST['pers_name']);
				update_array_db('members', 'user_id', $det_user->user_id, $upd_array);
			}
			
			$notice_text = 'Datos de usuario actualizados correctamente';
			if($changed_pwd)
				$notice_text.='<br>Se ha cambiado la contraseña del usuario a '. $changed_pwd .'<br>El usuario deberá cambiar la contraseña la próxima vez que se identifique.';

			# Reload user details array after update.			
			$user_data = $det_user->get_all_details();
		}	
	}	//	if($_POST['pers_name']) {
	
	# ------------ BANK DETAILS
	if($_POST['CCC_update']) {
		# 1 . Check that the CCC has changed:
		$old_ccc = $det_user->get_ccc();
		$old_name = $det_user->get_ccc_name();
		$new_ccc = $_POST['CCC_ent'] .'/'. $_POST['CCC_suc'] .'/'. $_POST['CCC_DC'] .'/'. $_POST['CCC_acc'];
		
		if($old_ccc != $new_ccc || $old_name != $_POST['CCC_titular']) {
			# 2. Check if new CCC is empty:
			if($_POST['CCC_ent'] == '') {
				# 3. Check if user is member:
				if($det_user->is_member_member()) {
					$notice_text = 'No se puede borrar el código de cuenta de un socio';
				}
				else {
					$det_user->rem_ccc_payment();
					$notice_text = 'Codigo de cuenta borrado';
				}
			}
			else {
				# 4. Check if new CCC is ok.
				if(digito_control($_POST['CCC_ent'] . $_POST['CCC_suc'], $_POST['CCC_acc']) == $_POST['CCC_DC']) {
					# 5. Check if old CCC is empty
					if($old_ccc == '') {
						if($det_user->add_ccc_payment(encode($new_ccc), encode($_POST['CCC_titular'])))
							$notice_text = 'Código de cuenta insertado';
						else 
							$notice_text = 'Error al insertar el código de cuenta';
					}
					else {
						if($det_user->upd_ccc_payment(encode($new_ccc), encode($_POST['CCC_titular'])))
							$notice_text = 'Código de cuenta actualizado';
						else 
							$notice_text = 'Error al actualizar el código de cuenta';
					}
				}
				else
					$notice_text = 'El código de cuenta '. $new_ccc .' no es válido';
			}
		}
		
		# update the number of cash bookings.
		$arr_upd = array('available_books_num' => $_POST['cash_books']);
		update_array_db('users', 'user_id', $det_user->user_id, $arr_upd);
		
		$ccc = $det_user->get_ccc();
		$ccc_name = $det_user->get_ccc_name();

		$user_data = $det_user->get_all_details();
	}	

	# ------------ NEW MEMBERSHIP
	if($_POST['member_type']) {
		# check that the user has a valid CCC.
		if($det_user->has_valid_ccc()) {
			$types_arr = explode('_', $_POST['member_type']);
			$member_id = $det_user->create_member_account($types_arr['1'], $types_arr['0']);
			
			if($member_id) {
				write_log_db('member', 'new membership', 'Member: '. $det_user->user_id);
				$notice_text = 'Se ha creado correctamente la cuenta de socio para el usuario.';
				$notice_type = 'notice';
				
				# re-create the user object and its details.
				$det_user = new user($_GET['detail']);
				$member_ind = $det_user->is_member ? 'socio' : 'no socio';
				
				$user_data = $det_user->get_all_details();
				$ccc = $det_user->get_ccc();
				$ccc_name = $det_user->get_ccc_name();
			}
			else {
				$notice_text = 'Ha habido un error al crear la cuenta de socio';
				$notice_type = 'alert';
			}
		}
		else {
			$notice_text = 'Es necesario un número de cuenta corriente para crear la cuenta de socio.';
			$notice_type = 'alert';
		}
	}

	# ------------ REMOVE MEMBER WITH
	if($_POST['member_with']) {
		# unset 'member_with' as member	# remove association on members_users
		$member_to_remove = new user($_POST['member_with']);
		if($member_to_remove->unset_as_member_with_member($det_user->user_id)) {
			write_log_db('member', 'unset member with user', 'Member: '. $det_user->user_id .', User: '. $member_to_remove->user_id);
			$notice_text = 'Se ha quitado a '. $member_to_remove->get_user_name() .' como socio con este usuario.';
			$notice_type = 'notice';
		}
		else {
			$notice_text = 'Ha habido un error al quitar el socio.';
			$notice_type = 'alert';
		}
	}
	
	# ------------ REMOVE MEMBER WITH from the other user point of view
	if($_POST['member_with_member']) {
		# unset 'member_with' as member	# remove association on members_users
		//$member_to_remove = new user($_POST['member_with']);
		if($det_user->unset_as_member_with_member($_POST['member_with_member'])) {
			write_log_db('member', 'unset member with user', 'Member: '. $_POST['member_with_member'] .', User: '. $det_user->user_id);
			$notice_text = 'Se ha quitado al usuario número '. $_POST['member_with_member'] .' como socio con este usuario.';
			$notice_type = 'notice';
		}
		else {
			$notice_text = 'Ha habido un error al quitar el socio.';
			$notice_type = 'alert';
		}
			
		unset($member_to_remove);
	}
}	//	if($_POST) {


$arr_addr = explode('<br>', $user_data['address']);

if($ccc) {
	$arr_ccc = explode('/', $ccc);
	$ccc_titular = $ccc_name;
}

unset($_SESSION['new_user']['pasapalabra']);

if($notice_text) {
?>

<div id="alert" class="notice_<?= $notice_type; ?> default_text" style="top:-10px; position:relative;">
  <?= $notice_text; ?>
</div>
<?php	}	?>
<div class="default_text" style="width:95%;"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users">&lt; Volver a la lista de usuarios</a></div>
<div class="title_3" align="center" style="background-color:#DDDDDD; width:95%;">
  <?= $user_data['full_name'] .' ('. $det_user->user_id .', '. $member_ind .')'; ?>
</div>
<table cellpadding="1" cellspacing="0" border="0" class="default_text" width="100%">
  <tr>
    <td valign="top"><div class="standard_container">
        <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;"><strong>Datos personales</strong></div>
        <form name="pers_form" id="pers_form" method="post" action="">
          <table cellpadding="3" cellspacing="2" border="0">
            <tr>
              <td bgcolor="#DDDDDD" align="right">Número de usuario: </td>
              <td><?= $det_user->user_id; ?></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Nombre: </td>
              <td><input type="text" class="inputnormal" name="pers_name" id="pers_name" style="width:170px;" value="<?= $user_data['full_name']; ?>" maxlength="250" /></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">email:</td>
              <td><input type="text" class="inputnormal" name="pers_email" id="pers_email" style="width:170px;" value="<?= $user_data['email']; ?>" maxlength="250" disabled="disabled" />
                <a href="<?= $conf_main_page; ?>mod=admin&tab=users&subtab=change_mail&detail=<?= $det_user->user_id; ?>"><img src="<?= $conf_images_path; ?>edit.gif" border="0" align="absmiddle" title="Cambiar email" /></a></td>
            </tr>
            <tr>
              <td align="right"><input type="button" name="new_pwd" class="button_small" value=" Nueva contraseña " onclick="JavaScript:update_pwd();" /></td>
              <td><span id="pasapalabra" style="color:#CC3300">
                <?= $_SESSION['new_user']['pasapalabra']; ?>
                </span></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Tel&eacute;fono 1: </td>
              <td><input type="text" class="inputnormal" name="pers_phone1" id="pers_phone1" style="width:170px;" value="<?= $user_data['phone1']; ?>" maxlength="250" /></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Tel&eacute;fono 2:</td>
              <td><input type="text" class="inputnormal" name="pers_phone2" id="pers_phone2" style="width:170px;" value="<?= $user_data['phone2']; ?>" maxlength="250" /></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Direcci&oacute;n: </td>
              <td><input type="text" class="inputnormal" name="pers_addr" id="pers_addr" style="width:170px;" value="<?= $arr_addr[0]; ?>" maxlength="250" /></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">C&oacute;digo Postal:</td>
              <td><input type="text" class="inputnormal" name="pers_postcode" id="pers_postcode" style="width:170px;" value="<?= $arr_addr[1]; ?>" maxlength="250" /></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Ciudad: </td>
              <td><input type="text" class="inputnormal" name="pers_city" id="pers_city" style="width:170px;" value="<?= $arr_addr[2]; ?>" maxlength="250" /></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Provincia:</td>
              <td><input type="text" class="inputnormal" name="pers_province" id="pers_province" style="width:170px;" value="<?= $arr_addr[2]; ?>" maxlength="250" /></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td bgcolor="#DDDDDD" align="right">Fecha de nacimiento:</td>
              <td><input type="text" class="inputnormal" name="DOB" id="DOB" style="width:100px;" value="<?=  $user_data['DOB']; ?>" maxlength="250"  onblur="JavaScript:construct_date('DOB');" /></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td colspan="2" bgcolor="#DDDDDD">Comentarios:</td>
            </tr>
            <tr>
              <td colspan="2" align="right"><textarea class="inputnormal" style="width:200px;" name="comments" id="comments"><?= $user_data['admin_comments']; ?>
</textarea></td>
            </tr>
          </table>
        </form>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save7" class="button_small" value=" GUARDAR CAMBIOS" onclick="JavaScript:save_personal();" />
        </div>
      </div>
      <div class="standard_container">
        <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;"><strong>Datos bancarios</strong></div>
        <form name="bank_form" id="bank_form" method="post" action="">
          <input type="hidden" name="CCC_update" id="CCC_update" value="1" />
          <table cellpadding="3" cellspacing="2" border="0">
            <tr>
              <td colspan="2" bgcolor="#DDDDDD">Número de cuenta: </td>
            </tr>
            <tr>
              <td colspan="2"><table cellpadding="1" cellspacing="1" border="0">
                  <tr class="small_text">
                    <td>Entidad</td>
                    <td>Sucursal</td>
                    <td>D.C.</td>
                    <td>N&ordm; Cuenta</td>
                  </tr>
                  <tr>
                    <td><input type="text" class="inputnormal" name="CCC_ent" id="CCC_ent" style="width:60px;" value="<?= $arr_ccc[0]; ?>" maxlength="4" /></td>
                    <td><input type="text" class="inputnormal" name="CCC_suc" id="CCC_suc" style="width:60px;" value="<?= $arr_ccc[1]; ?>" maxlength="4" /></td>
                    <td><input type="text" class="inputnormal" name="CCC_DC" id="CCC_DC" style="width:30px;" value="<?= $arr_ccc[2]; ?>" maxlength="2" /></td>
                    <td><input type="text" class="inputnormal" name="CCC_acc" id="CCC_acc" style="width:120px;" value="<?= $arr_ccc[3]; ?>" maxlength="10" /></td>
                  </tr>
                </table></td>
            </tr>
            <tr>
              <td align="right" bgcolor="#DDDDDD">Titular:</td>
              <td><input type="text" class="inputnormal" name="CCC_titular" id="CCC_titular" style="width:170px;" value="<?= $ccc_titular; ?>" maxlength="250" /></td>
            </tr>
            <tr height="10px">
              <td height="10"></td>
              <td height="10"></td>
            </tr>
            <tr>
              <td align="right" bgcolor="#DDDDDD">N&ordm;  reservas con <br />
                pago en efectivo: </td>
              <td><input type="text" class="inputnormal" name="cash_books" id="cash_books" style="width:30px;" value="<?= $user_data['available_books_num']; ?>" maxlength="250" /></td>
            </tr>
          </table>
        </form>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save8" class="button_small" value=" GUARDAR CAMBIOS " onclick="JavaScript:save_bank();" />
        </div>
      </div></td>
    <td valign="top"><div class="standard_container">
        <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;"><strong>Otros datos</strong></div>
        <?php
	$next_book_date = new date_time($det_user->get_next_book_date());
	$next_book_str = $next_book_date->odate->odate == '0000-00-00' ? 'no hay reservas' : $next_book_date->odate->format_date('med') .' '. $next_book_date->format_time();
	
	$last_login_date = new date_time($user_data['last_login_datetime']);
	
	$date_registered = new my_date($user_data['date_registered']);
	
	$is_admin_str = $user_data['is_admin'] == '1' ? 'Sí' : 'No';
?>
        <table cellpadding="3" cellspacing="2" border="0">
          <tr>
            <td align="right" bgcolor="#DDDDDD">Fecha Pr&oacute;xima reserva:</td>
            <td><?= $next_book_str ?></td>
            <td><input type="button" name="save4" class="button_small" value=" Hacer Reserva " onclick="JavaScript:place_booking();" /></td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">N&ordm; total de reservas</td>
            <td colspan="2"><?= $user_data['total_books_num']; ?>
              (<a href="#">ver todas las reservas</a>)</td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">&Uacute;ltimo Acceso:</td>
            <td colspan="2"><?= $last_login_date->odate->format_date('med') .' '. $last_login_date->otime; ?></td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Fecha registro:</td>
            <td><?= $date_registered->format_date('med'); ?></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">N&ordm; veces no presentado:</td>
            <td><?= $user_data['no_shows_num']; ?></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td height="10"></td>
            <td height="10"></td>
            <td height="10"></td>
          </tr>
          <?php
	if($user_data['blocked_ind']) {
?>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Bloqueado:</td>
            <td>Sí</td>
            <td></td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Bloqueado hasta</td>
            <td>[add blocks funct]</td>
            <td><input type="button" name="save5" class="button_small" value=" Desbloquear " onclick="JavaScript:unblock_user();" /></td>
          </tr>
          <?php
	}
	else {
?>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Bloqueado:</td>
            <td>No</td>
            <td><input type="button" name="save" class="button_small" value=" Bloquear " onclick="JavaScript:block_user();" /></td>
          </tr>
          <?php
	}
?>
          <tr>
            <td height="10"></td>
            <td height="10"></td>
            <td height="10"></td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Solicitado cambio contrase&ntilde;a:</td>
            <td>No</td>
            <td>19-feb-2012</td>
          </tr>
          <tr>
            <td height="10"></td>
            <td height="10"></td>
            <td height="10"></td>
          </tr>
          <tr>
            <td align="right" bgcolor="#DDDDDD">Es administrador: </td>
            <td><?= $is_admin_str; ?></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td height="10"></td>
            <td height="10"></td>
            <td height="10"></td>
          </tr>
        </table>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save6" class="button_small" value=" BORRAR USUARIO " onclick="JavaScript:delete_user();" />
        </div>
      </div>
      <div class="standard_container">
        <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;"><strong>Cuenta de socio</strong></div>
        <?php
	if($det_user->is_member) {
		if($det_user->is_member_member()) {
			$sql = 'SELECT m.payment_freq, m.member_type, mt.type_name, m.registration_date, m.last_bill_date, m.last_bill_amount, m.next_bill_date
				FROM members m INNER JOIN member_types mt ON m.member_type = mt.type_id
				WHERE m.user_id = \''. $det_user->user_id .'\' AND m.member_account_status = \'active\'';
			
			$sel_member = my_query($sql, $conex);
			$member_data = my_fetch_array($sel_member);
			
			$date_member_from = new my_date($member_data['registration_date']);
			$date_last_bill = new my_date($member_data['last_bill_date']);
			$date_next_bill = new my_date($member_data['next_bill_date']);
	
			$freq_str = array('mth' => 'mensual', '4mt' => 'cuatrimestral', 'yrl' => 'anual');
	?>
        <form name="form_member" id="form_member" action="" method="post">
          <input type="hidden" name="member_with" id="member_with" value="" />
          <table cellpadding="3" cellspacing="2" border="0">
            <tr>
              <td align="right" bgcolor="#DDDDDD">Tipo:</td>
              <td><?= $member_data['type_name']; ?></td>
            </tr>
            <?php
            # Check if there are other members in this account.
			$member = new member($det_user->user_id);
			//echo 'jelou '. $member->get_num_other_users();
			if($member->get_num_other_users()) {
				echo '<td align="right" bgcolor="#DDDDDD">Otros usuarios en esta cuenta: </td><td>';
				$other_users_names = $member->get_other_users_names();
				$first = true;
				foreach($other_users_names as $user_id => $user_name) {
					if($first)	$first = false;
					else		echo '<br>';
						
					echo '<a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $user_id .'">'. $user_name .'</a> ('. $user_id .') <a href="JavaScript:remove_association(\''. $user_id .'\')" title="Quitar como socio con '. $det_user->get_user_name() .'"><img src="'. $conf_images_path .'close2.png" align="absmiddle" border="0" /></a>';
				}				echo '</td></tr>';
			}	//	if($other_users) {

              ?>
            <tr>
              <td align="right" bgcolor="#DDDDDD">Socio desde:</td>
              <td><?= $date_member_from->format_date('med'); ?></td>
            </tr>
            <tr>
              <td align="right" bgcolor="#DDDDDD">Cuota</td>
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
              <td align="right" bgcolor="#DDDDDD">Fecha &uacute;ltima factura:</td>
              <td><?= $date_last_bill->format_date('med'); ?></td>
            </tr>
          </table>
        </form>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save3" class="button_small" value=" CANCELAR CUENTA DE SOCIO " onclick="JavaScript:cancel_membership();" />
        </div>
        <?php
		}	//	if($det_user->is_member_member()) {
		else {
			# the user is member by proxy. Get the name and ID of the member is member with.
			$sql = 'SELECT m.user_id, m.user_name, m.member_type, mt.type_name
				FROM members m 
				INNER JOIN members_users mu ON mu.member_id = m.user_id 
				INNER JOIN member_types mt ON m.member_type = mt.type_id
				WHERE mu.user_id = \''. $det_user->user_id .'\' and m.member_account_status = \'active\'';
			
			$sel = my_query($sql, $conex);
			$member_data = my_fetch_array($sel);
	?>
        <form name="form_member_with" id="form_member_with" action="" method="post">
          <input type="hidden" name="member_with_member" id="member_with_member" value="" />
          <table cellpadding="3" cellspacing="2" border="0">
            <tr>
              <td align="right" bgcolor="#DDDDDD">Asociado a la cuenta
                <?= $member_data['type_name']; ?>
                de:</td>
              <td><a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=user_detail&detail=<?= $member_data['user_id']; ?>">
                <?= $member_data['user_name']; ?>
                </a> (
                <?= $member_data['user_id']; ?>
                ) <a href="JavaScript:remove_association_with(<?= $member_data['user_id']; ?>)" title="Quitar como socio con <?= $member_data['user_name']; ?>"><img src="<?= $conf_images_path; ?>close2.png" align="absmiddle" border="0" /></a></td>
            </tr>
          </table>
        </form>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save3" class="button_small" value=" CANCELAR CUENTA DE SOCIO " onclick="JavaScript:cancel_membership();" />
        </div>
        <?php		}
	}	//	if($det_user->is_member) {
	else {
?>
        El usuario no es socio. [<a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=add_member_user&detail=<?= $det_user->user_id; ?>">Crear cuenta con otro socio</a>]<br />
        Para crear una cuenta de socio selecciona el tipo:
        <?php
$sql = 'SELECT * FROM member_types';
$sel_mts = my_query($sql, $conex);

?>
        <form name="member_form" id="member_form" method="post" action="">
          <table border="0" cellpadding="2" cellspacing="2" class="default_text">
            <tr>
              <td width="25%"></td>
              <th width="25%">Mensual</th>
              <th width="25%">Cuatrimestral</th>
              <th width="25%">Anual</th>
            </tr>
            <?php
while($record = my_fetch_array($sel_mts)) {
?>
            <tr>
              <td align="right"><?= $record['type_name']; ?></td>
              <td bgcolor="#DDDDDD"><label>
                  <input type="radio" value="mth_<?= $record['type_id']; ?>" name="member_type" />
                  <?= print_money($record['month_quote']); ?>
                </label></td>
              <td bgcolor="#DDDDDD"><label>
                  <input type="radio" value="4mt_<?= $record['type_id']; ?>" name="member_type" />
                  <?= print_money($record['4month_quote']); ?>
                </label></td>
              <td bgcolor="#DDDDDD"><label>
                  <input type="radio" value="yrl_<?= $record['type_id']; ?>" name="member_type" />
                  <?= print_money($record['year_quote']); ?>
                </label></td>
            </tr>
            <?php
}

	if(!$det_user->has_valid_ccc()) {
?>
            <tr>
              <td colspan="4" class="error_message">Es necesario un número de cuenta corriente válido.</td>
            </tr>
            <?php	}	?>
          </table>
          <div align="center" style="width:100%; padding:10px;">
            <input type="button" name="save3" class="button_small" value=" CREAR CUENTA DE SOCIO " onclick="JavaScript:create_membership();" />
          </div>
        </form>
        <?php
	}
?>
      </div>
      <div class="standard_container">
        <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;"><strong>Bonos</strong></div>
        <table cellpadding="3" cellspacing="2" border="0">
          <?php
	# Select active bonus for this user.
	$sql = 'SELECT b.bonus_id, bt.type_description, b.remaining_hours FROM bonuses b INNER JOIN bonus_types bt ON b.bonus_type = bt.type_code
			WHERE b.user_id = \''. $det_user->user_id .'\' AND b.status = \'active\' AND b.remaining_hours > 0';
	
	$sel = my_query($sql, $conex);
	
	if(my_num_rows($sel)) {
		while($record = my_fetch_array($sel)) {
?>
          <tr>
            <td><?= $record['type_description']; ?>
              . Quedan
              <?= $record['remaining_hours']; ?>
              horas</td>
            <td><input type="button" name="save6" class="button_small" value=" Cancelar " onclick="JavaScript:cancel_bonus('<?= $record['bonus_id']; ?>');" /></td>
          </tr>
          <?php
		}
	}
	else {
?>
          <tr>
            <td>El usuario no tiene bonos</td>
          </tr>
          <?php
	}
?>
        </table>
        <div align="center" style="width:95%; padding:10px;">
          <input type="button" name="save6" class="button_small" value=" AÑADIR BONO " onclick="JavaScript:add_bonus('<?= $det_user->user_id; ?>');" />
        </div>
      </div></td>
  </tr>
</table>
<script language="JavaScript">
function save_personal() {
	var error = '';
	with(document.pers_form) {
		if(pers_name.value == '' || pers_email.value == '') {
			error = 'El nombre y el correo electrónico son obligatorios';
		}
	}
	
	if(error == '')
		document.pers_form.submit();
	else
		alert(error);
}

function save_bank() {
	var error = '';
/*	with(document.bank_form) {
		if(CCC_ent.value == '' || CCC_suc == '' || CCC_DC == '' || CCC_acc == '')
			error = 'Código de cuenta corriente incorrecto\n';
		
		if(CCC_titular.value == '')
			error+= 'El nombre del titular de la cuenta es obligatorio\n';
	}
	
	// we might want the CCC to be empty if we want to delete the CCC.
*/	
	if(error == '')
		document.bank_form.submit();
	else
		alert(error);
}

function add_bonus(user_id) {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=add_bonus&usr=' + user_id;
}

function update_pwd() {
	url = '<?= $conf_include_path; ?>ajax.php?content=upd_pwd';
	getData(url, 'pasapalabra');
}

function place_booking() {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&user=<?= $_GET['detail']; ?>';	
}

function create_membership() {
	if(confirm('¿Seguro que quieres crear una cuenta de socio para este usuario?'))
		document.member_form.submit();
}

function remove_association(user_id) {
	if(confirm('¿Seguro que quieres quitar esta asociación?')) {
		document.form_member.member_with.value = user_id;
		document.form_member.submit();
	}
}

function remove_association_with(user_id) {
	if(confirm('¿Seguro que quieres quitar esta asociación?')) {
		document.form_member_with.member_with_member.value = user_id;
		document.form_member_with.submit();
	}
}

</script> 
