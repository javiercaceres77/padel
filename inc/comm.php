<?php

//--------------------------------- SPORTSCLUB FUNCTIONS ---------------------------------

function refresh_users_modules($overwrite = false) {
	global $conex, $conf_default_lang;
	if($overwrite || !isset($_SESSION['login']['modules'])) {
		$_SESSION['login']['modules'] = array();
		$lang = $_SESSION['misc']['lang'] ? $_SESSION['misc']['lang'] : $conf_default_lang;

		$sql = 'SELECT m.mod_id, m.name_'. $lang .', m.desc_'. $lang .', m.icon, um.read, um.write
			FROM modules m INNER JOIN users_modules um ON um.mod_id = m.mod_id
			WHERE um.user_id = '. $_SESSION['login']['user_id']	.' AND um.read = 1 AND m.active = 1 ORDER BY m.mod_order';

		$select_modules = my_query($sql, $conex);
	
		while($record = my_fetch_array($select_modules)) {
			$_SESSION['login']['modules'][$record['mod_id']] = array('name' => $record['name_'. $lang]
																	,'desc' => $record['desc_'. $lang]
																	,'icon' => $record['icon']
																	,'read' => $record['read']
																	,'write' => $record['write']);
		}
	}
}

function add_alert($mod, $type, $duration, $text, $alert_id = 0) {
	if($alert_id)
		$arr_alert = simple_select('default_alerts', 'alert_code', $alert_id, 'alert_text', ' AND language = \''. $_SESSION['misc']['lang'] .'\'');
	else
		$arr_alert = array('alert_text' => $text);

	$_SESSION['alerts'][$mod][] = array('type' => $type, 'duration' => $duration, 'text' => $arr_alert['alert_text']);
}

function print_alerts($mod) {
	if($_SESSION['alerts'][$mod]) {
		foreach($_SESSION['alerts'][$mod] as $key => $alert) {
			echo '<div id="alert_'. $key .'" class="alert_'. $alert['type'] .'">
					<table width="100%" border="0" cellpadding="1" cellspacing="1"><tr><td align="left" class="default_text">'. $alert['text'] .'</td>
					<td align="right"><input type="button" value="OK" class="button_small" onclick="JavaScript:ok_alert(\''. $key .'\');" />&nbsp;&nbsp;</td></tr></table>
					</div>';
			
			$_SESSION['alerts'][$mod][$key]['duration']--;
			if($_SESSION['alerts'][$mod][$key]['duration'] <= 0)
				unset($_SESSION['alerts'][$mod][$key]);
		}
	}
}

function ok_alert($mod, $alert_id) {
	unset($_SESSION['alerts'][$mod][$alert_id]);
}

function remove_pre_book($slot_id, $user_id) {
	global $conex;
	$sql = 'DELETE FROM bookings WHERE slot_id = '. $slot_id .' AND user_id = '. $user_id .' AND status = \'prebook\'';
	$del_pre_book = my_query($sql, $conex);
	return $del_pre_book;
}

function remove_expired_pre_books($now_timestamp) {
	foreach($_SESSION['pre_books'] as $slot_id => $pre_book) {
		$pre_expire = new date_time($pre_book['expire_date'], $pre_book['expire_time']);
		if($pre_expire->timestamp < $now_timestamp) {
			$text = 'La "pre-reserva" en '. $pre_book['court_name'] .' a las '. $pre_book['book_starts'] .' ha caducado';
			add_alert('book', 'info', 1, $text);
			unset($_SESSION['pre_books'][$slot_id]);
		}
	}
}

//--------------------------------- DATABASE HANDLING ---------------------------------

function dump_table($table, $code, $desc, $conditions = '') {
	# dumps an entire table into an array, usefull for picklists
	global $conex;

	$select = my_query('SELECT '. $code .', '. $desc .' FROM '. $table . $conditions, $conex);
	$ret_arr = array();
	while($record = my_fetch_array($select)) {
		$ret_arr[$record[$code]] = $record[$desc];
	}
	return $ret_arr;
}

function insert_array_db($table, $arr_columns, $return_id = false) {
	global $conex;
	$columns = '('. implode_keys(', ', $arr_columns) .')';
	$values = '(\''. implode('\', \'', $arr_columns) .'\')';
	$sql = 'INSERT INTO '. $table . $columns .' VALUES '. $values;

	$insert = my_query($sql, $conex); 
	if($return_id && $insert)
		return get_last_insert_id($conex);
	elseif($insert)
		return true;
	else
		return false;
}

