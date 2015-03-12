<style type="text/css"></style>
<table border="0" width="100%" cellpadding="2" cellspacing="6">
  <tr>
    <td width="50%" valign="top"><div class="standard_container"><span class="standard_cont_title">Noticias</span><br />
        <?php
        $news_arr = event::get_current_events(5);
	
	$first = true;
	foreach($news_arr as $new_id => $event) {
		$new_date = new my_date($event['date_from']);
	?>
        <table width="100%" border="0" cellpadding="2" cellspacing="2">
          <?php
		if($first) $first = false;
		else {
			?>
          <tr>
            <td colspan="2" class="border_bottom_dotted" height="6"></td>
          </tr>
          <tr>
            <td colspan="2" height="6"></td>
          </tr>
          <?php
		}

		?>
          <tr>
            <td class="small_text" colspan="2" valign="bottom"><?= $new_date->format_date('long'); ?></td>
          </tr>
          <tr>
            <td colspan="2" class="title_3"><a href="<?= $conf_main_page .'?mod=news&detail='. $new_id; ?>">
              <?= $event['header']; ?>
              </a></td>
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
        </table>
        <?php
	}
		?>
      </div>
      <div class="standard_container"><span class="standard_cont_title">Tarifas</span><br />
        <?php
	  $sql = 'SELECT * FROM fares_conf';
	  $sel = my_query($sql, $conex);
	  
	  $arr_fares = array();
	  while($rec = my_fetch_array($sel)) {
		  if($rec['week_day_ind'] && !$rec['holiday_ind']) # laborable
		  	$day_type = 'lab';
		  elseif($rec['week_day_ind'] && $rec['holiday_ind']) # festivo
		    $day_type = 'fes';
		  else
		  	$day_type = 'fin';
			
		  $is_member = $rec['is_member'] ? 'soc' : 'nosoc';
		$arr_fares[$day_type][$rec['time_starts'] .' a '. $rec['time_ends']][$is_member] = $rec['fare'];
	  }
	  
