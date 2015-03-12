<?php

function draw_pages_navigator($parameters) {
	global $initial_row, $final_row, $conf_main_page;

/*	$parameters = array('page' => $_SESSION['mod'][$_GET['mod']]['nav_page']
					   ,'num_rows' => 20 ,'num_rows_page' => 25, 'class' => 'border_bottom_dotted')
*/
	# input parameters: width (default 100%); page (default 1); num_rows; num_rows_page; class
	# output values: initial_row; final_row
	# the number of pages to be displayed, also deppending on the page width, 3, 5 or 7
	# $list_pages:   < | 1 | 2 | 3 | 4 | 5 | >
	# $list_rows_per_page:    25 | 100 | 500
	if($parameters['num_rows'] > 0) {
		$num_rows_limit1 = 25;
		$num_rows_limit2 = 100;
		$num_rows_limit3 = 500;
		
		if(!$parameters['width'])	$parameters['width'] = '100%';
		
		if(!$parameters['page']) {
			if($_GET['pag']) $parameters['page'] = $_GET['pag'];
			else $parameters['page'] = 1;
		}
		
		if(!$parameters['num_pages_list']) $parameters['num_pages_list'] = 5;	# Num of pages to display in the list
		
		$num_pages = ceil($parameters['num_rows'] / $parameters['num_rows_page']);		# total number of pages
		
		if($parameters['page'] > $num_pages || !is_numeric($parameters['page'])) $parameters['page'] = $num_pages;	# Shouldn't happen, but just in case
	
		$initial_row = ($parameters['num_rows_page'] * ($parameters['page'] - 1));			# initial row
		$final_row = $initial_row + $parameters['num_rows_page'] - 1;						# final row
		if($final_row > $parameters['num_rows'])
			$final_row = $parameters['num_rows'] - 1;										# adjust final row
		
		$init_page = $parameters['page'] - floor($parameters['num_pages_list'] / 2);		# first page shown is 1 or 2 lower than the current
		if($init_page < 1)																	# unless it's lower than 0
			$init_page = 1;
		
		$fin_page = $init_page + $parameters['num_pages_list'] - 1;							# same for the final page
		if($fin_page > $num_pages) {														# if we are at the end...
			$fin_page = $num_pages;
			$init_page = $num_pages - $parameters['num_pages_list'] + 1;
			if($init_page < 1)
				$init_page = 1;
		}
		
		$file = $conf_main_page .'?';
		foreach($_GET as $key => $value) {
			if($key != 'pag') $file.= $key .'='. $value .'&';
		}
		
		if($init_page > 1) $list_pages = '<a href="'. $file .'pag=1" title="'. first_page .'">&lt; </a>';
		
		for($i = $init_page; $i <= $fin_page; $i++) {
			if($i != $init_page) $list_pages .= '|';
			if($i == $parameters['page'])
				$list_pages .= '<span class="currpage">&nbsp;'. $i .'&nbsp;</span>';
			else
				$list_pages .= '<a href="'. $file .'pag='. $i .'" title="'. go_to .' '. $i .'">&nbsp;'. $i .'&nbsp;</a>';
		}
		
		if($fin_page < $num_pages) $list_pages.= '|<a href="'. $file .'pag='. $num_pages .'" title="'. go_to .' '. last_page .'"> &gt;</a>';
	
		$file = $conf_main_page .'?';	# reuse the same $file variable
		foreach($_GET as $key => $value) {
			if($key != 'nrows') $file.= $key .'='. $value .'&';
		}
	
		# This is for the number of pages to display
		if($parameters['num_rows'] > $num_rows_limit1) {
			if($parameters['num_rows'] > $num_rows_limit2) {
				switch($parameters['num_rows_page']) {
					case $num_rows_limit1:
						$list_num_rows = '<span class="currpage">&nbsp;'. $num_rows_limit1 .'&nbsp;</span>|<a href="'. $file .'nrows='. $num_rows_limit2 .'" title="'. ucfirst(view) .' '. $num_rows_limit2 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit2 .'&nbsp;</a>|<a href="'. $file .'nrows='. $num_rows_limit3 .'" title="'. ucfirst(view) .' '. $num_rows_limit3 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit3 .'&nbsp;</a>';
					break;
					case $num_rows_limit2:
						$list_num_rows = '<a href="'. $file .'nrows='. $num_rows_limit1 .'" title="'. ucfirst(view) .' '. $num_rows_limit1 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit1 .'&nbsp;</a>|<span class="currpage">&nbsp;'. $num_rows_limit2 .'&nbsp;</span>|<a href="'. $file .'nrows='. $num_rows_limit3 .'" title="'. ucfirst(view) .' '. $num_rows_limit3 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit3 .'&nbsp;</a>';
					break;
					case $num_rows_limit3: default:
						$list_num_rows = '<a href="'. $file .'nrows='. $num_rows_limit1 .'" title="'. ucfirst(view) .' '. $num_rows_limit1 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit1 .'&nbsp;</a>|<a href="'. $file .'nrows='. $num_rows_limit2 .'" title="'. ucfirst(view) .' '. $num_rows_limit2 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit2 .'&nbsp;</a>|<span class="currpage">&nbsp;'. $num_rows_limit3 .'&nbsp;</span>';
				}
			}
			else {
				switch($parameters['num_rows_page']) {
					case $num_rows_limit1:
						$list_num_rows = '<span class="currpage">&nbsp;'. $num_rows_limit1 .'&nbsp;</span>|<a href="'. $file .'nrows='. $num_rows_limit2 .'" title="'. ucfirst(view) .' '. $num_rows_limit2 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit2 .'&nbsp;</a>';
					break;
					default:
						$list_num_rows = '<a href="'. $file .'nrows='. $num_rows_limit1 .'" title="'. ucfirst(view) .' '. $num_rows_limit1 .' '. rows_per_page .'">&nbsp;'. $num_rows_limit1 .'&nbsp;</a>|<span class="currpage">&nbsp;'. $num_rows_limit2 .'&nbsp;</span>';
				}
			}												# ^^^^^^^^^^ between first limit and second limit
		}
		else
			$list_num_rows = $parameters['num_rows'];		# ---------- between first limit and first limit + 10
	?>

<table border="0" cellpadding="3" cellspacing="0" width="100%" class="<?php echo $parameters['class']; ?>">
  <tr>
    <td class="small_text"><strong><?php echo $parameters['num_rows']; ?></strong> <?php echo results; ?>&nbsp;&nbsp;&nbsp;<?php echo ucfirst(shown); ?>: <?php echo ($initial_row + 1) .' '. to .' '. ($final_row + 1); ?></td>
    <td class="small_text"><?php echo ucfirst(view); ?>:&nbsp;&nbsp;<?php echo $list_num_rows; ?></td>
    <!--<td class="small_text">list | grid</td>-->
    <td align="right" class="small_text"><?php echo ucfirst(page) .':&nbsp;'. $list_pages; ?></td>
  </tr>
</table>
<?php  
	}	//	if($parameters['num_rows'] > 0) {
}	//function draw_pages_navigator($parameters) {


?>