function update_array_db($table, $keys, $values, $arr_columns, $extra_condition = '') {
	global $conex;
	# build conditions
	if(!is_array($keys))	$keys = array($keys);
	if(!is_array($values))	$values = array($values);
	$conditions = '';
	for($i = 0; $i < count($keys); $i++) {
		if($i > 0) $conditions.= ' AND ';
		$conditions.= $keys[$i] .' = \''. $values[$i] .'\'';
	}
	
	$conditions.= $extra_condition;

	# build colums to be updated
	$first = true; $columns_str = '';
	foreach($arr_columns as $column => $value) {
		if($first)	$first = false;
		else		$columns_str.= ', ';
		$columns_str.= $column .' = \''. $value .'\'';
	}
	
	$sql = 'UPDATE '. $table .' SET '. $columns_str .' WHERE '. $conditions;

	return my_query($sql, $conex);
}

function delete_db($table, $keys, $values) {
	global $conex;
	
	if(!is_array($keys))	$keys = array($keys);
	if(!is_array($values))	$values = array($values);
	$conditions = '';
	for($i = 0; $i < count($keys); $i++) {
		if($i > 0) $conditions.= ' AND ';
		$conditions.= $keys[$i] .' = \''. $values[$i] .'\'';
	}
	
	$sql = 'DELETE FROM '. $table .' WHERE '. $conditions;
	
	return my_query($sql, $conex);
}

function simple_select($table, $field, $value, $field_return, $extra_condition = '') {
	# returns an array with the results of a row in the database like: array(field_name => field_value, field2_name => field2_value ...
//use to get ONE SINGLE ROW with one or many fields from a table with one condition,
//in case there are more conditions, use $extra_conditions like ' AND extra_field = \'ex_field_value\''
//$field_return can be a string or an array of strings.

	global $conex;
	
	if(!is_array($field_return))
		$field_return = array($field_return);
	
	$sql = 'SELECT '. implode(',', $field_return).' FROM '. $table .' WHERE '. $field .'=\''. $value .'\'' . $extra_condition;
//	print($sql);
	$my_query = my_query($sql, $conex);
	$arr_results = array();
	if($my_query) {
		foreach($field_return as $field_name) {
			$arr_results[$field_name] = @my_result($my_query, 0, $field_name);
		}
		my_free_result($my_query);
		return $arr_results;
	}
	else
		return false;
}

function get_config_value($field) {
	$arr_ret = simple_select('configuration', 'config_code', $field, 'config_value');
	return $arr_ret['config_value'];
}

//--------------------------------- PRINTING STUFF ---------------------------------

function print_languages_flags() {
	global $conf_images_path, $conf_main_page;
	$languages = dump_table('languages', 'tag', 'name', ' WHERE active = \'1\'');

	$get_str = '';
	$first = true;
	foreach($_GET as $key => $value) {
		if($key != 'lang') {
			if($first)
				$first = false;
			else
				$get_str.= '&';
		
			$get_str.= $key .'='. $value;
		}
	}
		
	foreach($languages as $tag => $lang) {
		$sep = $get_str ? '&' : '';
		$link =  basename($_SERVER['PHP_SELF']) .'?'. $get_str . $sep .'lang='. $tag;
			
		echo '<a href="'. $link .'"><img align="absmiddle" src="'. $conf_images_path .'flags/flag_'. $tag .'.gif" title="'. $lang .'" alt="'. $lang .'" width="18" height="12" border="0" /></a> ';
	}
}

function print_combo_array($parameters) {
	# prints a combo selector with the data from an array
	#
	# $parameters:	array		-> array variable (required)
	#				name		-> name of the combo (required)
	#				id			-> if undefined gets same value as name
	#				selected	-> code of the field selected can be an array if multiple selection
	#				class		-> class for the style of the combo
	#				on_change	-> call to a JS function to be called by 'onChange' event.>
	#				substr		-> number that indicates the max number of characters to display
	#				empty		-> inserts an empty option at the beggining
	#				detail		-> prints de code also with the options  01 : Option 1
	#				disabled	-> if true the combo is disabled
	#				multiple	-> shows a list of multiple values. (the value of this parameter is the size of the list)
	
	if(!is_array($parameters['selected'])) $parameters['selected'] = array($parameters['selected']);

	if($parameters['on_change'])		$str_on_change = ' onchange="'. $parameters['on_change'] .'" ';
	else								$str_on_change = '';
		
	if($parameters['class'])			$str_class = ' class="'. $parameters['class'] .'" ';
	else								$str_class = '';
	
	$str_disabled = $parameters['disabled']? ' disabled="disabled" ': '';
	
	if($parameters['multiple'])			$str_mult = ' size="'. $parameters['multiple'] .'" multiple="multiple"';
	else								$str_mult = '';
	
	if(!$parameters['id'])				$parameters['id'] = $parameters['name'];
	
	print('<select name="'. $parameters['name'] .'"'. $str_on_change . $str_class . $str_disabled . $str_mult .' id="'. $parameters['id'] .'">');
	if($parameters['empty'])
		print('<option value=""></option>');
	foreach($parameters['array'] as $key => $value) {
//	while($result = mysql_fetch_array($my_select, MYSQL_BOTH)) {
		if(in_array($key, $parameters['selected'])) //$result[$my_code_field])
			$str_selected = ' SELECTED';
		else
			$str_selected = '';
		
		if($paremeters['detail'])
			$value = $key .' : '. $value;
		
		if($parameters['substr'] && (strlen($value) > $parameters['substr']))
			$str_option = substr($value,0,$parameters['substr']) .'...';
		else
			$str_option = $value;
		
		print('<option value="'. $key .'"'. $str_selected .'>'. $str_option .'</option>');
	}
	print('</select>');
}

