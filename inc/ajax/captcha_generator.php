<?php
/* ------ captcha code generation -------
   by Javier Caceres 06-02-2011
   generates simple aritmethic operations to be resolved by a human
*/
$arr_operators = array('plus', 'by', 'minus', 'between');
$arr_operands = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten'
					 ,11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty');


//pick one random operator
$rand_operator = array_rand($arr_operators);
$str_operator = defined($arr_operators[$rand_operator])?constant($arr_operators[$rand_operator]):$arr_operators[$rand_operator];

switch($rand_operator) {
	case 0: // +	pick two operands from one to twenty
		$op1 = rand(1, 20);  $str_op1 = defined($arr_operands[$op1])?constant($arr_operands[$op1]):$arr_operands[$op1];
		$op2 = rand(1, 20);  $str_op2 = defined($arr_operands[$op2])?constant($arr_operands[$op2]):$arr_operands[$op2];
		
		$_SESSION['misc']['captcha'] = $op1 + $op2;
		
		print($str_op1 .' '. $str_operator .' '. $str_op2);
	break;
	case 1: // *	pick two operands from one to ten
		$op1 = rand(1, 10);  $str_op1 = defined($arr_operands[$op1])?constant($arr_operands[$op1]):$arr_operands[$op1];
		$op2 = rand(1, 10);  $str_op2 = defined($arr_operands[$op2])?constant($arr_operands[$op2]):$arr_operands[$op2];
		
		$_SESSION['misc']['captcha'] = $op1 * $op2;
		
		print($str_op1 .' '. $str_operator .' '. $str_op2);
	break;
	case 2: // -	op1 is allways bigger than op2 to avoid negatives
		$op1 = rand(1, 20);  $str_op1 = defined($arr_operands[$op1])?constant($arr_operands[$op1]):$arr_operands[$op1];
		$op2 = rand(1, $op1);  $str_op2 = defined($arr_operands[$op2])?constant($arr_operands[$op2]):$arr_operands[$op2];
		
		$_SESSION['misc']['captcha'] = $op1 - $op2;
		
		print($str_op1 .' '. $str_operator .' '. $str_op2);

	break;
	case 3: // /	op1 is the product of op2 by 1, 2, 3, 4 or 5
		$op1 = rand(1, 4);  $str_op1 = defined($arr_operands[$op1])?constant($arr_operands[$op1]):$arr_operands[$op1];
		$op2 = $op1 * rand(1, 5);  $str_op2 = defined($arr_operands[$op2])?constant($arr_operands[$op2]):$arr_operands[$op2];
		
		$_SESSION['misc']['captcha'] = $op2 / $op1;
		
		print($str_op2 .' '. $str_operator .' '. $str_op1);
	break;
}
print (' = ');
?>