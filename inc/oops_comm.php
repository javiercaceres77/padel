<?php

class date_dim {
	public $date_id;
	public $date_db;
	public $odate;

	public function __construct($date_str) {
		# date_str can be yyyy-mm-dd or just a ### that indicates a date_id on the date dimension
		if(strlen($date_str) == '10') {
			$this->odate = new my_date($date_str);
			$this->date_db = $date_str;
			$this->date_id = $this->get_id_from_db($this->date_db);
		}
		else {
			$this->date_id = $date_str;
			$this->date_db = $this->get_db_from_id($this->date_id);
			$this->odate = new my_date($this->date_db);
		}
	}
	
	private function get_id_from_db($date_db) {
		$arr_ret = simple_select('date_dim', 'date_db', $date_db, 'date_id');
		return $arr_ret['date_id'];
	}
	
	private function get_db_from_id($date_id) {
		$arr_ret = simple_select('date_dim', 'date_id', $date_id, 'date_db');
		return $arr_ret['date_db'];
	}
	
	public function unset_holiday() {
		$arr_upd = array('holiday_ind' => '0');
		if(update_array_db('date_dim', 'date_id', $this->date_id, $arr_upd)) {
			$arr_upd = array('holiday_desc' => '');
			return update_array_db('date_translations', 'date_id', $this->date_id, $arr_upd);
		}
	}
	
	public function unset_special() {
		$arr_upd = array('special_opening_time_ind' => '0');
		if(update_array_db('date_dim', 'date_id', $this->date_id, $arr_upd)) {
			return delete_db('opening_times_special', 'date_id', $this->date_id);
		}
	}
	
	public function set_holiday($name = '') {
		$arr_upd = array('holiday_ind' => '1');
		if(update_array_db('date_dim', 'date_id', $this->date_id, $arr_upd) && $name != '') {
			$arr_upd = array('holiday_desc' => $name);
			return update_array_db('date_translations', 'date_id', $this->date_id, $arr_upd);
		}
	}
	
	public function set_special($name, $ot1, $ct1, $ot2, $ct2) {
		$arr_upd = array('special_opening_time_ind' => '1');
		if(update_array_db('date_dim', 'date_id', $this->date_id, $arr_upd)) {
			$arr_ins = array('date_id' => $this->date_id, 'date_db' => $this->date_db, 'open_from_1' => $ot1, 'open_to_1' => $ct1, 'open_from_2' => $ot2, 'open_to_2' => $ct2, 'description' => $name);
			return insert_array_db('opening_times_special', $arr_ins, false);
		}
	}
}

class my_date {
    public $odate;
    public $year;
    public $month;
    public $day;
	private $weekday; # 1 - Mon to 7 - Sun
	private $mktime;
    private $language;
    private $allowed_separators = array('/', '-', ' ', '\\', '.');
    
