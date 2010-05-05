<?php

/*
 * This is the file you include in order to use the AB testing suite
 *
 * Three functions are exposed to the public:
 * ab_participant_id(...)
 * ab_test(...)
 * ab_track(...)
 *
 * Run ab_partipant_id before anything else
 */

/*
 * INCLUDES
 */
//include the redis connector
include_once('redis/redis.php');

include('config/configure.php');

//bring in the custom defined metrics and tests
include('config/metrics.php');
include('config/tests.php');

include('lib/metrics.php');
include('lib/tests.php');

$ab_participant_id = -1;

/*
 * Try to connect to redis
 */
$r =& new Redis($config['redis_host'],$config['redis_port']);
$redis_connected = $r->connect();


if ($redis_connected)
{
	$r->select_db($config['redis_db_number']);
	//$r->flushdb();
}
else
{
}


/************************************
 * CORE FUNCTIONS
 * 
 * The following three functions
 *
 * ab_participant_id(...)
 * ab_test(...)
 * ab_track(...)
 *
 * are the only three functions exposed to the public.
 *
 * For documentation on how to use this, read README.txt
 */

/**
 *
 * Sets the unique participant ID for the current visitor
 * this must be set once and must be done before using any
 * other of the ab testing functions.
 * @param $id - the unique ID of the visitor
 * @return (nothing)
 */
function ab_participant_id ($id)
{
	global $ab_participant_id;
	global $redis_connected;

	$ab_participant_id = $id;

	if ($redis_connected)
	{
		//set up the metrics (this should loop through them and link them to associated ab tests
		ab_metrics_initialize();
		ab_tests_initialize();
	}
}

/**
 * Runs a test.
 * @param $test is a string that specifieds the test to run
 * @return a string representing the alternative to run. will return null if the test isn't found
 */
function ab_test($test)
{
	global $redis_connected;
	global $ab_tests;
	
	//test if the test exists, otherwise return null
	if (!array_key_exists($test, $ab_tests)) return null;

	if ($redis_connected)
	{
		return ab_tests_test($test);
	}
	else
	{
		//the following function will still work, even without
		//a connection to redis
		return ab_tests_test($test);
	}
}

/**
 * Track a metric.
 * @param $metric : a string representing the metric to track
 * @param $value : (optional) how many conversions happened (e.g. use this 
 * for add to cart if the person adds 10 to the cart) default = 1
 * @return nothing
 */
function ab_track($metric, $value = 1)
{
	global $redis_connected, $ab_participant_id;

	if ($redis_connected)
	{
		if ($ab_participant_id != -1)
		{
			ab_metrics_track($metric, $value);
		}
	}
	else
	{
		//do nothing
	}
}





