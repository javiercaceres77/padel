<?php

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

if($_SESSION['admin']['user_book']) {
	$_POST['search_input'] = $_SESSION['admin']['user_book'];
	unset($_SESSION['admin']);
}

$now = new date_time('now');

$ob_slot = new time_slot($_GET['detail']);
$ob_court = new court($ob_slot->get_court_id());
$ob_slot_date = $ob_slot->get_date_time();

echo '<div class="title_3 indented">Reserva en '. $ob_court->name .'<br />';
echo 'Fecha: '. $ob_slot_date->odate->format_date('long') .' a las '. $ob_slot_date->hour .':'. $ob_slot_date->minute .'<br />';
echo 'Selecciona duración de la reserva: &nbsp;&nbsp;';
echo '<select id="duration" class="inputnormal" name="duration">';
for($i = 0.5; $i <= $_GET['rem']; $i += 0.5)
	echo '<option value="'. $i .'">'. $i .' h.</option>';
echo '</select><br /><br />';
?>
Selecciona usuario
<table border="0" cellspacing="3" cellpadding="3" class="default_text">
  <tr>
    <td bgcolor="#DDDDDD">Escribe parte de un nombre o de una dirección de e-mail o un número de usuario que quires buscar (0 para jugador desconocido):</td>
  </tr>
  <tr>
    <td>Nombre/email/núm.:
      <input type="text" class="inputnormal" name="search_input" id="search_input" style="width:170px;" value="" maxlength="250" />
      <input type="button" class="button_small" name="search" id="search" value=" BUSCAR " onclick="search_user();"></td>
  </tr>
</table></div>
<div id="users_result"> </div>
<div id="join_users"> </div>



<script language="javascript">

function search_user() {
	url = 'inc/ajax.php?content=search_ply&search='+ document.getElementById('search_input').value;
	getData(url, 'users_result');
}

var pay_meth = 'cash';

function select_user(user_id) {
	document.getElementById('join_users').setAttribute("class", "standard_container");
	url = 'inc/ajax.php?content=place_booking&user='+ user_id +'&slot=<?= $ob_slot->slot_id; ?>&dur='+ document.getElementById('duration').value;
	getData(url, 'join_users');
	pay_meth = 'cash';
}

function confirm_book(user_id, book_id) {
	document.getElementById('users_result').style.visibility = "hiden";
	url = 'inc/ajax.php?content=confirm_book&user=' + user_id + '&booking=' + book_id + '&pm=' + pay_meth;
	getData(url, 'join_users');
}

function select_pm(pm) {
	pay_meth = pm;
}


</script>

<?php
echo '</div>';
exit();

