<?php
$ab_tests = array();
//example:
/*
$ab_tests['checkout_button_color'] = array(
	"name" => "checkout_button_color", //must be the same as the key above. Whitespace is not allowed
	"description" => "What colour should we make the checkout button",
	"alternatives" => array("green", "red", "blue"), 	//all possible alternatives
														//as an array of strings
	"metrics" => array('conversion') 	//what metrics refer to a conversion here? this is an array of strings
										//that correspond to metrics in the config/metrics file
);
*/

$ab_tests['checkout_button_color'] = array(
	"name" => "checkout_button_color",
	"description" => "What colour should we make the checkout button",
	"alternatives" => array("green", "red", "blue"),
	"metrics" => array('conversion', 'signup')
);

$ab_tests['checkout_button_size'] = array(
	"name" => "checkout_button_size",
	"description" => "How big should we make it?",
	"alternatives" => array("small", "medium", "large"),
	"metrics" => array('conversion')
);