<?php

if($_POST) {
	# remove bonus -------------------------------------------------------------
	$notice_text = '';
	$notice_type = 'notice';	// 'alert'
	$changes_days = get_config_value('days_advance_booking_available'); # many changes will be effective after the bookings are available.
/*	$today = new my_date('today');
	$today_plus7 = $today->plus_days($changes_days);
*/
	if($_POST['bonus_removed']) {
		$bonus = new bonus_type($_POST['bonus_removed']);
		if($bonus->deactivate()) {
			$notice_text = 'El bono "'. $bonus->description .'" ha sido desactivado. Los usuario que todavía tengan horas podrán seguir usándolas';
		}
		else {
			$notice_text = 'Ha habido un error al desactivar el bono';
			$notice_type = 'alert';
		}
	}

	# save courts --------------------------------------------------------------
	if($_POST['court_save']) {
		$desc = $_POST['court_desc_'. $_POST['court_save']];
		$mins = $_POST['court_mins_'. $_POST['court_save']];
		
		$court = new court($_POST['court_save']);
		if($court->update_desc($desc))
			$notice_text = 'Descripción de la pista actualizada. ';
		else {
			$notice_text = 'Ha habido un error al actualizar la pista';
			$notice_type = 'alert';
		}
		
		if($court->update_mins($mins)) 
			$notice_text = 'Duración de las reservas en '. $court->name .' actualizada. El cambio tendrá lugar a partir de '. $changes_days .' días';
		else {
			$notice_text = 'Ha habido un error al actualizar la pista';
			$notice_type = 'alert';
		}
	}
	
	# save ots -----------------------------------------------------------------
	if($_POST['open_from_1#festivo']) {
		# load the current opening times into an array
		$sql = 'SELECT open_from_1, open_to_1, open_from_2, open_to_2, description FROM opening_times_conf';
		$sel = my_query($sql, $conex);
		$ots_array = array();
		while($record = my_fetch_array($sel)) {
			$ots_array[$record['description']] = array('ot' => $record['open_from_1'], 'ct1' => $record['open_to_1'], 'ot2' => $record['open_from_2'], 'ct' => $record['open_to_2']);
		}	
		# compare each part of the array and update if necessary.
		$updated = false;
		foreach($ots_array as $desc => $ots) {
			if($_POST['open_from_1#'. $desc] != $ots['ot'] || $_POST['open_to_2#'. $desc] != $ots['ct'] || $_POST['open_to_1#'. $desc] != $ots['ct1'] || $_POST['open_from_2#'. $desc] != $ots['ot2']) {
				$arr_upd = array('open_from_1' => $_POST['open_from_1#'. $desc],
								 'open_to_2' => $_POST['open_to_2#'. $desc],
								 'open_to_1' => $_POST['open_to_1#'. $desc],
								 'open_from_2' => $_POST['open_from_2#'. $desc]);
				if(update_array_db('opening_times_conf', 'description', $desc, $arr_upd)) {
					$notice_text.= 'Actualizado horario para '. $desc .'<br>';
					$updated = true;
				}
				else {
					$notice_text = 'Ha habido un error al actualizar los horarios';
					$notice_type = 'alert';
				}
			}
		}
		if($updated)
			$notice_text.= 'Los cambios tendrán lugar a partir de '. $changes_days .' días';
		else
			$notice_text = 'No ha habido cambios en los horarios.';

	}
	
	# save holiday -------------------------------------------------------------
	if($_POST['date_holiday']) {
		$date_holiday = new date_dim($_POST['date_holiday']);
		if($date_holiday->set_holiday($_POST['name_holiday'])) {
			$notice_text.= 'Festivo '. $date_holiday->odate->format_date('med') .' agregado correctamente';
		}
		else {
			$notice_text = 'Ha habido un error al agregar el festivo';
			$notice_type = 'alert';
		}
	}
	
	# remove holiday -----------------------------------------------------------
	if($_POST['delete_holiday']) {
		$date_holiday = new date_dim($_POST['delete_holiday']);
		if($date_holiday->unset_holiday()) {
			$notice_text.= 'Festivo '. $date_holiday->odate->format_date('med') .' eliminiado correctamente';
		}
		else {
			$notice_text = 'Ha habido un error al eliminar el festivo';
			$notice_type = 'alert';
		}
	}
	
	# save special -------------------------------------------------------------
	if($_POST['esp_date'] && !$_POST['delete_special']) {
		$date_special =new date_dim($_POST['esp_date']);
		if($date_special->set_special($_POST['esp_name'], $_POST['ot_special'], $_POST['ct_special1'], $_POST['ot_special2'], $_POST['ct_special'])){
			$notice_text.= 'Día con horario especial '. $date_special->odate->format_date('med') .' agregado correctamente';
		}
		else {
			$notice_text = 'Ha habido un error al agregar el día con horario especial';
			$notice_type = 'alert';
		}
		
	}
	
	# remove special -----------------------------------------------------------
	if($_POST['delete_special']) {
		$date_special = new date_dim($_POST['delete_special']);
		if($date_special->unset_special()) {
			$notice_text.= 'Día con horario especial '. $date_special->odate->format_date('med') .' eliminiado correctamente';
		}
		else {
			$notice_text = 'Ha habido un error al eliminar el día con horario especial';
			$notice_type = 'alert';
		}
	}

	//pa($_POST);		
}