//	  pa($arr_fares);
	  
	  ?><div class="title_4 border_bottom_dotted" style="width:95%; margin-top:-10px;">Reserva de pistas</div>
        <table border="0" cellpadding="4" cellspacing="2" width="90%" align="center" class="default_text">
          <tr>
            <td>&nbsp;</td>
            <td align="center" width="25%">Socios</td>
            <td align="center" width="25%">No socios</td>
          </tr>
          <tr>
            <td align="right">Laborables Mañanas: </td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['lab']['10:00 a 14:00']['soc']); ?></td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['lab']['10:00 a 14:00']['nosoc']); ?></td>
          </tr>
          <tr>
            <td align="right">Laborables Tardes: </td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['lab']['16:00 a 23:00']['soc']); ?></td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['lab']['16:00 a 23:00']['nosoc']); ?></td>
          </tr>
          <tr>
            <td align="right">Festivos y fines de semana: </td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['fin']['09:00 a 21:00']['soc']); ?></td>
            <td class="bg_ddd" align="right"><?= print_money($arr_fares['fin']['09:00 a 21:00']['nosoc']); ?></td>
          </tr>
          <tr><td colspan="3" class="small_text" align="right">(Precios por persona y hora)</td></tr>
        </table>
        
        <br />
        <div class="title_4 border_bottom_dotted" style="width:95%;">Cuotas de socios</div>
        <table cellpadding="4" cellspacing="2" border="0" width="90%" class="default_text" align="center">
          <tr>
            <td></td>
            <td width="20%" align="center">Mensual</td>
            <td width="20%" align="center">Cuatrim.</td>
            <td width="20%" align="center">Anual</td>
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
            <td align="right"><?= $record['type_name']; ?>: </td>
            <td align="right" class="bg_ddd"><?= print_money($record['month_quote']); ?></td>
            <td align="right" class="bg_ddd"><?= print_money($record['4month_quote']); ?></td>
            <td align="right" class="bg_ddd"><?= print_money($record['year_quote']); ?></td>
          </tr>
          <?php	}	?>
        </table>
        <br />
      </div></td>
    <td width="50%" valign="top"><div class="standard_container"><span class="standard_cont_title">Horarios</span><br />
        <?php    
       
    $sql = 'SELECT open_from_1, open_to_1, open_from_2, open_to_2, description FROM opening_times_conf';
	$sel = my_query($sql, $conex);
	
	$arr_ots = array();
	while($rec = my_fetch_array($sel)) {
		$arr_ots[$rec['description']] = array('of1' => $rec['open_from_1'], 'ot1' => $rec['open_to_1'], 'of2' => $rec['open_from_2'], 'ot2' => $rec['open_to_2']);
	}

	?>
        <table border="0" cellpadding="2" cellspacing="2" width="80%" align="center" class="default_text" style="margin-top:-10px;">
          <tr>
            <td>&nbsp;</td>
            <td align="center">Mañanas</td>
            <td align="center">Tardes</td>
          </tr>
          <tr>
            <td align="right">Laborables:</td>
            <td align="center" class="bg_ddd"><?= $arr_ots['laborable']['of1'] .' a '. $arr_ots['laborable']['ot1']; ?></td>
            <td align="center" class="bg_ddd"><?= $arr_ots['laborable']['of2'] .' a '. $arr_ots['laborable']['ot2']; ?></td>
          </tr>
          <tr>
            <td align="right">Fines de semana:</td>
            <td align="center" class="bg_ddd"><?= $arr_ots['fin_de_semana']['of1'] .' a '. $arr_ots['fin_de_semana']['ot1']; ?></td>
            <td align="center" class="bg_ddd"><?= $arr_ots['fin_de_semana']['of2'] .' a '. $arr_ots['fin_de_semana']['ot2']; ?></td>
          </tr>
          <tr>
            <td align="right">Festivos:</td>
            <td align="center" class="bg_ddd"><?= $arr_ots['festivo']['of1'] .' a '. $arr_ots['festivo']['ot1']; ?></td>
            <td align="center" class="bg_ddd"><?= $arr_ots['festivo']['of2'] .' a '. $arr_ots['festivo']['ot2']; ?></td>
          </tr>
        </table>
        <table border="0" cellpadding="2" cellspacing="2" width="80%" align="center" class="small_text">
          <tr>
            <td>Nota: Los Socios que quieran reserva pista en el horario 14 a 16 Hrs, tendrán que avisar al Club con antelación, en los siguientes Teléfonos: 987.406.060 / 636.152.765. Con el fin de poder organizarnos.</td>
          </tr>
        </table>
      </div>
      <div class="standard_container"> <span class="standard_cont_title">Ubicación</span><br />
        <table border="0" cellpadding="2" cellspacing="2" width="80%" align="center" class="default_text" style="margin-top:-10px;">
          <tr>
            <td>Nuestro Club se encuentra situado en la carretera Nacional VI Km. 386,
              en una de las principales entradas a la ciudad de Ponferrada y a muy
              poca distancia del centro de la misma y de la autovia A-6. Salidas desde
              la A6 a la N-VI:<br />
              &bull; Salida 382 - Ponferrada (Este)<br />
              &bull; Salida 388 - Ponferrada (Norte)<br />
              También podrás acceder a Padelindoor desde el barrio de Compostilla. </td>
          </tr>
               <tr>
            <td><iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.es/maps?f=q&amp;source=s_q&amp;hl=es&amp;geocode=&amp;q=ponferrada+nacional+6+hotel+novo&amp;aq=&amp;sll=42.569189,-6.600814&amp;sspn=0.055752,0.111494&amp;ie=UTF8&amp;hq=nacional+6+hotel+novo&amp;hnear=Ponferrada,+Le%C3%B3n,+Castilla+y+Le%C3%B3n&amp;t=m&amp;ll=42.569189,-6.600814&amp;spn=0.050286,0.021958&amp;output=embed"></iframe>
              <br />
              <small><a href="http://maps.google.es/maps?f=q&amp;source=embed&amp;hl=es&amp;geocode=&amp;q=ponferrada+nacional+6+hotel+novo&amp;aq=&amp;sll=42.569189,-6.600814&amp;sspn=0.055752,0.111494&amp;ie=UTF8&amp;hq=nacional+6+hotel+novo&amp;hnear=Ponferrada,+Le%C3%B3n,+Castilla+y+Le%C3%B3n&amp;t=m&amp;ll=42.569189,-6.600814&amp;spn=0.050286,0.021958" style="color:#0000FF;text-align:left">Ver mapa más grande</a></small></td>
          </tr>
        </table>
      </div></td>
  </tr>
</table>
<?php

?>
