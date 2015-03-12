<?php

if($in['file'])
	$file_path_name = $in['file'];
else
	$file_path_name = 'tra/'. basename($_SERVER['SCRIPT_NAME'], '.php') .'_'. $_SESSION['misc']['lang'] . '.php';
	
@include_once $file_path_name;
	
?>