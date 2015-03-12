<?php

	# To make a reservation for a user, put the ID on Session
	if($_GET['user']) 
		$_SESSION['admin']['user_book'] = $_GET['user'];
		
	# Delete any pre-books for the admin so that the slots are freed.
//	pa($ob_user);
	$sql = 'DELETE FROM bookings WHERE status = \'prebook\' AND user_id = '. $ob_user->user_id;
	$del = my_query($sql, $conex);
	$_SESSION['pre_books'] = array();

	//$arr_num_days = simple_select('configuration', 'config_code', 'days_advance_booking_available', array('config_value', 'value_type'));
	$num_days_avl = get_config_value('days_advance_booking_available');
	
	if(!$_GET['date'])	# get the first key of the array
		$_GET['date'] = date('Y-m-d');
	
	$now = new date_time('now');
	$today = new my_date('today');
	$current_date = new my_date($_GET['date']);
	$today_plus_num_days = $today->plus_days($num_days_avl);

	# Get the opening times for the next "7" days.	
	$sql = 'SELECT dd.date_id, dd.date_db, dt.date_desc, dt.week_day_desc, dt.month_desc, dd.num_day_of_month, ot.open_from_1, ot.open_to_2
		FROM date_dim dd
		INNER JOIN date_translations dt ON dt.date_id = dd.date_id
		INNER JOIN opening_times ot ON ot.date_id = dd.date_id
		WHERE dd.date_db BETWEEN \''. $today->odate .'\' AND \''. $today_plus_num_days->odate .'\' AND dt.language = \''. $_SESSION['misc']['lang'] .'\'
		ORDER BY dd.date_db ASC';

	$sel_dates = my_query($sql, $conex);
	$arr_dates = array();
	while($record = my_fetch_array($sel_dates)) {
		$arr_dates[$record['date_db']] = array('date_db' => $record['date_db'],
											   'date_desc_long' => $record['date_desc'],
											   'date_desc' => $record['week_day_desc'] .' '. $record['num_day_of_month'], //substr($record['week_day_desc'], 0, 3
											   'open_from_1' => $record['open_from_1'],
											//   'open_to_1' => $record['open_to_1'],
											//   'open_from_2' => $record['open_from_2'],
											   'open_to_2' => $record['open_to_2'],
											   'date_id' => $record['date_id']);
	}
	
	$today_minus_num_days = $today->plus_days(-$num_days_avl);
	$sql = 'SELECT date_db, desc_day_of_week, num_day_of_month, desc_month FROM date_dim_es WHERE date_db >= \''. $today_minus_num_days->odate .'\' AND date_db < \''. $today->odate .'\'';
	
	$sel_dates = my_query($sql, $conex);
	$arr_dates_prev = array();
	while($record = my_fetch_array($sel_dates)) {
		//$tmp_date = new my_date($record['date_db']);
		$arr_dates_prev[$record['date_db']] = substr($record['desc_day_of_week'], 0, 3) .', '. $record['num_day_of_month'] .' '. substr($record['desc_month'], 0, 3);
	}
	
	#to avoid forbidden date ranges from $_GET check that it is on the array
	if(!$arr_dates[$current_date->odate] && !$arr_dates_prev[$current_date->odate]) {
		$current_date = $today->plus_days(1);
	}
	
	$num_days = count($arr_dates) + 1;
	$column_width = floor(100 / $num_days);
?>
<!--<div id="alert" class="notice_yellow default_text" style="top:-10px; position:relative;">Alerta aquí</div>-->
<table border="0" cellpadding="3" cellspacing="0" width="100%" class="default_text">
  <tr>
    <?php
		# first the previous dates tab
		$class = $arr_dates_prev[$current_date->odate] ? '' : ' bottomborderthin';
		echo '<td align="center" width="'. $column_width .'" class="off_tab'. $class .'">';
    
		$parameters = array('array' => $arr_dates_prev, 'name' => 'date_selector', 'selected' => $current_date->odate, 'class' => 'inputdate', 'on_change' => 'change_date()', 'empty' => '1');
	
		print_combo_array($parameters);
		echo '</td>';
		
		# now the next seven days tabs
		foreach($arr_dates as $my_date) {
			$str = $my_date['date_db'] == $today->odate ? ucfirst(today) : $my_date['date_desc'];
			$class = $my_date['date_db'] == $current_date->odate ? 'active_tab' : 'inactive_tab bottomborderthin';
		  	echo '<td align="center" width="'. $column_width .'%" class="'. $class .'" onclick="JavaScript:jump_to(\''. $conf_main_page .'?mod='. $_GET['mod'] .'&tab=bookings&date='. $my_date['date_db'] .'\');">'. $str .'</td>';
		  }		  
		  ?>
  </tr>
  <tr>
    <td colspan="<?= $num_days; ?>" class="bottomborderthin sideborderthin"><br />
      <div id="admin_books"> </div>
      <br />
</td>
  </tr>
</table><br />
<script language="javascript">

num_updates = 180;
document.onload = update_slots();
//document.onload = show_pre_books();

function update_slots() {
	if(num_updates > 0) {
		url = 'inc/ajax.php?content=update_admin_slots&date=<?= $current_date->odate; ?>';
		getData(url, 'admin_books');
		
		num_updates--;
		window.setTimeout(update_slots, 30000);
	}
	else {
		document.location = '<?= $conf_main_page; ?>?func=logout';
	}
}

function place_booking(slot_id, remaining) {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&subtab=place_booking&rem='+ remaining +'&detail='+ slot_id;
}

function pay_booking(slot_id) {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&subtab=pay_booking&detail='+ slot_id;
}

function add_no_show(slot_id) {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&subtab=no_show&detail='+ slot_id;
}

function see_booking(slot_id) {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&subtab=book_detail&detail='+ slot_id;
}

function change_date() {
	document.location = '<?= $conf_main_page; ?>?mod=admin&tab=bookings&date='+ document.getElementById('date_selector').value;
}

</script>