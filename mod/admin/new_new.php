<?php

if($_GET['detail']) {
	$ob_new = new event($_GET['detail']);
	$new_props = $ob_new->get_properties();	
}

$today = new my_date('today');

if($_POST) {
	if($_POST['editing']) {
		$upd_ret = $ob_new->update_event($_POST['header'], $_POST['summary'], $_POST['content'], $_POST['author'], $_POST['date_from'], $_POST['date_to'], 'editing');
		if(substr($upd_ret, 0, 3) == 'ERR')
			add_alert('admin', 'alert', 1, 'Error: '. substr($new_id, 4));
		else{
			jump_to($conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_pictures&detail='. $ob_new->event_id);
			exit();
		}
	}
	else {
		$new_id = event::add_event($_POST['header'], $_POST['summary'], $_POST['content'], $_POST['author'], $_POST['date_from'], $_POST['date_to'], 'editing');
		if(substr($new_id, 0, 3) == 'ERR')
			add_alert('admin', 'alert', 1, 'Error: '. substr($new_id, 4));
		else{
			jump_to($conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_pictures&detail='. $new_id);
			exit();
		}
	}
}
	
print_alerts($_GET['mod']);

# assign values to $_POST if it is an edition.
if(count($new_props))
	$_POST = $new_props;

?>
<!-- Skin CSS file -->
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/assets/skins/sam/skin.css">
<!-- Utility Dependencies -->
<script src="http://yui.yahooapis.com/2.9.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
<script src="http://yui.yahooapis.com/2.9.0/build/element/element-min.js"></script>
<!-- Needed for Menus, Buttons and Overlays used in the Toolbar -->
<script src="http://yui.yahooapis.com/2.9.0/build/container/container_core-min.js"></script>
<script src="http://yui.yahooapis.com/2.9.0/build/menu/menu-min.js"></script>
<script src="http://yui.yahooapis.com/2.9.0/build/button/button-min.js"></script>
<!-- Source file for Rich Text Editor-->
<script src="http://yui.yahooapis.com/2.9.0/build/editor/editor-min.js"></script>

<div class="title_3">
  <?= $_GET['detail'] ? 'Editar noticia' : 'Insertar noticia'; ?>
</div>
<form name="form_new" id="form_new" action="" method="post">
<table width="80%" border="0" cellpadding="5" cellspacing="4" align="center" class="default_text">
  <tr>
    <td class="bg_ddd" align="right">Titular: </td>
    <td><input type="text" class="inputlarge" maxlength="250" name="header" id="header" placeholder="Titular de la noticia" value="<?= $_POST['header']; ?>" />
      <span class="small_text">250 car. máx.</span></td>
  </tr>
  <tr>
    <td class="bg_ddd" align="right">Resumen: </td>
    <td><textarea class="inputlarge" name="summary" id="summary" rows="2" placeholder="Breve resumen de la noticia"><?= $_POST['summary']; ?>
</textarea></td>
  </tr>
  <tr>
    <td class="bg_ddd" align="right">Contenido: </td>
    <td class="yui-skin-sam"><textarea name="content" id="content" rows="5" style="width:400px;"><?= $_POST['content']; ?>
</textarea></td>
  </tr>
  <tr>
    <td class="bg_ddd" align="right">Fecha Inicio: </td>
    <td><input type="text" class="inputlarge" maxlength="12" name="date_from" id="date_from" placeholder="aaaa-mm-dd" onblur="JavaScript:construct_date('date_from');" value="<?= $_POST['date_from'] ? $_POST['date_from'] : $today->odate; ?>" /></td>
  </tr>
  <tr>
    <td class="bg_ddd" align="right">Fecha Fin: </td>
    <td><input type="text" class="inputlarge" maxlength="12" name="date_to" id="date_to" placeholder="aaaa-mm-dd" onblur="JavaScript:construct_date('date_to');"  value="<?= $_POST['date_to']; ?>" />
      <span class="small_text">(opcional)</span></td>
  </tr>
  <tr>
    <td class="bg_ddd" align="right">Autor: </td>
    <td><input type="text" class="inputlarge" maxlength="250" name="author" id="author" placeholder="Nombre del autor" value="<?= $_POST['author']; ?>" />
      <span class="small_text">(opcional)</span></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input type="submit" class="button" value="  SIGUIENTE >  " name="next" /></td>
  </tr>
</table>
<input type="hidden" name="editing" id="editing" value="<?= $_GET['detail'] ? '1' : '0'; ?>" />
</form>
<script language="javascript">

var myEditor = new YAHOO.widget.Editor('content', {
	height: '175px',
    width: '500px',
    dompath: false,
    animate: true,
	handleSubmit: true,
    toolbar: {
		titlebar: 'Contenido de la noticia',
		draggable: false,
		buttonType: 'advanced',
		buttons: [
			{ group: 'textstyle', label: 'Estilo',
				buttons: [
					{ type: 'push', label: 'Negrita', value: 'bold' },
					{ type: 'push', label: 'Cursiva', value: 'italic' },
					{ type: 'push', label: 'Subrrayado', value: 'underline' },
					{ type: 'color', label: 'Color Fuente', value: 'forecolor', disabled: true }
					/*{ type: 'color', label: 'Color Fondo', value: 'backcolor', disabled: true },*/
				]
			},
			{ type: 'separator' },
			{ group: 'alignment', label: 'Alineación',
				buttons: [
					{ type: 'push', label: 'Izquierda', value: 'justifyleft' },
					{ type: 'push', label: 'Centro', value: 'justifycenter' },
					{ type: 'push', label: 'Derecha', value: 'justifyright' },
					{ type: 'push', label: 'Justificado', value: 'justifyfull' }
				]
			},
			{ type: 'separator' },
			{ group: 'indentlist', label: 'Indent. y listas',
				buttons: [
					{ type: 'push', label: '+ indentación', value: 'indent', disabled: true },
					{ type: 'push', label: '- indetación', value: 'outdent', disabled: true },
					{ type: 'push', label: 'Viñetas', value: 'insertunorderedlist' },
					{ type: 'push', label: 'Números', value: 'insertorderedlist' }
				]
			},
			{ type: 'separator' },
			{ group: 'insertitem', label: 'Link',
				buttons: [
					{ type: 'push', label: 'Vínculo HTML', value: 'createlink', disabled: true }
				]
			}
		]

    }
});
myEditor.render();

</script>