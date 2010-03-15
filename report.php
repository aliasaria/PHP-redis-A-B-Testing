<?php
//include the redis connector
include('redis/redis.php');


include('config/configure.php');

//bring in the custom defined metrics
include('config/metrics.php');
include('config/tests.php');

include('lib/metrics.php');
include('lib/tests.php');

include('lib/report.php');


//do auth
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<p style=\"font-family: Zapfino, cursive;\">Text to send if user hits Cancel button</p>';
    exit;
} else if (
				$_SERVER['PHP_AUTH_USER'] != $config['ADMIN_USERNAME'] &&
				$_SERVER['PHP_AUTH_PW']   != $config['ADMIN_PASSWORD']
		) 
{
	header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo "hi";
	exit;
} else {
    //echo "<p style=\"font-family: Zapfino, cursive;\">Hello {$_SERVER['PHP_AUTH_USER']}.</p>";
    //echo "<p style=\"font-family: Zapfino, cursive;\">You entered {$_SERVER['PHP_AUTH_PW']} as your password.</p>";
}



//connect to redis
$r =& new Redis($config['redis_host']);
$connected = $r->connect();

if ($connected)
{
	$r->select_db($config['redis_db_number']); //should make it configurable later
	//$r->flushdb();
}
else
{
	echo "<p>couldn't connect to redis</p>";
	die();
}



//do forced alternative logic if necessary
if (isset($_GET['force']))
{
	$test = $_GET['test'];
	$alt = $_GET['alt'];
	
	if ($alt != "!!null!!")
		ab_tests_force_alternative($test, $alt);
	else
		ab_tests_force_alternative($test, null);
}

?>
<html>
<head><title>a/b testing report</title></head>
<!--[if IE]><script language="javascript" type="text/javascript" src="../excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="js/flot/jquery.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>

<link href="css/report.css" media="all" rel="stylesheet" type="text/css" />
<link href="css/buttons.css" media="all" rel="stylesheet" type="text/css" />

<body>
<h1>a/b testing report</h1>
<hr/>
<?php 
$i = 0;
foreach ($ab_tests as $test)
{
	$name = $test['name'];
	$alternatives = $test['alternatives'];
	
	$forced_alternative = ab_tests_get_forced_alternative($name);
	
	
	$num_alternatives = count($alternatives);
	
	echo "<a name=\"$name\"><h2>" . $name . "</h2></a>";
		
	//echo "<P class=\"indent\" style='color: #556; font-size: 12px'>There are " . $num_alternatives . " alternatives for this test";
	echo "<P class=\"indent\" style='color: #556; font-size: 12px'>" .  $test['description'];
	
	
	if ($forced_alternative != null)
	{
		//echo "<P class=\"indent\" style='color: #999; font-size: 15px; margin-top: 12px'>" .  "Currently forced to show option <u style='color: #666'>$forced_alternative</u> for all participants";
		/*echo '  <a href="?force=true&test=' . $test['name'] . '&alt=' . "!!null!!" . '" class="red-button pcb">
			<span style="color: black;">Clear</span>
			</a>'; */
	}
	
	
	$j = 0;
	$a = array();
	$total_converted = 0;
	$total_conversions = 0;
	$total_particants = 0;
	$array_of_conversion_rates = array();
	
	echo "<div class=\"alternatives\">";
	foreach ($alternatives as $alt)
	{
		$p = $a[$alt]['participants'] 	= (int)ab_tests_total_participants_for_alternative($name,$alt);
		$c1 = $a[$alt]['conversions']  	= (int)ab_tests_total_conversions_for_alternative($name,$alt);
		$c2 = $a[$alt]['converted'] 	= (int)ab_tests_total_converted_for_alternative($name,$alt);
		
		if ($p > 0)
			$conv_rate = $c2 / $p * 100;
		else
			$conv_rate = 0;
			
		$conv_rate = round($conv_rate,3);
		
		$array_of_conversion_rates[] = array( $j+1,$conv_rate);
		echo "<h3>Alternative <span class=\"alternative\">" . $alt  . "</span>:";
		
		if ($forced_alternative != $alt)
		{
			echo '  <a href="?force=true&test=' . $test['name'] . '&alt=' . $alt . '#'. $name .'" class="grey-button pcb">
			<span style="color: #666;">Show Always</span>
			</a>';
		}
		else
		{
			echo ' <span class="forced-text">Always Showing</span> <a href="?force=true&test=' . $test['name'] . '&alt=' . "!!null!!" . '#'. $name .'" class="red-disabled-button pcb">
			<span style="color: #333;">Clear</a>
			</span>';
		}
		echo "</h3>\n";
		
		echo "<p class=\"results\">$p Participants, $c1 conversions, $c2 converted\n";
		
		echo "<p class=\"results\">conversion rate: " . $conv_rate . "%";
		
		$total_particants += $a[$alt]['participants'];
		$total_converted += $a[$alt]['converted'];
		
		$j++;
	}
	echo "</div>";
	
	
	
	$total_conversion_rate = ($total_particants > 0)?($total_converted/$total_particants*100):0;
	echo "<h3><strong>Total:</strong></h3>";
	echo "<p class=\"results\">" . $total_particants . " participants, " . $total_converted . " converted";
	echo " = " . round($total_conversion_rate,2) . "% total conversion rate";
	
	//echo 'Test is active: <p><a class="button" href="#"><span>Stop this test</span></a></p><br/><br/>';
	
	
    $chart_data = json_encode($array_of_conversion_rates);
    
    //echo "<pre>";
    //print_r ($chart_data);
    //echo "</pre>";
    
       echo '
       <div id="placeholder'; echo $i; echo '" style="width:300px;height:200px; margin-top: 10px;"></div>

       
<script id="source" language="javascript" type="text/javascript">
$(function () {
    var d1 = '; echo $chart_data; echo ';
    
    var options =  {
    
    			grid: {
    				backgroundColor: { colors: ["#ddd", "#ddd"] },
    				tickColor: "#ccc"
  				},
    
    			yaxis: {
		            ticks: 5,
		            min: 0,
		            xmax: 100
       			 },
       			xaxis: {
       				tickSize: 1,
       				tickDecimals: 0
       			},
       			bars: 
       				{show: true, barWidth: 0.6, fill: 0.8 },
  
       			};
       			
       			
    var data = [
  
    				{
    					color: "#333",
    					fill: 1,
    					data: d1,
    					
					}
    			];
       			
   
	var plotarea = $("#placeholder'; echo $i; echo '");  
    plotarea.css("height", "100px");  
    plotarea.css("width", "200px");
    
    $.plot(plotarea, data, options);
});
</script>
';
       
	$conclusion = ab_tests_conclusion($test['name']);
	
	echo "<p class=\"indent\" style='color: #556; font-size: 12px'>" . $conclusion . "</p>";
       
	echo "<hr/>";
	
	
	
	
       
       $i++;
}
?>