    public function __construct($vdate, $lan = '') {
		global $conf_default_lang;
		
		if($lan == '') {
			if($_SESSION['misc']['lang'])
				$this->language = $_SESSION['misc']['lang'];
			else
				$this->language = $conf_default_lang;
		}
		else
			$this->language = $lan;
			
        # Trim spaces on the input
        $vdate = trim($vdate);
		if($vdate == 'today' || $vdate == 'now')
			$vdate = date('Y-m-d');
        # Check the length of the input date:
        switch(strlen($vdate)) {
            case 1: case 2:
            # Only the dd part is given, current month and year will be assumed
                if(is_numeric($vdate) && (checkdate(date('m'), $vdate, date('Y')))) {
                    $this->year = date('Y');
                    $this->month = date('m');
                    $this->day = add_zeroes($vdate);
                }
                else {
                    $this->set_error_date($vdate, 'no_valid_numeric');
                    return;
                }
            break;
            
            case 3: case 4: case 5:
            # Day and month are provided. dd-mm will be assumed, if it can't be parsed will try with mm-dd
                # find separator
                foreach($this->allowed_separators as $sep) {
                    if(strpos($vdate, $sep)) {
                        $separator = $sep;
                        break;
                    }
                }

                if($separator != '') {    # if separator has been found
                    $part1 = substr($vdate, 0, strpos($vdate, $separator));
                    $part2 = substr($vdate, strpos($vdate, $separator) + 1);
                }
                elseif(strlen($vdate) == 4) {    # if there is no separator and length = 4, asume ddmm or mmdd
                    $part1 = substr($vdate, 0, 2);
                    $part2 = substr($vdate, 2, 2);
                }
                else {
                    $this->set_error_date($vdate, 'no_separator');
                    return;
                }
                
                if(is_numeric($part1) && is_numeric($part2)) {
                    # try first dd-mm
                    if(checkdate($part2, $part1, date('Y'))) {
                        $this->year = date('Y');
                        $this->month = add_zeroes($part2);
                        $this->day = add_zeroes($part1);
                    }
                    elseif(checkdate($part1, $part2, date('Y'))) {
                        $this->year = date('Y');
                        $this->month = add_zeroes($part1);
                        $this->day = add_zeroes($part2);
                    }
                    else {
                        $this->set_error_date($vdate, 'no_valid_date');
                        return;
                    }
                }
                else {
                    $this->set_error_date($vdate, 'not_numeric_part');
                    return;
                }
            break;
            case 6: case 7: case 8: case 9: case 10:
            # year, month, day are provided. yyyy-mm-dd will be assumed, if it can't be parsed, will try with dd-mm-yyyy and dd-mm-yy. Not mm-dd-yyyy
                # find separators
                $pos_sep1 = 0; $pos_sep2 = 0;
                foreach($this->allowed_separators as $sep) {
                    $pos_sep1 = strpos($vdate, $sep);
                    $pos_sep2 = strpos($vdate, $sep, $pos_sep1 + 1);
                    
                    if($pos_sep1 && $pos_sep2) {
                        $separator = $sep;
                        break;
                    }
                }
				
                if($separator != '') {
                    $part1 = substr($vdate, 0, $pos_sep1);
                    $part2 = substr($vdate, $pos_sep1 + 1, $pos_sep2 - $pos_sep1 - 1);
                    $part3 = substr($vdate, $pos_sep2 + 1);
                }
                elseif(strlen($vdate) == 6 || strlen($vdate) == 8) {    # no separators, try yymmdd or yyyymmdd
                    $part1 = substr($vdate, 0, 2);
                    $part2 = substr($vdate, 2, 2);
                    $part3 = substr($vdate, 4);
                }
                else {
                    $this->set_error_date($vdate, 'no_separator');
                    return;
                }
                
				
                if(is_numeric($part1) && is_numeric($part2) && is_numeric($part3)) {
                    $part1_cent = $this->set_century($part1); # auxiliary function that receives yy and returns yyyy considering the century split point on 38
					$part3_cent = $this->set_century($part3);
					
					if(checkdate($part2, $part3, $part1_cent)) {	# try yyyy-mm-dd, yy-mm-dd, yyyymmdd, yymmdd
                        $this->year = $part1_cent;
                        $this->month = add_zeroes($part2);
                        $this->day = add_zeroes($part3);
					}
					elseif(checkdate($part2, $part1, $part3_cent)) {	# try dd-mm-yyyy, dd-mm-yy
						$this->year = $part3_cent;
                        $this->month = add_zeroes($part2);
                        $this->day = add_zeroes($part1);
					}
					else {
                   		$this->set_error_date($vdate, 'wrong_date');
		                return;
					}
                }	//if(is_numeric($part1) && is_numeric($part2) && is_numeric($part3)) {
               else {
                    $this->set_error_date($vdate, 'not_numeric_part');
                    return;
                }
            break;
            default:
                $this->set_error_date();
            break;
        }

        $this->odate = $this->year .'-'. $this->month .'-'. $this->day;
    }

	private function set_weekday() {
		$weekday = date('w', $this->get_mktime());
		if($weekday == 0) $weekday = 7;
		$this->weekday = $weekday;
	}

	private function set_mktime() {
		$this->mktime = mktime(0, 0, 0, $this->month, $this->day, $this->year);
	}

	public function get_weekday() {
		if(!isset($this->weekday))
			$this->set_weekday();
		
		return $this->weekday;
	}

