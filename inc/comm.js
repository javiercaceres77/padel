// JavaScript Document
function construct_date(input_field) {
	// sets the correct format for a date on an input value
	field_value = document.getElementById(input_field).value;
	if(field_value != '') {
		url = 'inc/ajax.php?content=construct_date&value=' + field_value;
		getData_value(url, input_field);
	}
}

function jump_to(destination) {
	document.location = destination;
}
