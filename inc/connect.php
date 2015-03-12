<?php

include_once('comm.php');

$user = decode($conf_db_user);
$pass = decode($conf_db_pass);

switch($conf_db_type) {
	case 'mysql': 
		$conex= mysql_connect($conf_db_host, $user, $pass);
		if (!$conex)
			die(msg_unable_db_connect .' '. mysql_error($conex));
		else {
			if(!mysql_select_db($conf_db_name,$conex)) {
				die(msg_unable_db_connect .' '. mysql_error($conex));
			}
		}
		
		# set variables for different databases
		$db_getdate = 'CURDATE()';
	break;
	case 'mssql':
		$conex= mssql_connect($conf_db_host, $user, $pass);
		if (!$conex)
			die(msg_unable_db_connect .' '. mssql_get_last_message($conex));
		else
			mssql_select_db($conf_db_name, $conex);
		// set variables for different databases
		$db_getdate = 'getdate()';
	break;
}

function my_query($str_query, $conex) {
	global $conf_db_type, $conf_is_prod;
	
	$queries2log = array('UPD', 'DEL', 'INS', 'DRO', 'ALT', 'TRU');
	
	if(in_array(strtoupper(substr($str_query,0,3)), $queries2log) && !$conf_is_prod)
		@write_log('db_trans', $str_query);	
			
	switch($conf_db_type) {
		case 'mysql':
			$res = @mysql_query($str_query, $conex);
			if($res)
				return $res;
			else
				write_log('db_error', mysql_error() ." ----> ". $str_query);
		break;
		case 'mssql': 
			$res = @mssql_query($str_query, $conex);
			if($res)
				return $res;
			else
				write_log('db_error', mssql_get_last_message() ." ----> ". $str_query);
		break;
	}
}

function my_fetch_array($result, $mode = MYSQL_ASSOC) {
	global $conf_db_type;
	
	switch($conf_db_type) {
		case 'mysql': return @mysql_fetch_array($result, $mode); break;
		case 'mssql': return @mssql_fetch_array($result); break;	# this function doesn't accept 'mode' parameter.
	}
}

function my_num_rows($result) {
	global $conf_db_type;
	
	switch($conf_db_type) {
		case 'mysql': return @mysql_num_rows($result);	break;
		case 'mssql': return @mssql_num_rows($result);	break;
	}
}

function my_result($result, $row, $column) {
	global $conf_db_type;
	
	switch($conf_db_type) {
		case 'mysql': return @mysql_result($result, $row, $column);	break;
		case 'mssql': return @mssql_result($result, $row, $column);	break;
	}
}

function get_serv_info() {
	global $conf_db_type;
	
	switch($conf_db_type) {
		case 'mysql': return 'MySQL '. mysql_get_server_info();	break;
		case 'mssql': return 'MS SQL Server';					break;
	}
}

function my_free_result($result) {
	global $conf_db_type;
	
	switch($conf_db_type) {
		case 'mysql': return @mysql_free_result($result);	break;
		case 'mssql': return @mssql_free_result($result);	break;
	}
}

function get_last_insert_id($conex) {
	global $conf_db_type;

	switch($conf_db_type) {
		case 'mysql': $res = my_query('SELECT LAST_INSERT_ID() as Last_ID;', $conex); break;
		case 'mssql': $res = my_query('SELECT @@identity as Last_ID;', $conex); break;
	}
	return my_result($res, 0, 'Last_ID');
}


?>