	public function get_weekday_desc($lan = '') {
		if($lan == '')
			$lan = $this->language;
		else
			$lan = $_SESSION['misc']['lang'];
		
		switch($lan) {
			case 'es': $arr_days = array(1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'); break;
			case 'en': default; $arr_days = array(1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'); break;
		}

		return $arr_days[$this->get_weekday()];
	}
	
	public function get_month_desc($lan = '') {
		if($lan == '')
			$lan = $this->language;
		else
			$lan = $_SESSION['misc']['lang'];
		
		switch($lan) {
			case 'es':
				$arr_mths = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
				$mth = $this->month - 1;
				return $arr_mths[$mth];
			break;
			case 'en': default:
				$ts = $this->get_mktime();
				return date('F', $ts);
			break;
		}
	}

	public function get_mktime() {
		if(!isset($this->mktime))
			$this->set_mktime();
		
		return $this->mktime;
	}

	public function first_of_month() {
		return $this->year .'-'. $this->month .'-01';
	}

	public function plus_days($days) {
		$ret_mk = mktime(0, 0, 0, $this->month, $this->day + $days, $this->year);
		return new my_date(date('Y-m-d', $ret_mk));
	}

	public function plus_cycle($cycles, $type) {
		# types: mth - monthly; qtr - quarterly (3 mth); 4mt - 4 monthly; yrl - yearly
		global $conex;
				
		switch($type) {
			case 'mth':
				$ret_mk = mktime(0, 0, 0, $this->month + $cycles, $this->day, $this->year);
				return new my_date(date('Y-m-d', $ret_mk));
			break;
			case '4mt':
				$sql = 'SELECT d1.date_id, d1.date_db 
				FROM date_dim d1 
				INNER JOIN date_dim d2
				ON d1.num_day_of_4month = d2.num_day_of_4month
				AND d1.num_4month = d2.num_4month + '. $cycles .'
				WHERE d2.date_db = \''. $this->odate .'\'';
				
				$sel_dates = my_query($sql, $conex);
				$date_arr = my_fetch_array($sel_dates);
				
				return new my_date($date_arr['date_db']);
			break;
			case 'yrl':
				$ret_mk = mktime(0, 0, 0, $this->month, $this->day, $this->year + $cycles);
				return new my_date(date('Y-m-d', $ret_mk));
			break;		
		}
		
	}

/*	public function date_format($format = '') {
		# alias of format_date
		return $this->format_date($format);
	}
*/
	public function format_date($format = '') {
		# $format: '' -> 15-06-2006; 
			# med -> 15-jul-2006; 
			# long -> 15 de junio de 2006; 
			# year_month -> jul 2011; 
			# month_day -> 24 jul
			# short_day -> hoy, mañana, martes 12, miércoles 13 ...
		# $format: '' -> 15-06-2011; med -> Jul-15-2011; long -> June 15th 2011; very_long -> wednesday, 24th of july 2011; year_month -> jul 2011; month_date -> Jul 24th 
		$method_name = 'format_date_'. $format;
		if(method_exists($this, $method_name))
			return $this->$method_name();
		else 
			return $this->format_date_med();
	}

	private function format_date_short_day() {
		# short_day -> hoy, mañana, martes 12, miércoles 13 ...
		switch($this->language) {
			case 'es': 
			 	if($this->odate == date('Y-m-d'))
					return 'hoy';
				elseif($this->odate == date('Y-m-d', mktime(0,0,0,date('m'), date('d') + 1, date('Y'))))
					return 'mañana';
				else
					return $this->get_weekday_desc('es') .' '. $this->day;
			break;
			case 'en': default:
			 	if($this->odate == date('Y-m-d'))
					return 'today';
				elseif($this->odate == date('Y-m-d', mktime(0,0,0,date('m'), date('d') + 1, date('Y'))))
					return 'tomorrow';
				else
					return $this->get_weekday_desc('en') .' the '. $this->day .'<sup>'. date('S', $this->odate) .'</sup>';
			break;
		}
	}
	
	private function format_date_long() {
		switch($this->language) {
			case 'es':	#2006-07-24 -> 24 de julio de 2006
				$months_es = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
				return $this->remove_zeroes($this->day) .' de '. $months_es[$this->month - 1] .' de '. $this->year;
			break;
			case 'en':	#2006-07-24 -> July 24th 2006
				$my_mk = mktime(0, 0, 0, $this->month, $this->day, $this->year);
				return @date('F jS Y', $my_mk);
			break;
		}
	}

	private function format_date_med() {
		switch($this->language) {
			case 'es':	#15-jul-2006
				$months_es = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
				return $this->day .'-'. $months_es[$this->month - 1] .'-'. $this->year;
			break;
			case 'en':	#Jul-15-2006
				$months_en = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'agu', 'sep', 'oct', 'nov', 'dec');
				return ucfirst($months_en[$this->month - 1]) .'-'. $this->day .'-'. $this->year;
			break;
		}
	}
	
	private function format_date_month_day() {
		switch($this->language) {
			case 'es':	#24 jul
				$months_es = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
				return $this->day .' '. $months_es[$this->month - 1];
			break;
			case 'en':	#Jul 24th
				$my_mk = mktime(0, 0, 0, $this->month, $this->day, $this->year);				
				return @date('F jS', $my_mk);
			break;
		}
	}
	
	private function format_date_year_month() {
		switch($this->language) {
			case 'es':	#jul 2011
				$months_es = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
				return $months_es[$this->month - 1] .' '. $this->year;
			break;
			case 'en':	#July 2011
				$my_mk = mktime(0, 0, 0, $this->month, $this->day, $this->year);				
				return @date('F Y', $my_mk);
			break;
		}
	}

	private function format_date_year_mth() {
		switch($this->language) {
			case 'es':	#jul 2011
				$months_es = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
				return $months_es[$this->month - 1] .' '. $this->year;
			break;
			case 'en':	#jul 2011
				$months_en = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
				return $months_en[$this->month - 1] .' '. $this->year;
			break;
		}
	}
    
    private function set_error_date() {
        $this->odate = '0000-00-00'; $this->year = '0000'; $this->month = '00'; $this->day = '00';
    }
	
	private function set_century($part) {
		if(strlen($part) <= 2) {
			if($part < 38)
				return '20'. $part;
			else
				return '19'. $part;
		}
		else
			return $part;
	}
	
	private function remove_zeroes($value) {
		return $value += 0;
//		return preg_replace('~^[0]*([1-9][0-9]*)$~','$1',$value);
	}
}

class date_time {
	public $datetime;
	public $odate;
	public $otime;
	public $hour;
	public $minute;
	public $second;
	public $timestamp;
	
	
	public function __construct($vdate, $time = '00:00:00') {
		if($vdate == 'now') {
			$vdate = date('Y-m-d'); $time = date('H:i:s');
		}
		
		if(strlen($vdate) == 19) {	# the date includes the time as 2012-01-27 20:00:00
			$time = substr($vdate, 11);
			$vdate = substr($vdate, 0, 10);
		}
		
		$this->odate = new my_date($vdate);

		$arr_time = explode(':', $time);
		$this->hour = $arr_time[0] ? add_zeroes($arr_time[0]) : '00';
		$this->minute = $arr_time[1] ? add_zeroes($arr_time[1]) : '00';
		$this->second = $arr_time[2] ? add_zeroes($arr_time[2]) : '00';
		$this->otime = $this->hour .':'. $this->minute .':'. $this->second;
		$this->timestamp = mktime($this->hour, $this->minute, $this->second, $this->odate->month, $this->odate->day, $this->odate->year);
		
		$this->datetime = $this->odate->odate .' '. $this->otime;
	}
	
