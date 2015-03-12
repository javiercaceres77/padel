<?php

class member {
	public $member_id;
	public $user_id;
	private $props;
	private $member_type_props;
	private $num_other_users;
//	private $max_other_users;
	
	public function __construct($user_id) {		# This is the user id, not the member id
		$this->user_id = $user_id;
		$sel_arr = simple_select('members', 'user_id', $this->user_id, array('member_id', 'user_name', 'payment_freq', 'member_type', 'registration_date', 'last_bill_date', 'last_bill_amount', 'next_bill_date'), ' AND member_account_status = \'active\'');
		$this->member_id = $sel_arr['member_id'];
		unset($sel_arr['member_id']);
		$this->props = $sel_arr;
	}
	
	public function get_props() {
		return $this->props;
	}
	
	public function get_num_other_users() {
		if(!isset($this->num_other_users))
			$this->set_num_other_users();
			
		return $this->num_other_users;
	}
	
	public function get_other_users_names() {
		# returns an array(user_id => array('name' => user_name, 'rel' => relationship);
		global $conex;
		$sql = 'SELECT mu.user_id, u.full_name FROM members_users mu INNER JOIN users u ON u.user_id = mu.user_id WHERE mu.member_id = '. $this->user_id;

		$sel = my_query($sql, $conex);
		$ret_arr = array();
		while($record = my_fetch_array($sel))
			$ret_arr[$record['user_id']] = $record['full_name'];
		
		return $ret_arr;
	}
	
	private function set_num_other_users() {
		global $conex;
		$sql = 'SELECT count(*) as num_users FROM members_users WHERE member_id = '. $this->user_id;

		$sel = my_query($sql, $conex);
		$this->num_other_users = my_result($sel, 0, 'num_users');
	}
	
	public function get_member_type_props() {
		if(!isset($this->member_type_props))
			$this->set_member_type_props();
			
		return $this->member_type_props;
	}
	
	private function set_member_type_props() {
		$member_type = new member_type($this->props['member_type']);
		$this->member_type_props = $member_type->get_props();
	}

	static function get_members_up_for_renewal($top_date) {
		# returns an array with the user ids that should be renewed during the next 7 days
		global $conex;
//		$today = new my_date('today');
		$ob_top_date = new my_date($top_date);
		if($ob_top_date->odate != '0000-00-00') {
			$sql = 'SELECT user_id FROM members WHERE member_account_status = \'active\' AND next_bill_date <= \''. $ob_top_date->odate .'\'';
			$sel = my_query($sql, $conex);
			$ret_arr = array();
			while($record = my_fetch_array($sel))
				$ret_arr[] = $record['user_id'];
		}
		return $ret_arr;
	}

	public function renew_membership() {
		$type_props = $this->get_member_type_props();
		# generate new bill
		$ob_user = new user($this->user_id);
		$bill_id = $ob_user->generate_member_bill($this->props['next_bill_date'], $this->props['payment_freq'], $this->props['member_type']);

		$bill = new bill($bill_id);
		$bill_props = $bill->get_props();
		$next_bill_date = new my_date($this->props['next_bill_date']);
		$next_cycle_date = $next_bill_date->plus_cycle(1, $this->props['payment_freq']);

		# update member table		
		$upd_arr = array('last_bill_date' => $this->props['next_bill_date'],
						 'last_bill_amount' => $bill_props['total_amount'],
						 'last_bill_id' => $bill_id,
						 'next_bill_date' => $next_cycle_date->odate);
						 
		return update_array_db('members', 'member_id', $this->member_id, $upd_arr, ' AND member_account_status = \'active\'');
	}
}

class member_type {
	public $type_id;
	
	public function __construct($type_id) {
		$this->type_id = $type_id;
	}
	
	public function get_props() {
		$sel_arr = simple_select('member_types', 'type_id', $this->type_id, array('max_num_members', 'type_name', 'description', 'month_quote', '4month_quote', 'year_quote'));
		return $sel_arr;
	}
	
