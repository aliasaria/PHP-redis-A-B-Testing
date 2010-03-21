<?php
require_once ('common.php');

//initialize the metrics -- this should be called before we do anything below
function ab_metrics_initialize()
{
	global $ab_metrics;
	
	foreach ($ab_metrics as $m)
	{
		if (!isset($m['associated_tests']))
		{
			//initialize the associated test with blank array
			$m['associated_tests'] = array();
		}
	}
}

//track a metric
function ab_metrics_track($metric, $value = 1)
{	
	$timestamp = date("m-d-y");
	
	ab_metrics_track_on_date($metric, $timestamp, $value);
}

//track a metric and specify the date (for testing)
function ab_metrics_track_on_date($metric, $date, $value = 1)
{
	global $r;
	global $ab_metrics;
	
	$timestamp = $date;
	
	//echo ab_key(array("metric", $metric, $timestamp)) . "<br>";
		
	$r->incr(ab_key(array("metric", $metric, $timestamp)), $value);
	
	//loop through all related experiments and add
	if (isset($ab_metrics[$metric]['associated_tests']))
	{
		foreach ($ab_metrics[$metric]['associated_tests'] as $test)
		{
			ab_tests_track($test, $value);
		}
	}
}

//get all values for a metric in a range of dates
function ab_values($metric, $from, $to)
{
	global $r;
	
	//convert to unicode times
	$ut_from 	= strtotime($from);
	$ut_to	= strtotime($to);
	
	//calculate difference
	$ut_diff = $ut_to - $ut_from;
	
	$ut_diff_in_days = $ut_diff / 24 / 60 / 60;
	
	
	//i am adding an extra day just to fix the bug.
	//this whole function needs to be rewritten with proper math
	$ut_diff_in_days += 1;
	//if ($ut_diff_in_days > 90) $ut_diff_in_days = 90;
	
	if ($ut_diff_in_days < 1) $ut_diff_in_days = 0;
	
	$keys = array();
	$dates = array();
	$i = 0;
	
	while ($i <= $ut_diff_in_days)
	{
		
		$date_key = date("m-d-y", $ut_from + ($i*24*60*60) );
		
		$dates[] = $ut_from + ($i*24*60*60);
		$keys[] = ab_key(array("metric", $metric, $date_key));
		
		//add one day. this algorithm doesn't handle daylight savings time, etc. properly
		$i ++;
	}
	
	//print_r($keys);
	$vals = $r->mget($keys);
	//print_r($vals);
	//print_r ($vals);
	
	$result = array();
	
	$i = 0;
	
	foreach ($vals as $val)
	{
		//echo $dates[$i] . " - ";
		//$d = strtotime($dates[$i]);
		//echo $d;
		$result[] = array($dates[$i], (int)$val);
		$i++;
	}
	
	//print_r($r);
	
	return $result;
}

