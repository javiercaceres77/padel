<?php

if(!$ob_user->is_admin()) {
	jump_to($conf_main_page);
	exit();
}

$now = new date_time('now');
$_SESSION['multiplayer'] = array();

# get slot information
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

$slot_starts = new date_time($arr_slot['date']->odate, $arr_slot['starts']);
$mins_to_start = round(($slot_starts->timestamp - $now->timestamp) / 60);
$start_str = $mins_to_start >= 0 ? 'La reserva comienza en '. $mins_to_start .' minutos' : 'La reserva comenzó hace '. abs($mins_to_start) .' minutos';

# get booking information
$sql = 'SELECT u.full_name, u.user_id, u.is_admin, b.user_is_member, b.booking_fare, b.book_placement_datetime, b.payment_method, b.booking_id
FROM bookings b
INNER JOIN users u on u.user_id = b.user_id
WHERE b.slot_id = \''. $_GET['detail'] .'\' AND b.status = \'confirmed\'';

$sel = my_query($sql, $conex);

$arr_book = my_fetch_array($sel);
if(!count($arr_book)) {
	exit();
}

//pa($arr_book);
?>
<style type="text/css">
<!--
.huge_number {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 64px;
	font-weight:bold;
	color: #FFFFFF;
	background-color: #999999;
}
.player_name {
	font-weight:bold;
	color:#5B0B0B;
}
-->
</style>
<table border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td class="title_3">Pagar reserva para
      <?= $arr_slot['court_name'] .', '. $arr_slot['date']->format_date('med') .', '. $arr_slot['starts'] .' &ndash; '. $arr_slot['ends']; ?></td>
    <td width="20px">&nbsp;</td>
    <td class="small_text"><?= print_money($arr_slot['fare']['0']['fare']) .' '. $arr_slot['fare']['0']['name']; ?>
      <br>
      <?= print_money($arr_slot['fare']['1']['fare']) .' '. $arr_slot['fare']['1']['name']; ?></td>
  </tr>
</table>
<span style="background-color:#DDDDDD; padding:4px 15px;" class="default_text">
<?= $start_str; ?>
</span>
<div class="standard_container">
  <table width="100%" border="0" cellpadding="2" cellspacing="2" class="default_text">
    <tr>
      <td width="50%" valign="top"><table width="100%" border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td width="40" height="250" align="center" valign="top" class="huge_number">1</td>
            <td valign="top"><?php
			  
			  $det_user1 = new user($arr_book['user_id']);
			  //$arr_pms = $det_user1->get_payment_methods();
			  
			  $member_str = $arr_book['user_is_member'] == '1' ? '<img src="'. $conf_images_path .'user16.png" title="'. $arr_book['full_name'] .' es socio."  align="absmiddle" />' : '';
              echo '<span class="player_name">'. $arr_book['full_name'] .'</span> ('. $arr_book['user_id'] .') '. $member_str;
			  
			  $book_datetime = new date_time($arr_book['book_placement_datetime']);
			  echo '&nbsp;&nbsp;&nbsp;(Hizo la reserva el '. $book_datetime->odate->format_date('month_day') .', '. $book_datetime->hour .':'. $book_datetime->minute .')<br />';
			  
			  if($arr_book['payment_method'] == 'bonus') {
			  	echo 'Pagado con bono';
			  }
			  elseif($arr_book['payment_method'] == 'ccc') {
			  	echo 'Pre-pago cuenta corriente: '. print_money($arr_book['booking_fare']);
			  }
			  elseif($arr_book['payment_method'] == 'cash') {
			  	echo 'Pago en efectivo / tarjeta: '. print_money($arr_book['booking_fare']);
			  }
			  
			  $_SESSION['multiplayer'][1] = array('user_id' => $det_user1->user_id, 'user_name' => $arr_book['full_name'], 'is_member' => $arr_book['user_is_member'],
			  									  'pm' => $arr_book['payment_method'], 'amount' => $arr_book['booking_fare']);
			  # default values (unknown player) for the rest of the players
			  $default_arr = array('user_id' => 0, 'user_name' => 'Jugador desconocido', 'is_member' => 0, 'pm' => 'cash', 'amount' => $arr_slot['fare']['0']['fare']);
			  $_SESSION['multiplayer'][2] = $default_arr;
			  $_SESSION['multiplayer'][3] = $default_arr;
			  $_SESSION['multiplayer'][4] = $default_arr;
			  
			  //pa($arr_pms);
			  ?></td>
          </tr>
        </table></td>
      <td width="50%" valign="top"><table width="100%" border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td width="40" height="250" align="center" valign="top" class="huge_number">2</td>
            <td valign="top">Busca usuario por nombre/email/núm.:
              <input type="text" class="inputnormal" name="search_input2" id="search_input2" style="width:170px;" maxlength="250" autofocus="autofocus" />
              <input type="button" class="button_small" name="search2" id="search2" value=" BUSCAR " onclick="JavaScript:search_ply(2);">
              <div id="player2"><span class="player_name">Jugador Desconocido</span><br />
                <label>
                <input type="radio" value="ply2_cash" name="ply2pm" checked="checked"  />
                Efectivo / tarjeta:
                <?= print_money($arr_slot['fare']['0']['fare']) .' ('. $arr_slot['fare']['0']['name'] .')'; ?>
                </label>
              </div></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td valign="top"><table width="100%" border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td width="40" height="250" align="center" valign="top" class="huge_number">3</td>
            <td valign="top">Busca usuario por nombre/email/núm.:
              <input type="text" class="inputnormal" name="search_input3" id="search_input3" style="width:170px;" maxlength="250" />
              <input type="button" class="button_small" name="search3" id="search3" value=" BUSCAR " onclick="JavaScript:search_ply(3);">
              <div id="player3"><span class="player_name">Jugador Desconocido</span><br />
                <label>
                <input type="radio" value="ply3_cash" name="ply3pm" checked="checked"  />
                Efectivo / tarjeta:
                <?= '<strong>'. print_money($arr_slot['fare']['0']['fare']) .'</strong> ('. $arr_slot['fare']['0']['name'] .')'; ?>
                </label>
              </div></td>
          </tr>
        </table></td>
      <td valign="top"><table width="100%" border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td width="40" height="250" align="center" valign="top" class="huge_number">4</td>
            <td valign="top">Busca usuario por nombre/email/núm.:
              <input type="text" class="inputnormal" name="search_input4" id="search_input4" style="width:170px;" maxlength="250" />
              <input type="button" class="button_small" name="search4" id="search4" value=" BUSCAR " onclick="JavaScript:search_ply(4);">
              <div id="player4"><span class="player_name">Jugador Desconocido</span><br />
                <label>
                <input type="radio" value="ply4_cash" name="ply4pm" checked="checked"  />
                Efectivo / tarjeta:
                <?= print_money($arr_slot['fare']['0']['fare']) .' ('. $arr_slot['fare']['0']['name'] .')'; ?>
                </label>
              </div></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td colspan="2" align="center" valign="top"><div class="indented">
          <input type="button" class="button" name="pay" id="pay" onclick="JavaScript:pay();" value="   RESERVA&nbsp;&nbsp;&nbsp;PAGADA   " />
        </div></td>
    </tr>
  </table>