	public function get_member_quote($freq) {
		# freq: mth, 4mt, yrl
		# type: ind, mat, fam, men18
		$col_freq = array('mth' => 'month_quote', '4mt' => '4month_quote', 'yrl' => 'year_quote');
		$arr_quotes = simple_select('member_types', 'type_id', $this->type_id, $col_freq[$freq]);
		return $arr_quotes[$col_freq[$freq]];
	}
}

class bill {
	public $bill_id;
	private $props;	#user_id, concept, total_amount, date_issued, date_paid, bill_status, comments
	
	public function __construct($bill_id) {
		$this->bill_id = $bill_id;
	}
	
	public function get_props() {
		if(!isset($this->props))
			$this->set_props();
		
		return $this->props;
	}

	private function set_props() {
		$this->props = simple_select('bills', 'bill_id', $this->bill_id, array('user_id', 'concept', 'total_amount', 'date_issued', 'date_paid', 'bill_status', 'comments'));
	}
	
	static function generate_member_bill($date, $freq, $type, $user_id){
		# freq: mth, 4mt, yrl
		# type: ind, mat, fam, men18
		$col_freq = array('mth' => 'month_quote', '4mt' => '4month_quote', 'yrl' => 'year_quote');
		$str_freq = array('mth' => 'mensual', '4mt' => 'cuatrimestral', 'yrl' => 'anual');
		
		$arr_quotes = simple_select('member_types', 'type_id', $type, array($col_freq[$freq], 'type_name'));
		
		$arr_ins = array('user_id' => $user_id,
						 'concept' => 'Cuota '. $str_freq[$freq] .' Socio, tipo: '. $arr_quotes['type_name'],
						 'total_amount' => $arr_quotes[$col_freq[$freq]],
						 'date_issued' => $date,
						 'bill_status' => 'issued');
		
		return insert_array_db('bills', $arr_ins, true);
	}
}

class ledger {
	public $entry_id;
	
	public function __construct($entry_id) {
		$this->entry_id = $entry_id;
	}
	
	static function add_entry($user_id, $status, $pm, $amount, $date_time, $comments, $booking_ids, $type, $bill_id) {
		if($date_time == 'now' || $date_time == '')
			$date_time = date('Y-m-d H:i:s');
		
		$arr_ins = array('user_id' => $user_id, 'entry_status' => $status, 'payment_method' => $pm, 'amount' => $amount,
						 'entry_datetime' => $date_time);
		if($comments != '') 	$arr_ins['comments'] = $comments;
		if($booking_ids != '') 	$arr_ins['booking_ids'] = $booking_ids;
		if($type != '')			$arr_ins['entry_type'] = $type;
		if($bill_id != '')		$arr_ins['bill_id'] = $bill_id;
		
		return insert_array_db('ledger', $arr_ins, true);
	}
}

class bonus {
	public $bonus_id;
	
	public function __construct($bonus_id) {
		$this->bonus_id = $bonus_id;
	}

	static function add_bonus($user_id, $type, $date_time, $added_by, $status, $remaining, $pm, $cost = 0) {
		if($date_time == 'now' || $date_time == '')
			$date_time = date('Y-m-d H:i:s');
			
		$arr_ins = array('user_id' => $user_id, 'bonus_type' => $type, 'issued_datetime' => $date_time, 'added_by' => $added_by,
						 'status' => $status, 'remaining_hours' => $remaining, 'payment_method' => $pm, 'bonus_cost' => $cost);
		
		return insert_array_db('bonuses', $arr_ins, true);
	}
	
	public function deactivate() {
		$arr_upd = array('remaining_hours' => 0, 'status' => 'used');
		update_array_db('bonuses', 'bonus_id', $this->bonus_id, $arr_upd);
	}
	
	public function discount_hours($hours) {
		global $conex;
		$sql = 'UPDATE bonuses SET remaining_hours = remaining_hours - '. $hours .' WHERE bonus_id = '. $this->bonus_id;
		$upd = my_query($sql, $conex); 
	}
}

