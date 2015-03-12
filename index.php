<?php

header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

session_start();
//session_unset();

# control $_SESSION inactivity time
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    // last request was more than 30 minates ago
    @session_destroy();   // destroy session data in storage
    session_unset();     // unset $_SESSION variable for the runtime
}
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

//if($_GET['func'] == 'logout')  session_unset();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="title" content="Pádel Indoor Ponferrada" />
<meta name="description" content="Pádel deporte Ponferrada Indoor reservas multijugador gestión club deportivo"  />
<meta name="Keywords" content="deporte reservas pádel padel padle paddle" />
<link rel="icon" type="image/png" href="img/favicon.png" />
<!--<link rel="shortcut icon" href="img/favicon.ico" />-->
<link href="css/main.css" rel="stylesheet" type="text/css" />
<?php

# Includes  ----------------------------------
	
include 'inc/config.php';
include $conf_include_path .'comm.php';
include $conf_include_path .'connect.php';
include $conf_include_path .'oops_comm.php';
include $conf_include_path .'oops_sc.php';
if($_GET['mod'] == 'admin') include $conf_include_path .'comm_admin.php';

# add include path for PEAR extensions
/*$path = '/usr/local/lib/php';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
include_once 'Mail.php';
include_once 'Mail/mime.php';
*/
if(!$_GET['lang'] && !$_SESSION['misc']['lang']) $_GET['lang'] = $conf_default_lang;
if($_GET['lang']) $_SESSION['misc']['lang'] = $_GET['lang'];

include $conf_include_path .'translation.php'; 

date_default_timezone_set($conf_timezone);

# Sanitize get and post  ----------------------------------
sanitize_input();

# Logout user  ----------------------------------
if($_GET['func'] == 'logout') {
	# store record of user logging out?
	session_unset(); session_destroy();
	jump_to($conf_main_page);
	exit();
}

# Login user  ----------------------------------
if($_GET['action'] == 'login' && $_POST['user'] && $_POST['pass']) {

	$user_status = user::validate_login($_POST['user'], $_POST['pass']);
		
	write_log_db('login', $user_status, $_POST['user']);
	
	switch($user_status) {
		case 'NORMAL': case 'FIRST': case 'NOT_VALIDATED': 
			# NORMAL: 		usr and pwd are correct and the user is in normal condition
			# FIRST: 		usr and pwd are correct and it is the first time the user logs in
			# NOT_VALIDATED:user hasn't validated the e-mail address. Can login, when it is validaded the user will be able to place bookings.
			# ^ this really is the same as normal. the difference is that it doesn't have permissions over certain modules, must jump to the validation page?
			
			$user = new user($_POST['user']);
			$user->upd_last_login_date();
			$user->upd_session_login();

			if($user_status == 'FIRST') {
				# generate welcome alert for first login users and send to users page
				$text = '<div align="center">¡Bienvenido!</div>Puedes empezar a <a href="'. $conf_main_page .'?mod=book&tab=bookings">hacer reservas</a> inmediatamente. Comprueba que tus datos son correctos y añade más datos personales si lo deseas.';
				add_alert('user', 'info', 2, $text);

				$_POST['url'] = $conf_main_page .'?mod=user';
			}
			
			if($user_status == 'NOT_VALIDATED') {
				$text = 'Tu usuario no ha sido validado, introduce tu código de validación para poder hacer reservas.';
				add_alert('home', 'alert', 2, $text);
				
				$_POST['url'] = $conf_main_page .'?mod=home&view=check_code';
			}
			
			if(strpos($_POST['url'], 'pwd'))
				$_POST['url'] = $conf_main_page;
			
			if($_POST['url'])
				jump_to($_POST['url']);
			exit();
		break;

		case 'INCORRECT':
			# INCORRECT: 	usr doesn't exist or pwd doesn't match
			user::manage_wrong_login($_POST['user']);
/*			if($_SESSION['login']['num_tries'] > 3)
				$show_captcha = true;*/
			$wrong_login = true;
		break;
		
		case 'DELETED': case 'NOT_EXIST':
			# DELETED:		user has been deleted and can't login. treat as an incorrect.
			$wrong_login = true;
		break;
		
		case 'BLOCKED':
			# BLOCKED:		user has been blocked. Show block info
			# check the blocks table and if period is over, remove the block on the user
			$user = new user($_POST['user']);
			$blocked_until = $user->get_until_blocked();
			if($blocked_until) {
				$_SESSION['login']['blocked_id'] = $user->user_id;
				$_GET['view'] = 'block';
//				jump_to($conf_main_page .'?mod=home&view=block');
			}
			else {
				$user->unblock_user();
				# and the treat as a regular login
				$user->upd_last_login_date();
				$user->upd_session_login();

				if($_POST['url'])
					jump_to($_POST['url']);
			}
			exit();
		break;
		
		case 'MIGRATED':
			# MIGRATED:		user must insert a code and will be activated.
			$m = encode($_POST['user']);
			jump_to($conf_main_page .'?mod=home&view=mig_users&m='. $m);
		break;	
				
		case 'CHG_PWD_REQ':
			# CHG_PWD_REQ:	user must change password before logging in.
			$m = encode($_POST['user']);
			$p = encode($_POST['pass']);
			jump_to($conf_main_page .'?mod=home&view=chg_pwd&m='. $m .'&p='. $p);
			exit();
		break;

	}	//	switch($user_status) {
}	//	if($_GET['action'] == 'login' && $_POST['user'] && $_POST['pass']) {
# Get user info  ----------------------------------
if(!isset($_SESSION['login']['user_id']))
	$_SESSION['login']['user_id'] = $conf_generic_user_id;