if($notice_text) {
?>
<div id="alert" class="notice_<?= $notice_type; ?> default_text" style="top:-10px; position:relative;"><?= $notice_text; ?></div>
<?php	}	?>
<table cellpadding="1" cellspacing="4" border="0" class="default_text" width="100%">
  <tr>
    <td valign="top" width="50%"><div class="standard_container"><form name="bonus_form" id="bonus_form" method="post" action="">
    <input type="hidden" name="bonus_removed" id="bonus_removed" />
        <div class="title_3 border_bottom_dotted" style="width:95%;">Bonos</div>
        <?php
	$sql = 'SELECT * FROM bonus_types WHERE active_ind = \'1\'';
	$sel = my_query($sql, $conex);

	if(my_num_rows($sel) == '0')
		echo 'No existen bonos';
	
	while($record = my_fetch_array($sel)) {
		$members_only_str = $record['members_only_ind'] == '1' ? ' (solo socios)' : '';
		$members_price = $record['price'] > 0 ? print_money($record['price']) : '-';
	?>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <tr>
            <td colspan="2" bgcolor="#DDDDDD"><table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td><a href="<?= $conf_main_page; ?>?mod=admin&tab=club&subtab=edit_bonus&detail=<?= $record['type_code']; ?>" title="Editar bono">
                    <?= $record['type_description'] ?>
                    </a>
                    <?= $members_only_str; ?>
                  </td>
                  <td align="right"><input type="button" name="save" class="button_small" value=" DESACTIVAR " onclick="JavaScript:remove_bonus('<?= $record['type_code']; ?>');" />
                  </td>
                </tr>
              </table></td>
          </tr>
          <tr>
            <td width="50%">Precio socios:
              <?= print_money($record['price_members']); ?></td>
            <td width="50%">Precio no socios:
              <?= $members_price; ?></td>
          </tr>
          <tr>
            <td colspan="2" class="small_text bottomborderthin"><?= $record['conditions']; ?></td>
          </tr>
        </table>
        <?php
	}
?>
        <div style="padding:15px;width:95%;" align="center">
          <input type="button" name="add_button" class="button_small" value=" CREAR BONO " onclick="JavaScript:create_bonus();" />
        </div>
      </form></div><div class="standard_container">
      <form name="fares_form" id="fares_form" method="post" action="">
        <div class="title_3 border_bottom_dotted" style="width:95%;">Tarifas</div>
        <?php
	
