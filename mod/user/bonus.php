<?php

$total_hours = round($ob_user->get_user_total_bonus_hours(), 1);

?>
<table cellpadding="5" cellspacing="4" border="0" class="default_text" width="80%" align="center">
  <tr>
    <td class="title_3">Total <?= $total_hours; ?> horas disponibles en bonos</td>
    <td align="right"><input type="button" name="save" class="button" value="  COMPRAR BONO  " onclick="JavaScript:document.location='<?= $conf_main_page ?>?mod=user&tab=bonus&subtab=add_bonus';" /></td>
  </tr>
</table>
<?php

$sql = 'SELECT b.bonus_id, b.bonus_type, b.issued_datetime, b.status, b.remaining_hours, b.bonus_cost, bt.type_description
FROM bonuses b INNER JOIN bonus_types bt ON bt.type_code = b.bonus_type
WHERE b.user_id = '. $ob_user->user_id .' ORDER BY bonus_id DESC';

$sel = my_query($sql, $conex);

?>
<table cellpadding="3" cellspacing="6" border="0" class="default_text" width="80%" align="center">
  <?php
while($rec = my_fetch_array($sel)) {
	$class = $rec['status'] == 'used' || $rec['status'] == 'canceled' ? 'class="bg_standard"' : '';
	$buy_date = new date_time($rec['issued_datetime']);
	switch($rec['status']) {
		case 'used':		$status = 'Usado';		$class = 'class="bg_standard"';				break;
		case 'canceled':	$status = 'Cancelado';	$class = 'class="bg_standard"';				break;
		case 'active':		$status = round($rec['remaining_hours'], 1) .' horas disponibles';	break;
	}
?>
  <tr>
    <td <?= $class; ?>><div class="title_4">
        <?= $rec['type_description']; ?>
      </div>
      N&ordm; bono:
      <?= $rec['bonus_id'] .'&nbsp;&nbsp; Comprado el '. $buy_date->odate->format_date('med') .' '. $buy_date->format_time() .'&nbsp;&nbsp; '. print_money($rec['bonus_cost']); ?></td>
    <td <?= $class; ?>><?= $status; ?></td>
  </tr>
  <tr>
    <td colspan="2" height="1" class="bg_ddd"></td>
  </tr>
  <?php
}
?>
</table>
