<?php
//this contains the functions needed for the report. keeping this out of the metric and
//tests libraries keeps them more lean for the production code and this is only included
//when generating a report.

require ('z_scores_table.php');

/*
# Scores alternatives based on the current tracking data.  This method
      # returns a structure with the following attributes:
      # [:alts]   Ordered list of alternatives, populated with scoring info.
      # [:base]   Second best performing alternative.
      # [:least]  Least performing alternative (but more than zero conversion).
      # [:choice] Choice alterntive, either the outcome or best alternative.
      #
      # Alternatives returned by this method are populated with the following
      # attributes:
      # [:z_score]      Z-score (relative to the base alternative).
      # [:probability]  Probability (z-score mapped to 0, 90, 95, 99 or 99.9%).
      # [:difference]   Difference from the least performant altenative.
      # 
      # The choice alternative is set only if its probability is higher or
      # equal to the specified probability (default is 90%).
*/      

/**
 * @param unknown_type $test
 * @param unknown_type $probability
 */
function ab_tests_score($test, $probability = 90)
{
	global $ab_tests;
	
	//i am going to create a nice array/mixed type thingy
	//that will carry all the results from this function
	$result = array();
	
	//get all alternatives for this test
	$alts = $ab_tests[$test]['alternatives'];
	
	
//	echo "<br>alts: "; print_r($alts);
	
	//gather a list of conversion rates for each test
	$conversion_rates = array();
	
	foreach($alts as $alt)
	{
		$conversion_rates[$alt] = ab_test_conversion_rate($test, $alt);
	}
	
	arsort($conversion_rates);
	
	$result['sorted_alts'] = $conversion_rates;
	
//	echo "<br>sortedalts: "; print_r($conversion_rates);
	
	//base is the second best result
	$base = array_slice($conversion_rates, 1, 1);
	
	$result['base'] = $base;
	
//	echo "<br>base: "; print_r($base);
	
	//calculate z-score for base:
	$base_name = array_slice( array_keys($conversion_rates) , 1, 1);
	$base_name = $base_name[0];
	
//	echo "<br>base name: "; print_r($base_name);
	
	$result['base_name'] = $base_name;
	
	$pc = ab_test_conversion_rate($test, $base_name)/100;
	$nc = (int) ab_tests_total_participants_for_alternative($test,$base_name);
	
//	echo "<br>pc: ". $pc . " nc:" . $nc;
	
	$z_scores = array();
	$percentiles = array();
	
	foreach($conversion_rates as $alt => $rate)
	{
//		echo "<br>rate: "; print_r($alt); echo " "; print_r($rate);
		
		$p = ab_test_conversion_rate($test, $alt)/100;
		$n = (int) ab_tests_total_participants_for_alternative($test,$alt);
		
		//prevent division by zero
		$z_scores[$alt] = 0;
		$percentiles[$alt] = 0;
		if ($n == 0 || $nc == 0) continue;
		
		//z-score is the difference over the std deviation
		//(p - pc) / ((p * (1-p)/n) + (pc * (1-pc)/nc)).abs ** 0.5
		$std_deviation = pow(abs(  ($p * (1-$p)/$n) + ($pc * (1-$pc)/$nc)  ),0.5);
		if ($std_deviation == 0) continue;
		$z_scores[$alt] = ($p - $pc) /  $std_deviation;
		$percentiles[$alt] = convert_z_score_to_percentile( abs($z_scores[$alt]) );
	}
	
//	echo "<br>z-score: "; print_r($z_scores);
	
	$result['z-scores'] = $z_scores;
	$result['percentiles'] = $percentiles;
	
	
	//find the least converted one that converted more than 0 times
	$least = false;
	//fast forward to the end
	end($conversion_rates);
	
	if (current($conversion_rates) > 0)
	{
			$least['name'] = key($conversion_rates);
			$least['rate'] = current($conversion_rates);
	}
	else
	{
		//keep rewinding until you find one
		while(prev($conversion_rates) !== false && $least !== false)
		{
			if (current($conversion_rates) > 0)
			{
				$least['name'] = key($conversion_rates);
				$least['rate'] = current($conversion_rates);
			}
		}
	}
	
//	echo "<br>least: "; print_r($least);
	
	$result['least'] = $least;
	
	$differences = array();
	
	foreach($conversion_rates as $alt => $rate)
	{
		$c1 = ab_test_conversion_rate($test, $alt);
		$c2 = $least['rate']; 
		
//		echo "<br>c1: " . $c1 . " c2: " . $c2;
		
		$differences[$alt] = $c1 - $c2;
	}
	
//	echo "<br>differences: "; print_r($differences);
	
	$result['differences'] = $differences;
	
	$best = array_slice( array_keys($conversion_rates) , 0, 1);
	$best = $best[0];
	
	$result['best'] = $best;
	
//	echo "<br>best: "; print_r($best);
	
	//print_r($result);
	return ($result);
}

//state a conclusion
function ab_tests_conclusion($test)
{
	$r = "";
	
	$score = ab_tests_score($test);
	
	
	//var_dump($score);
	
	$r .= "The best option was " . $score['best'] . ". ";
	
	$r .= "It converted at " . round($score['sorted_alts'][$score['best']],3) . "%. ";
	
	$best_percent_significance = $score['percentiles'][$score['best']];
	
	$r .= "With " .  $best_percent_significance . "% probability this result is statistically significant. ";
	
	if ($best_percent_significance > 90)
	{
		$r .= "So we think this is pretty significant. ";
	}
	else
	{
		$r .= "So this isn't that significant, we suggest you continue this experiment. ";
	}
	
	return($r);
}


function erase_data_for_test($test)
{
		global $r;
				
		//Find all keys that refer to this test using redis search functionality
		//@FIXME: this wont work right if there is a * in the test name but i am not checking for this
		$keys_to_delete = $r->keys("ab:test:$test:*");
		
		//print_r ($keys_to_delete);
		
		//try deleting these keys
		foreach ($keys_to_delete as $key)
		{
			if ($key != null)
				$r->delete($key);
		}
}

function erase_all_keys_except_forced_keys()
{
		global $r;
		
		/////////////////////////////////////////
		//First get all the keys you want to save
		/////////////////////////////////////////
		$force_keys = array();
		$force_vals = array();
		
		//the redis "keys" function searches for keys that match a specified pattern
		$force_keys = $r->keys("ab:*:force");
		
		foreach($force_keys as $key)
		{
			$force_vals[] = $r->get($key);
		}
		
		//print_r($force_keys);
		//print_r($force_vals);
		
		
		/////////////////////////////////////////
		//Now erase everything else
		/////////////////////////////////////////
		$r->flushdb();
		
		/////////////////////////////////////////
		//Now restore the forced keys
		/////////////////////////////////////////
		for ($i = 0; $i < count($force_keys); $i++)
		{
			$r->set($force_keys[$i], $force_vals[$i]);
		}
}