# Create objects for this page.  ----------------------------------
if(!isset($ob_user))
	$ob_user = new user($_SESSION['login']['user_id'], $_SESSION['login']['name']);

$now = new date_time('now');

# Manage modules  ----------------------------------
if(!$_GET['mod']) $_GET['mod'] = $conf_default_mod;
	
refresh_users_modules(true);

?>
<script language="javascript">
function submit_login_form() {
	if(document.login_form.user.value == '' || document.login_form.pass.value == '')
		alert('<?= ucfirst(write_user_name_pass); ?>');
	document.login_form.url.value = document.location;
	
	document.login_form.submit();
}

function ok_alert(alert_id) {
	url = 'inc/ajax.php?content=ok_alert&detail='+ alert_id +'&mod=<?= $_GET['mod']; ?>';
	getData3(url, 'alerts_box');
}

function show_alerts() {
	url = 'inc/ajax.php?content=show_alerts&mod=<?= $_GET['mod']; ?>';
	getData3(url, 'alerts_box');
}

</script>
<script language="javascript" src="<?= $conf_include_path; ?>comm.js"></script>
<script language="javascript" src="<?= $conf_include_path; ?>ajax.js"></script>
<title>::: PÁDEL INDOOR PONFERRADA :::<?php echo ucfirst($_SESSION['login']['modules'][$_GET['mod']]['name']); ?></title>
</head>
<body class="greydegbg">
<table border="0" align="center" cellpadding="0" cellspacing="0" width="960" class="head_bg">
  <tr>
    <td align="right" class="header_table"><?php
    if($_SESSION['login']['user_id'] == 0) {
		?>
      <form action="<?= $conf_main_page; ?>?action=login" method="post" name="login_form">
        <table border="0" cellpadding="0" cellspacing="0" class="small_text">
          <tr>
            <td></td>
            <td align="left" class="left_right_padding">Número de socio</td>
            <td align="left" class="left_right_padding">Contraseña</td>
            <td rowspan="2" class="left_right_padding"><input name="Submit" type="submit" onClick="JavaScript:submit_login_form()" class="button_small" value="   Entrar   " tabindex="3"/>
              <input type="hidden" name="url" id="url" value="" /></td>
            <td rowspan="2" align="right" class="left_right_padding"><a title="regístrate para hacer reservas, es gratis" href="<?= $conf_main_page; ?>?mod=home&view=new_user">Nuevo Usuario</a><br />
              <a title="&iquest;olvidate tu n&uacute;mero de usuario o tu contrase&ntilde;a?" href="<?= $conf_main_page; ?>?mod=home&view=rec_pwd">&iquest;problemas?</a></td>
          </tr>
          <tr>
            <td class="left_right_padding default_text" valign="bottom"><?php 
		  if($wrong_login)
			  echo '<span class="error_message">'. ucfirst(wrong_login) .'</span>';
		  else 
		  	  echo 'Acceso usuarios: '; ?></td>
            <td class="left_right_padding"><input name="user" type="text" class="inputsmall" id="user" maxlength="60" style="width:100px;" autofocus="autofocus" tabindex="1" /></td>
            <td class="left_right_padding"><input name="pass" type="password" class="inputsmall" id="pass" maxlength="30" style="width:100px;" tabindex="2" /></td>
          </tr>
        </table>
      </form>
      <?php
	}
	else {
?>
      <table border="0" cellpadding="1" cellspacing="2" class="default_text">
        <tr>
          <td align="right"><a href="<?= $conf_main_page; ?>?mod=user" title="Tu cuenta de usuario"><?= $ob_user->get_user_name(); ?></a>&nbsp;&nbsp;&nbsp;[<a href="<?= $conf_main_page; ?>?func=logout">cerrar sesión</a>]</td>
        </tr>
        <tr>
          <td align="right"><?php
          
	$sql = 'SELECT b.court_id, b.booking_datetime, c.name
	FROM bookings b INNER JOIN courts c ON b.court_id = c.court_id
	WHERE user_id = '. $_SESSION['login']['user_id'] .' 
	  AND booking_datetime > \''. $now->datetime .'\'
	  AND status = \'confirmed\'
	ORDER BY booking_datetime ASC';
	
	$sel_next_books = my_query($sql, $conex);
	$arr_next_books = array();
	
	while($record = my_fetch_array($sel_next_books))
		$arr_next_books[] = $record;
	
	if(count($arr_next_books)) {
		$next_book = array_shift($arr_next_books);
		$next_book_datetime = new date_time(substr($next_book['booking_datetime'], 0, 10), substr($next_book['booking_datetime'], 11, 5));
		echo 'Próxima reserva: '. $next_book_datetime->odate->format_date('short_day') .' a las '. $next_book_datetime->format_time();
	}
	else
		echo 'No tienes reservas pendientes';
		?></td>
        </tr>
      </table>
      <?php	}	?></td>
  </tr>
  <tr>
    <td><div id="menu_container" class="menu_container">
        <table border="0" cellpadding="3" cellspacing="0">          <tr>
 <?php 
