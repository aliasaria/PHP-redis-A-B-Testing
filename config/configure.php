<?php

$ab_config['redis_host'] = 'localhost';
$ab_config['redis_port'] = 6379; 				//redis's default port is 6379
$ab_config['redis_db_number'] = 0;


$ab_config['USE_AUTHENTICATION'] = true;		// Use (internal) authentication - best choice if 
											// no other authentication is available
											// If set to 0:
											//  There will be no further authentication. You 
											//  will have to handle this by yourself!
											// If set to 1:
											//  You need to change ADMIN_PASSWORD to make
											//  this work!
$ab_config['ADMIN_USERNAME']  = 'admin'; 			// Admin Username
$ab_config['ADMIN_PASSWORD'] = 'elephant';  	// Admin Password - CHANGE THIS TO ENABLE!!!