?>
        <table cellpadding="1" cellspacing="2" border="0" width="95%" class="small_text">
          <tr>
            <th class="bottomborderthin">Nombre</th>
            <th class="bottomborderthin">Horario</th>
            <th class="bottomborderthin">Tarifa</th>
            <th title="Tarifa socio" class="bottomborderthin">Soc.</th>
            <th title="Festivo" class="bottomborderthin">Fes.</th>
            <th title="Lunes a viernes" class="bottomborderthin">Lab.</th>
          </tr>
          <?php	
	$sql = 'SELECT * FROM fares_conf';
	$sel = my_query($sql, $conex);

	while($record = my_fetch_array($sel)) {
		$mem_str = $record['is_member'] == '1' ? '&times;' : '-';
		$hol_str = $record['holiday_ind'] == '1' ? '&times;' : '-';
		$wkd_str = $record['week_day_ind'] == '1' ? '&times;' : '-';
		?>
          <tr>
            <td><?= $record['fare_name']; ?></td>
            <td align="center"><?= $record['time_starts'] .' - '. $record['time_ends']; ?></td>
            <td align="right"><strong>
              <?= print_money($record['fare']); ?>
              </strong></td>
            <td align="center"><?= $mem_str; ?></td>
            <td align="center"><?= $hol_str; ?></td>
            <td align="center"><?= $wkd_str; ?></td>
          </tr>
          <?php	}	?>
        </table>
        <div style="padding:15px;width:95%;" align="center">
          <input type="button" name="add_button" class="button_small" value=" CAMBIAR TARIFAS " onclick="JavaScript:edit_fares();" />
        </div>
      </form></div><div class="standard_container">
      <form name="courts_form" id="courts_form" method="post" action=""><input type="hidden" name="court_save" id="court_save" />
        <div class="title_3 border_bottom_dotted" style="width:95%;">Pistas</div>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <?php
	$sql = 'SELECT name, court_id, court_type_desc as descr, time_slot_min as minutes FROM courts';
	$sel = my_query($sql, $conex);

	while($record = my_fetch_array($sel)) {
?>
          <tr>
            <td bgcolor="#DDDDDD"><?= $record['name']; ?></td>
            <td><input type="text" class="inputnormal" name="court_desc_<?= $record['court_id']; ?>" id="court_desc_<?= $record['court_id']; ?>" style="width:130px;" value="<?= $record['descr']; ?>" maxlength="250" /></td>
            <td><input type="text" class="inputnormal" name="court_mins_<?= $record['court_id']; ?>" id="court_mins_<?= $record['court_id']; ?>" style="width:30px;" value="<?= $record['minutes']; ?>" maxlength="3" />
              minutos</td>
            <td align="center"><input type="button" name="save4" class="button_small" value=" GUARDAR " onclick="JavaScript:save_court('<?= $record['court_id']; ?>');" /></td>
          </tr>
          <?php	}	?>
        </table>
      </form></div></td>
    <td valign="top" width="50%"><div class="standard_container"><form name="fees_form" id="fees_form" method="post" action="">
        <div class="title_3 border_bottom_dotted" style="width:95%;">Cuotas Socios</div>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <tr>
            <th class="bottomborderthin">Tipo</th>
            <th width="20%" class="bottomborderthin"> mensual</th>
            <th width="20%" class="bottomborderthin"> cuatr.</th>
            <th width="20%" class="bottomborderthin">anual</th>
          </tr>
          <?php	
	$sql = 'SELECT * FROM member_types';
	$sel = my_query($sql, $conex);

	while($record = my_fetch_array($sel)) {
	/*	$mem_str = $record['is_member'] == '1' ? '&times;' : '-';
		$hol_str = $record['holiday_ind'] == '1' ? '&times;' : '-';
		$wkd_str = $record['week_day_ind'] == '1' ? '&times;' : '-';*/
		?>
          <tr>
            <td class="bottomborderthin"><?= $record['type_name']; ?></td>
            <td align="right" class="bottomborderthin"><?= print_money($record['month_quote']); ?></td>
            <td align="right" class="bottomborderthin"><?= print_money($record['4month_quote']); ?></td>
            <td align="right" class="bottomborderthin"><?= print_money($record['year_quote']); ?></td>
          </tr>
          <?php	}	?>
        </table>
        <div style="padding:15px; width:95%;" align="center">
          <input type="button" name="add_button" class="button_small" value=" EDITAR CUOTAS " onclick="JavaScript:edit_fees();" />
        </div>
      </form></div><div class="standard_container">
      <form name="ots_form" id="ots_form" method="post" action="">
        <div class="title_3 border_bottom_dotted" style="width:95%;">Horarios</div>
        <?php
	# generate an array with the times of the day instead of writing it all.
	$arr_hours = array();
/*	
	# this is to generate half hours
	for($i = 0; $i < 48; $i++) {
		$hour = add_zeroes(floor($i / 2));
		$minute = $i % 2 == 0 ? '00' : '30';
		if($hour == '0') $hour = '00';
		$arr_hours[$hour .':'. $minute] = $hour .':'. $minute;
	}*/
	
	for($i = 0; $i < 24; $i++) {
		$hour = add_zeroes($i);
		//$minute = $i % 2 == 0 ? '00' : '30';
		if($hour == '0') $hour = '00';
		$arr_hours[$hour .':00'] = $hour .':00';
	}
	
	$sql = 'SELECT open_from_1, open_to_1, open_from_2, open_to_2, description FROM opening_times_conf';
	$sel = my_query($sql, $conex);
