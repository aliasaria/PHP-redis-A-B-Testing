<?php
require_once ('common.php');

function ab_tests_initialize()
{
	global $ab_tests, $ab_metrics;
	
	foreach ($ab_tests as $test)
	{
		
		foreach ($test['metrics'] as $m)
		{
			if (!array_key_exists($m, $ab_metrics))
			{
				//if a test refers to a metric we don't know about
				//throw a warning and ignore
				$name = $test['name'];
				trigger_error("ab_test - one of your tests **$name** refers to an unknown metric **$m**", E_USER_WARNING);
				continue;
			}
			
			//link metrics to assciated tests
			$ab_metrics[$m]['associated_tests'][] = $test['name'];
		}
		
		ab_tests_choose($test['name']);
	}
	
}


//for the each test and for the current participant, pre-choose the right alternative
//pass in the name of a test
function ab_tests_choose($test)
{
	global $ab_participant_id;
	global $r;
	
	$p = $ab_participant_id;
		
	$forced_alternative = ab_tests_get_forced_alternative($test);
	
	if ($forced_alternative != null)
	{
		//don't register this user as having partipated
		//as there is a forced alternative (therefore the test
		//is not stopped)
		return;
	}
	
	$alt = ab_tests_alternative_for($p, $test);
	
	//register this participant as a participant in this tests applicable alternative
	//by adding the current parcipant to the set of participants for this test+alt
	$r->sadd(ab_key( array('test',$test,'alt',$alt,'participants') ), $p);
}

//this is core and actual ab test function
//-- it returns an alternative for the current visitor
//don't call directly: use the wrapper in core.php
function ab_tests_test($test)
{
	global $ab_participant_id;
	global $redis_connected;
	
	$forced_alternative = null;
	
	if ($redis_connected)
	{
		$forced_alternative = ab_tests_get_forced_alternative($test);
	}
	
	if ($forced_alternative == null)
		$alt = ab_tests_alternative_for ($ab_participant_id, $test);
	else
		$alt = $forced_alternative;
		
	return $alt;
}

//record a conversion for a *test* and current paricipant
function ab_tests_track($test, $value)
{
	global $ab_participant_id;
	global $r;
	
	$p = $ab_participant_id;
	
	$alt = ab_tests_alternative_for($p, $test);
	
	//note that if a test was "forced" to a specific alternative,
	//then the following sismember function will return false
	//and nothing will be tracked for this test
	if ($r->sismember(  ab_key(array('test',$test,'alt',$alt,'participants')) , $p))
	{
		//add this participant to the set of people who have converted on this metric
		$r->sadd( ab_key( array('test',$test,'alt',$alt,'converted')) , $p);
		//and increment the total number of conversions as well
		$r->incr( ab_key( array('test',$test,'alt',$alt,'conversions') ), $value );
	}
}

//pass me the participants ID and the name of the test and i will
//return the correct alternative (by name as string) to use
function ab_tests_alternative_for($p, $test)
{
	global $ab_tests;
	
	//generate a unique id combining the name of the test
	//and the participants ID
	$u = $ab_tests[$test]['name'] . "/" . $p;
	
	//how many alternatives are there?
	$n = count($ab_tests[$test]['alternatives']);
	
	
	//i get an md5 of the test name + unique user id
	//then i substring it and convert to a decimal number
	//so i can do math on it later.
	$m = md5($u);
	$m = substr($m,0,7); 	//take substring as this number is too large to conver to an int
							//we choose the number 7 bc 0xFFFFFFF is less than 2^32
	$m = hexdec($m);
	
	$c = $m % $n;
	//echo "<br><pre>" . $u . " " . $m . " " . $n . " " . $c . "</pre></br>";
	
	$choice = $ab_tests[$test]['alternatives'][$c];
	
	// echo "<br>for this $p dude, i choose: " . $choice;
	
	return ($choice);
}

//count the number of converstions for the given test and alternative
function ab_tests_total_conversions_for_alternative($test,$alternative)
{
	global $r;
	
	$a = $r->get( ab_key( array('test',$test,'alt',$alternative,'conversions') ) );
	
	return ($a);
}

//count the number of participants for the given test and alternative
function ab_tests_total_participants_for_alternative($test,$alternative)
{
	global $r;
	
	$a = $r->scard( ab_key( array('test',$test,'alt',$alternative,'participants') ) );
	
	return ($a);
}

//count the number of converted participants for the given test and alternative
function ab_tests_total_converted_for_alternative($test,$alternative)
{
	global $r;
	
	$a = $r->scard( ab_key( array('test',$test,'alt',$alternative,'converted') ) );
	
	return ($a);
}

/**
 * Force a test to always land on specific result:
 * This function does no validation, and assumes test and alternative are valid 
 * 
 * @param $test -- name of test as string
 * @param $alternative -- name of alternative as string. Pass null to clear
 * @return n/a
 */
function ab_tests_force_alternative($test, $alternative)
{
	global $r;
	
	$key = ab_key(  array('test',$test,'force')   );
	
	if ($alternative != null)
		$r->set($key,$alternative);
	else
		$r->delete($key);
	
}

/**
 * For the given test, return which alternative is currently forced.
 * 
 * Return null if 
 * 
 * @param $test -- name of test as string
 * @return String: name of alternative as string. null if no alternative
 * is currently forced
 */
function ab_tests_get_forced_alternative($test)
{
	global $r;
	
	$key = ab_key(  array('test',$test,'force')   );
	
	$return = $r->get($key);
	
	return($return);
}

function ab_test_clear_and_restart_test($test)
{
	//not yet implemented
}

//get conversion rate (as percent) for the provided test and alternative
function ab_test_conversion_rate($test, $alt)
{
		$p 	= (int)ab_tests_total_participants_for_alternative($test,$alt);
		//$c1 = (int)ab_tests_total_conversions_for_alternative($test,$alt);
		$c2 = (int)ab_tests_total_converted_for_alternative($test,$alt);
		
		if ($p > 0)
			$conv_rate = $c2 / $p * 100;
		else
			$conv_rate = 0;
			
			
		return ($conv_rate);
}