	public function plus_mins($mins) {
		$new_timestamp = $this->timestamp + ($mins * 60);
		$ret_date = date('Y-m-d', $new_timestamp);
		$ret_time = date('H:i:s', $new_timestamp);
		return new date_time($ret_date, $ret_time);
	}
	
	public function format_time() {
		return $this->hour .':'. $this->minute;
	}
}

class my_time {
	# time of the day
	public $time;
	public $hour;
	public $minute;
	public $second;
	public $total_hours;
	public $total_minutes;
	public $total_seconds;
	
	public function __construct($time) {
		if(strlen($time) == 8) {	# 00:00:00
			$arr_time = explode(':', $time);
			
			$this->hour = $arr_time[0] ? add_zeroes($arr_time[0]) : '00';
			$this->minute = $arr_time[1] ? add_zeroes($arr_time[1]) : '00';
			$this->second = $arr_time[2] ? add_zeroes($arr_time[2]) : '00';
			$this->time = $this->hour .':'. $this->minute .':'. $this->second;
		}
		elseif(strlen($time) == 5) {	# 00:00
			$arr_time = explode(':', $time);
			
			$this->hour = $arr_time[0] ? add_zeroes($arr_time[0]) : '00';
			$this->minute = $arr_time[1] ? add_zeroes($arr_time[1]) : '00';
			$this->second = '00';
			$this->time = $this->hour .':'. $this->minute .':'. $this->second;
		}
		
		$this->total_seconds = ($this->hour * 3600) + ($this->minute * 60) + $this->second;
		$this->total_minutes = $this->total_seconds / 60;
		$this->total_hours = $this->total_minutes / 60;
		
	}
	
	private function get_time_from_seconds($seconds) {
		$mins = floor($seconds / 60);
		$rest_sec = $seconds - ($mins * 60);
		
		$hours = floor($mins / 60);
		$rest_mins = $mins - ($hours * 60);
		
		$hours = $hours ? add_zeroes($hours) : '00';
		$rest_mins = $rest_mins ? add_zeroes($rest_mins) : '00';
		$rest_sec = $rest_sec ? add_zeroes($rest_sec) : '00';
		
		return $hours .':'. $rest_mins .':'. $rest_sec;
	}
	
	public function plus_mins($mins) {
		$new_total_seconds = $this->total_seconds + ($mins * 60);
		return new my_time($this->get_time_from_seconds($new_total_seconds));
	}
}

class user {

	public $user_id;
	public $is_member;	# could be member_member or member by proxy
	private $user_name;
	private $email;
	private $is_admin;
	private $account_active;


	public function __construct($user_id, $user_name = '') {
		if(is_numeric($user_id)) {
			# construct the user with a user id as usual
			$this->user_id = $user_id;
			$arr_usr = simple_select('users', 'user_id', $this->user_id, array('full_name', 'is_admin', 'is_member', 'email'));
		}
		else {
			# construct the user with email
			$arr_usr = simple_select('users', 'email', $user_id, array('full_name', 'is_admin', 'is_member', 'email', 'user_id'));
			$this->user_id = $arr_usr['user_id'];
		}
		
		if($this->user_id == '')
			$this->user_id = 0;
		
		$this->is_admin = $arr_usr['is_admin'];
		$this->is_member = $arr_usr['is_member'];
		$this->user_name = $arr_usr['full_name'];
		$this->email = $arr_usr['email'];
	}
	
	public function upd_session_login()  {
		$_SESSION['login']['user_id'] = $this->user_id;
		$_SESSION['login']['name'] = $this->user_name;
		$_SESSION['login']['email'] = $this->email;
		$_SESSION['login']['is_member'] = $this->is_member;
	}
		
	public function get_user_name() {
		return $this->user_name;
	}
	
	public function get_email() {
		if(!isset($this->email)) {
			$this->set_email();
		}
		
		return $this->email;
	}
	
	public function get_all_details() {
		$arr_cols = array('full_name', 'phone1', 'phone2', 'email', 'address', 'added_by', 'date_registered', 'no_shows_num', 'available_books_num', 'total_books_num',
						  'blocked_ind', 'deleted_ind', 'admin_comments', 'required_change_pwd', 'is_admin', 'last_login_datetime', 'user_level', 'is_member', 'DOB', 
						  'migration_code', 'control_code');
		return simple_select('users', 'user_id', $this->user_id, $arr_cols);
	}
	
