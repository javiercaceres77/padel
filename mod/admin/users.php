<?php
if($_POST) $_SESSION['last_search'] = $_POST;
?>

<table width="100%" border="0" cellpadding="3" cellspacing="2">
  <tr>
    <td class="title_3"><?= ucfirst(search_users); ?></td>
    <td class="default_text" align="right"><a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_user'; ?>"><img src="<?= $conf_images_path; ?>new.gif" border="0" align="absmiddle" />
      <?= ucfirst(create_new_user); ?>
      </a>&nbsp;&nbsp;&nbsp;&nbsp; <a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=config_users'; ?>"><img src="<?= $conf_images_path; ?>settings.png" border="0" align="absmiddle" />
      <?= ucfirst(config_users); ?>
      </a> </td>
  </tr>
  <tr>
    <td colspan="2" class="default_text" bgcolor="#DDDDDD"><form name="form_search_users" id="form_search_users" method="post" action="">
        <?= ucfirst(name); ?>
        :
        <input type="text" class="inputnormal" name="user_name" id="user_name" autofocus="autofocus" maxlength="150" value="<?= $_SESSION['last_search']['user_name']; ?>" />
        &nbsp;&nbsp;&nbsp;Número de usuario :
        <input type="text" class="inputdate" name="user_id" id="user_id" maxlength="10" value="<?= $_SESSION['last_search']['user_id']; ?>" />
        &nbsp;&nbsp;&nbsp;
        e-mail:
        <input type="text" class="inputnormal" name="email" id="email" maxlength="250" value="<?= $_SESSION['last_search']['email']; ?>" />
        &nbsp;&nbsp;&nbsp;
        <input type="submit" value=" <?= ucfirst(search); ?> " name="submit" class="button" />
      </form></td>
  </tr>
  <tr>
    <td colspan="2"><?php

$now = new date_time('now');

$conditions = array();
if($_SESSION['last_search']['user_name'])		$conditions[] = 'u.full_name like \'%'. $_SESSION['last_search']['user_name'] .'%\'';
if($_SESSION['last_search']['email'])			$conditions[] = 'u.email like \'%'. $_SESSION['last_search']['email'] .'%\'';
if($_SESSION['last_search']['user_id'])			$conditions[] = 'u.user_id = '. $_SESSION['last_search']['user_id'];

$sql = 'SELECT u.user_id, u.full_name, u.email, u.phone1, u.phone2, u.date_registered , min(b.booking_datetime) as book_datetime, 
u.total_books_num, u.blocked_ind, u.deleted_ind, u.is_member, u.control_code
FROM users u
LEFT JOIN bookings b
ON b.user_id = u.user_id AND b.booking_datetime > \''. $now->datetime .'\' AND b.status = \'confirmed\'';
if(count($conditions)) $sql.= 'WHERE '. implode(' AND ', $conditions);
$sql .= 'GROUP BY u.user_id, u.full_name, u.email, u.phone1, u.phone2, u.date_registered, u.total_books_num, u.blocked_ind, u.deleted_ind, u.is_member, u.control_code';
//$sql .='ORDER BY u.date_registered DESC';

$select_users = my_query($sql, $conex);

$num_results = my_num_rows($select_users);

$initial_row = 0;
$final_row = 0;

if($_GET['pag']) $_SESSION['login']['modules'][$_GET['mod']]['nav_page'] = $_GET['pag'];

if(!$_SESSION['login']['modules'][$_GET['mod']]['nav_page']) $_SESSION['login']['modules'][$_GET['mod']]['nav_page'] = 1;

if($_GET['nrows']) $_SESSION['login']['modules'][$_GET['mod']]['nrows'] = $_GET['nrows'];
if(!$_SESSION['login']['modules'][$_GET['mod']]['nrows']) $_SESSION['login']['modules'][$_GET['mod']]['nrows'] = 25;

if(!is_numeric($_SESSION['login']['modules'][$_GET['mod']]['nrows']) || $_SESSION['login']['modules'][$_GET['mod']]['nrows'] < 0 || $_SESSION['login']['modules'][$_GET['mod']]['nrows'] > 500)
	$_SESSION['login']['modules'][$_GET['mod']]['nrows'] = 25;
	
	$parameters = array('page' => $_SESSION['login']['modules'][$_GET['mod']]['nav_page']
					   ,'num_rows' => $num_results ,'num_rows_page' => $_SESSION['login']['modules'][$_GET['mod']]['nrows'], 'class' => 'border_bottom_dotted');

	draw_pages_navigator($parameters);

