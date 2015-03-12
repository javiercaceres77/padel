<?php

header("Content-Type: text/html; charset=iso-8859-1");

session_start();

# Includes
	
include 'config.php';
include 'comm.php';
include 'connect.php';
include 'oops_comm.php';
include 'oops_sc.php';
if($_GET['mod'] == 'admin') include 'comm_admin.php';

if(!$_GET['lang'] && !$_SESSION['misc']['lang']) $_GET['lang'] = $conf_default_lang;
if($_GET['lang']) $_SESSION['misc']['lang'] = $_GET['lang'];

//$in['file'] = '../tra/'. basename($_SERVER['SCRIPT_NAME'], '.php') .'_'. $_SESSION['misc']['lang'] . '.php';
$in['file'] = '../tra/index_'. $_SESSION['misc']['lang'] . '.php';
include 'translation.php'; 

date_default_timezone_set($conf_timezone);

# Sanitize get and post  ----------------------------------
sanitize_input();

# This file is always called when we need some ajax.
# The $_GET parameter content indicates which file will actually return the contents.

switch($_GET['content']) {
	case 'update_slots':			include 'ajax/time_slots.php';			break;
	
	case 'update_admin_slots':		include 'ajax/admin_time_slots.php';	break;
	
	case 'pre_book':				include 'ajax/pre_book_box.php';		break;
	
	case 'delete_pre_book':
		$now = new date_time('now');
		$sql = 'DELETE FROM bookings WHERE user_id = \''. $_SESSION['login']['user_id'] .'\' AND slot_id = \''. $_GET['detail'] .'\' AND expire_datetime > \''. $now->datetime .'\' AND status = \'prebook\'';
		$del_book = my_query($sql, $conex);
		if($del_book)
			unset($_SESSION['pre_books'][$_GET['detail']]);
	break;
	
	case 'show_alerts':				print_alerts($_GET['mod']);				break;
	
	case 'ok_alert':
		ok_alert($_GET['mod'], $_GET['detail']);
		print_alerts($_GET['mod']);
    break;
	
	case 'construct_date':
		$odate = new my_date($_GET['value']);
		echo $odate->odate;
	break;
	
	case 'upd_pwd':
		$_SESSION['new_user']['pasapalabra'] = get_random_pwd(6);
		echo $_SESSION['new_user']['pasapalabra'];
	break;
	
	case 'captcha':					include 'ajax/captcha_generator.php';	break;
	
	case 'join_users':				include 'ajax/join_users_box.php';		break;
	
	case 'confirm_join':
		$ob_user = new user($_GET['user']);
		if($ob_user->set_as_member_with_member($_GET['member'])) {
			write_log_db('member', 'Set member with user', 'Member: '. $_GET['member'] .', User: '. $ob_user->user_id);
			echo '<span class="title_4">El usuario se ha agregado con éxito a la cuenta de socio</span><br><a href="'. $conf_main_page .'?mod=admin&tab=users">Volver a la lista de usuarios</a>';
		}
		else
			echo 'Ha habido un error al agregar el usuario a la cuenta de socio';
	break;
	
	case 'place_booking':			include 'ajax/admin_book_4_user.php';	break;
	
	case 'confirm_book':			include 'ajax/admin_confirm_book.php';	break;
	
	case 'search_ply':				include 'ajax/admin_search_users.php';	break;
	
	case 'user_pms':				include 'ajax/admin_show_user_pms.php';	break;
	
	case 'check_code':				include 'ajax/home_create_new_pwd.php'; break;
}

?>