class bonus_type {
	public $type;
	public $description;
	public $hours;
	public $price;
	public $price_members;
	public $members_only;	# boolean
	
	public function __construct($type) {
		$arr_props = simple_select('bonus_types', 'type_code', $type, array('type_description', 'number_of_hours', 'price', 'price_members', 'members_only_ind'));
		
		$this->type = 			$type;
		$this->description = 	$arr_props['type_description'];
		$this->hours = 			$arr_props['number_of_hours'];
		$this->price = 			$arr_props['price'];
		$this->price_members = 	$arr_props['price_members'];
		$this->members_only =	$arr_props['members_only_ind'] == '1';
	}
	
	public function deactivate() {
		$arr_upd = array('active_ind' => '0');
		return update_array_db('bonus_types', 'type_code', $this->type, $arr_upd);
	}
}

class court {
	public $court_id;
	public $name;
	public $description;
	public $minutes;
	
	public function __construct($court_id) {
		$arr_props = simple_select('courts', 'court_id', $court_id, array('name', 'court_type_desc', 'time_slot_min'));
		
		$this->court_id = 		$court_id;
		$this->description = 	$arr_props['court_type_desc'];
		$this->minutes = 		$arr_props['time_slot_min'];
		$this->name = 			$arr_props['name'];
	}
	
	public function update_mins($mins) {
		if($mins != $this->minutes) {
			if($mins >= 5 && $mins <= 1000) {
				$arr_upd = array('time_slot_min' => $mins);
				return update_array_db('courts', 'court_id', $this->court_id, $arr_upd);
			}
			else
				return false;
		}
		else 
			return true;
	}
	
	public function update_desc($description) {
		if($description != $this->description) {
			$arr_upd = array('court_type_desc' => $description);
			return update_array_db('courts', 'court_id', $this->court_id, $arr_upd);
		}
		else
			return true;
	}
}

class time_slot {
	public $slot_id;
	private $date_time;	# object of date_time class	START TIME
	private $otime;		# object of my_time class	START TIME in the day
	private $time_slot_min;
	private $court_id;
	
	public function __construct($slot_id, $start_time = '00:00') {
		$this->slot_id = $slot_id;
		if($start_time == '00:00') {
			$arr_sel = simple_select('time_slots', 'slot_id', $this->slot_id, 'slot_starts');
			$this->otime = new my_time($arr_sel['slot_starts']);
		}
		else
			$this->otime = new my_time($start_time);
	}
	
	public function get_time_slot_min() {
		if(!isset($this->time_slot_min))
			$this->set_time_slot_min();
		
		return $this->time_slot_min;
	}
	
	public function get_date_time() {
		if(!isset($this->date_time))
			$this->set_date_time();
			
		return $this->date_time;	
	}
	
	public function get_time() {
		if(!isset($this->otime))
			$this->set_time();
			
		return $this->otime;	
	}
	
	public function get_court_id() {
		if(!isset($this->court_id))
			$this->set_court_id();
		
		return $this->court_id;
	}
	
	private function set_time_slot_min() {
		/*$arr_sel = simple_select('time_slots', 'slot_id', $this->slot_id, 'time_slot_min');*/
		$this->time_slot_min = '30'; //$arr_sel['time_slot_min'];
	}
	
	private function set_date_time() {
		$arr_sel = simple_select('time_slots', 'slot_id', $this->slot_id, array('date_db', 'slot_starts'));
		$this->date_time = new date_time($arr_sel['date_db'], $arr_sel['slot_starts'] .':00');
	}
	
	private function set_court_id() {
		$arr_sel = simple_select('time_slots', 'slot_id', $this->slot_id, 'court_id');
		$this->court_id = $arr_sel['court_id'];
	}


	
	private function set_time() {
		if(!isset($this->date_time))
			$this->set_date_time();
		
		$this->otime = new my_time($this->date_time->otime);
	}
	
