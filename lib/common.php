<?php
//creates a redis-supported key name
//based on an array of strings passed to it
function ab_key($strings)
{
	//remove whitespace from keys because not sure if my version
	//of the redis connector can handle spaces:
	foreach ($strings as $string)
	{
		$string = strtr($string, " \n\t", "___");
	}
	
	//combine all the given strings and add :'s between them
	$r = 'ab:' . implode(":", $strings);
	return($r);
}