	public function get_available_cash_books () {
		$arr_sel = simple_select('users', 'user_id', $this->user_id, 'available_books_num');
		if($arr_sel['available_books_num'])
			return $arr_sel['available_books_num'];
		else 
			return false;
	}
	
	public function get_payment_methods() {
		global $conex;
		
		$now = new date_time('now');
		$arr_pms = array();
		# cash from users table, count how many are active
		$sql = 'SELECT count(1) as num_books FROM bookings WHERE user_id = '. $this->user_id .' AND status IN (\'confirmed\', \'prebook\')
				AND booking_datetime > \''. $now->datetime .'\' AND payment_method = \'cash\'';
		$sel = my_query($sql, $conex);
		$num_books = my_result($sel, 0, 'num_books') + 0;	// + 0 to convert to int.
		
//		$arr_sel = simple_select('users', 'user_id', $this->user_id, 'available_books_num');
//		if($arr_sel['available_books_num'])
//			$arr_pms['cash'] = $arr_sel['available_books_num'] - $num_books;
			$arr_pms['cash'] = $this->get_available_cash_books() - $num_books;
		
		# make sure is not negative
		if($arr_pms['cash'] < 0) $arr_pms['cash'] = 0;
		
		# ccc from payment_user_details
		$arr_sel = simple_select('payment_user_details', 'user_id', $this->user_id, array('details', 'ccc_name'));
		if($arr_sel['details'])
			$arr_pms['ccc'] = array('ccc' => decode($arr_sel['details']), 'name' => decode($arr_sel['ccc_name']));
		
		# bonus from bonuses
		$arr_pms['bonus'] = $this->get_user_bonuses();
		/*
		$sql = 'SELECT b.bonus_type, b.bonus_id, b.remaining_hours, bt.type_description 
		FROM bonuses b 
		LEFT JOIN bonus_types bt ON bt.type_code = b.bonus_type 
		WHERE b.user_id = '. $this->user_id .' AND b.remaining_hours > 0 AND b.status = \'active\' 
		ORDER BY b.bonus_id';
		$sel = my_query($sql, $conex);
		while($record = my_fetch_array($sel))
			$arr_pms['bonus'][$record['bonus_id']] = array('type' => $record['bonus_type'], 'hours' => $record['remaining_hours'], 'name' => $record['type_description']);
		*/
		
		# credit card
		# when available:
		# $arr_pms['card'] = '1';
		
		return $arr_pms;		
	}
	
	public function get_user_bonuses() {
		global $conex;
		
		$sql = 'SELECT b.bonus_type, b.bonus_id, b.remaining_hours, bt.type_description 
		FROM bonuses b 
		LEFT JOIN bonus_types bt ON bt.type_code = b.bonus_type 
		WHERE b.user_id = '. $this->user_id .' AND b.remaining_hours > 0 AND b.status = \'active\' 
		ORDER BY b.bonus_id';
		$sel = my_query($sql, $conex);
		$arr_ret = array();
		while($record = my_fetch_array($sel))
			$arr_ret[$record['bonus_id']] = array('type' => $record['bonus_type'], 'hours' => $record['remaining_hours'], 'name' => $record['type_description']);
			
		return $arr_ret;
	}
	
	public function get_user_total_bonus_hours() {
		global $conex;
		$sql = 'SELECT sum(remaining_hours) AS num_hours FROM bonuses WHERE user_id = '. $this->user_id .' AND status = \'active\'';
		$sel = my_query($sql, $conex);
		$arr_ret = my_fetch_array($sel);
		
		return $arr_ret['num_hours'];
	}
	
	public function get_next_book_date() {
		global $conex;
		$now = new date_time('now');
		$sql = 'SELECT min(booking_datetime) as booking_datetime FROM bookings WHERE user_id = \''. $this->user_id .'\' AND booking_datetime > \''. $now->datetime .'\' AND status = \'confirmed\'';
	
		$sel = my_query($sql, $conex);
		$sel_arr = my_fetch_array($sel);
		return $sel_arr['booking_datetime'];
	}
	
	public function get_slot_fare($slot_id) {
		global $conex;
		
		$sql = 'SELECT (f.fare / 60 * t.time_slot_min) as fare, f.fare_id, f.fare_name
 FROM fares f INNER JOIN time_slots t
   ON t.date_id = f.date_id AND t.slot_starts >= f.time_starts AND t.slot_starts <= f.time_ends
WHERE slot_id = '. $slot_id .' AND f.is_member = '. $this->is_member;
 		
		$sel = my_query($sql, $conex);
		return my_fetch_array($sel);
	}
	
	private function set_email() {
		$arr_email = simple_select('users', 'user_id', $this->user_id, 'email');
		$this->email = $arr_email['email'];
	}
	
	private function set_is_admin() {
		$arr_usr = simple_select('users', 'user_id', $this->user_id, 'is_admin');
		$this->is_admin = $arr_usr['is_admin'];
	}
	