?>
      <table border="0" cellpadding="2" cellspacing="1" width="100%" class="default_text">
        <tr>
          <th class="search_result">nº usr.</th>
          <th class="search_result"><?= ucfirst(name); ?>
          </th>
          <th class="search_result">e-mail </th>
          <th class="search_result"><?= ucfirst(phones); ?>
          </th>
          <th class="search_result"><?= ucfirst(next_book); ?>
          </th>
          <th class="search_result"><?= ucfirst(date_registered); ?>
          </th>
          <th class="search_result" colspan="3">&nbsp;</th>
        </tr>
        <?php
if($num_results > 0) {
	$row = 0;
	while($record = my_fetch_array($select_users)) {
		if($row >= $initial_row && $row <= $final_row) {
			$class = $row % 2 != 0 ? 'search_result' : 'search_result_light';
?>
        <tr>
          <td class="<?= $class; ?>" align="center"><?= $record['user_id']; ?></td>
          <td class="<?= $class; ?>" title="<?= $record['full_name']; ?>"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=user_detail&detail=<?= $record['user_id']; ?>">
            <?= shorten_str($record['full_name'], 25); ?>
            </a></td>
          <td class="<?= $class; ?>" title="<?= $record['email']; ?>"><?= shorten_str($record['email'], 30); ?></td>
          <td class="<?= $class; ?>" align="center"><?php 
		if($record['phone1'] || $record['phone2']) {
			if($record['phone1'] && $record['phone2'])
				$str = $record['phone1'] .' / '. $record['phone2'];
			else
				$str = $record['phone1'] . $record['phone2'];
			echo '<img src="'. $conf_images_path .'smart_phone_16.png" title="'. $str .'" /></td>';
		}
		
		if($record['book_datetime']) {
			$ob_book_date = new date_time($record['book_datetime']);
			$book_date_str = $ob_book_date->odate->format_date('med') .' '. $ob_book_date->hour .':'. $ob_book_date->minute;
		}
		else
			$book_date_str = '';
		
		$ob_date_reg = new my_date($record['date_registered']);
		?>
          <td class="<?= $class; ?>" align="center"><?= $book_date_str; ?></td>
          <td class="<?= $class; ?>" align="center"><?= $ob_date_reg->format_date('med'); ?></td>
          <td class="<?= $class; ?>" align="center"><?php
        if($record['deleted_ind'])
			$book_str = '<img src="'. $conf_images_path .'checkbox_bn.gif" border="0" title="'. ucfirst(user_deleted) .', '. cant_make_reservation .'" />';
		elseif($record['blocked_ind'])
			$book_str = '<img src="'. $conf_images_path .'checkbox_bn.gif" border="0" title="'. ucfirst(user_blocked) .', '. cant_make_reservation .'" />';
		else
			$book_str = '<a href="'. $conf_main_page .'?mod=admin&tab=bookings&user='. $record['user_id'] .'"><img src="'. $conf_images_path .'checkbox.gif" border="0" title="'. make_reservation_for .' '. $record['full_name'] .'" /></a>';
		
		echo $book_str;
		?></td>
          <!--          <td class="<?= $class; ?>" align="center"><a href="<?= $conf_main_page; ?>xxxx"><img src="<?= $conf_images_path; ?>edit.gif" border="0" title="<?= user_details; ?>" /></a></td>-->
          <td class="<?= $class; ?>" align="center"><a href="<?= $conf_main_page; ?>?mod=admin&tab=users&subtab=add_bonus&usr=<?= $record['user_id']; ?>"><img src="<?= $conf_images_path; ?>money.png" border="0" title="Añadir bono para <?= $record['full_name']; ?>" /></a></td>
          <td class="<?= $class; ?>"><?php
		  
          if($record['is_member'] == '1') {
		  ?>
            <img src="<?= $conf_images_path; ?>user16.png" title="<?= $record['full_name']; ?> es socio." />
            <?php
          }
		  
          if($record['control_code'] != '') {
		  ?>
            <img src="<?= $conf_images_path; ?>alert.png" title="El usuario todavía no ha activado su cuenta" />
            <?php
          }

          if($record['blocked_ind'] == '1') {
		  ?>
            <img src="<?= $conf_images_path; ?>block.png" title="El usuario está bloqueado." />
            <?php
          }
		  ?></td>
        </tr>
        <?php 	
		}	//if($row >= $initial_row && $row <= $final_row) {

		$row++;
	}	//while($record = my_fetch_array($select_routes)) {
}	//if($num_results > 0) {
else {
?>
        <tr>
          <td colspan="10" align="center" class="error_message"><?= ucfirst(no_results_found); ?></td>
        </tr>
        <?php
}
			?>
      </table>
      <?php
      $parameters['class'] = 'border_top_dotted';
	  draw_pages_navigator($parameters);
      ?>
    </td>
  </tr>
</table>
