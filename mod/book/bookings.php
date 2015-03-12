<?php
	# number of days in advace.
	$num_days_adv = get_config_value('days_advance_booking_available');
//	$arr_num_days = simple_select('configuration', 'config_code', 'days_advance_booking_available', array('config_value', 'value_type'));
	
	if(!$_GET['date'])	# get the first key of the array
		$_GET['date'] = date('Y-m-d');
	
	$now = new date_time('now');
	$today = new my_date('today');
	$current_date = new my_date($_GET['date']);
	$today_plus_num_days = $today->plus_days($num_days_adv);
	//$today_minus_num_days = $today->plus_days(-$num_days_adv);
	
	$sql = 'SELECT dd.date_id, dd.date_db, dt.date_desc, dt.week_day_desc, dt.month_desc, dd.num_day_of_month, ot.open_from_1, ot.open_from_2, ot.open_to_1, ot.open_to_2
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
											   'open_to_1' => $record['open_to_1'],
											   'open_from_2' => $record['open_from_2'],
											   'open_to_2' => $record['open_to_2'],
											   'date_id' => $record['date_id']);
	}

	#to avoid forbidden date ranges from $_GET check that it is on the array
	if(!$arr_dates[$current_date->odate]) {
		$current_date = $today->plus_days(1);
	}
	
	# check if club is closed or is about to close (> 1:30 hour for closing)
	$closing_time = new date_time(date('Y-m-d'), $arr_dates[$today->odate]['open_to_2']);
//	$opening_time = new date_time(date('Y-m-d'), $arr_dates[$today
	$last_time_to_book = $closing_time->plus_mins(-90);
	
	# if it's too late to book today, remove it from the array
	if($last_time_to_book->timestamp < $now->timestamp) {
		unset($arr_dates[$today->odate]);
		# also if the current date is today move it to tomorrow
		if($current_date->odate == $today->odate) {
			# rewrite the $current_date object
			$current_date = $today->plus_days(1);
		}
	}
	
	$num_days = count($arr_dates);
	$column_width = floor(100 / $num_days);
	
?>

<div class="default_text bg_ddd round_borders_3" style="padding:3px 10px; margin-bottom:8px;">
  <form name="dur_form" id="dur_form">
    Reserva:
    <?php
	$arr_durations = explode(',', get_config_value('possible_booking_duration'));
	$first = true;
	foreach($arr_durations as $duration) {
		$checked = '';
		if($first) {
			$checked = ' checked="checked"';
			$first = false;
		}
?>
    <label>
      <input type="radio"<?= $checked; ?> name="duration" onclick="JavaScript:set_duration(this.value);" id="dur_<?= $duration; ?>" value="<?= $duration; ?>" />
      <?= $duration; ?>
      min.</label>
    <?php
	}
?>
  </form>
</div>
<table border="0" cellpadding="3" cellspacing="0" width="100%" class="default_text">
  <tr>
    <?php
		  foreach($arr_dates as $my_date) {
			$str = $my_date['date_db'] == $today->odate ? ucfirst(today) : $my_date['date_desc'];
			$class = $my_date['date_db'] == $current_date->odate ? 'active_tab' : 'inactive_tab bottomborderthin';
		  	echo '<td align="center" width="'. $column_width .'%" class="'. $class .'" onclick="JavaScript:jump_to(\''. $conf_main_page .'?mod='. $_GET['mod'] .'&date='. $my_date['date_db'] .'\');">'. $str .'</td>';
		  }		  
		  ?>
  </tr>
  <tr>
    <td colspan="<?= $num_days; ?>" class="bottomborderthin sideborderthin"><br />
      <div id="time_slots_container"> </div>
      <br />
      
      <!--<table border="0" width="100%" cellpadding="2" cellspacing="2">
        <tr>
          <td align="center">libre&nbsp;&nbsp;<span class="time_slot ts_free" style="width:60px;"></span></td>
          <td align="center">pre-reserva&nbsp;&nbsp;<span class="time_slot ts_prebook" style="width:60px;"></span></td>
          <td align="center">confirmada&nbsp;&nbsp;<span class="time_slot ts_confirmed" style="width:60px;"></span></td>
          <td align="center">ocupada&nbsp;&nbsp;<span class="time_slot ts_unavailable" style="width:60px;"></span></td>
        </tr>
      </table>--></td>
  </tr>
</table>
<div class="standard_container" id="pre_book_container"> </div>
<script language="javascript">

num_updates = 65;
duration = 60;
document.onload = update_slots();
//document.onload = show_pre_books();

function set_duration(dur) {
	duration = dur;
	update_slots();
}

function update_slots() {
	// get slot duration
	if(num_updates > 0) {
		url = 'inc/ajax.php?content=update_slots&date=<?= $current_date->odate; ?>&dur='+ duration;
		getData(url, 'time_slots_container');
		
		num_updates--;
		window.setTimeout(update_slots, 10000);
		show_pre_books();
	}
	else {
		document.location = '<?= $conf_main_page; ?>?func=logout';
	}
}

function update_slots_once() {
	if(num_updates > 0) {
		url = 'inc/ajax.php?content=update_slots&date=<?= $current_date->odate; ?>&dur='+ duration;
		getData(url, 'time_slots_container');
	}
}

function pre_book(slot_id) {
	document.getElementById(slot_id).style.backgroundImage = 'url(\'<?= $conf_images_path; ?>processing.gif\')';
	document.getElementById(slot_id).style.backgroundPosition = 'center';
	document.getElementById(slot_id).style.backgroundRepeat = 'no-repeat';
	document.getElementById(slot_id).style.cursor = 'default'
	document.getElementById(slot_id).onclick = '';

	url = 'inc/ajax.php?content=pre_book&detail='+ slot_id + '&duration=' + duration;
	getData2(url, 'pre_book_container');
	window.setTimeout(update_slots_once, 2000);
	window.setTimeout(show_alerts, 1000);
}

function show_pre_books() {
	url = 'inc/ajax.php?content=pre_book';
	getData2(url, 'pre_book_container');
}

function delete_pre_book(slot_id) {
	url = 'inc/ajax.php?content=delete_pre_book&detail='+ slot_id;
	getData_no_div(url); //, 'pre_book_container');
	window.setTimeout(update_slots_once, 2000);
	window.setTimeout(show_pre_books, 2000);
}

function confirm_bookings() {
	document.location = '<?= $conf_main_page; ?>?mod=book&subtab=confirm';
}

function activate_slots(slots_str) {
	var my_str;
	arr_slots = slots_str.split(',');

	for (i = 0; i < arr_slots.length; i++) {
		document.getElementById(arr_slots[i]).className = 'hours_table_cell ts_free_hover';
	}
}

function deactivate_slots(slots_str) {
	var my_str;
	arr_slots = slots_str.split(',');

	for (i = 0; i < arr_slots.length; i++) {
		if(document.getElementById('mark_' + arr_slots[i]).value == '0')
			document.getElementById(arr_slots[i]).className = 'hours_table_cell ts_free_read_only';
		else
			document.getElementById(arr_slots[i]).className = 'hours_table_cell ts_free';
	}
}

</script> 