<h1>metrics report</h1>
<hr/>

<?php 

$i = 0;
foreach ($ab_metrics as $metric)
	{
		echo '<h2>' . $metric['name'] . '</h2>';
		echo "<P class=\"indent\" style='color: #556; font-size: 12px'>" .  $metric['description'];
		
		$today = date('Y-m-d');
		$sixtydaysago = date('Y-m-d', strtotime("-60 days"));
		
		$d = json_encode(ab_values($metric['name'],$sixtydaysago,$today));
		//print_r($d);
		
		
       echo '
       <div id="placeholder2'; echo $i; echo '" style="width:300px;height:200px; margin-top: 10px;"></div>

       
<script id="source" language="javascript" type="text/javascript">
$(function () {
    var d1 = '; echo $d; echo ';
    
    for (var i in d1)
    {
    	//alert(d1[i]);
    	d1[i] = [ d1[i][0]*1000, d1[i][1] ];
    }
    
    //alert(d1);
    
    var options =  {
        			grid: {
    				backgroundColor: { colors: ["#ddd", "#ddd"] },
    				tickColor: "#ccc"
  				},
       			xaxis: {
	                mode: "time",
	                minTickSize: [7, "day"],
	                 xtimeformat: "%y/%m/%d"
	                
	                
       			},
       			lines: 
       				{show: true},
       				
       				
    series: { lines: { show: true, lineWidth: 2, fill: 0.8, fillColor: "#444"},
              points: { show: false, radius: 3 }, shadowSize: 0 },

       			};
       			
       			
    var data = [
    				{
    					color: "#333",
    					data: d1,
    					xlabel: "y = 5",
    					fill: true, fillColor: { colors: ["#ddd", "#ddd"] },
    					
					}
    			];
       			
   
	var plotarea = $("#placeholder2'; echo $i; echo '");  
    plotarea.css("height", "120px");  
    plotarea.css("width", "450px");
    
    $.plot(plotarea, data, options);
});
</script>
';
		
		
	$i ++;	
	}
	
	echo "</div>";

?>


</body>
</html>