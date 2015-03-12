<?php

if($_POST) {
	$today = new my_date('today');
	$tomorrow = $today->plus_days(1);

	# Check email already exists
	$sql = 'SELECT user_id FROM users WHERE email = \''. $_POST['email'] .'\' AND deleted_ind <> \'1\'';
	$sel_usr = my_query($sql, $conex);
	
	$arr_user_id = my_fetch_array($sel_usr);
	
	if(my_num_rows($sel_usr)) {
		$text = 'Ya existe un usuario con el correo '. $_POST['email'] .'. <a href="'. $conf_main_page .'?mod=admin&tab=users&subtab=user_detail&detail='. $arr_user_id['user_id'] .'">Pincha aquí</a> para verlo';
		add_alert('admin', 'alert', 1, $text);
	}
	else {
		$error = array();
		$CCC_empty = $_POST['CCC_ent'] == '' && $_POST['CCC_suc'] == '' && $_POST['CCC_acc'] == '' && $_POST['CCC_DC'] == '';
		$CCC_ok = $_POST['CCC_DC'] == digito_control($_POST['CCC_ent'] . $_POST['CCC_suc'], $_POST['CCC_acc']);

		if(!$CCC_ok)
			write_log_db('Admin_create_user', 'BAD_CCC', 'el Código de cuenta introducido no es correcto');
	
		$error[1] = !$CCC_empty && !$CCC_ok;
//		$error[1] = !$CCC_ok;
		$error[2] = !$CCC_empty && $_POST['CCC_titular'] == '';
		$error[3] = $_POST['member'] == 'on' && ($CCC_empty || !$CCC_ok || $_POST['CCC_titular'] == '');
		
		if($error[1] || $error[2] || $error[3]) {
			$alert = '';
			if($error[1]) $alert.= 'El código de cuenta corriente no es válido<br>';
			if($error[2]) $alert.= 'El titular de la cuenta no puede estar vacío<br>';
			if($error[3]) $alert.= 'Para crear una cuenta de socio es necesario un código de cuenta corriente';
			add_alert('admin', 'alert', 1, $alert);			
		}
		else {
			# insert user
			$address = $_POST['pers_addr'] .'<br>'. $_POST['pers_postcode'] .'<br>'. $_POST['pers_city'] .'<br>'. $_POST['pers_province'];
			$check_code = md5(rand() . date('his'));
			$is_member = $_POST['member'] == 'on' ? '1' : '0';
			$available_books = get_config_value('num_books_user_default');
					
			$arr_ins = array('full_name'		=> $_POST['name'],
							 'pasapalabra'		=> digest(substr($_POST['email'],0,2) . $_SESSION['new_user']['pasapalabra']),
							 'phone1'			=> $_POST['phone1'],
							 'phone2'			=> $_POST['phone2'],
							 'email'			=> $_POST['email'],
							 'address'			=> $address,
							 'added_by'			=> $_SESSION['login']['name'],
							 'control_code'		=> $check_code,
							 'date_registered'	=> date('Y-m-d'),
							 'available_books_num' => get_config_value('num_books_user_default'),
							 'admin_comments'	=> $_POST['comments'],
							 'required_change_pwd' => '1',
							 'user_level'		=> $_POST['user_level'],
							 'is_member'		=> $is_member,
							 'DOB'				=> $_POST['DOB']);
							
			$user_id = insert_array_db('users', $arr_ins, true);
			
			if($user_id) {
				$ob_new_user = new user($user_id);
				$ob_new_user->add_default_modules();
				//$ob_new_user->add_cash_payment_method();
				# Add CCC as payment method
				
				if($_POST['CCC_ent'] && $_POST['CCC_suc'] && $_POST['CCC_DC'] && $_POST['CCC_acc'] && $_POST['CCC_titular']) {
					$ccc = encode($_POST['CCC_ent'] .'/'. $_POST['CCC_suc'] .'/'. $_POST['CCC_DC'] .'/'. $_POST['CCC_acc']);
					$ccc_name = encode($_POST['CCC_titular']);
			
					$ob_new_user->add_ccc_payment($ccc, $ccc_name);
				}

				if($_POST['member'] == 'on') {
					# generate bill for tomorrow.
					$types_arr = explode('_', $_POST['member_type']);
					$bill_id = $ob_new_user->generate_member_bill($tomorrow->odate, $types_arr[0], $types_arr[1]);
					$bill = new bill($bill_id);
					$bill_props = $bill->get_props();
					$next_bill_date = $tomorrow->plus_cycle(1, $types_arr[0]);
					# create new member for the user
					$arr_ins = array('user_id'				=> $ob_new_user->user_id,
									 'user_name'			=> $_POST['name'],
									 'member_type'			=> $types_arr[1],
									 'payment_freq'			=> $types_arr[0],
									 'registration_date'	=> date('Y-m-d'),
									 'next_bill_date'		=> $next_bill_date->odate,
									 'last_bill_date'		=> $bill_props['date_issued'],
									 'last_bill_amount'		=> $bill_props['total_amount'],
									 'last_bill_id'			=> $bill->bill_id,
									 'member_account_status'=> 'active');
									 
					$member_id = insert_array_db('members', $arr_ins, true);
					
					$member_ok = $bill_id && $member_id;
				}	//	if($_POST['member'] == 'on') {
				# User inserted ok
				# Send e-mail with code.

				$to = $_POST['email'];
				$subject = 'Registro en Padel Indoor Ponferrada';
				
				$headers = 'To: "'. $_POST['name'] .'" <'. $_POST['email'] .'>' . "\r\n";
				$headers .= 'From: Padel Indoor Ponferrada <info@padelindoorponferrada.com>' . "\r\n";
			
				$message = "Ya casi estás registrado como usuario en Padel Indoor Ponferrada.\n
Para completar el registro, copia y pega el siguiente código: ". $check_code ." \n
en ". $conf_main_url . $conf_main_page ."?mod=home&view=check_code \n
o bien, ve directamente a 
". $conf_main_url . $conf_main_page ."?mod=home&view=check_code&code=". $check_code ." \n\n
contacta con nosotros si tienes cualquier problema.\n\n
WWW.PADELINDOORPONFERRADA.COM ". $conf_main_phone_contact;
								
				@mail($to, $subject, $message, $headers);

				?>
<div class="alert_info default_text">
  <div align="center" class="title_4">El usuario se ha creado correctamente</div>
  &middot; La contraseña del usuario es <span style="color:#CC3333">
  <?= $_SESSION['new_user']['pasapalabra']; ?>
  </span><br />
  &middot; El usuario tendrá que cambiar esta contraseña cuando se identifique por primera vez.<br />
  &middot; Se ha enviado un mensaje al correo
  <?= $_POST['email']; ?>
  con un código para activar la cuenta.<br />
  &middot; No podrá hacer reservas online hasta que active la cuenta.<br />
  &middot; El usuario pude hacer
  <?= $available_books; ?>
  reservas pagando en efectivo. Si proporciona un número de cuenta el número de reservas es ilimitado.</div>
<?php
                if($_POST['member'] == 'on' && $member_ok) {
					?>
<br />
<div class="alert_info default_text">Se ha creado una cuenta de socio para el usuario.<br />
  &middot; La cuota es de
  <?= print_money($bill_props['total_amount']); ?>
  .<br />
  &middot; La próxima cuota se pasará el
  <?= $next_bill_date->format_date('long'); ?>
</div>
<?php
					write_log_db('Admin_create_user', 'member_created_ok', 'Nuevo socio creado por '. $_SESSION['login']['name']);
				}
?>
<div class="default_text indented"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=user_detail&detail=<?= $ob_new_user->user_id; ?>">Ver los detalles del usuario</a><br />
  <a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=new_user">Agregar nuevo usuario</a><br />
  <a href="<?= $conf_main_page; ?>?mod=admin&tab=users">&lt; Volver a la lista de usuarios</a></div>
<?php
				write_log_db('Admin_create_user', 'user_created_ok', 'Usuario creado por '. $_SESSION['login']['name']);
				unset($_POST);
				exit();
			}	//	if($user_id) {
			else {
				$text = 'Ha habido un error al insertar el usuario en la base de datos';
				add_alert('admin', 'alert', 1, $text);
			}
		}	//	else		if($error[1] || $error[2] || $error[3]) {
	}	//	else		if(my_num_rows($sel_usr)) {	
}	//	if($_POST) {