</div>
<form name="pay_form" id="pay_form" action="<?= $conf_main_page; ?>?mod=admin&tab=bookings&subtab=confirm_payment" method="post">
  <input type="hidden" name="ply_id1" id="ply_id1" value="<?= $det_user1->user_id; ?>" />
  <input type="hidden" name="amount1" id="amount1" value="<?= $arr_book['booking_fare']; ?>" />
  <input type="hidden" name="pm1" id="pm1" value="<?= $arr_book['payment_method']; ?>" />
  <input type="hidden" name="booking_id" id="booking_id" value="<?= $arr_book['booking_id']; ?>" />
  <input type="hidden" name="slot_id" id="slot_id" value="<?= $_GET['detail']; ?>" />
  <input type="hidden" name="pm2" id="pm2" value="" />
  <input type="hidden" name="pm3" id="pm3" value="" />
  <input type="hidden" name="pm4" id="pm4" value="" />
<!--  <input type="hidden" name="ply_id2" id="ply_id2" value="" />
  <input type="hidden" name="ply_id3" id="ply_id3" value="" />
  <input type="hidden" name="ply_id4" id="ply_id4" value="" />-->
<!--  <input type="hidden" name="amount2" id="amount2" value="" />
  <input type="hidden" name="amount3" id="amount3" value="" />
  <input type="hidden" name="amount4" id="amount4" value="" />-->
</form>
<script language="javascript">

var pay_meth2 = 'cash';
var pay_meth3 = 'cash';
var pay_meth4 = 'cash';

function search_ply(ply) {
	my_div = 'player' + ply;
	search_input = document.getElementById('search_input' + ply).value;
	if(search_input != '') {
//		url = 'inc/ajax.php?content=search_ply&ply=' + ply + '&booking=<?= $arr_book['booking_id']; ?>&search=' + search_input;
		url = 'inc/ajax.php?content=search_ply&ply=' + ply + '&search=' + search_input;
		getData(url, my_div);
	}
}

function select_user(user_id, ply) {
	//document.getElementById('join_users').setAttribute("class", "standard_container");
	url = 'inc/ajax.php?content=user_pms&user='+ user_id +'&slot_id=<?= $_GET['detail']; ?>&ply=' + ply; //+'&booking=<?= $book_id; ?>';
	my_div = 'player' + ply;
	getData(url, my_div);
	/*pay_meth2 = 'cash';
	pay_meth3 = 'cash';
	pay_meth4 = 'cash';*/
	eval('pay_meth' + ply + ' = \'cash\'');
}

function select_pm(pm, ply) {
	eval('pay_meth' + ply + ' = pm');
}

function pay() {
	with(document.pay_form) {
		
//		alert('pm2: '+ pay_meth2 +'\npm3: ' + pay_meth3 +'\npm4: ' + pay_meth4);
		
		pm2.value = pay_meth2;
		pm3.value = pay_meth3;
		pm4.value = pay_meth4;
		submit();
	}
}

</script>