?>
<tr>
  <td><?php
	if(my_num_rows($sel)) {
		?>
    <table width="100%" border="0" cellpadding="2" cellspacing="2" class="default_text">
      <tr>
        <td colspan="3" class="small_text bottomborderdotted"><?= my_num_rows($sel); ?>
          resultados</td>
      </tr>
      <?php	while($record = my_fetch_array($sel)) {		?>
      <tr>
        <td class="bottomborderthin"><a href="JavaScript:select_user('<?= $record['user_id']; ?>')">
          <?= $record['full_name']; ?>
          </a> (
          <?= $record['user_id']; ?>
          )</td>
        <td class="bottomborderthin"><?= $record['email']; ?></td>
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
		echo '<span class="error_message">No se han encontrado resultados para '. $_POST['search_input'] .'</span>';
	}
	?></td>
</tr>
<?php	if($_POST) {	?>
<tr>
  <td>Selecciona el usuario de la lista de arriba para el que quires hacer la reserva</td>
</tr>
<?php	}	?>
<tr>
  <td><div id="join_users"> </div></td>
</tr>
</table>
<?php


exit();




	# check that the booking time is free or that the pre-book belongs to the logged in user.
$sql = 'SELECT booking_id, user_id, status FROM bookings WHERE slot_id = '. $_GET['detail'] .' AND expire_datetime > \''. $now->datetime .'\' AND status NOT IN (\'cancelled\')';
	# if status = prebook -> continue with process, do not insert. This will happen on $_POST
	# if status = confirmed -> there is a conflict, jump to bookings
	# if status = waiting -> for future use.

$sel = my_query($sql, $conex);
if(my_num_rows($sel))
	$book_id = my_result($sel, 0, 'booking_id');

# get slot and booking information
$sql = 'SELECT t.slot_starts, t.slot_ends, t.date_db, (f.fare / 60 * t.time_slot_min) as fare, f.fare_name, f.is_member, c.court_id, c.name
FROM time_slots t
INNER JOIN fares f ON f.date_id = t.date_id AND t.slot_starts >= f.time_starts	AND t.slot_starts < f.time_ends
INNER JOIN courts c ON t.court_id = c.court_id
WHERE t.slot_id = '. $_GET['detail'];

$sel = my_query($sql, $conex);

$arr_slot = array();
while($record = my_fetch_array($sel)) {
	$arr_slot['starts'] = $record['slot_starts'];
	$arr_slot['ends']   = $record['slot_ends'];
	$arr_slot['date']   = new my_date($record['date_db']);
	$arr_slot['fare'][$record['is_member']]   = array('fare' => $record['fare'], 'name' => $record['fare_name']);
	$arr_slot['court']  = $record['court_id'];
	$arr_slot['court_name'] = $record['name'];
}

//if($arr_book['status'] != 'prebook' || $arr_book['user_id'] != $_SESSION['login']['user_id'])

# create a pre-book for the admin user.
if(!$book_id) {

	$expire_time = $now->plus_mins(5);
	$booking_datetime = new date_time($arr_slot['date']->odate, $arr_slot['starts']);
	$cancel_period = get_config_value('min_advance_cancel_booking') * 60;
	$cancel_datetime = $booking_datetime->plus_mins(-$cancel_period);
	
	$arr_ins = array('user_id' 					=> $_SESSION['login']['user_id'],
	//				 'user_is_member' 			=> '',
					 'slot_id' 					=> $_GET['detail'],
					 'booking_datetime' 		=> $booking_datetime->datetime,
					 'book_placement_datetime' 	=> $now->datetime,
					 'status'					=> 'prebook',
					 'booked_by'				=> 'admin',
					 'channel'					=> 'web',
					 'waiting_guests'			=> 0,
					 'court_id'					=> $arr_slot['court'],
	//				 'booking_fare'				=> $_SESSION['pre_books'][$_GET['detail']]['fare'] * 4,
					 'cancel_until_datetime'	=> $cancel_datetime->datetime,
					 'expire_datetime'			=> $expire_time->datetime,
	//				 'player_level'				=> $_SESSION['login']['user_level'],
	//				 'fare_id'					=> $_SESSION['pre_books'][$_GET['detail']]['fare_id']
					 );
	
	$book_id = insert_array_db('bookings', $arr_ins, true);
}

if($_POST) {
	$sql = 'SELECT user_id, full_name, email, is_member
	FROM users ';
	if(is_numeric($_POST['search_input']))
		$sql.= 'WHERE user_id = '. $_POST['search_input'];
	else
		$sql.= 'WHERE (full_name like \'%'. $_POST['search_input'] .'%\' OR email like \'%'. $_POST['search_input'] .'%\')';
	$sql.= ' AND deleted_ind = \'0\'';

	$sel = my_query($sql, $conex);
}

?>
<div class="title_3 indented">Reservar
  <?= $arr_slot['court_name'] .'<br>Fecha: '. $arr_slot['date']->format_date('long') .'<br> Hora: '. $arr_slot['starts'] .' a '. $arr_slot['ends'] .'<br>'; ?>
  <?= 'Tarifas: <br>&middot; '. $arr_slot['fare']['0']['name'] .': '. print_money($arr_slot['fare']['0']['fare']) .'<br>&middot; '. $arr_slot['fare']['1']['name'] .': '. print_money($arr_slot['fare']['1']['fare']); ?>
</div>
<div class="default_text"></div>
<form name="search_users" id="search_users" method="post" action="">
  <table border="0" cellspacing="3" cellpadding="3" class="default_text">
    <tr>
      <td bgcolor="#DDDDDD">Escribe parte de un nombre o de una dirección de e-mail o un número de usuario que quires buscar (0 para jugador desconocido):</td>
    </tr>
    <tr>
      <td>Nombre/email/núm.:
        <input type="text" class="inputnormal" name="search_input" id="search_input" style="width:170px;" value="<?= $_POST['search_input']; ?>" maxlength="250" autofocus="autofocus" />
        <input type="submit" class="button_small" name="search" id="search" value=" BUSCAR "></td>
    </tr>
    <tr>
      <td><?php
	if(my_num_rows($sel)) {
		?>
        <table width="100%" border="0" cellpadding="2" cellspacing="2" class="default_text">
          <tr>
            <td colspan="3" class="small_text bottomborderdotted"><?= my_num_rows($sel); ?>
              resultados</td>
          </tr>
          <?php	while($record = my_fetch_array($sel)) {		?>
          <tr>
            <td class="bottomborderthin"><a href="JavaScript:select_user('<?= $record['user_id']; ?>')">
              <?= $record['full_name']; ?>
              </a> (
              <?= $record['user_id']; ?>
              )</td>
            <td class="bottomborderthin"><?= $record['email']; ?></td>
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
		echo '<span class="error_message">No se han encontrado resultados para '. $_POST['search_input'] .'</span>';
	}
	?></td>
    </tr>
    <?php	if($_POST) {	?>
    <tr>
      <td>Selecciona el usuario de la lista de arriba para el que quires hacer la reserva</td>
    </tr>
    <?php	}	?>
    <tr>
      <td><div id="join_users"> </div></td>
    </tr>
  </table>
</form>
<script language="javascript">

var pay_meth = 'cash';

function select_user(user_id) {
	document.getElementById('join_users').setAttribute("class", "standard_container");
	url = 'inc/ajax.php?content=place_booking&user='+ user_id +'&booking=<?= $book_id; ?>';
	getData(url, 'join_users');
	pay_meth = 'cash';
}

function confirm_book(user_id, book_id) {
	//document.getElementById('users_result').style.visibility = "none";
	alert('user: '+ user_id +'; book: '+ book_id +'; pay: '+ pay_meth);
//	url = 'inc/ajax.php?content=confirm_book&user=' + user_id + '&booking=' + book_id + '&pm=' + pay_meth;
//	getData(url, 'join_users');
}

function select_pm(pm) {
	pay_meth = pm;
}

</script>