	public function get_following_slots_db($duration) {
		global $conex;
		if(!isset($this->otime))
			$this->set_time();
		if(!isset($this->date_time))
			$this->set_date_time();
		
		$end_time = $this->otime->plus_mins($duration);
		$sql = 'SELECT slot_id FROM time_slots 
WHERE date_db = \''. $this->date_time->odate->odate .'\'
  AND court_id = \''. $this->get_court_id() .'\'
  AND slot_starts > \''. $this->otime->hour .':'. $this->otime->minute .'\'
  AND slot_ends <= \''. $end_time->hour .':'. $end_time->minute .'\'';

		$sel_slots = my_query($sql, $conex);
		$ret_arr = array();
		while($record = my_fetch_array($sel_slots))
			$ret_arr[] = $record['slot_id'];
		
		return $ret_arr;
	}
	
	public function get_following_slots($duration, $arr_times) {
		# arr_times has the times and slot_ids of the same court and date as this slot
		if(!isset($this->otime))
			$this->set_time();
		
		$ret_arr = array();
	//	pa($arr_times);
		foreach($arr_times as $time => $slot) {
			$slot_time = new my_time($time);
			
			if( ($slot_time->total_seconds < ($this->otime->total_seconds + ($duration * 60)))
			 && ($slot_time->total_seconds > $this->otime->total_seconds)) {
				$ret_arr[] = $slot['slot_id'];
			}
		}
		
		return $ret_arr;
	}

	public function get_next_blocked($arr_times) {
		if(!isset($this->otime))
			$this->set_time();
		
		if(ksort($arr_times)) {
			foreach($arr_times as $time => $slot) {
				$slot_time = new my_time($time);
				
				if($slot_time->total_seconds > $this->otime->total_seconds)
					if($slot['book_id'] || $slot['slot_status'] == 'md')
						return $time;
			}
		}
		else
			return false;
	}
	
	public function get_prev_blocked($arr_times) {
		if(!isset($this->otime))
			$this->set_time();
		
		if(krsort($arr_times)) {
			//pa($arr_times);
			
			foreach($arr_times as $time => $slot) {
				$slot_time = new my_time($time);
				
				if($slot_time->total_seconds < $this->otime->total_seconds)
					if($slot['book_id'] || $slot['slot_status'] == 'md')
						return $time;
			}
		}
		else
			return false;
	}
}

class booking {
	public $booking_id;
	private $slot_id;
	
	public function __construct($booking_id) {
		$this->booking_id = $booking_id;	
	}
	
	public function get_slot_id() {
		if(!isset($this->slot_id))
			$this->set_slot_id();
			
		return $this->slot_id;
	}
	
	private function set_slot_id() {
		$arr_sel = simple_select('bookings', 'booking_id', $this->booking_id, 'slot_id');
		$this->slot_id = $arr_sel['slot_id'];
	}
	
}

class event {
	public $event_id;
	private $properties;
	
	public function __construct($event_id) {
		$this->event_id = $event_id;
	}
	
	static function add_event($header, $summary, $content, $author, $date_from, $date_to, $status = 'editing') {
		//$today = new my_date('today');
		$user = new user($_SESSION['login']['user_id']);
		
		# check that the dates are valid.
		$ob_date_from = new my_date($date_from);
		$ob_date_to = new my_date($date_to);

		if($ob_date_from->year > 2037) $ob_date_from = new my_date('2037-12-31');
		if($ob_date_to->year > 2037 || $ob_date_to->odate == '0000-00-00') $ob_date_to = new my_date('2037-12-31');
		
		if($ob_date_from->odate == '0000-00-00' || $ob_date_to->odate == '0000-00-00')		return 'ERR.Formatos de fecha no válidos';
		//if($ob_date_to->get_mktime() < $today->get_mktime())				return 'ERR.end_date_past';
		if($ob_date_from->get_mktime() > $ob_date_to->get_mktime())			return 'ERR.Fecha de inicio posterior a fecha de fin';
		if(!$user->is_admin())												return 'ERR.No tienes permisos para insertar noticias';
		if($header == '')			return 'ERR.Debes escribir un titular';
		
		$arr_ins = array('header' => $header,
						 'summary' => $summary, 
						 'content' => $content, 
						 'author' => $author, 
						 'date_from' => $ob_date_from->odate, 
						 'date_to' => $ob_date_to->odate, 
						 'status' => $status);
		
		return insert_array_db('news', $arr_ins, true);
	}

