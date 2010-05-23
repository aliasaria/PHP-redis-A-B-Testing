<?php
require_once("core.php");

//we hard code the parcipant ID in this example
$participant_id = "1017";

//you can provide it in the URL:
if (isset($_GET['uid']))	
	$participant_id = $_GET['uid'];

//STEP 1: SET THE PARTICIPANT ID
ab_init($participant_id, false);

// implement logic based on submission status
if (isset($_POST['submit_action']))
{
	ab_track("conversion");
	
	echo "<br><br>Success: Conversion Tracked!";	
	
	exit;
}


//STEP 2: DO THE TEST BY CALLING ab_test(..)
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Checkout Button Color Test</title>
</head>
<body>
<form method="post">
	<p>
		Hello User #<?php echo $participant_id; ?>,
		<br />Please press the button if you like its color.
	</p>
	
	<?php 
		$size = ab_test('checkout_button_size');
		
		$font_size = "12pt";
		
		if ($size == "small")
			$font_size = "9pt";
		elseif ($size == "medium")
			$font_size = "12pt";
		else //if $size = "large"
			$font_size = "20pt";
	?>
	<input type="submit" name="submit_action" value="Submit With This Color" style="background-color: <?php echo ab_test('checkout_button_color') ?>; font-size: <?php echo $font_size?>;" />
</form>
</body>
</html>