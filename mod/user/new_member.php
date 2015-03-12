<?php

$today = new my_date('today');
$tomorrow = $today->plus_days(1);

if($_POST) {
	print_array($_POST);
	$types_arr = explode('_', $_POST['member_type']);
	print_array($types_arr);
	
//	print_array($ob_user);
	# Insert the member in the members table
	$arr_ins = array('user_id'				=> $_SESSION['login']['user_id'],
					 'user_name'			=> $_SESSION['login']['name'],
					 'payment_method_id' 	=> '2',
					 'member_type'			=> $types_arr['1'],
					 'registration_date'	=> date('Y-m-d'),
					 'next_bill_date'		=> $today->plus_cycle(1, $types_arr['0'])
					 
	
	create member account
Update user.is_member = 1
add payment_detail
/*
	
member_id        int(11) PK
user_id          int(11)
user_name        varchar(75)
payment_method_id int(11)
member_type      char(5)
registration_date date
last_bill_date   date
last_bill_amount decimal(17,3)
last_bill_id     int(11)
next_bill_date   date
member_account_status varchar(45)

	*/
/*	$address = $_POST['pers_addr'] .'<br>'. $_POST['pers_postcode'] .'<br>'. $_POST['pers_city'] .'<br>'. $_POST['pers_province'];
	$upd_array = array('full_name' => $_POST['pers_name'], 'phone1' => $_POST['pers_phone'], 'phone2' => $_POST['pers_phone2'], 'address' => $address);
	update_array_db('users', 'user_id', $_SESSION['login']['user_id'], $upd_array);
	
	if($_POST['CCC_ent'] && $_POST['CCC_suc'] && $_POST['CCC_DC'] && $_POST['CCC_acc'] && $_POST['CCC_titular']) {
		$ccc = encode($_POST['CCC_ent'] .'/'. $_POST['CCC_suc'] .'/'. $_POST['CCC_DC'] .'/'. $_POST['CCC_acc']);
		$ccc_name = encode($_POST['CCC_titular']);

		$ins_array = array('user_id' => $_SESSION['login']['user_id'], 'method_id' => '2', 'details' => $ccc, 'ccc_name' => $ccc_name, 'authorized_method_ind' => '0');
		insert_array_db('payment_user_details', $ins_array);
	}
	*/
}


/*
$sql = 'SELECT full_name, phone1, phone2, email, address, available_books_num, last_login_datetime, is_member, member_type
		FROM users WHERE user_id = '. $_SESSION['login']['user_id'] .' AND deleted_ind <> \'1\'';
		
$sel_usr = my_query($sql, $conex);
$usr_array = my_fetch_array($sel_usr);

$arr_addr = explode('<br>', $usr_array['address']);
*/
$sql = 'SELECT * FROM payment_user_details WHERE user_id = \''. $_SESSION['login']['user_id'] .'\' AND method_id = \'2\'';

$sel_ccc = my_query($sql, $conex);
$exist_ccc = my_num_rows($sel_ccc);

if($exist_ccc)
	$pay_array = my_fetch_array($sel_ccc);


$sql = 'SELECT * FROM member_types';
$sel_mts = my_query($sql, $conex);


?>

<form name="member_form" id="member_form" method="post" action="">
  <div class="default_text" style="width:70%">Selecciona el tipo de socio y la frecuencia de la cuota que quieres:</div>
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
        <?= print_money($record['quarter_quote']); ?>
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
  <?php
if($exist_ccc) {
?>
  <div class="default_text" style="width:70%">Las cuotas se cargarán en tu cuenta corriente:
    <?= decode($pay_array['details']); ?>
    a partir de mañana (
    <?= $tomorrow->format_date(); ?>
    ).<br />
    <?php
	if($pay_array['authorized_method_ind']) {
?>
    En cuanto crees tu cuenta de socio podrás empezar a hacer reservas con tarifa reducida de socio.<br />
    <?php
	}
	else {
?>
    Para poder hacer reservas como socio debes esperar a que tu código de cuenta corriente sea autorizado.<br />
    <?php
	}
	?></div>
<?php
}
else {
?>
  <div class="default_text" style="width:70%; padding-top:30px;">Para cargar las cuotas necesitasmos un código de cuenta corriente.<br />
Para poder hacer reservas como socio debes esperar a que tu código de cuenta corriente sea autorizado.<br /></div>
  <table cellpadding="5" cellspacing="4" border="0" class="default_text" style="width:70%">
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
            <td><input type="text" class="inputlarge" name="CCC_ent" id="CCC_ent" style="width:60px;" value="" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_suc" id="CCC_suc" style="width:60px;" value="" maxlength="4" /></td>
            <td><input type="text" class="inputlarge" name="CCC_DC" id="CCC_DC" style="width:30px;" value="" maxlength="2" /></td>
            <td><input type="text" class="inputlarge" name="CCC_acc" id="CCC_acc" style="width:120px;" value="" maxlength="10" /></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td bgcolor="#DDDDDD" align="right">Nombre del titular de la cuenta: </td>
      <td align="left"><input type="text" class="inputlarge" name="CCC_titular" id="CCC_titular" style="width:170px;" value="" maxlength="250" /></td>
    </tr>
  </table>
  <?php
}
?>
  <div align="center" style="padding:20px 0px 20px 0px; width:70%">
    <input type="button" name="save" class="button" value="  CREAR CUENTA  " onclick="JavaScript:save_data();" />
  </div>
</form>
</div>
<script language="JavaScript">
function save_data() {
	var error = '';
	if(document.getElementById('CCC_ent')) {	// if exist the CCC form inputs
		with(document.member_form) {
			if(CCC_ent.value == '' || CCC_suc.value == '' || CCC_DC.value == '' || CCC_acc.value == '')	error+= 'El código de cuenta corriente no es válido.\n';
			if(CCC_titular.value == '') error+= 'Debes escribir el nombre del titular de la cuenta.\n';
		}
	}
		
	if(error == '')
		document.member_form.submit();
	else
		alert(error);
}
</script>