	public function update_event($header, $summary, $content, $author, $date_from, $date_to, $status = 'editing') {
		$user = new user($_SESSION['login']['user_id']);
		
		# check that the dates are valid.
		$ob_date_from = new my_date($date_from);
		$ob_date_to = new my_date($date_to);

		if($ob_date_from->year > 2037) $ob_date_from = new my_date('2037-12-31');
		if($ob_date_to->year > 2037 || $ob_date_to->odate == '0000-00-00') $ob_date_to = new my_date('2037-12-31');
		
		if($ob_date_from->odate == '0000-00-00' || $ob_date_to->odate == '0000-00-00')		return 'ERR.Formatos de fecha no válidos';
		//if($ob_date_to->get_mktime() < $today->get_mktime())				return 'ERR.end_date_past';
		if($ob_date_from->get_mktime() > $ob_date_to->get_mktime())			return 'ERR.Fecha de inicio posterior a fecha de fin';
		if(!$user->is_admin())												return 'ERR.No tienes permisos para insertar noticias';
		if($header == '')			return 'ERR.Debes escribir un titular';
		
		$arr_upd = array('header' => $header,
						 'summary' => $summary, 
						 'content' => $content, 
						 'author' => $author, 
						 'date_from' => $ob_date_from->odate, 
						 'date_to' => $ob_date_to->odate, 
						 'status' => $status);
		
		return update_array_db('news', 'new_id', $this->event_id, $arr_upd);
	}

	public function get_properties() {
		if(!count($this->properties))
			$this->set_properties();

		return $this->properties;
	}

	private function set_properties() {
		$arr_props = simple_select('news', 'new_id', $this->event_id, array('header', 'summary', 'date_from', 'date_to', 'content', 'author', 'status', 'photo_id'));
		$this->properties = $arr_props;
	}

	public function print_event() {
		if(!count($this->properties))
			$this->set_properties();
		
		$date_from = new my_date($this->properties['date_from']);
		//$author = new user($this->properties['author']);
		$author_str = $this->properties['author'] ? '&nbsp;&nbsp;&nbsp;Por: '. $this->properties['author'] : '';
		if($this->properties['photo_id'])
			$photo = new photo($this->properties['photo_id']);

		echo '<div class="event_header">'. $this->properties['header'] .'</div>';
		echo $this->properties['summary'] ? '<div class="event_summary">'. $this->properties['summary'] .'</div>' : '';
		if(isset($photo))
			$photo->print_photo('med');
		echo '<div class="event_content" style="font-weight:bold;" align="right">'. $date_from->format_date('long') . $author_str .'</div>';
		echo '<div class="event_content">'. $this->properties['content'] .'</div>';
        
	}
	
	public function add_photo($photo_id) {
		$arr_upd = array('photo_id' => $photo_id);
		return update_array_db('news', 'new_id', $this->event_id, $arr_upd);
	}
	
	public function publish_event() {
		$arr_upd = array('status' => 'published');
		return update_array_db('news', 'new_id', $this->event_id, $arr_upd);
	}
	
	public function remove_event() {
		# set expiration date = yesterday.
		$today = new my_date('today');
		$yesterday = $today->plus_days(-1);
		
//		$arr_upd = array('status' => 'expired', 'date_to' => $yesterday->odate());
		$arr_upd = array('date_to' => $yesterday->odate);
		return update_array_db('news', 'new_id', $this->event_id, $arr_upd);
	}

	static function get_current_events($limit = 10) {
		# returns id, header, summary, date_from, and photo_id
		global $conex;
		$sql = 'SELECT new_id, header, summary, date_from, photo_id FROM news 
		WHERE status = \'published\' AND sysdate() BETWEEN date_from AND date_to ORDER BY date_from DESC LIMIT '. $limit;
		
		$sel = my_query($sql, $conex);
		$ret_arr = array();
		while($rec = my_fetch_array($sel))
			$ret_arr[$rec['new_id']] = $rec;
		
		return $ret_arr;
	}
}