if(!isset($_SESSION['new_user']['pasapalabra']))
	$_SESSION['new_user']['pasapalabra'] = get_random_pwd(6); 

?>
<div class="default_text" style="width:95%;"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users">&lt; Volver a la lista de usuarios</a></div>
<div class="title_3" align="center" style="background-color:#DDDDDD; width:95%;">Nuevo usuario</div>
<div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Datos obligatorios</div>
<form name="new_user_form" id="new_user_form" method="post" action="">
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td bgcolor="#DDDDDD" align="right">Nombre: </td>
      <td colspan="2" align="left"><input type="text" class="inputlarge" name="name" id="name" style="width:170px;" value="<?= $_POST['name']; ?>" maxlength="250" autofocus="autofocus" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">e-mail:</td>
      <td colspan="2" align="left"><input type="text" class="inputlarge" name="email" id="email" style="width:170px;" value="<?= $_POST['email']; ?>" maxlength="250" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Contrase&ntilde;a:</td>
      <td align="left"><span style="color:#CC3300" id="pasapalabra">
        <?=  $_SESSION['new_user']['pasapalabra'];  ?>
        </span>&nbsp;&nbsp;&nbsp;<a href="JavaScript:update_pwd();"><img src="<?= $conf_images_path; ?>reload.png" align="absmiddle" border="0" title="Actualizar contraseña" /></a></td>
      <td align="left" class="small_text">* Esta contraseña es temporal. El usuario deberá cambiarla la primera vez que se identifique.</td>
    </tr>
  </table>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Datos opcionales</div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td bgcolor="#DDDDDD" align="right">Tel&eacute;fono 1:</td>
      <td align="left"><input type="text" class="inputlarge" name="phone1" id="phone1" style="width:170px;" value="<?= $_POST['phone1']; ?>" maxlength="45" /></td>
      <td align="right" bgcolor="#DDDDDD">Tel&eacute;fono 2: </td>
      <td align="left"><input type="text" class="inputlarge" name="phone2" id="phone2" style="width:170px;" value="<?= $_POST['phone2']; ?>" maxlength="45" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Calle, n&ordm;, piso, puerta: </td>
      <td colspan="3" align="left"><input type="text" class="inputlarge" name="pers_addr" id="pers_addr" style="width:450px;" value="<?= $_POST['pers_addr']; ?>" maxlength="150" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">C&oacute;digo Postal:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_postcode" id="pers_postcode" style="width:70px;" value="<?= $_POST['pers_postcode']; ?>" maxlength="10" /></td>
      <td bgcolor="#DDDDDD" align="right">Ciudad:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_city" id="pers_city" style="width:170px;" value="<?= $_POST['pers_city']; ?>" maxlength="75" /></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Provincia:</td>
      <td align="left"><input type="text" class="inputlarge" name="pers_province" id="pers_province" style="width:170px;" value="<?= $_POST['pers_province']; ?>" maxlength="10" /></td>
      <td align="right">&nbsp;</td>
      <td align="left">&nbsp;</td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Fecha de nacimiento:</td>
      <td align="left"><input type="text" class="inputlarge" name="DOB" id="DOB" style="width:90px;" value="<?= $_POST['DOB']; ?>" maxlength="12" onblur="JavaScript:construct_date('DOB');"/></td>
      <td align="right">&nbsp;</td>
      <td align="left">&nbsp;</td>
    </tr>
  </table>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Cuenta de socio</div>
  <?php