function print_combo_db ($parameters) {
	# prints a combo selector with the data from $table
	# 
	# $parameters:	table 		-> table (required)
	#				code_field	-> code field (required)
	#				desc_field	-> description field, if not translated: get that; else make inner join with translation table (required if not trans)
	#				name		-> name of the combo
	#				selected	-> code of the field selected
	#				on_change	-> call to a JS function to be called by 'onChange' event
	#				class		-> class for the style of the combo
	#				extra_condition -> condition like ' extra_field = \'ex_field_value\''
	#				substr		-> (#) number that indicates the max number of characters to display
	#				empty		-> (1/0) inserts an empty option at the beggining
	#				detail		-> (1/0) prints de code also with the options  01 : Option 1
	#				order		-> field name and way to order: ' Name ASC';
	#				disabled	-> if true shows the combo disabled.
	#				tabindex	-> tab index inside the form
	#				no_header	-> (1/0) prints only the options
	
	global $conex;
	
	if($parameters['table'] && $parameters['code_field']) {
		# prepare sql
		$my_code_field = $parameters['code_field'];
		
		if($parameters['desc_field']) {
			$my_desc_field = $parameters['desc_field'];
		}
		else
			return;
		
		if($parameters['extra_condition']) {
			$my_condition = ' WHERE '. $parameters['extra_condition'];
		}
		
		if($parameters['order']) {
			$my_order = ' ORDER BY '. $parameters['order'];
		}
		
		$sql = 'SELECT '. $my_code_field .', '. $my_desc_field .' FROM '. $parameters['table'] . $my_condition . $my_order;

		$my_select = @my_query($sql, $conex);
		
		if($my_select) {
			# draw the combo

			if(!isset($parameters['no_header'])) $parameters['no_header'] = 0;
			$str_on_change = $parameters['on_change']? ' onchange="'. $parameters['on_change'] .'" ': '';
			$str_class = $parameters['class']? ' class="'. $parameters['class'] .'" ': '';
			$str_disabled = $parameters['disabled']? ' disabled="disabled" ': '';
			$str_tabindex = isset($parameters['tabindex']) ? ' tabindex="'. $parameters['tabindex'] .'" ' : '';
			
			if($parameters['no_header'] == 0) {		
					print('<select name="'. $parameters['name'] .'"'. $str_on_change . $str_class . $str_disabled . $str_tabindex .'>');
				if($parameters['empty'])
					print('<option value=""></option>');
			}
			while($result = my_fetch_array($my_select, MYSQL_BOTH)) {
				if($parameters['selected'] == $result[$my_code_field])
					$str_selected = ' SELECTED';
				else
					$str_selected = '';
				
				if($parameters['detail'])
					$result[$my_desc_field] = $result[$my_code_field] .' : '. $result[$my_desc_field];
				
				if($parameters['substr'] && (strlen($result[$my_desc_field]) > $parameters['substr']))
					$str_option = substr($result[$my_desc_field],0,$parameters['substr']) .'...';
				else
					$str_option = $result[$my_desc_field];
				
				print('<option value="'. $result[$my_code_field] .'"'. $str_selected .'>'. htmlentities($str_option) .'</option>');
			}
			if($parameters['no_header'] == 0)  print('</select>');
		}
	}
}

function print_money($amount) {
	return number_format($amount, 2, '.', ' ') .' €';
}

function add_zeroes($value) {
	if(substr($value, 0, 1) != '0' && $value < 10)
		return '0'. $value;
	else
		return $value;
}

//-----------------------------------  OTHER FUNCTIONS  ---------------------------------
function write_log($type, $message = '', $addfile = '') {
/*	global $conf_logs_path;
	//$logs_path = str_repeat('../', substr_count(getcwd(), '\\', strpos(getcwd(), 'rocaya'))) . $conf_logs_path;
	// Count the number of \ in the current working directory (cwd) after 'rocaya' and sets as many ../ as \ there are.
	
	$logs_path = $conf_logs_path;
	
	$log = "\r\n". date('Y-m-d H:i:s') ." - " . sprintf("%15s", $_SERVER['REMOTE_ADDR']) . ' - ' . sprintf("%20s", $_SESSION['login']['email'] .'('. $_SESSION['login']['user_id'] .')') .' - ';
	if($addfile != '') $addfile='_'.$addfile;
	$filetoopen = $logs_path . $type .'_'. date('Ym') . $addfile .'.log';

	if($file=fopen($filetoopen,"ab")) {
		$log.= $message;
		fwrite($file,$log);
		fclose($file);
	}*/
}