	private function set_is_account_active() {
		$arr_usr = simple_select('users', 'user_id', $this->user_id, 'control_code');
		$this->account_active = $arr_usr['control_code'] == '';
	}
	
	# membership functions------------------------------------------------------------
	
	public function is_member_member() {
		# check if the user has a member account and is not just member by proxy
		$sel_arr = simple_select('members', 'user_id', $this->user_id, 'member_id', ' AND member_account_status = \'active\'');
		return $sel_arr['member_id'] != '';
	}
	
	private function set_is_member() {
		$arr_usr = simple_select('users', 'user_id', $this->user_id, 'is_member');
		$this->is_member = $arr_usr['is_member'];
	}
	
	public function set_as_member_with_member($member_id) {
		$arr_ins = array('user_id' => $this->user_id, 'member_id' => $member_id, 'added_datetime' => date('Y-m-d H:i:s'));
		if(insert_array_db('members_users', $arr_ins))
			return $this->set_as_member();
		else
			return false;
	}
	
	public function unset_as_member_with_member($member_id) {
		$arr_keys = array('user_id', 'member_id');
		$arr_values = array($this->user_id, $member_id);
		if(delete_db('members_users', $arr_keys, $arr_values))
			return $this->unset_as_member();
		else
			return false;
	}
	
	public function set_as_member() {		# just updates the user table, does not create the member in members table. See create_member_account
		$arr_upd = array('is_member' => '1');
		return update_array_db('users', 'user_id', $this->user_id, $arr_upd);
	}

	public function unset_as_member() {
		$arr_upd = array('is_member' => '0');
		if(update_array_db('users', 'user_id', $this->user_id, $arr_upd))
			return $this->set_is_member();
		else
			return false;
	}

	public function create_member_account($type, $freq) {
		$today = new my_date('today');
		$tomorrow = $today->plus_days(1);

		# check that the user is not already a member
		$this->set_is_member();
		if($this->is_member)
			return false;

		# update user, set as member.
		update_array_db('users', 'user_id', $this->user_id, array('is_member' => '1'));
		
		# create bill
		$bill_id = $this->generate_member_bill($tomorrow->odate, $freq, $type);
		$bill = new bill($bill_id);
		$bill_props = $bill->get_props();
		$next_bill_date = $tomorrow->plus_cycle(1, $freq);
		# create new member for the user
		$arr_ins = array('user_id'				=> $this->user_id,
						 'user_name'			=> $this->get_user_name(),
						 'member_type'			=> $type,
						 'payment_freq'			=> $freq,
						 'registration_date'	=> $today->odate,
						 'next_bill_date'		=> $next_bill_date->odate,
						 'last_bill_date'		=> $bill_props['date_issued'],
						 'last_bill_amount'		=> $bill_props['total_amount'],
						 'last_bill_id'			=> $bill->bill_id,
						 'member_account_status'=> 'active');
						 
		$member_id = insert_array_db('members', $arr_ins, true);
		
		if($bill_id && $member_id)
			return $member_id;
	}

	public function generate_member_bill($date, $freq, $type) {
		$bill_id = bill::generate_member_bill($date, $freq, $type, $this->user_id);
		# add ledger entry
		$bill = new bill($bill_id);
		$bill_props = $bill->get_props();
		ledger::add_entry($this->user_id, 'pending', 'ccc', $bill_props['total_amount'], 'now', '', '', 'membership', $bill_id);
		
		return $bill_id;
	}
	
	
	
	public function is_admin() {
		if(!isset($this->is_admin))
			$this->set_is_admin();
			
		return $this->is_admin;
	}
	
	public function is_account_active() {
		if(!isset($this->account_active))
			$this->set_is_account_active();
		
		return $this->account_active;
	}
	
	public function upd_last_login_date() {
		$arr_cols = array('last_login_datetime' => date('Y-m-d H:i:s'), 'change_pwd_code' => '');
		update_array_db('users', 'user_id', $this->user_id, $arr_cols);
	}
	
	public function increase_num_books($num_books = 1) {
		global $conex;
		$sql = 'UPDATE users SET total_books_num = total_books_num + '. $num_books .' WHERE user_id = \''. $this->user_id .'\'';
		$sel = my_query($sql, $conex);
		return $sel;
	}
	
	public function increase_num_noshows() {
		global $conex;
		$sql = 'UPDATE users SET no_shows_num = no_shows_num + 1 WHERE user_id = \''. $this->user_id .'\'';
		$sel = my_query($sql, $conex);
		return $sel;
	}
	
	public function activate_user($control_code) {
		$arr_dets = $this->get_all_details();
		if($control_code == $arr_dets['control_code']) {
			# remove control_code on user table:
			$upd_arr = array('control_code' => '');
			update_array_db('users', 'user_id', $this->user_id, $upd_arr);
			write_log_db('new_user', 'user_activated', 'User: '. $this->user_id);
			
			# add writing permissions on modules: Books, Home, etc.
			$this->add_default_modules_write();
			
			return true;
		}
		else
			return false;

		# add cash as payment method by default
		//$this->add_cash_payment_method();
	}
	
