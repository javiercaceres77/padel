<?php

if(!$_GET['detail']) {
	jump_to($conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_new');
	exit();
}

$today = new my_date('today');
$ob_event = new event($_GET['detail']);

if($_POST) {
	$error = false;
	if($_POST['title'] == '')	$error = 'Debes escribir un título';

	if(!$error) {
		// http://www.webdeveloper.com/forum/showthread.php?t=101466
		
		$upload_dir = $conf_photos_path; 
		$fieldname = 'ph_file';
		$max_allowed_size = 8388608;
		
		$img_max_width = 1280;
		$img_max_height = 1280;
		
		$med_img_max_width = 640;
		$med_img_max_height = 640;
		
		$small_img_max_width = 360;
		$small_img_max_height = 360;
		
		$thumb_img_max_width = 72;
		$thumb_img_max_height = 72;
		
		$array_extensions = array('jpg', 'gif', 'png', 'jpeg', 'jpe');
		
		$error = '';

		// possible PHP upload errors 
		$errors = array(1 => 'php.ini max file size exceeded', 
						2 => 'max file size exceeded', 
						3 => 'file upload was only partial', 
						4 => 'no file was attached',
						5 => 'incorrect file',
						6 => 'incorrect file type'); 
		
		
		# Check that the file is actually the one uploaded
		if(!is_uploaded_file($_FILES[$fieldname]['tmp_name']))
			$error = $errors[5];

		$allowed_formats = array('image/jpeg', 'image/png', 'image/gif' ,'image/pjpeg');	#pjpeg is for ie
		# Check that it is an image
		if(!in_array($_FILES[$fieldname]['type'], $allowed_formats))
			$error = $errors[6];
		
		# Check max allowed size
		if($_FILES[$fieldname]['size'] > $max_allowed_size)
			$error = $errors[2];

		// make a unique filename for the uploaded file and check it is not already 
		// taken... if it is already taken keep trying until we find a vacant one 
		// sample filename: 1140732936-filename.jpg 
		$now = time(); 
		while(file_exists($uploadFilename = $upload_dir . $now .'-'. $_FILES[$fieldname]['name'])) 
			$now++; 
		 
		if($error) {
			echo $error;
			exit();
		}

		#Move the file to its destination
		if(!move_uploaded_file($_FILES[$fieldname]['tmp_name'], $uploadFilename)) {
			$error = $errors[3];
		}
		
		# Extract EXIF details from image
		if(function_exists('exif_read_data'))
			$arr_exif = @exif_read_data($uploadFilename, 0, true);
		
		$exif_date_time = str_replace(':', '-', substr($arr_exif['IFD0']['DateTime'], 0, 10));
		unset($arr_exif);
		
		# Resize image and create thumbnails
		$filename = stripslashes($uploadFilename);
		$filename_no_dir = substr($uploadFilename, strrpos($uploadFilename, '/') + 1);
		//get_file_only($uploadFilename);
		$extension = getExtension($filename);
		
		if(in_array($extension, $array_extensions)) {
			switch($extension) {
				case 'jpg': case 'jpeg': case 'jpe':
					$im = imagecreatefromjpeg($uploadFilename);
					break;
				case 'gif':
					$im = imagecreatefromgif($uploadFilename);
					break;
				case 'png':
					$im = imagecreatefrompng($uploadFilename);
					break;
			}
			
			$width = imagesx($im);
			$height = imagesy($im);
		
			$arr_l_size = get_new_size($width, $height, $img_max_width, $img_max_height);
			$arr_s_size = get_new_size($width, $height, $small_img_max_width, $small_img_max_height);
			$arr_t_size = get_new_size($width, $height, $thumb_img_max_width, $thumb_img_max_height);
			$arr_m_size = get_new_size($width, $height, $med_img_max_width, $med_img_max_height);
		
			$tmp_l = imagecreatetruecolor($arr_l_size['w'], $arr_l_size['h']);
			$tmp_s = imagecreatetruecolor($arr_s_size['w'], $arr_s_size['h']);
			$tmp_t = imagecreatetruecolor($arr_t_size['w'], $arr_t_size['h']);
			$tmp_m = imagecreatetruecolor($arr_m_size['w'], $arr_m_size['h']);
			
			imagecopyresampled($tmp_l, $im, 0, 0, 0, 0, $arr_l_size['w'], $arr_l_size['h'], $width, $height);
			imagecopyresampled($tmp_s, $im, 0, 0, 0, 0, $arr_s_size['w'], $arr_s_size['h'], $width, $height);
			imagecopyresampled($tmp_t, $im, 0, 0, 0, 0, $arr_t_size['w'], $arr_t_size['h'], $width, $height);
			imagecopyresampled($tmp_m, $im, 0, 0, 0, 0, $arr_m_size['w'], $arr_m_size['h'], $width, $height);
		
			$file_name_l = $upload_dir . 'large_' . $filename_no_dir;
			$file_name_s = $upload_dir . 'small_' . $filename_no_dir;
			$file_name_t = $upload_dir . 'thumb_' . $filename_no_dir;
			$file_name_m = $upload_dir . 'med_' . $filename_no_dir;
			
			imagejpeg($tmp_l, $file_name_l, 100);
			imagejpeg($tmp_s, $file_name_s);
			imagejpeg($tmp_t, $file_name_t);
			imagejpeg($tmp_m, $file_name_m);
			
			imagedestroy($im);
			imagedestroy($tmp_l);
			imagedestroy($tmp_s);
			imagedestroy($tmp_t);
			imagedestroy($tmp_m);
			
			#Delete original file
			unlink($uploadFilename);
		
			#Photos must allways have a date
			if(!$_POST['photo_date']) {
				if(!$exif_date_time)
					$_POST['photo_date'] = date('Y-m-d');
				else
					$_POST['photo_date'] = $exif_date_time;
			}
		
			$large_img_id = image::create_img('large_'. $filename_no_dir, $arr_l_size['w'], $arr_l_size['h'], $extension);
			$med_img_id   = image::create_img('med_'. $filename_no_dir,   $arr_m_size['w'], $arr_m_size['h'], $extension);
			$small_img_id = image::create_img('small_'. $filename_no_dir, $arr_s_size['w'], $arr_s_size['h'], $extension);
			$thumb_img_id = image::create_img('thumb_'. $filename_no_dir, $arr_t_size['w'], $arr_t_size['h'], $extension);

			$photo_id = photo::create_photo($large_img_id, $med_img_id, $small_img_id, $thumb_img_id, $_POST['author'], $_POST['title'], $_POST['description'], $_POST['date_taken']);

			if($photo_id)
				add_alert('admin', 'info', 1, 'Foto agregada correctamente a la noticia');
		}	//if(in_array($extension, $array_extensions)) {
	}	//if(!$error) {
	else {
		add_alert('admin', 'alert', 1, 'Error: '. $error);	
	}
	
	if($photo_id) {
		$ob_event->add_photo($photo_id);
	}
}	//	if($_POST) {

	
print_alerts($_GET['mod']);

?>

<div class="title_3">Vista previa de la noticia:</div>
<div class="standard_container">
  <?php
	$ob_event->print_event();
?>
  <br />
  <table border="0" width="100%" cellpadding="2" cellspacing="2">
    <tr>
      <td><input type="button" class="button" value=" &lt; EDITAR NOTICIA " onclick="JavaScript:document.location='<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&subtab=new_new&detail='. $_GET['detail']; ?>'"></td>
      <td align="right"><input type="button" class="button" value=" PUBLICAR NOTICIA &gt; " onclick="JavaScript:document.location='<?= $conf_main_page .'?mod='. $_GET['mod'] .'&tab='. $_GET['tab'] .'&action=publish&detail='. $_GET['detail']; ?>'"></td>
    </tr>
  </table>
  <br />
  <br />
</div>
<div class="title_3">Subir foto:</div>
<form name="form_img" id="form_img" action="" method="post" enctype="multipart/form-data">
  <table width="80%" border="0" cellpadding="5" cellspacing="4" align="center" class="default_text">
    <tr>
      <td class="bg_ddd" align="right">Título: </td>
      <td><input type="text" class="inputlarge" maxlength="250" name="title" id="title" placeholder="Título de la foto" value="" />
        <span class="small_text">250 car. máx.</span></td>
    </tr>
    <tr>
      <td class="bg_ddd" align="right">Descripción: </td>
      <td><input type="text" class="inputlarge" maxlength="250" name="description" id="description" placeholder="Descripción / Pie de foto" value="" />
        <span class="small_text">250 car. máx. (opcional)</span></td>
    </tr>
    <tr>
      <td class="bg_ddd" align="right">Autor: </td>
      <td><input type="text" class="inputlarge" maxlength="250" name="author" id="author" placeholder="Nombre del autor" value="" />
        <span class="small_text">250 car. máx. (opcional)</span></td>
    </tr>
    <tr>
      <td class="bg_ddd" align="right">Fecha: </td>
      <td><input type="text" class="inputlarge" maxlength="12" name="date_taken" id="date_taken" placeholder="Fecha de la foto" value="" onblur="JavaScript:construct_date('date_taken');"  />
        <span class="small_text">(opcional)</span></td>
    </tr>
    <tr>
      <td class="bg_ddd" align="right">Archivo: </td>
      <td><input type="file" id="ph_file" name="ph_file" class="inputlarge" style="width:225px;" />
        <br />
        <span class="small_text">Tamaño máximo permitido por imágen: 8Mb</span></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="  SUBIR FOTO  " name="next" /></td>
    </tr>
  </table>
</form>
<br />