function write_log_db($type, $subtype, $message) {
	$arr_ins = array('date_time' => date('Y-m-d H:i:s'),
					 'ip' => $_SERVER['REMOTE_ADDR'],
					 'user_id' => $_SESSION['login']['user_id'],
					 'user_name' => $_SESSION['login']['email'],
					 'message' => addslashes($message),
					 'log_type' => $type,
					 'log_subtype' => $subtype);
					 
	insert_array_db('log_table', $arr_ins);
}

function pa($array, $name = '') {
	print($name); print('<pre>'); print_r($array); print('</pre>');
}

function print_array($array, $name = '') {
	print($name); print('<pre>'); print_r($array); print('</pre>');
}

function implode_keys($glue, $array) {
	return implode($glue, array_keys($array));
}

function explode_keys($delimiter, $string) {
	$temp_arr = explode($delimiter, $string);
	$ret_arr = array();
	foreach($temp_arr as $val)
		$ret_arr[$val] = '1';
	return $ret_arr;
}

function jump_to($destination) {
	?><script language="javascript">
	document.location = '<?= $destination; ?>';
	</script>
	<?php
}

function shorten_str($str, $value) {
	if(strlen($str) > $value)
		return substr($str, 0, $value). '...';
	else
		return $str;
}

//---------------------------------- SECURITY FUNCITONS  -----------------------
function encode($input) {
	$enc_input = '';
	$i_len = strlen($input);
	
	for($i=1; $i <= $i_len; $i++)
		$enc_input.= chr(ord($input[$i_len - $i]) + 3);
	return base64_encode($enc_input);
}

function decode($input) {
	$dec_input = '';
	$aux = base64_decode($input);
	$i_len = strlen($aux);
	
	for($i=1; $i <= $i_len; $i++)
		$dec_input.= chr(ord($aux[$i_len - $i]) - 3);
		
	return $dec_input;
}

function sanitize_input() {
	if(!get_magic_quotes_gpc()) {
		$_POST = addslashes_array($_POST);
		$_GET = addslashes_array($_GET);
		$_COOKIE = addslashes_array($_COOKIE);
		$_REQUEST = addslashes_array($_REQUEST);
	}
}

function addslashes_array($arr_in) {
	$arr_ret = array();
	foreach($arr_in as $key => $val)
		$arr_ret[addslashes($key)] = addslashes($val);

	return $arr_ret;
}

function digest($value) {	# don't change this function once system is working.
	return md5(encode($value));	
}

function get_random_pwd($length = 4) {
	return substr(encode(md5(rand())), 0, $length);
}

function digito_control($ent_ofi, $num_cuenta) {
	$arr_pesos = array(1,2,4,8,5,10,9,7,3,6);
	$dc1 = 0;	// sale del código de entidad y oficina
	$dc2 = 0;	// sale del número de cuenta
	$i = 8;
	
	while($i > 0) {
		$digito = $ent_ofi[$i - 1];
		$dc1+= $arr_pesos[$i + 1] * $digito;
		$i--;
	}
	
	$resto = $dc1 % 11;
	$dc1 = 11 - $resto;
	if($dc1 == 10) $dc1 = 1;
	if($dc1 == 11) $dc1 = 0;
	
	$i = 10;
	
	while($i > 0) {
		$digito = $num_cuenta[$i - 1];
		$dc2+= $arr_pesos[$i - 1] * $digito;
		$i--;
	}
	
	$resto = $dc2 % 11;
	$dc2 = 11 - $resto;
	if($dc2 == 10) $dc2 = 1;
	if($dc2 == 11) $dc2 = 0;
	
	return $dc1 . $dc2;
}

// --------------- IMAGES HANDLER FUNCTIONS -----------------------
function get_new_size($w, $h, $max_w, $max_h) {
	if($w > $max_w) {
		$new_w = $max_w;
		$new_h = $new_w*($h/$w);
		# if new height is still larger than allowed, reduce furhter
		if($new_h > $max_h) {
			$new_h = $max_h;
			$new_w = $new_h*($w/$h);
		}
	}
	else {
		$new_w = $w;
		if($h > $max_h) {
			$new_h = $max_h;
			$new_w = $new_h*($w/$h); //$new_w*($max_h/$h)
		}
		else
			$new_h = $h;
	}

	return array('w' => $new_w, 'h' => $new_h);
}

function getExtension($str) {
	$i = strrpos($str,".");
	if (!$i) { return ""; } 
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return strtolower($ext);
}

?>