# --------------------  MENU BAR --------------------- #
/*
	background-image:url(../img/home.png);
*/

foreach($_SESSION['login']['modules'] as $mod_id => $mod_info) {
	$title = $mod_info['desc'] ? ' title="'. ucfirst($mod_info['desc']) .'"' : '';
	$icon = $mod_info['icon'] ? '<img src="'. $conf_images_path . $mod_info['icon'] .'" height="18" width="18" align="absmiddle">' : '';
	$class = $mod_id == $_GET['mod'] ? 'menu_active' : 'menu_item';
	
	echo '<td class="'. $class .'"'. $title .' onclick="JavaScript:document.location=\''. $conf_main_page .'?mod='. $mod_id .'\'" align="center">'. $icon . $mod_info['name'] .'</td>';
}
?>
          </tr>
        </table>
      </div>
</td>
  </tr>
  <tr>
    <td><?php 
# -------------------- INCLUDE THE MODULE view --------------------- #	
if(!$_GET['view']) $_GET['view'] = 'mod_main';

$include_file = 'mod/'. $_GET['mod'] .'/'. $_GET['view'] .'.php';

if($_SESSION['login']['modules'][$_GET['mod']]['read'])
	include $include_file;

	?></td>
  </tr>
  <!-- ------------------------ FOOTER ----------------------- -->
  <tr>
    <td align="center" class="small_text"><a href="<?= $conf_main_page; ?>?mod=home&view=tycs">Términos y condiciones</a> | <a href="<?= $conf_main_page; ?>?mod=home&view=contact">Contacto</a> | <a href="<?= $conf_main_page; ?>?mod=home&view=about">Quienes somos</a><br />
      <br /></td>
  </tr>
</table>
</body>
</html>