$sql = 'SELECT * FROM member_types';
$sel_mts = my_query($sql, $conex);

?>
  <div class="default_text" style="width:70%; padding:10px;">
    <label>
    <?php
	$checked = $_POST['member'] == 'on' ? 'checked="checked"' : '';
	?>
    <input type="checkbox" name="member" id="member" onchange="JavaScript:show_member_box();" <?= $checked; ?>/>
    Crear cuenta de socio</label>
  </div>
  <div id="member_box" style="display:none;">
    <div class="default_text" style="width:70%">Seleccionar el tipo de socio y la frecuencia de la cuota:</div>
    <table width="70%" border="0" cellpadding="4" cellspacing="4" class="default_text">
      <tr>
        <td width="19%"></td>
        <th width="27%">Mensual</th>
        <th width="27%">Cuatrimestral</th>
        <th width="27%">Anual</th>
      </tr>
      <?php
$first = true;
while($record = my_fetch_array($sel_mts)) {
if($first) {
	$first = false;
	$checked = 'checked="checked" ';
}
else
	$checked = '';
	
?>
      <tr>
        <td align="right"><?= $record['type_name']; ?></td>
        <td bgcolor="#DDDDDD"><label>
          <input type="radio" value="mth_<?= $record['type_id']; ?>" name="member_type" <?= $checked; ?>/>
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
?>
    </table>
  </div>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Datos bancarios (obligatorio para socios)</div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td align="right" bgcolor="#DDDDDD" width="30%">C&oacute;digo Cuenta Corriente: </td>
      <td align="left"><table cellpadding="1" cellspacing="1" border="0">
          <tr class="small_text">
            <td>Entidad</td>
            <td>Sucursal</td>
            <td>D.C.</td>
            <td>Nº Cuenta</td>
          </tr>
          <tr>
            <td><input type="text" class="inputlarge" name="CCC_ent" id="CCC_ent" style="width:60px;" value="<?= $_POST['CCC_ent']; ?>" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_suc" id="CCC_suc" style="width:60px;" value="<?= $_POST['CCC_suc']; ?>" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_DC" id="CCC_DC" style="width:30px;" value="<?= $_POST['CCC_DC']; ?>" maxlength="2" /></td>
            <td><input type="text" class="inputlarge" name="CCC_acc" id="CCC_acc" style="width:120px;" value="<?= $_POST['CCC_acc']; ?>" maxlength="10" /></td>
          </tr>
        </table></td>
      <td align="left"><div id="error_pwd2" class="error_message" style="display:none">El código de cuenta corriente no es válido</div></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Nombre del titular de la cuenta: </td>
      <td align="left"><?php
	  if($exist_ccc) {
	  	echo decode($pay_array['ccc_name']);
	  }
	  else {
	  ?>
        <input type="text" class="inputlarge" name="CCC_titular" id="CCC_titular" style="width:170px;" value="<?= $_POST['CCC_titular']; ?>" maxlength="250" />
        <?php	}	?></td>
      <td align="left">&nbsp;</td>
    </tr>
  </table>
  <div class="small_text border_bottom_dotted" style="width:95%; padding-top:10px;">Otros datos</div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" align="center" style="width:90%">
    <tr>
      <td align="right" bgcolor="#DDDDDD">Comentarios: </td>
      <td><textarea class="inputlarge" style="width:200px;" name="comments" id="comments"><?= $_POST['comments']; ?>
</textarea></td>
    </tr>
    <tr>
      <td align="right" bgcolor="#DDDDDD">Nivel de pádel: </td>
      <td><?php
	  if(!$_POST['user_level'])	$_POST['user_level'] = '2';
	  
      $parameters = array('table' => 'users_levels', 'code_field' => 'level_value', 'desc_field' => 'level_name', 'name' => 'user_level',
	  					  'selected' => $_POST['user_level'], 'class' => 'inputlarge');
	  print_combo_db ($parameters)
	  
	  ?></td>
    </tr>
  </table>
  <div align="center" style="padding:20px 0px 20px 0px;">
    <input type="button" name="save" class="button" value="  CREAR USUARIO  " onclick="JavaScript:save_data();" />
  </div>
</form>
</div>
<script language="JavaScript">
function save_data() {
	var error = '';
	with(document.new_user_form) {
		if(name.value == '')	error+= 'El nombre no puede estar vacío.\n';
		if(email.value == '')	error+= 'El correo electrónico no puede estar vacío.\n';
		if(member.checked) {
			if(CCC_ent.value == '' || CCC_suc == '' || CCC_DC == '' || CCC_acc == '')
				error+= 'Para crear una cuenta de socio es necesario el código de cuenta corriente\n';
			if(CCC_titular.value == '')
				error+= 'El nombre del titular de la cuenta es obligatorio\n';
		}
	}
	
	if(error == '')
		document.new_user_form.submit();
	else
		alert(error);
}

function show_member_box() {
	if(document.new_user_form.member.checked)
		my_display = 'block';
	else
		my_display = 'none';
	
	document.getElementById('member_box').style.display = my_display;
}

show_member_box();

function update_pwd() {
	url = '<?= $conf_include_path; ?>ajax.php?content=upd_pwd';
	getData(url, 'pasapalabra');
}
</script>
