<?php
$ab_metrics = array();

//example:
/*
$ab_metrics['conversion'] = array(
	"name" => "conversion", //this must be the same as the key above. Whitespace is not allowed
	"description" => "When someone completes checkout"
);
*/

$ab_metrics['conversion'] = array(
	"name" => "conversion",
	"description" => "When someone completes checkout"
);

$ab_metrics['signup'] = array(
	"name" => "signup",
	"description" => "When someone signsup for a newsletter"
);