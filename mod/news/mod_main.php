<?php
# Check that the user actually has permissions to view this module

if($_SESSION['login']['modules'][$_GET['mod']]['read']) {
	/*
	if(!$_GET['tab']) $_GET['tab'] = 'news';
	
	$arr_tabs = array('news' => 'Noticias');
		*/			  
?>

<div class="standard_container">
  <?php
/*foreach($arr_tabs as $key => $value) {
	$class = $key == $_GET['tab'] ? 'active_tab' : 'inactive_tab';

	if(!($_SESSION['login']['modules'][$_GET['mod']]['write'] == '0' && $key == 'user_books'))
		echo '<span class="'. $class .' big_tab_text" onclick="JavaScript:jump_to(\''. $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $key .'\')">'. $value .'</span>';
}
*/
	
	$news_arr = event::get_current_events();

?>
  <table width="100%" border="0" cellpadding="3" cellspacing="2">
    <tr>
      <td valign="top"><?php 
	foreach($news_arr as $new_id => $event) {
		if(!$_GET['detail']) $_GET['detail'] = $new_id;
		
		$new_date = new my_date($event['date_from']);
	?>
        <table width="100%" border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td class="small_text" colspan="2"><?= $new_date->format_date('long'); ?></td>
          </tr>
          <tr>
            <td colspan="2" class="title_3"><a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&detail='. $new_id; ?>"><?= $event['header']; ?></a></td>
          </tr>
          <tr>
            <td><?php
	if($event['photo_id']) {
		$ob_photo = new photo($event['photo_id']);
		$ob_photo->print_photo('thumb', false);
	}
	?></td>
            <td><?= $event['summary']; ?></td>
          </tr>
          <tr><td colspan="2" class="border_bottom_dotted" height="3"></td></tr>
        </table>
        <?php
	}
	  ?></td>
      <td valign="top" width="640" class="bg_ddd" style="padding:12px;"><?php
      $ob_new = new event($_GET['detail']);
	  $ob_new->print_event();
	  ?></td>
    </tr>
  </table>
</div>
<?php
}	//	if($_SESSION['login']['modules'][$_GET['mod']]['read']) {
else {
	jumpt_to($conf_main_page);
	exit();
}

?>
