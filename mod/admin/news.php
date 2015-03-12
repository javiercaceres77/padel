<?php

$today = new my_date('today');

if($_GET['action'] == 'publish' && $_GET['detail']) {
	$ob_new = new event($_GET['detail']);
	if($ob_new->publish_event())
		add_alert($_GET['mod'], 'info', 1, 'Noticia publicada correctamente');
}
elseif($_GET['action'] == 'remove' && $_GET['detail']) {
	$ob_new = new event($_GET['detail']);
	if($ob_new->remove_event())
		add_alert($_GET['mod'], 'info', 1, 'Noticia quitada correctamente');
}

print_alerts($_GET['mod']);
?>

<table width="100%" border="0" cellpadding="3" cellspacing="2">
  <tr>
    <td class="title_3">Noticias</td>
    <td class="default_text" align="right"><a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_new'; ?>"><img src="<?= $conf_images_path; ?>new.gif" border="0" align="absbottom" /> Insertar noticia</a></td>
  </tr>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="2">
  <?php

$sql = 'SELECT n.new_id, n.header, n.summary, n.date_from, n.date_to, n.status, n.photo_id, p.title, i.img_file, i.img_w, i.img_h
FROM news n LEFT JOIN photos p ON p.photo_id = n.photo_id LEFT JOIN images i ON p.thumb_img_id = i.img_id ORDER BY n.new_id DESC LIMIT 200';

$sel = my_query($sql, $conex);
while($rec = my_fetch_array($sel)) {
	$date_from = new my_date($rec['date_from']);
	$date_to = new my_date($rec['date_to']);
	
	switch($rec['status']) {
		case 'editing': 	$stat_str = 'Pendiente';	break;
		case 'published':	$stat_str = 'Publicada';	break;
		case 'expired':		$stat_str = 'Caducada';		break;
	}

	if($date_to->get_mktime() < $today->get_mktime())
		$stat_str = 'Caducada';
	
	?>
  <tr>
    <td colspan="3" class="border_bottom_dotted bg_ddd" height="4"></td>
  </tr>
  <tr>
    <td><img src="<?= $conf_photos_path . $rec['img_file']; ?>" title="<?= $rec['title']; ?>" /></td>
    <td><div class="title_3"><a href="<?= $conf_main_page; ?>?mod=news&detail=<?= $rec['new_id']; ?>" title="Ver noticia">
        <?= $rec['header']; ?>
        </a> </div>
      <div class="event_summary">
        <?= $rec['summary']; ?>
      </div>
      <div class="small_text">Fecha:
        <?= $date_from->format_date('med') .' &ndash; '. $date_to->format_date('med'); ?>
        |
        <?= $stat_str; ?>
      </div></td>
    <td width="150" class="default_text"><?php	if($rec['status'] == 'published') {	?>
    <img src="<?= $conf_images_path; ?>checkbox_bn.gif" border="0" align="absmiddle" title="La noticia está publicada" /> Publicar<br />
    <?php	}	else	{	?>
    <a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&action=publish&detail='. $rec['new_id']; ?>" title="Publicar Noticia"><img src="<?= $conf_images_path; ?>checkbox.gif" border="0" align="absmiddle" /> Publicar</a><br />
    <?php	}	?>
      <a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_new&detail='. $rec['new_id']; ?>" title="Modificar Noticia"><img src="<?= $conf_images_path; ?>edit.gif" border="0" align="absmiddle" /> Modificar</a><br />
      <a href="<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&action=remove&detail='. $rec['new_id']; ?>" title="Quitar Noticia"><img src="<?= $conf_images_path; ?>close2.png" border="0" align="absmiddle" /> Quitar</a></td>
  </tr>
  <?php
}
?>
  <tr>
    <td colspan="3" class="border_bottom_dotted bg_ddd" height="4"></td>
  </tr>
</table>
