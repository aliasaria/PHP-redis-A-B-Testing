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

/*
 * Initialize variables
 */
$ab_participant_id = -1;
$redis_connected = false;


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
 * Initializes the tests, connects to redis, and
 * sets the unique participant ID for the current visitor
 * this must be set once and must be done before using any
 * other of the ab testing functions.
 * 
 * If you do not call this function, the other functions
 * below will still work and they will behave as though
 * redis isn't connected. So metrics will not be tracked
 * and tests will return randomized results but also not
 * be tracked. This can be used to deactive the redis-
 * dependant portion of this code. So for example if you
 * have developer instances of this code that do not have
 * access to redis, you can simply comment out this following
 * function and everything else in your code will still
 * work even if it calls ab_track
 * 
 * @param $id - the unique ID of the visitor
 * @param $developer_mode_no_redis - set this to true if you do not have redis
 * 		this will disable logging but make everything else work fine
 * 		use this flag for developer machines that do not have redis
 * @return (nothing)
 */
function ab_init ($id = -1, $developer_mode_no_redis = false)
{
	global	$ab_participant_id,
			$redis_connected,
			$r;
			
	$ab_participant_id = $id;
			
	/*
	 * Try to connect to redis
	 */
	
	if ($developer_mode_no_redis)
	{
		$redis_connected = false;
		return;
	}
	else
	{
		$r =& new Redis($ab_config['redis_host'],$ab_config['redis_port']);
		$redis_connected = $r->connect();
		
		if ($redis_connected)
		{
			$r->select_db($ab_config['redis_db_number']);
			
			//set up the metrics (this should loop through them and link them to associated ab tests
			ab_metrics_initialize();
			ab_tests_initialize();
			
		}
	}
}

//deprecated -- do not use this function. replaced by init
//this function will be dropped in the next release
function ab_participant_id ($id)
{
	trigger_error("ab_test - function ab_participant_id() is DEPRECATED. please us ab_init() instead.", E_USER_WARNING);
	ab_init($id, false);
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