	public function acitvate_migrated_user ($password) {
		# updated user table record
		$email = $this->get_email();
		$now = new my_date('now');
		$upd_arr = array('pasapalabra' => digest(substr($email,0,2) . $password)
						,'date_registered' => $now->odate
						,'migrated_ind' => 0
						,'migration_code' => ''
						,'control_code' => '');
						
		update_array_db('users', 'user_id', $this->user_id, $upd_arr);
		
		# add writing permissions on modules: Books, Home, etc.
		$this->add_default_modules();
		$this->add_default_modules_write();
		write_log_db('new_user', 'user_migrated', 'User: '. $this->user_id);
		
		return $this->is_account_active();
	}
	
	public function add_default_modules() {
		global $conex;
		$sql = 'INSERT INTO users_modules (user_id, mod_id, `read`, `write`) SELECT '. $this->user_id .', mod_id, \'1\', \'0\' FROM modules WHERE active = \'1\' AND add_on_user_registration = \'1\'';
		$add_mods = my_query($sql, $conex);
		return ($add_mods);
	}

	private function add_default_modules_write() {
		global $conex;
		$arr_modules = dump_table('modules', 'mod_id', 'mod_id', ' WHERE add_on_user_registration = \'1\'');
		
		$sql = 'UPDATE users_modules SET `write` = \'1\' WHERE mod_id IN (\''. implode('\', \'', $arr_modules) .'\') AND user_id = '. $this->user_id;
		$upd_mods = my_query($sql, $conex);
		return($upd_mods);
	}
	
	# ccc functions------------------------------------------------------------
	
	public function has_valid_ccc() {
		$sel_arr = simple_select('payment_user_details', 'user_id', $this->user_id, 'details');
		return $sel_arr['details'] != '';
	}
	
	public function add_ccc_payment($ccc, $ccc_name) {
		$ins_array = array('user_id' => $this->user_id, 'details' => $ccc, 'ccc_name' => $ccc_name);
		return insert_array_db('payment_user_details', $ins_array);
	}
	
	public function upd_ccc_payment($ccc, $ccc_name) {	# data not encoded here, need to be enc previously
		$upd_array = array('details' => $ccc, 'ccc_name' => $ccc_name);
		return update_array_db('payment_user_details', 'user_id', $this->user_id, $upd_array);
	}

	public function rem_ccc_payment() {
		return delete_db('payment_user_details', 'user_id', $this->user_id);
	}
	
	public function get_ccc() {
		$arr_ccc = simple_select('payment_user_details', 'user_id', $this->user_id, 'details');
		if($arr_ccc['details'])
			return decode($arr_ccc['details']);
	}
	
	public function get_ccc_name() {
		$arr_ccc = simple_select('payment_user_details', 'user_id', $this->user_id, 'ccc_name');
		if($arr_ccc['ccc_name'])
			return decode($arr_ccc['ccc_name']);
	}
	
	
	
	static function validate_login($user_name, $user_pass) {
		global $conex;

		if($user_pass == '') return 'INCORRECT';
				
		$sql = 'SELECT user_id, full_name, email, last_login_datetime, ul.level_name, user_level, is_member, blocked_ind, 
			pasapalabra, deleted_ind, control_code, required_change_pwd, migrated_ind
		FROM users u LEFT JOIN users_levels ul ON u.user_level = ul.level_value
		WHERE user_id = \''. $user_name .'\'';
		
		$my_select = my_query($sql, $conex);
		
		# Possible statuses to return from this function:
			# NORMAL: 		usr and pwd are correct and the user is in normal condition
			# INCORRECT: 	usr doesn't exist or pwd doesn't match
			# FIRST: 		usr and pwd are correct and it is the first time the user logs in
			# DELETED:		user has been deleted and can't login. treat as an incorrect.
			# BLOCKED:		user has been blocked. Show block info
			# MIGRATED:		user doesn't have a password. Show screen to create password and insert control code and send control code.
			# NOT_VALIDATED:user hasn't validated the e-mail address. Can login, when it is validaded the user will be able to place bookings.
			# CHG_PWD_REQ:	user must change password before logging in.
		
		$user_arr = my_fetch_array($my_select);
		
		if(my_num_rows($my_select)) {
		$sql_word = digest(substr($user_arr['email'],0,2) . $user_pass);
			
			if($user_arr['migrated_ind'] == '1') {
																return 'MIGRATED';
			}
			elseif($user_arr['pasapalabra'] == $sql_word) {
				if($user_arr['deleted_ind'] == '1')				return 'DELETED';	
				elseif($user_arr['blocked_ind'] == '1')			return 'BLOCKED';	
				elseif($user_arr['required_change_pwd'] == '1')	return 'CHG_PWD_REQ';
				elseif($user_arr['control_code'] != '')			return 'NOT_VALIDATED';
				elseif($user_arr['last_login_datetime'] == '')	return 'FIRST';
				else											return 'NORMAL';
			}
			else												return 'INCORRECT';
		}
		else
																return 'NOT_EXIST';
	}
	