class image {
	public $image_id;
	private $properties;
	
	public function __construct($image_id) {
		$this->image_id = $image_id;
	}

	public function get_properties() {
		if(!count($this->properties))
			$this->set_properties();

		return $this->properties;
	}

	private function set_properties() {
		$arr_props = simple_select('images', 'img_id', $this->image_id, array('img_file', 'img_w', 'img_h', 'img_type'));
		$this->properties = $arr_props;
	}

	public function get_file_name() {
		if(!count($this->properties))
			$this->set_properties();
		
		return $this->properties['img_file'];
	}

	static function create_img($file_name, $w, $h, $type) {
		$arr_ins = array('img_file' => $file_name, 'img_w' => $w, 'img_h' => $h, 'img_type' => $type);
		return insert_array_db('images', $arr_ins, true);
	}

	public function print_img($title = '') {
		global $conf_photos_path;
		if(!count($this->properties))
			$this->set_properties();

		echo '<img class="thin_border_picture" border="0" src="'. $conf_photos_path . $this->properties['img_file'] .'" width="'. $this->properties['img_w'] .'" height="'. $this->properties['img_h'] .'" title="'. $title .'" />';
	}
}


class photo {
	public $photo_id;
	private $properties;
	
	public function __construct($photo_id) {
		$this->photo_id = $photo_id;
	}

	static function create_photo($large_img_id, $med_img_id, $small_img_id, $thumb_img_id, $author, $title, $description, $date_taken) {
		$arr_ins = array('large_img_id' => $large_img_id,
						 'med_img_id'   => $med_img_id,
						 'small_img_id' => $small_img_id,
						 'thumb_img_id' => $thumb_img_id,
						 'author_name'	=> $author,
						 'title'		=> $title,
						 'description'  => $description,
						 'date_taken'	=> $date_taken,
						 'date_uploaded'=> date('Y-m-d'));
						 	
		return insert_array_db('photos', $arr_ins, true);
	}
	
	public function get_properties() {
		if(!count($this->properties))
			$this->set_properties();

		return $this->properties;
	}

	private function set_properties() {
		$arr_props = simple_select('photos', 'photo_id', $this->photo_id, array('large_img_id', 'med_img_id', 'small_img_id', 'thumb_img_id',
																				'author_name', 'title', 'description', 'date_taken'));
		$this->properties = $arr_props;
	}

	public function print_photo($format, $details = true) {
		if(!count($this->properties))		
			$this->set_properties();
	
		switch($format) {
			case 'large': $img = new image($this->properties['large_img_id']); break;
			case 'thumb': $img = new image($this->properties['thumb_img_id']); break;
			case 'small': $img = new image($this->properties['small_img_id']); break;
			case 'med': default: $img = new image($this->properties['med_img_id']); break;
		}
		
		echo '<table border="0" align="center" cellpadding="3" cellspacing="0"><tr><td align="center" colspan="2">';
		$img->print_img($this->properties['title']);
		echo '</td></tr><tr>';
		if($details)
			$this->print_photo_description('true');
		echo '</tr></table>';
	}
	
	private function print_photo_description($save = false) {
		global $conf_images_path, $conf_main_url, $conf_photos_path;
		if(!count($this->properties))
			$this->set_properties();

		$img_large = new image($this->properties['large_img_id']);
		$colspan = $save ? '' : ' colspan="2"';
		echo '<td class="photo_footer"'. $colspan .' align="center">'. $this->properties['title'] .'. '. $this->properties['description'] .'</td>';
		if($save)
			echo '<td width="18" align="right"><a href="'. $conf_main_url . $conf_photos_path . $img_large->get_file_name() .'" target="_blank"><img src="'. $conf_images_path .'zoom.png" title="Ver Tamaño Completo" /></a></td>';
	}
}

?>