?>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <tr>
            <th class="bottomborderthin"></th>
            <th width="33%" class="bottomborderthin" title="Apertura mañana">Apert. m.</th>
            <th width="33%" class="bottomborderthin" title="Cierre mañana">Cierre m.</th>
            <th width="33%" class="bottomborderthin" title="Apertura tarde">Apert. t.</th>
            <th width="33%" class="bottomborderthin" title="Cierre tarde">Cierre t.</th>
          </tr>
          <?php
	while($record = my_fetch_array($sel)) {
?>
          <tr>
            <td align="right" class="bottomborderthin"><?= $record['description']; ?></td>
            <td align="center" class="bottomborderthin"><?php
        $parameters = array('array' => $arr_hours, 'name' => 'open_from_1#'. $record['description'], 'selected' => $record['open_from_1'], 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		  ?></td>
          <td align="center" class="bottomborderthin"><?php
        $parameters = array('array' => $arr_hours, 'name' => 'open_to_1#'. $record['description'], 'selected' => $record['open_to_1'], 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		  ?></td>
          <td align="center" class="bottomborderthin"><?php
        $parameters = array('array' => $arr_hours, 'name' => 'open_from_2#'. $record['description'], 'selected' => $record['open_from_2'], 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		  ?></td>
            <td align="center" class="bottomborderthin"><?php
        $parameters = array('array' => $arr_hours, 'name' => 'open_to_2#'. $record['description'], 'selected' => $record['open_to_2'], 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		  ?></td>
          </tr>
          <?php
	}
?>
        </table>
        <div style="padding:15px;width:95%;" align="center">
          <input type="button" name="add_button" class="button_small" value=" GUARDAR HORARIOS  " onclick="JavaScript:save_ots();" />
        </div>
      </form></div><div class="standard_container">
      <form name="holidays_form" id="holidays_form" method="post" action=""><input type="hidden" name="delete_holiday" id="delete_holiday" />
        <div class="title_3 border_bottom_dotted" style="width:95%;">Festivos</div>
        <?php
	$sql = 'SELECT dd.date_id, dd.date_db, dt.holiday_desc 
		FROM date_dim dd
		INNER JOIN date_translations dt ON dd.date_id = dt.date_id
		WHERE dt.language = \''. $_SESSION['misc']['lang'] .'\' AND dd.holiday_ind = \'1\'
		AND dd.date_db > \''. date('Y-m-d') .'\'';
	
	$sel = my_query($sql, $conex);
?>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <tr>
            <td colspan="3" bgcolor="#DDDDDD">Próximos Festivos</td>
          </tr>
          <?php
	while($record = my_fetch_array($sel)) {
		$holiday = new my_date($record['date_db']);
?>
          <tr>
            <td class="bottomborderthin"><?= $holiday->format_date('med'); ?></td>
            <td class="bottomborderthin"><?= $record['holiday_desc']; ?></td>
            <td class="bottomborderthin" align="center"><input type="button" name="save2" class="button_small" value=" BORRAR " onclick="JavaScript:remove_holiday('<?= $record['date_id']; ?>');" /></td>
          </tr>
          <?php	}	?>
          <tr>
            <td colspan="3" bgcolor="#DDDDDD">A&ntilde;adir festivo</td>
          </tr>
          <tr>
            <td class="small_text">Fecha (aaaa-mm-dd)</td>
            <td class="small_text">Nombre</td>
            <td class="small_text">&nbsp;</td>
          </tr>
          <tr>
            <td class="bottomborderthin"><input type="text" class="inputnormal" name="date_holiday" id="date_holiday" style="width:100px;" value="<?= $arr_addr[2]; ?>" maxlength="250" onblur="JavaScript:construct_date('date_holiday');" /></td>
            <td class="bottomborderthin"><input type="text" class="inputnormal" name="name_holiday" id="name_holiday" style="width:170px;" value="<?= $arr_addr[2]; ?>" maxlength="250" /></td>
            <td class="bottomborderthin" align="center"><input type="button" name="save4" class="button_small" value=" GUARDAR " onclick="JavaScript:save_holiday('');" /></td>
          </tr>
        </table>
        <br />
      </form></div><div class="standard_container">
      <form name="special_form" id="special_form" method="post" action=""><input type="hidden" name="delete_special" id="delete_special" />
        <div class="title_3 border_bottom_dotted" style="width:95%;">Días con horario especial</div>
        <?php
	$sql = 'SELECT * FROM opening_times_special WHERE date_db > \''. date('Y-m-d') .'\'';
	$sel = my_query($sql, $conex);
?>
        <table cellpadding="3" cellspacing="2" border="0" width="95%">
          <tr>
            <td colspan="4" bgcolor="#DDDDDD">Próximos días con horario especial</td>
          </tr>
          <?php
	while($record = my_fetch_array($sel)) {
		$esp_day = new my_date($record['date_db']);
?>
          <tr>
            <td class="bottomborderthin"><?= $esp_day->format_date('med'); ?></td>
            <td class="bottomborderthin"><?= $record['description']; ?></td>
            <td align="center" class="bottomborderthin">mañ.: <?= $record['open_from_1'] .' a '. $record['open_to_1'] .'<br>tarde: '. $record['open_from_2'] .' a '. $record['open_to_2']; ?></td>
            <td class="bottomborderthin" align="center"><input type="button" name="save2" class="button_small" value=" BORRAR " onclick="JavaScript:remove_special('<?= $record['date_id']; ?>');" /></td>
          </tr>
          <?php	}	?>
          <tr>
            <td colspan="4" bgcolor="#DDDDDD" class="bottomborderthin">A&ntilde;adir d&iacute;a con horario especial</td>
          </tr>
          <tr>
            <td colspan="4" class="bottomborderthin"><table cellpadding="1" cellspacing="1" border="0" width="100%" class="small_text">
                <tr>
                  <th>Fecha</th>
                  <th>Nombre</th>
                  <th>Desde</th>
                  <th>Hasta</th>
                </tr>
                <tr>
                  <td><input type="text" class="inputnormal" name="esp_date" id="esp_date" style="width:70px;" value="<?= $arr_addr[2]; ?>" maxlength="250" onblur="JavaScript:construct_date('esp_date');" /></td>
                  <td><input type="text" class="inputnormal" name="esp_name" id="esp_name" style="width:100px;" value="<?= $arr_addr[2]; ?>" maxlength="250" /></td>
                  <td align="right">mañ. <?php
        $parameters = array('array' => $arr_hours, 'name' => 'ot_special', 'selected' => '10:00', 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		echo '<br>tarde ';
		$parameters = array('array' => $arr_hours, 'name' => 'ot_special2', 'selected' => '14:00', 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		  ?></td>
                  <td><?php
        $parameters = array('array' => $arr_hours, 'name' => 'ct_special1', 'selected' => '14:00', 'class' => 'inputnormal input_short');
		print_combo_array($parameters);
		echo '<br>';
        $parameters = array('array' => $arr_hours, 'name' => 'ct_special', 'selected' => '16:00', 'class' => 'inputnormal input_short');
		print_combo_array($parameters);

		  ?></td>
                </tr>
                <tr>
                  <td height="10"></td>
                  <td height="10"></td>
                  <td></td>
                  <td></td>
                </tr>
                <tr>
                  <td colspan="4" align="center"><input type="button" name="save3" class="button_small" value=" GUARDAR " onclick="JavaScript:save_special('');" /></td>
                </tr>
              </table></td>
          </tr>
        </table>
      </form></div></td>
  </tr>
</table>
<script language="javascript">

function remove_bonus(bonus_type) {
	if(confirm('Desactivar el bono ¿Estás seguro?')) {
		document.bonus_form.bonus_removed.value = bonus_type;
		document.bonus_form.submit();
	}
}

function create_bonus() {
	document.location = '<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=create_bonus'; ?>';
}

function edit_fares() {
	document.location = '<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=edit_fares'; ?>';
}

function save_court(court_id) {
	document.courts_form.court_save.value = court_id;
	document.courts_form.submit();
}

function edit_fees() {
	document.location = '<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=edit_member_fees'; ?>';
}

function save_ots() {
	document.ots_form.submit();
}

function remove_holiday(date_id) {
	if(confirm('Eliminar festivo ¿Estás seguro?')) {
		document.holidays_form.delete_holiday.value = date_id;
		document.holidays_form.submit();
	}
}

function save_holiday() {
	document.holidays_form.submit();
}

function remove_special(date_id) {
	if(confirm('Eliminar día con horario espcial ¿Estás seguro?')) {
		document.special_form.delete_special.value = date_id;
		document.special_form.submit();
	}
}

function save_special() {
	document.special_form.submit();
}

</script>