	static function manage_wrong_login($user_name) {
		$_SESSION['login']['num_tries'] ? $_SESSION['login']['num_tries']++ : $_SESSION['login']['num_tries'] = 1;
		
		if($_SESSION['login']['num_tries'] > 6) {
			user::block_user($user_name, 'system', 120);
			write_log_db('login', 'BLOCK_USER', $user_name);
			$_SESSION['login']['num_tries'] = 0;
		}
	}
	
	static function block_user($user_name, $blocker, $period = 0) {
		# Period = 0 means permanently
		# check that $user_name exists
		$user_id_arr = simple_select('users', 'email', $user_name, 'user_id', ' AND deleted_ind = 0');
		
		if($user_id_arr) {
			$arr_upd = array('blocked_ind' => 1);
			update_array_db('users', 'user_id', $user_id_arr['user_id'], $arr_upd);
			
			$now = new date_time(date('Y-m-d'), date('H:i:s'));
			if($period)
				$now_plus_period = $now->plus_mins($period);
			else
				$now_plus_period = new date_time('2038-01-01', '00:00:00');

			$arr_ins = array('user_id' => $user_id_arr['user_id'], 
							 'reason' => wrong_login .' + 6 '. tries, 
							 'block_datetime' => $now->datetime, 
							 'blocked_until_datetime' => $now_plus_period->datetime,
							 'blocked_by' => $blocker,
							 'blocked_IP' => $_SERVER['REMOTE_ADDR']);
							 
			insert_array_db('blocks', $arr_ins);
		}	
	}

	public function unblock_user() {
		$arr_upd = array('blocked_ind' => 0);
		return update_array_db('users', 'user_id', $this->user_id, $arr_upd);
	}
		
	public function get_until_blocked() {
		# returns the number of minutes that are left to unlock the user.
		global $conex;
		$sql = 'SELECT max(blocked_until_datetime) AS blocked_until_datetime FROM blocks WHERE user_id = \''. $this->user_id .'\'';
		$sel = my_query($sql, $conex);
		
		return my_result($sel, 0, 'blocked_until_datetime');
	}
}

class mail_templates {
	public $name;
	
	public function __construct($name) {
		$this->name = $name;
	}
	
	static function send_mail($destination, $template, $arr_vars) {
		$template_details = simple_select('mail_templates', 'template_name', $template, array('from_addr', 'mail_subject', 'mail_message', 'mail_html', 'mail_footer', 'template_id'), ' AND language = \''. $_SESSION['misc']['lang'] .'\'');
		
		$message = self::substitute_variables($template_details['mail_message'], $arr_vars);
		//$send_html = self::substitute_variables($template_details['mail_html'], $arr_vars);
		
		$to = $destination;
		$subject = $template_details['mail_subject'];
				
		$headers = 'To: "'. $destination .'" <'. $destination .'>' . "\r\n";
		$headers .= 'From: Padel Indoor Ponferrada <'. $template_details['from_addr'] .'>' . "\r\n";
			
		if(@mail($to, $subject, $message, $headers))
			self::log_mail_sent($template_details['template_id'], $destination, 'system');
		else
			write_log_db('E-MAIL', 'ERROR-SENT', 'Dest: '. $destination .'; template: '. $template, 'oops_comm.php; class: mail_templates; method: send_mail');
	}
	
	static function send_mail_old($destination, $template, $arr_vars) {
		# valid for pear mail mime
	/*	$template_details = simple_select('mail_templates', 'template_name', $template, array('from_addr', 'mail_subject', 'mail_message', 'mail_html', 'mail_footer', 'template_id'), ' AND language = \''. $_SESSION['misc']['lang'] .'\'');
		
		$send_text = self::substitute_variables($template_details['mail_message'], $arr_vars);
		$send_html = self::substitute_variables($template_details['mail_html'], $arr_vars);
		
		$headers = array('From' => $template_details['from_addr'], 'To' => $destination, 'Subject' => $template_details['mail_subject']);
		
		$mime = new Mail_Mime("\n");
		
		$mime->setTXTBody($send_text);
		$mime->setHTMLBody($send_html);
		
		$body = $mime->get();
		$hdrs = $mime->headers($headers);
		
		//echo $body;
		
		$mail =& Mail::factory('mail');
		if($mail->send($destination, $hdrs, $body))
			self::log_mail_sent($template_details['template_id'], $destination, 'system');
		else
			write_log_db('E-MAIL', 'ERROR-SENT', 'Dest: '. $destination .'; template: '. $template, 'oops_comm.php; class: mail_templates; method: send_mail');*/
	}
	
	static function substitute_variables($text, $arr_vars) {
		foreach($arr_vars as $var => $value) {
			$text = str_replace('##'. $var .'##', $value, $text);
		}
		return $text;
	}
	
	static function log_mail_sent($template_id, $destination, $sent_by) {
		global $conf_db_datetime_format;
		$arr_ins = array('template_id' => $template_id, 'destination' => $destination, 'sent_by' => $sent_by, 'sent_datetime' => date($conf_db_datetime_format));
		return insert_array_db('mail_sent', $arr_ins);
	}
}


?>